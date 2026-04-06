<?php
/**
 * Database – PDO singleton.
 * Connection parameters are read from environment variables injected by Docker.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'letsgo';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'letsgo';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'letsgo_secret';

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
