<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Queue class.
 *
 * Requires a running database. Each test cleans up after itself by
 * deleting the jobs it inserts.
 */
class QueueTest extends TestCase
{
    private array $insertedIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->insertedIds)) {
            $db   = Database::getInstance();
            $in   = implode(',', $this->insertedIds);
            $db->exec("DELETE FROM jobs WHERE id IN ({$in})");
        }
    }

    private function dispatch(array $payload = [], ?string $type = 'test_job'): void
    {
        Queue::dispatch($type, $payload);
        // Capture the last inserted ID
        $db   = Database::getInstance();
        $stmt = $db->query("SELECT id FROM jobs ORDER BY id DESC LIMIT 1");
        $row  = $stmt->fetch();
        if ($row) {
            $this->insertedIds[] = (int)$row['id'];
        }
    }

    public function testDispatchInsertsRow(): void
    {
        $db = Database::getInstance();
        $before = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE type='test_job'")->fetchColumn();

        $this->dispatch(['hello' => 'world']);

        $after = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE type='test_job'")->fetchColumn();
        $this->assertSame($before + 1, $after);
    }

    public function testNextClaimsJob(): void
    {
        $this->dispatch(['x' => 1]);
        $id = end($this->insertedIds);

        $job = Queue::next();
        $this->assertNotNull($job);
        $this->assertSame($id, (int)$job['id']);
        $this->assertSame('processing', $job['status']);

        // Ensure Queue::complete cleans up for our tearDown
        Queue::complete($id);
    }

    public function testCompleteMarksJobDone(): void
    {
        $this->dispatch(['x' => 2]);
        $id  = end($this->insertedIds);
        $job = Queue::next();
        Queue::complete((int)$job['id']);

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT status FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertSame('done', $stmt->fetchColumn());
    }

    public function testFailRetries(): void
    {
        $this->dispatch(['x' => 3]);
        $id  = end($this->insertedIds);
        $job = Queue::next();

        Queue::fail((int)$job['id'], 'simulated error');

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT status, attempts FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        // After first failure, attempts=1 which is < MAX_ATTEMPTS(3), so status=pending
        $this->assertSame('pending', $row['status']);
        $this->assertSame(1, (int)$row['attempts']);
    }

    public function testFailPermanentlyAfterMaxAttempts(): void
    {
        $this->dispatch(['x' => 4]);
        $id = end($this->insertedIds);

        // Simulate MAX_ATTEMPTS failures
        for ($i = 0; $i < 3; $i++) {
            // Force attempts back to processing before each fail
            $db = Database::getInstance();
            $db->prepare("UPDATE jobs SET status='processing', attempts=? WHERE id=?")
               ->execute([$i + 1, $id]);
            // Use attempts value equal to MAX_ATTEMPTS on last iteration
            Queue::fail($id, 'error');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT status FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        $this->assertSame('failed', $stmt->fetchColumn());
    }

    public function testPruneDeletesOldDoneJobs(): void
    {
        $db = Database::getInstance();
        // Insert a job and manually set it to done with an old created_at
        $db->prepare(
            "INSERT INTO jobs (type, payload, status, attempts, run_at, created_at)
             VALUES ('prune_test', '{}', 'done', 1, NOW(), DATE_SUB(NOW(), INTERVAL 10 DAY))"
        )->execute();
        $pruneId = (int)$db->lastInsertId();

        $pruned = Queue::prune(7);
        $this->assertGreaterThanOrEqual(1, $pruned);

        // Verify row is gone (no need to add to insertedIds)
        $stmt = $db->prepare("SELECT id FROM jobs WHERE id = ?");
        $stmt->execute([$pruneId]);
        $this->assertFalse($stmt->fetch());
    }
}
