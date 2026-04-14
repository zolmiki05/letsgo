<?php
/**
 * Comment model – threaded comments on events.
 *
 * Comments are attached to an event and authored by a user.
 * Only the comment author or an admin may delete a comment.
 *
 * Table: comments (id, event_id, user_id, body, created_at)
 */
class Comment
{
    /**
     * Return all comments for an event, newest first, with author display name.
     *
     * @return array<int, array{id:int,event_id:int,user_id:int,body:string,created_at:string,author:string}>
     */
    public static function forEvent(int $eventId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT c.*, COALESCE(u.username, u.email) AS author
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.event_id = ?
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single comment by its primary key.
     *
     * @return array|null  Comment row, or null if not found.
     */
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Insert a new comment and return its ID.
     *
     * @param int    $eventId  Event the comment belongs to.
     * @param int    $userId   Author's user ID.
     * @param string $body     Comment text (max 2000 chars enforced at controller level).
     * @return int  New comment ID.
     */
    public static function create(int $eventId, int $userId, string $body): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO comments (event_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$eventId, $userId, $body]);
        return (int)$db->lastInsertId();
    }

    /**
     * Permanently delete a comment.
     *
     * Authorization (only author or admin may delete) must be checked by the caller.
     */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$id]);
    }
}
