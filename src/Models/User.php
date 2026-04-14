<?php
/**
 * User model – authentication, profile, and admin/ban management.
 *
 * Handles all database interactions related to the `users` table:
 *   - Lookup by ID, email, username, or combined identifier
 *   - Account creation with optional username
 *   - Credential verification (supports both email and username login)
 *   - Password change
 *   - Admin flag checks
 *   - Ban/unban and hard-delete (admin actions)
 *   - Listing all users for the admin panel
 *
 * Security notes:
 *   - Passwords are hashed with password_hash(PASSWORD_DEFAULT) (bcrypt).
 *   - Banned users cannot log in; verify() returns null for banned accounts.
 *   - Admin actions (ban/delete) are enforced at the controller level; the model
 *     trusts that the caller has already verified admin privileges.
 */
class User
{
    // ── Lookup ────────────────────────────────────────────────────────────────

    /**
     * Find a user by their primary key.
     * Returns a safe subset of columns (excludes password_hash).
     *
     * @return array{id:int,email:string,username:?string,is_admin:int,is_banned:int}|null
     */
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, email, username, is_admin, is_banned, locale FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by their email address (case-insensitive via collation).
     * Returns the full row including password_hash (needed for credential checks).
     *
     * @return array|null  Full users row, or null if not found.
     */
    public static function findByEmail(string $email): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by their username.
     * Returns the full row including password_hash.
     *
     * @return array|null  Full users row, or null if not found.
     */
    public static function findByUsername(string $username): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Resolve a login identifier to a user row.
     *
     * Tries email first (identifiers containing '@' are always treated as email).
     * If the identifier contains no '@' and the email lookup fails, a username
     * lookup is attempted as a fallback.
     *
     * @param string $identifier  Email address or username.
     * @return array|null  Full users row, or null if not found.
     */
    public static function findByIdentifier(string $identifier): ?array
    {
        // Always try email first
        $user = self::findByEmail($identifier);
        // Fall back to username only when the input doesn't look like an email
        if (!$user && !str_contains($identifier, '@')) {
            $user = self::findByUsername($identifier);
        }
        return $user;
    }

    // ── Creation ──────────────────────────────────────────────────────────────

    /**
     * Create a new user account and return the new user's ID.
     *
     * The very first user ever created is automatically granted admin rights
     * (detected via AppInvite::noUsersExist()).
     *
     * @param string $email     Unique email address (already validated by caller).
     * @param string $password  Plain-text password; hashed before storage.
     * @param string $username  Optional display username; stored as NULL if empty.
     * @return int  The new user's auto-increment ID.
     */
    public static function create(string $email, string $password, string $username = ''): int
    {
        $db      = Database::getInstance();
        $hash    = password_hash($password, PASSWORD_DEFAULT); // bcrypt
        $isAdmin = AppInvite::noUsersExist() ? 1 : 0;         // first user → admin
        $uname   = $username !== '' ? $username : null;        // empty string → NULL

        $stmt = $db->prepare(
            'INSERT INTO users (email, username, password_hash, is_admin) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $uname, $hash, $isAdmin]);
        return (int)$db->lastInsertId();
    }

    // ── Authentication ────────────────────────────────────────────────────────

    /**
     * Verify login credentials and return the user row on success.
     *
     * Accepts either email or username as the identifier.
     * Returns null if:
     *   - The identifier doesn't match any user
     *   - The password is incorrect
     *   - The account is banned
     *
     * @param string $identifier  Email address or username.
     * @param string $password    Plain-text password to verify.
     * @return array|null  Full users row on success, null on failure.
     */
    public static function verify(string $identifier, string $password): ?array
    {
        $user = self::findByIdentifier($identifier);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        if ((int)($user['is_banned'] ?? 0) === 1) return null; // banned accounts cannot log in
        return $user;
    }

    /**
     * Update a user's password.
     * The new password is hashed before storage; the old hash is replaced.
     *
     * @param int    $id           User ID.
     * @param string $newPassword  Plain-text new password (min-length validated by caller).
     */
    public static function changePassword(int $id, string $newPassword): void
    {
        $db   = Database::getInstance();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    // ── Role / status checks ──────────────────────────────────────────────────

    /**
     * Return true if the given user has the admin flag set.
     */
    public static function isAdmin(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && (int)$user['is_admin'] === 1;
    }

    /**
     * Return true if the given user is banned.
     */
    public static function isBanned(int $userId): bool
    {
        $user = self::findById($userId);
        return $user && (int)$user['is_banned'] === 1;
    }

    // ── Admin actions ─────────────────────────────────────────────────────────

    /**
     * Set a user's is_banned flag to 1 (login will be refused).
     * Caller must ensure they are an admin and not banning themselves.
     */
    public static function ban(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET is_banned = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Clear the is_banned flag, re-enabling login for the user.
     */
    public static function unban(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET is_banned = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Permanently delete a user and all their owned data (cascade via FK).
     * Caller must ensure they are an admin and not deleting themselves.
     *
     * Cascade deletes: owned groups → group_members, events, invites, responses.
     */
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Return all users ordered by ID ascending, for the admin panel.
     * Excludes password_hash from the result set.
     *
     * @return array<int, array{id:int,email:string,username:?string,is_admin:int,is_banned:int,created_at:string}>
     */
    public static function all(): array
    {
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT id, email, username, is_admin, is_banned, created_at FROM users ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    // ── Locale ────────────────────────────────────────────────────────────────

    /**
     * Return a user's preferred locale (defaults to 'hu' if column is missing).
     */
    public static function getLocale(int $id): string
    {
        $user = self::findById($id);
        return $user['locale'] ?? 'hu';
    }

    /**
     * Persist a new locale preference for the given user.
     *
     * @param string $locale  ISO 639-1 code, e.g. 'hu' or 'en'.
     */
    public static function setLocale(int $id, string $locale): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET locale = ? WHERE id = ?');
        $stmt->execute([$locale, $id]);
    }

    // ── Uniqueness checks ─────────────────────────────────────────────────────

    /**
     * Check whether a username is already taken.
     *
     * @param string   $username    Username to check.
     * @param int|null $excludeId   Optionally exclude a specific user ID (for edit scenarios).
     * @return bool  True if the username is already in use.
     */
    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $db = Database::getInstance();
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
