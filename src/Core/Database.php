<?php
/**
 * Database – PDO singleton factory.
 *
 * Provides a single shared PDO connection for the entire request lifecycle.
 * Connection parameters are injected via environment variables, making this
 * portable across Docker, bare-metal, and CI environments.
 *
 * Configuration env vars:
 *   DB_HOST  – MySQL host        (default: "db", the Docker service name)
 *   DB_NAME  – Database name     (default: "letsgo")
 *   DB_USER  – MySQL username    (default: "letsgo")
 *   DB_PASS  – MySQL password    (default: "letsgo_secret")
 *
 * PDO is configured with:
 *   - ERRMODE_EXCEPTION   → all DB errors throw PDOException (no silent failures)
 *   - FETCH_ASSOC         → all fetchAll/fetch return associative arrays
 *   - EMULATE_PREPARES=0  → native prepared statements (safer, avoids type coercion bugs)
 */
class Database
{
    /**
     * Shared PDO instance; null until first call to getInstance().
     * Use reset() in tests to force a fresh connection.
     */
    private static ?PDO $instance = null;

    /**
     * Return the shared PDO connection, creating it on first call.
     *
     * @throws PDOException  If the connection cannot be established.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Read from $_ENV first (Docker/process injection), fall back to getenv()
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'letsgo';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'letsgo';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'letsgo_secret';

            // utf8mb4 required for full Unicode support (emoji, Hungarian characters, etc.)
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arrays, not objects
                PDO::ATTR_EMULATE_PREPARES   => false,                    // native prepared stmts
                PDO::ATTR_PERSISTENT         => true,                     // connection pooling via pconnect
            ]);
        }

        return self::$instance;
    }

    /**
     * Discard the cached connection, forcing a fresh one on the next getInstance() call.
     *
     * Intended for use in tests that need an isolated connection.
     * Not needed during normal request handling.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
