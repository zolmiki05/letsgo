<?php
/**
 * RateLimiter – DB-backed sliding-window rate limiter.
 *
 * Limits are keyed by endpoint + client IP (SHA-256 hashed).
 * Rows are stored in the `rate_limits` table and automatically expired.
 *
 * Defined limits:
 *   login        – 10 attempts per 5 minutes
 *   forgot       – 5 attempts per 10 minutes
 *   invite_join  – 20 attempts per hour
 *
 * Usage:
 *   RateLimiter::check('login');   // aborts with 429 if over limit
 *   RateLimiter::hit('login');     // record a failed attempt
 */
class RateLimiter
{
    private const LIMITS = [
        'login'       => ['max' => 10, 'window' => 300],   // 10 / 5 min
        'forgot'      => ['max' => 5,  'window' => 600],   // 5 / 10 min
        'invite_join' => ['max' => 20, 'window' => 3600],  // 20 / hour
    ];

    /**
     * Check whether the client has exceeded the limit for the given endpoint.
     *
     * If the limit is exceeded: sets a flash error, sends HTTP 429,
     * and redirects to /login. Does not return.
     *
     * @param string $endpoint  Key from self::LIMITS.
     */
    public static function check(string $endpoint): void
    {
        if (!isset(self::LIMITS[$endpoint])) {
            return;
        }

        // Probabilistic cleanup: 10 % chance on each check to avoid a cron dependency
        if (random_int(1, 10) === 1) {
            self::cleanup();
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT hits FROM rate_limits
             WHERE key_hash = ? AND window_end > NOW()
             LIMIT 1'
        );
        $stmt->execute([self::key($endpoint)]);
        $hits = (int)$stmt->fetchColumn();

        if ($hits >= self::LIMITS[$endpoint]['max']) {
            http_response_code(429);
            Session::flash('error', Lang::t('errors.rate_limited'));
            redirect('/login');
        }
    }

    /**
     * Record a request attempt for the given endpoint.
     *
     * Uses INSERT … ON DUPLICATE KEY UPDATE to atomically increment the counter
     * within an existing window, or create a new window row.
     * key_hash must be UNIQUE in the rate_limits table for this to work.
     *
     * @param string $endpoint  Key from self::LIMITS.
     */
    public static function hit(string $endpoint): void
    {
        if (!isset(self::LIMITS[$endpoint])) {
            return;
        }

        $window = self::LIMITS[$endpoint]['window'];
        $db     = Database::getInstance();
        $db->prepare(
            'INSERT INTO rate_limits (key_hash, hits, window_end)
             VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
             ON DUPLICATE KEY UPDATE hits = hits + 1'
        )->execute([self::key($endpoint), $window]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private static function key(string $endpoint): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return hash('sha256', $endpoint . ':' . $ip);
    }

    private static function cleanup(): void
    {
        Database::getInstance()->exec('DELETE FROM rate_limits WHERE window_end < NOW()');
    }
}
