<?php
/**
 * PasswordReset model – single-use password reset tokens.
 *
 * Flow:
 *   1. create($userId) – deletes any existing token for the user,
 *      inserts a new 64-char hex token valid for 1 hour, returns it.
 *   2. findValid($token) – returns the row if the token exists and has
 *      not expired; null otherwise.
 *   3. consume($token) – deletes the token (marks it as used).
 */
class PasswordReset
{
    /** Token lifetime in seconds (1 hour). */
    const TTL = 3600;

    /**
     * Generate a new reset token for the user.
     * Replaces any existing token for that user (only one active at a time).
     *
     * @return string  64-char uppercase hex token.
     */
    public static function create(int $userId): string
    {
        $db    = Database::getInstance();
        $token = bin2hex(random_bytes(32)); // 32 bytes = 64 hex chars

        // Remove any previous token for this user
        $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);

        $db->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . self::TTL . ' SECOND), NOW())'
        )->execute([$userId, $token]);

        return $token;
    }

    /**
     * Find a valid (non-expired) token row.
     *
     * @return array|null  Row with user_id, token, expires_at; or null.
     */
    public static function findValid(string $token): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM password_resets
             WHERE token = ? AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Consume (delete) a token after successful use.
     */
    public static function consume(string $token): void
    {
        Database::getInstance()
            ->prepare('DELETE FROM password_resets WHERE token = ?')
            ->execute([$token]);
    }

    /**
     * Delete all expired tokens (housekeeping, called opportunistically).
     */
    public static function purgeExpired(): void
    {
        Database::getInstance()
            ->query('DELETE FROM password_resets WHERE expires_at <= NOW()');
    }
}
