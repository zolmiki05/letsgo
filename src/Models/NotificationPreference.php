<?php
/**
 * NotificationPreference model – opt-out notification settings per user.
 *
 * Uses an opt-out model: a row in `notification_preferences` means that
 * notification type is DISABLED for that user. If no row exists, the
 * notification is enabled (the default).
 *
 * Supported notification types (self::TYPES):
 *   event_created  – new event added to a group I belong to
 *   event_updated  – event I belong to was edited
 *   event_deleted  – event I belong to was removed
 *   status_changed – event status changed
 *   feedback       – someone rated my event
 *   member_joined  – new member joined my group
 *   comment        – new comment on an event I participate in
 *
 * Table: notification_preferences (id, user_id, type)
 *   UNIQUE KEY on (user_id, type)
 */
class NotificationPreference
{
    public const TYPES = [
        'event_created',
        'event_updated',
        'event_deleted',
        'status_changed',
        'feedback',
        'member_joined',
        'comment',
    ];

    /**
     * Check whether a notification type is disabled for a user.
     *
     * Returns true  → do NOT send the notification
     * Returns false → send the notification (default)
     */
    public static function isDisabled(int $userId, string $type): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT 1 FROM notification_preferences WHERE user_id = ? AND type = ? LIMIT 1'
        );
        $stmt->execute([$userId, $type]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Enable or disable a notification type for a user.
     *
     * $disabled = true  → INSERT IGNORE (adds the opt-out row)
     * $disabled = false → DELETE (removes the opt-out row, re-enabling the notification)
     */
    public static function setDisabled(int $userId, string $type, bool $disabled): void
    {
        $db = Database::getInstance();
        if ($disabled) {
            $db->prepare(
                'INSERT IGNORE INTO notification_preferences (user_id, type) VALUES (?, ?)'
            )->execute([$userId, $type]);
        } else {
            $db->prepare(
                'DELETE FROM notification_preferences WHERE user_id = ? AND type = ?'
            )->execute([$userId, $type]);
        }
    }

    /**
     * Return the set of disabled notification types for a user.
     *
     * @return array<string>  e.g. ['event_updated', 'feedback']
     */
    public static function disabledTypesForUser(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT type FROM notification_preferences WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
