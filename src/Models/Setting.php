<?php
/**
 * Setting model – application-wide key/value configuration store.
 *
 * Settings are persisted in the `settings` table and cached only within the
 * current request (no cross-request caching). This keeps behaviour predictable
 * while avoiding stale reads.
 *
 * Current settings:
 *   registration_open       (0|1)  – When 1, any visitor may register without
 *                                    an invite code. When 0, an AppInvite code
 *                                    is required (invite-only mode).
 *
 *   users_can_create_groups (0|1)  – When 1, any logged-in user may create
 *                                    groups. When 0, only admins can.
 *
 * Defaults (inserted by init.sql / upgrade.sql):
 *   registration_open        = '0'  (invite-only by default)
 *   users_can_create_groups  = '1'  (open group creation by default)
 *
 * All values are stored and returned as strings. Typed convenience methods
 * (isRegistrationOpen, usersCanCreateGroups) cast to bool for controllers.
 */
class Setting
{
    /**
     * Read a setting value by its key.
     *
     * @param string $key      Setting key (e.g. 'registration_open').
     * @param string $default  Returned when the key doesn't exist in the DB.
     * @return string  The stored value, or $default.
     */
    public static function get(string $key, string $default = ''): string
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $val  = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    }

    /**
     * Persist a setting value (INSERT or UPDATE via ON DUPLICATE KEY).
     *
     * Creates the row if it doesn't exist yet, or overwrites the value if it does.
     *
     * @param string $key    Setting key.
     * @param string $value  New value to store.
     */
    public static function set(string $key, string $value): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $value]);
    }

    // ── Typed convenience helpers ─────────────────────────────────────────────

    /**
     * Return true if open (invite-free) registration is enabled.
     * When false, users must supply a valid AppInvite code to register.
     */
    public static function isRegistrationOpen(): bool
    {
        return self::get('registration_open', '0') === '1';
    }

    /**
     * Return true if non-admin users are allowed to create groups.
     * When false, only admins can create groups.
     */
    public static function usersCanCreateGroups(): bool
    {
        return self::get('users_can_create_groups', '1') === '1';
    }
}
