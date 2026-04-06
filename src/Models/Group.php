<?php
/**
 * Group model – CRUD and membership management.
 */
class Group
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM `groups` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new group and add the owner as the first member.
     */
    public static function create(string $name, int $ownerId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO `groups` (name, owner_id, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$name, $ownerId]);
        $groupId = (int)$db->lastInsertId();

        $stmt = $db->prepare(
            'INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$groupId, $ownerId]);

        return $groupId;
    }

    /** Delete a group and cascade-remove all related data. */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM `groups` WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Return all groups where the given user is a member,
     * including a member count for display.
     */
    public static function forUser(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT g.*, COUNT(gm2.user_id) AS member_count
             FROM `groups` g
             JOIN group_members gm  ON gm.group_id  = g.id AND gm.user_id = ?
             JOIN group_members gm2 ON gm2.group_id = g.id
             GROUP BY g.id
             ORDER BY g.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function isMember(int $groupId, int $userId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$groupId, $userId]);
        return (bool)$stmt->fetch();
    }

    /** Add a user as a member; silently ignores duplicates (INSERT IGNORE). */
    public static function addMember(int $groupId, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT IGNORE INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$groupId, $userId]);
    }

    /** Return all members of a group with their email and join date. */
    public static function members(int $groupId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.id, u.email, gm.joined_at
             FROM group_members gm
             JOIN users u ON u.id = gm.user_id
             WHERE gm.group_id = ?
             ORDER BY gm.joined_at ASC'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }
}
