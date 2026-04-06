<?php
/**
 * Event model – programme idea CRUD within a group.
 */
class Event
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Return all events for a group, newest first, with creator email. */
    public static function forGroup(int $groupId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT e.*, u.email AS creator_email
             FROM events e
             JOIN users u ON u.id = e.creator_id
             WHERE e.group_id = ?
             ORDER BY e.created_at DESC'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    /** Insert a new event row and return its ID. */
    public static function create(array $data): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO events
             (group_id, creator_id, title, description, location,
              event_date, date_text,
              deadline_signup, deadline_signup_text,
              deadline_decision, deadline_decision_text,
              cost, notes, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'IDEA\', NOW(), NOW())'
        );
        $stmt->execute([
            $data['group_id'],
            $data['creator_id'],
            $data['title'],
            $data['description']            ?: null,
            $data['location']               ?: null,
            $data['event_date']             ?: null,
            $data['date_text']              ?: null,
            $data['deadline_signup']        ?: null,
            $data['deadline_signup_text']   ?: null,
            $data['deadline_decision']      ?: null,
            $data['deadline_decision_text'] ?: null,
            $data['cost']                   ?: null,
            $data['notes']                  ?: null,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Update the status of an event.
     * Only valid status values are accepted; invalid values are silently ignored.
     */
    /** Permanently delete an event and all its related responses (cascade). */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $allowed = ['IDEA', 'DISCUSSING', 'FINAL', 'CANCELLED'];
        if (!in_array($status, $allowed, true)) {
            return;
        }
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
    }

    /**
     * Return events with deadlines falling within the next 7 days
     * for groups the given user belongs to.
     */
    public static function upcomingDeadlines(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT e.id, e.title, e.deadline_signup, e.deadline_decision, g.name AS group_name, g.id AS group_id
             FROM events e
             JOIN `groups` g     ON g.id = e.group_id
             JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ?
             WHERE e.status NOT IN ('CANCELLED', 'FINAL')
               AND (
                     (e.deadline_signup   IS NOT NULL AND e.deadline_signup   BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                  OR (e.deadline_decision IS NOT NULL AND e.deadline_decision BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
               )
             ORDER BY LEAST(
               COALESCE(e.deadline_signup,   '9999-12-31'),
               COALESCE(e.deadline_decision, '9999-12-31')
             ) ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
