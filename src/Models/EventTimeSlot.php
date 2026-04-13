<?php
/**
 * EventTimeSlot model – multiple proposed time slots per event.
 *
 * Each event can have zero or more time slots representing candidate dates.
 * Like the main event date fields, each slot supports two parallel storage strategies:
 *   - slot_date (DATE)      – structured date for calendar display
 *   - slot_text (VARCHAR)   – free text, e.g. "jövő hétvégén", "TBD"
 * Only one of the two is set per slot; the other is NULL.
 *
 * Slots are ordered by sort_order ASC, then by id ASC.
 */
class EventTimeSlot
{
    /**
     * Return all time slots for an event, in sort order.
     *
     * @param int $eventId
     * @return array<int, array>
     */
    public static function forEvent(int $eventId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM event_time_slots WHERE event_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Replace all time slots for an event.
     *
     * Deletes existing slots, then inserts the new set.
     * Empty slots (both date and text are blank) are silently skipped.
     *
     * Each element of $slots must have:
     *   'mode' => 'date' | 'text'
     *   'date' => string  (DATE value, used when mode = 'date')
     *   'text' => string  (free text, used when mode = 'text')
     *
     * @param int   $eventId
     * @param array $slots   Array of slot descriptors (see above).
     */
    public static function replaceForEvent(int $eventId, array $slots): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM event_time_slots WHERE event_id = ?')->execute([$eventId]);

        $stmt = $db->prepare(
            'INSERT INTO event_time_slots (event_id, slot_date, slot_text, sort_order)
             VALUES (?, ?, ?, ?)'
        );

        $order = 0;
        foreach ($slots as $slot) {
            $slotDate = ($slot['mode'] === 'date') ? ($slot['date'] ?: null) : null;
            $slotText = ($slot['mode'] === 'text') ? ($slot['text'] ?: null) : null;
            if (!$slotDate && !$slotText) {
                continue; // skip completely empty slots
            }
            $stmt->execute([$eventId, $slotDate, $slotText, $order]);
            $order++;
        }
    }
}
