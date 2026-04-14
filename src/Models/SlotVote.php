<?php
/**
 * SlotVote model – availability voting on event time slots.
 *
 * Each group member can vote on every proposed time slot of an event,
 * indicating whether they are available (1) or unavailable (0).
 * Votes are upserted: re-submitting changes the availability value.
 *
 * Table: slot_votes (id, slot_id, user_id, available)
 */
class SlotVote
{
    /**
     * Cast or update a vote on a time slot.
     *
     * Uses INSERT … ON DUPLICATE KEY UPDATE on the UNIQUE(slot_id, user_id) key.
     *
     * @param int  $slotId     event_time_slots.id
     * @param int  $userId     voters's user ID
     * @param bool $available  true = available, false = unavailable
     */
    public static function vote(int $slotId, int $userId, bool $available): void
    {
        $db  = Database::getInstance();
        $val = $available ? 1 : 0;
        $db->prepare(
            'INSERT INTO slot_votes (slot_id, user_id, available)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE available = VALUES(available)'
        )->execute([$slotId, $userId, $val]);
    }

    /**
     * Return all votes for every slot belonging to an event.
     *
     * The result is keyed for easy lookup: $votes[$slotId][$userId] = 1|0.
     *
     * @return array<int, array<int, int>>  [slot_id => [user_id => available]]
     */
    public static function votesForEvent(int $eventId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT sv.slot_id, sv.user_id, sv.available
             FROM slot_votes sv
             JOIN event_time_slots ets ON ets.id = sv.slot_id
             WHERE ets.event_id = ?'
        );
        $stmt->execute([$eventId]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['slot_id']][(int)$r['user_id']] = (int)$r['available'];
        }
        return $map;
    }

    /**
     * Return the current user's votes for all slots of an event.
     *
     * @return array<int, int>  [slot_id => available (0|1)]
     */
    public static function myVotesForEvent(int $eventId, int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT sv.slot_id, sv.available
             FROM slot_votes sv
             JOIN event_time_slots ets ON ets.id = sv.slot_id
             WHERE ets.event_id = ? AND sv.user_id = ?'
        );
        $stmt->execute([$eventId, $userId]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['slot_id']] = (int)$r['available'];
        }
        return $map;
    }

    /**
     * Return aggregate vote counts per slot for an event.
     *
     * @return array<int, array{slot_id:int,yes:int,no:int,total:int}>
     */
    public static function totalsForEvent(int $eventId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT sv.slot_id,
                    SUM(sv.available)       AS yes,
                    SUM(1 - sv.available)   AS no,
                    COUNT(*)                AS total
             FROM slot_votes sv
             JOIN event_time_slots ets ON ets.id = sv.slot_id
             WHERE ets.event_id = ?
             GROUP BY sv.slot_id'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }
}
