<?php
/**
 * Invite model – group invitation token management.
 * Each token is valid for exactly 24 hours from creation.
 */
class Invite
{
    /**
     * Create a new invite token for a group and return the raw token string.
     */
    public static function create(int $groupId, int $createdBy): string
    {
        $db    = Database::getInstance();
        $token = bin2hex(random_bytes(16));
        $stmt  = $db->prepare(
            'INSERT INTO invites (group_id, token, created_at, expires_at, created_by)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), ?)'
        );
        $stmt->execute([$groupId, $token, $createdBy]);
        return $token;
    }

    /** Find an invite by its token string. Returns null if not found. */
    public static function findByToken(string $token): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM invites WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return the most recently created, still-valid invite for a group.
     * Returns null if no active invite exists.
     */
    public static function latestForGroup(int $groupId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM invites
             WHERE group_id = ? AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetch() ?: null;
    }
}
