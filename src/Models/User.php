<?php
/**
 * User model – authentication, user retrieval, and admin flag management.
 */
class User
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, email, is_admin FROM users WHERE id = ? LIMIT 1');
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

    /**
     * Create a new user and return their ID.
     * The first user ever created is automatically granted admin rights.
     */
    public static function create(string $email, string $password): int
    {
        $db      = Database::getInstance();
        $hash    = password_hash($password, PASSWORD_DEFAULT);
        $isAdmin = AppInvite::noUsersExist() ? 1 : 0; // first user becomes admin

        $stmt = $db->prepare(
            'INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $isAdmin]);
        return (int)$db->lastInsertId();
    }

    /**
     * Verify credentials and return the user row on success, or null on failure.
     */
    public static function verify(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        return $user;
    }

    public static function isAdmin(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && (int)$user['is_admin'] === 1;
    }
}
