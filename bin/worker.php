#!/usr/bin/env php
<?php
/**
 * Queue worker – processes pending jobs from the `jobs` table.
 *
 * Intended to be run on a cron schedule, e.g. every minute:
 *   * * * * * php /var/www/html/bin/worker.php >> /var/log/letsgo-worker.log 2>&1
 *
 * The worker runs until the queue is empty, then exits.
 * Supported job types:
 *   send_email  – dispatched by Mailer; payload: {to, subject, html}
 *
 * Usage:
 *   php bin/worker.php [--max-jobs=100]
 */

// Worker runs as CLI – no HTTP session needed
define('ROOT', dirname(__DIR__));

require_once ROOT . '/src/bootstrap.php';

// Parse optional --max-jobs argument
$maxJobs = 100;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--max-jobs=')) {
        $maxJobs = max(1, (int)substr($arg, 11));
    }
}

$processed = 0;
$failed    = 0;

while ($processed < $maxJobs) {
    $job = Queue::next();
    if (!$job) {
        break; // queue is empty
    }

    $id      = (int)$job['id'];
    $payload = json_decode($job['payload'], true);

    try {
        switch ($job['type']) {
            case 'send_email':
                if (!isset($payload['to'], $payload['subject'], $payload['html'])) {
                    throw new RuntimeException('send_email payload missing required keys');
                }
                $ok = Mailer::send($payload['to'], $payload['subject'], $payload['html']);
                if (!$ok) {
                    throw new RuntimeException('Mailer::send() returned false');
                }
                break;

            default:
                throw new RuntimeException("Unknown job type: {$job['type']}");
        }

        Queue::complete($id);
        $processed++;
        error_log("[Worker] Job {$id} ({$job['type']}) completed OK");

    } catch (Throwable $e) {
        Queue::fail($id, $e->getMessage());
        $failed++;
        error_log("[Worker] Job {$id} ({$job['type']}) failed: " . $e->getMessage());
    }
}

// Periodically prune old completed/failed jobs (1-in-10 chance per run)
if (random_int(1, 10) === 1) {
    $pruned = Queue::prune(7);
    if ($pruned > 0) {
        error_log("[Worker] Pruned {$pruned} old job(s)");
    }
}

error_log("[Worker] Done. processed={$processed} failed={$failed}");
