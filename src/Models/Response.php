<?php
/**
 * Response model – per-user three-scale feedback on a programme idea.
 *
 * Each group member can rate an event on three independent 1–10 scales:
 *   - interest_level    How interested they are in this type of activity in general
 *   - mood_level        How much they feel like doing it right now
 *   - willingness_level How willing they are to actually go
 *
 * The three scales are intentionally separate to help the group identify events
 * that are universally liked but where the timing might be off (high interest,
 * low mood) vs. events the group is ready to commit to now (all scores high).
 *
 * Constraints:
 *   - One response per user per event (UNIQUE KEY uq_response in DB).
 *   - Scores must be integers 1–10 (validated in EventController, not here).
 *   - upsert() transparently handles both initial submission and later edits.
 *
 * Averages:
 *   - Computed in MySQL via AVG() + ROUND() for efficiency.
 *   - Returns null when no responses exist yet.
 */
class Response
{
    // ── Lookup ────────────────────────────────────────────────────────────────

    /**
     * Find a specific user's response to a specific event.
     * Returns null if the user hasn't responded yet.
     *
     * @return array|null  Response row, or null.
     */
    public static function findByEventAndUser(int $eventId, int $userId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM responses WHERE event_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$eventId, $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return all responses for an event, ordered by submission time.
     * Each row includes the respondent's email address for display.
     *
     * @return array<int, array>  Response rows with an added 'email' column.
     */
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

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Insert or update a user's feedback for an event (upsert pattern).
     *
     * Checks for an existing row first:
     *   - Found    → UPDATE the three scale values and updated_at
     *   - Not found → INSERT a new row
     *
     * This approach is used instead of INSERT ... ON DUPLICATE KEY UPDATE
     * to keep the code explicit and easy to extend.
     *
     * Pre-condition: all scale values have been validated as integers 1–10.
     *
     * @param int $eventId      Event being rated.
     * @param int $userId       User submitting the rating.
     * @param int $interest     Interest level (1–10).
     * @param int $mood         Current mood level (1–10).
     * @param int $willingness  Willingness to attend (1–10).
     */
    public static function upsert(
        int $eventId,
        int $userId,
        int $interest,
        int $mood,
        int $willingness
    ): void {
        $db       = Database::getInstance();
        $existing = self::findByEventAndUser($eventId, $userId);

        if ($existing) {
            // Update existing response
            $stmt = $db->prepare(
                'UPDATE responses
                 SET interest_level = ?, mood_level = ?, willingness_level = ?, updated_at = NOW()
                 WHERE event_id = ? AND user_id = ?'
            );
            $stmt->execute([$interest, $mood, $willingness, $eventId, $userId]);
        } else {
            // Insert first response
            $stmt = $db->prepare(
                'INSERT INTO responses
                 (event_id, user_id, interest_level, mood_level, willingness_level, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$eventId, $userId, $interest, $mood, $willingness]);
        }
    }

    // ── Aggregates ────────────────────────────────────────────────────────────

    /**
     * Compute rounded average scores and response count for an event.
     *
     * Averages are rounded to one decimal place in MySQL (ROUND(..., 1)).
     * Returns null when there are no responses yet (count = 0), which signals
     * the view to show a "no responses" placeholder instead of zeroed averages.
     *
     * @return array{count:int,avg_interest:string,avg_mood:string,avg_willingness:string}|null
     */
    public static function averages(int $eventId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT
               COUNT(*)                             AS count,
               ROUND(AVG(interest_level),    1)     AS avg_interest,
               ROUND(AVG(mood_level),         1)    AS avg_mood,
               ROUND(AVG(willingness_level),  1)    AS avg_willingness
             FROM responses WHERE event_id = ?'
        );
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        // Return null when there are zero responses to simplify view logic
        return ($row && (int)$row['count'] > 0) ? $row : null;
    }
}
