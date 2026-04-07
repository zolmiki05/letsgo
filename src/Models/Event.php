<?php
/**
 * Event model – programme idea CRUD and deadline queries.
 *
 * An Event is a programme idea proposed within a group. It progresses through
 * a defined lifecycle tracked by the `status` field:
 *
 *   IDEA → DISCUSSING → FINAL
 *                     ↘ CANCELLED
 *
 * Date handling:
 *   Events support two parallel date storage strategies for each date field:
 *   1. Structured (DATE column)  – enables deadline calculations and calendar display.
 *   2. Free-text (VARCHAR)       – allows informal expressions like "next weekend", "TBD".
 *   Only one of the two should be set per field; the view shows whichever is non-null.
 *
 *   Date fields:
 *     event_date / date_text              – When the event takes place
 *     deadline_signup / deadline_signup_text   – Last day to sign up
 *     deadline_decision / deadline_decision_text – Deadline for the go/no-go decision
 *
 * Feedback:
 *   Group members rate each event on three scales (1–10) via the Response model.
 *   Averages are computed in the database for efficiency.
 *
 * Access control:
 *   - Any group member may create events and submit feedback.
 *   - Only the creator may change status or delete the event.
 */
class Event
{
    // ── Lookup ────────────────────────────────────────────────────────────────

    /**
     * Find an event by its primary key.
     * Returns the full row (all columns), or null if not found.
     *
     * @return array|null  Full events row, or null.
     */
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return all events for a group, newest first, with the creator's email.
     *
     * The JOIN adds `creator_email` for display without a separate User query.
     *
     * @return array<int, array>  Event rows enriched with 'creator_email'.
     */
    public static function forGroup(int $groupId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT e.*, u.email AS creator_email, u.username AS creator_username
             FROM events e
             JOIN users u ON u.id = e.creator_id
             WHERE e.group_id = ?
             ORDER BY e.created_at DESC'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    // ── Creation & deletion ───────────────────────────────────────────────────

    /**
     * Insert a new event row and return its auto-increment ID.
     *
     * All optional fields are passed as null when empty (the DB stores NULL).
     * Status is hardcoded to 'IDEA' on creation.
     *
     * Expected keys in $data:
     *   group_id, creator_id, title, description, location,
     *   event_date, date_text,
     *   deadline_signup, deadline_signup_text,
     *   deadline_decision, deadline_decision_text,
     *   cost, notes
     *
     * @param array $data  Associative array of field values.
     * @return int  New event ID.
     */
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
            $data['event_date']             ?: null, // DATE column (structured)
            $data['date_text']              ?: null, // VARCHAR (free text)
            $data['deadline_signup']        ?: null, // DATE column
            $data['deadline_signup_text']   ?: null, // VARCHAR
            $data['deadline_decision']      ?: null, // DATE column
            $data['deadline_decision_text'] ?: null, // VARCHAR
            $data['cost']                   ?: null,
            $data['notes']                  ?: null,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Update an existing event's editable fields.
     *
     * All optional fields are passed as null when empty.
     * Does NOT change creator_id, group_id, status, or created_at.
     *
     * Expected keys in $data (same as create, minus group_id/creator_id):
     *   title, description, location,
     *   event_date, date_text,
     *   deadline_signup, deadline_signup_text,
     *   deadline_decision, deadline_decision_text,
     *   cost, notes
     *
     * @param int   $id    Event ID to update.
     * @param array $data  Associative array of field values.
     */
    public static function update(int $id, array $data): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE events SET
               title = ?, description = ?, location = ?,
               event_date = ?, date_text = ?,
               deadline_signup = ?, deadline_signup_text = ?,
               deadline_decision = ?, deadline_decision_text = ?,
               cost = ?, notes = ?,
               updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
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
            $id,
        ]);
    }

    /**
     * Permanently delete an event.
     * Cascade deletes all associated responses (via FK ON DELETE CASCADE).
     *
     * @param int $id  Event ID to delete.
     */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ── Status management ─────────────────────────────────────────────────────

    /**
     * Update the status of an event.
     *
     * Only values in the allowed set are accepted; invalid values are silently
     * ignored to prevent accidental corruption from form tampering.
     * also updates the updated_at timestamp.
     *
     * @param int    $id      Event ID.
     * @param string $status  One of: 'IDEA', 'DISCUSSING', 'FINAL', 'CANCELLED'.
     */
    public static function updateStatus(int $id, string $status): void
    {
        $allowed = ['IDEA', 'DISCUSSING', 'FINAL', 'CANCELLED'];
        if (!in_array($status, $allowed, true)) {
            return; // silently ignore invalid values
        }
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
    }

    // ── Dashboard queries ─────────────────────────────────────────────────────

    /**
     * Return upcoming deadline events for a user's groups (next 7 days).
     *
     * Only structured DATE deadlines (deadline_signup, deadline_decision) are
     * considered; free-text deadlines cannot be compared arithmetically.
     * Events with status FINAL or CANCELLED are excluded.
     *
     * Results are sorted by the earliest approaching deadline.
     *
     * @param int $userId  User whose group memberships define the search scope.
     * @return array<int, array>  Event rows with 'group_name', 'group_id' added.
     */
    public static function upcomingDeadlines(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT e.id, e.title, e.deadline_signup, e.deadline_decision,
                    g.name AS group_name, g.id AS group_id
             FROM events e
             JOIN `groups` g       ON g.id = e.group_id
             JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ?
             WHERE e.status NOT IN ('CANCELLED', 'FINAL')
               AND (
                     (e.deadline_signup   IS NOT NULL
                        AND e.deadline_signup   BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                  OR (e.deadline_decision IS NOT NULL
                        AND e.deadline_decision BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
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
