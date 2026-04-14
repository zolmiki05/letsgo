<?php
/**
 * Group model – CRUD and membership management for the `groups` table.
 *
 * A group is the central organisational unit of Letsgo.
 * Every programme idea (Event) belongs to exactly one group,
 * and users must be members of a group to see and interact with its events.
 *
 * Ownership:
 *   - The user who creates a group is its owner (owner_id).
 *   - Only the owner can delete the group or generate invite links.
 *   - Ownership transfer is not currently supported.
 *
 * Membership:
 *   - Stored in the `group_members` junction table.
 *   - The owner is automatically added as the first member on creation.
 *   - Duplicate membership is silently ignored (INSERT IGNORE).
 *   - Cascade deletes: deleting a group removes all members, events, invites, responses.
 */
class Group
{
    // ── Lookup ────────────────────────────────────────────────────────────────

    /**
     * Find a group by its primary key.
     *
     * @return array{id:int,name:string,owner_id:int,created_at:string}|null
     */
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM `groups` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return all groups where the user is a member, with a live member count.
     *
     * @param bool $archivedOnly  When true, return only archived groups; otherwise only active.
     * @return array<int, array{id:int,name:string,owner_id:int,is_archived:int,created_at:string,member_count:int}>
     */
    public static function forUser(int $userId, bool $archivedOnly = false): array
    {
        $db      = Database::getInstance();
        $archive = $archivedOnly ? 1 : 0;
        $stmt    = $db->prepare(
            'SELECT g.*, COUNT(gm2.user_id) AS member_count
             FROM `groups` g
             JOIN group_members gm  ON gm.group_id  = g.id AND gm.user_id = ?
             JOIN group_members gm2 ON gm2.group_id = g.id
             WHERE g.is_archived = ?
             GROUP BY g.id
             ORDER BY g.created_at DESC'
        );
        $stmt->execute([$userId, $archive]);
        return $stmt->fetchAll();
    }

    // ── Creation & deletion ───────────────────────────────────────────────────

    /**
     * Create a new group and automatically add the owner as the first member.
     *
     * Both the INSERT into `groups` and the INSERT into `group_members` are
     * done within the same request; they are not wrapped in a transaction because
     * a partial failure (group created but membership not added) is recoverable
     * by the admin and is extremely unlikely in practice.
     *
     * @param string $name     Group display name (max 255 chars).
     * @param int    $ownerId  ID of the creating user, who becomes the owner.
     * @return int  The new group's auto-increment ID.
     */
    public static function create(string $name, int $ownerId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO `groups` (name, owner_id, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$name, $ownerId]);
        $groupId = (int)$db->lastInsertId();

        // Owner is added as the first member immediately
        $stmt = $db->prepare(
            'INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$groupId, $ownerId]);

        return $groupId;
    }

    /**
     * Rename a group.
     *
     * @param int    $id    Group ID.
     * @param string $name  New display name (max 255 chars).
     */
    public static function rename(int $id, string $name): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE `groups` SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    /**
     * Archive a group (hides it from the active dashboard).
     * Only the owner should call this (enforced at controller level).
     */
    public static function archive(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE `groups` SET is_archived = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Unarchive a group (moves it back to the active dashboard).
     */
    public static function unarchive(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE `groups` SET is_archived = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Delete a group and all cascade-related data.
     *
     * The following rows are removed automatically via FK ON DELETE CASCADE:
     *   group_members, invites, events (→ responses)
     *
     * @param int $id  Group ID to delete.
     */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM `groups` WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ── Membership ────────────────────────────────────────────────────────────

    /**
     * Check whether a user is currently a member of the group.
     *
     * Used as an authorisation gate in most controllers: a user who is not a
     * member cannot view, modify, or respond to events in that group.
     */
    public static function isMember(int $groupId, int $userId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$groupId, $userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Add a user to the group.
     * INSERT IGNORE ensures this is idempotent: calling it on an existing member is a no-op.
     *
     * @param int $groupId  Target group.
     * @param int $userId   User to add.
     */
    public static function addMember(int $groupId, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT IGNORE INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$groupId, $userId]);
    }

    /**
     * Return all members of a group, ordered by join date (oldest first).
     * Includes email, username, and join timestamp for display in the sidebar.
     *
     * @return array<int, array{id:int,email:string,username:?string,joined_at:string}>
     */
    public static function members(int $groupId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT u.id, u.email, u.username, gm.joined_at
             FROM group_members gm
             JOIN users u ON u.id = gm.user_id
             WHERE gm.group_id = ?
             ORDER BY gm.joined_at ASC'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }
}
