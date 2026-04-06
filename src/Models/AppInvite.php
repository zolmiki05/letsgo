<?php
/**
 * AppInvite model – single-use invite codes required for app registration.
 *
 * These are distinct from group invite links (Invite model).
 * created_by = NULL means the code was system-generated on first boot.
 */
class AppInvite
{
    /** Generate a new invite code and persist it. Returns the raw token string. */
    public static function create(?int $createdBy): string
    {
        $db    = Database::getInstance();
        $token = strtoupper(bin2hex(random_bytes(6))); // 12-char uppercase hex, readable
        $stmt  = $db->prepare(
            'INSERT INTO app_invites (token, created_by, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$token, $createdBy]);
        return $token;
    }

    /** Find an unused invite by its token. Returns null if not found or already used. */
    public static function findValid(string $token): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM app_invites WHERE token = ? AND used_by IS NULL LIMIT 1'
        );
        $stmt->execute([strtoupper($token)]);
        return $stmt->fetch() ?: null;
    }

    /** Mark a token as used by the given user. */
    public static function markUsed(string $token, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE app_invites SET used_by = ?, used_at = NOW() WHERE token = ?'
        );
        $stmt->execute([$userId, strtoupper($token)]);
    }

    /** Return all invite codes created by a specific admin, newest first. */
    public static function forAdmin(int $adminId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT ai.*, u.email AS used_by_email
             FROM app_invites ai
             LEFT JOIN users u ON u.id = ai.used_by
             WHERE ai.created_by = ? OR ai.created_by IS NULL
             ORDER BY ai.created_at DESC'
        );
        $stmt->execute([$adminId]);
        return $stmt->fetchAll();
    }

    /** Return true if there are no users in the database yet. */
    public static function noUsersExist(): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn() === 0;
    }
}
