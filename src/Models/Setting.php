<?php
/**
 * Setting model – simple key/value application settings.
 */
class Setting
{
    public static function get(string $key, string $default = ''): string
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $val  = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    }

    public static function set(string $key, string $value): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $value]);
    }

    public static function isRegistrationOpen(): bool
    {
        return self::get('registration_open', '0') === '1';
    }

    public static function usersCanCreateGroups(): bool
    {
        return self::get('users_can_create_groups', '1') === '1';
    }
}
