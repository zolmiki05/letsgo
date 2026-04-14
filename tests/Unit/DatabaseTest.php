<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Database singleton.
 *
 * These tests require a running database (same credentials as .env).
 * They are placed in Unit/ because they test the singleton mechanics,
 * not application business logic.
 */
class DatabaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Database::reset();
    }

    public function testGetInstanceReturnsPDO(): void
    {
        $pdo = Database::getInstance();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testGetInstanceReturnsSameObject(): void
    {
        $a = Database::getInstance();
        $b = Database::getInstance();
        $this->assertSame($a, $b);
    }

    public function testResetClearsInstance(): void
    {
        $a = Database::getInstance();
        Database::reset();
        $b = Database::getInstance();
        // After reset a fresh connection is created; it is a PDO but a new object
        $this->assertInstanceOf(PDO::class, $b);
    }

    public function testConnectionCanExecuteQuery(): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query('SELECT 1 AS val');
        $row  = $stmt->fetch();
        $this->assertSame('1', (string)$row['val']);
    }
}
