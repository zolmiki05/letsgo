<?php
/**
 * Response model – per-user feedback on a programme idea.
 *
 * Each user may have at most one response per event (UNIQUE constraint).
 * The upsert() method transparently handles both insert and update cases.
 */
class Response
{
    /** Find a user's response to a specific event. */
    public static function findByEventAndUser(int $eventId, int $userId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM responses WHERE event_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$eventId, $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Return all responses for an event, including the respondent's email. */
    public static function forEvent(int $eventId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT r.*, u.email
             FROM responses r
             JOIN users u ON u.id = r.user_id
             WHERE r.event_id = ?
             ORDER BY r.created_at ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Insert or update a user's three-scale feedback for an event.
     * All scale values must be integers between 1 and 10 (validated by the caller).
     */
    public static function upsert(int $eventId, int $userId, int $interest, int $mood, int $willingness): void
    {
        $db       = Database::getInstance();
        $existing = self::findByEventAndUser($eventId, $userId);

        if ($existing) {
            $stmt = $db->prepare(
                'UPDATE responses
                 SET interest_level = ?, mood_level = ?, willingness_level = ?, updated_at = NOW()
                 WHERE event_id = ? AND user_id = ?'
            );
            $stmt->execute([$interest, $mood, $willingness, $eventId, $userId]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO responses
                 (event_id, user_id, interest_level, mood_level, willingness_level, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$eventId, $userId, $interest, $mood, $willingness]);
        }
    }

    /**
     * Return rounded averages for all three scales, plus the total response count.
     * Returns null if there are no responses yet.
     */
    public static function averages(int $eventId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT
               COUNT(*)                              AS count,
               ROUND(AVG(interest_level),    1)      AS avg_interest,
               ROUND(AVG(mood_level),         1)     AS avg_mood,
               ROUND(AVG(willingness_level),  1)     AS avg_willingness
             FROM responses WHERE event_id = ?'
        );
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        return ($row && (int)$row['count'] > 0) ? $row : null;
    }
}
