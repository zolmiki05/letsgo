<?php
/**
 * Queue – simple DB-backed job queue.
 *
 * Jobs are stored in the `jobs` table. A CLI worker script (bin/worker.php)
 * processes pending jobs on a cron schedule.
 *
 * Supported job types:
 *   send_email  – dispatched by Mailer; processed by worker via Mailer::sendRaw()
 *
 * Race conditions are prevented by FOR UPDATE SKIP LOCKED (MySQL 8+).
 *
 * Retry policy:
 *   Attempt 1 fails → retry in 5 minutes
 *   Attempt 2 fails → retry in 10 minutes
 *   Attempt 3 fails → marked as 'failed', no more retries
 */
class Queue
{
    private const MAX_ATTEMPTS = 3;

    /**
     * Add a new job to the queue.
     *
     * @param string         $type    Job type, e.g. 'send_email'.
     * @param array          $payload Job data; will be JSON-encoded.
     * @param DateTime|null  $runAt   Earliest execution time (defaults to NOW).
     */
    public static function dispatch(string $type, array $payload, ?DateTime $runAt = null): void
    {
        $db  = Database::getInstance();
        $at  = $runAt ? $runAt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $db->prepare(
            'INSERT INTO jobs (type, payload, run_at, created_at)
             VALUES (?, ?, ?, NOW())'
        )->execute([$type, json_encode($payload, JSON_UNESCAPED_UNICODE), $at]);
    }

    /**
     * Claim the next available job for processing.
     *
     * Uses SELECT … FOR UPDATE SKIP LOCKED inside a transaction to avoid
     * race conditions when multiple workers run in parallel.
     *
     * @return array|null  Job row, or null if the queue is empty.
     */
    public static function next(): ?array
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                "SELECT * FROM jobs
                 WHERE status = 'pending' AND run_at <= NOW()
                 ORDER BY run_at ASC
                 LIMIT 1
                 FOR UPDATE SKIP LOCKED"
            );
            $stmt->execute();
            $job = $stmt->fetch();

            if (!$job) {
                $db->rollBack();
                return null;
            }

            $db->prepare(
                "UPDATE jobs SET status = 'processing', attempts = attempts + 1 WHERE id = ?"
            )->execute([$job['id']]);

            $db->commit();
            return $job;

        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a job as successfully completed.
     */
    public static function complete(int $id): void
    {
        Database::getInstance()
            ->prepare("UPDATE jobs SET status = 'done' WHERE id = ?")
            ->execute([$id]);
    }

    /**
     * Record a job failure. Retries up to MAX_ATTEMPTS with exponential back-off;
     * after that marks the job as permanently failed.
     */
    public static function fail(int $id, string $error = ''): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT attempts FROM jobs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= self::MAX_ATTEMPTS) {
            $db->prepare("UPDATE jobs SET status = 'failed' WHERE id = ?")
               ->execute([$id]);
            error_log("[Queue] Job {$id} permanently failed after {$attempts} attempts: {$error}");
        } else {
            // Back-off: retry in attempts × 5 minutes
            $delayMin = $attempts * 5;
            $db->prepare(
                "UPDATE jobs SET status = 'pending',
                 run_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?"
            )->execute([$delayMin, $id]);
        }
    }

    /**
     * Delete completed and permanently failed jobs older than the given number of days.
     * Called periodically by the worker to prevent table bloat.
     */
    public static function prune(int $olderThanDays = 7): int
    {
        $stmt = Database::getInstance()->prepare(
            "DELETE FROM jobs
             WHERE status IN ('done', 'failed')
             AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$olderThanDays]);
        return $stmt->rowCount();
    }
}
