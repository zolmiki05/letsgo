<?php
/**
 * User model – authentication, user retrieval, admin/ban management.
 */
class User
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, email, username, is_admin, is_banned FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /** Find by email OR username (for login). */
    public static function findByIdentifier(string $identifier): ?array
    {
        // Try email first, then username
        $user = self::findByEmail($identifier);
        if (!$user && !str_contains($identifier, '@')) {
            $user = self::findByUsername($identifier);
        }
        return $user;
    }

    /**
     * Create a new user and return their ID.
     * The first user ever created is automatically granted admin rights.
     */
    public static function create(string $email, string $password, string $username = ''): int
    {
        $db      = Database::getInstance();
        $hash    = password_hash($password, PASSWORD_DEFAULT);
        $isAdmin = AppInvite::noUsersExist() ? 1 : 0;
        $uname   = $username !== '' ? $username : null;

        $stmt = $db->prepare(
            'INSERT INTO users (email, username, password_hash, is_admin) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $uname, $hash, $isAdmin]);
        return (int)$db->lastInsertId();
    }

    /**
     * Verify credentials and return the user row on success, or null on failure.
     * Accepts email or username as identifier.
     */
    public static function verify(string $identifier, string $password): ?array
    {
        $user = self::findByIdentifier($identifier);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        if ((int)($user['is_banned'] ?? 0) === 1) return null;
        return $user;
    }

    public static function isAdmin(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && (int)$user['is_admin'] === 1;
    }

    public static function isBanned(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && (int)$user['is_banned'] === 1;
    }

    public static function ban(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET is_banned = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function unban(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET is_banned = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function changePassword(int $id, string $newPassword): void
    {
        $db   = Database::getInstance();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    /** Return all users (for admin panel). */
    public static function all(): array
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT id, email, username, is_admin, is_banned, created_at FROM users ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $db   = Database::getInstance();
        if ($excludeId !== null) {
            $stmt = $db->prepare('SELECT 1 FROM users WHERE username = ? AND id != ? LIMIT 1');
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
        }
        return (bool)$stmt->fetchColumn();
    }
}
