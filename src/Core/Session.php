<?php
/**
 * Session – thin, type-safe wrapper around PHP's native session mechanism.
 *
 * Responsibilities:
 *   - Starting the session with a consistent name ("letsgo_session")
 *   - Managing the authenticated user identity ($_SESSION['user_id'])
 *   - Providing a flash-message system (values survive exactly one request)
 *   - Offering typed get/set helpers for arbitrary session keys
 *
 * Security notes:
 *   - session_regenerate_id(true) is called on login to prevent session fixation.
 *   - On logout the session array is cleared and the session is destroyed.
 *   - Flash messages are stored under a '_flash' namespace key to avoid collisions.
 */
class Session
{
    /**
     * Start the PHP session if one is not already active.
     * Must be called once during bootstrap, before any output is sent.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('letsgo_session');
            session_start();
        }
    }

    // ── Authentication ────────────────────────────────────────────────────────

    /**
     * Return the currently authenticated user's ID, or null if not logged in.
     */
    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Authenticate a user for the current session.
     * Regenerates the session ID to prevent session fixation attacks.
     *
     * @param int $userId  The verified user's database ID.
     */
    public static function login(int $userId): void
    {
        session_regenerate_id(true); // invalidate old session ID
        $_SESSION['user_id'] = $userId;
    }

    /**
     * Destroy the current session, logging the user out.
     * Clears all session data before destroying.
     */
    public static function logout(): void
    {
        $_SESSION = [];       // wipe all session data
        session_destroy();    // destroy the server-side session file
    }

    // ── Generic key/value storage ─────────────────────────────────────────────

    /**
     * Persist an arbitrary value in the session under the given key.
     * Pass null as the value to delete the key.
     *
     * @param string $key    Session key (avoid leading '_', reserved for internals).
     * @param mixed  $value  Any serialisable value; pass null to remove the key.
     */
    public static function set(string $key, mixed $value): void
    {
        if ($value === null) {
            unset($_SESSION[$key]);
        } else {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key      Session key to look up.
     * @param mixed  $default  Returned when the key does not exist.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    /**
     * Read-once flash message store.
     *
     * Writing (with a value):  stores the message for the next request.
     * Reading (without value): returns the stored message and immediately deletes it.
     *
     * Usage:
     *   Session::flash('error', 'Something went wrong.');   // write
     *   $msg = Session::flash('error');                     // read → deletes after read
     *
     * @param string     $key    Message slot name (e.g. 'error', 'success').
     * @param mixed|null $value  Value to store, or null to read.
     * @return mixed             Returns null when writing; stored value (or null) when reading.
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            // Write mode: store value under the '_flash' namespace
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        // Read mode: retrieve and immediately remove
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}
