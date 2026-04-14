<?php
/**
 * Csrf – stateless CSRF token protection for POST requests.
 *
 * A single token is generated per session and reused for its lifetime.
 * This avoids breaking multi-tab usage where one tab would invalidate another.
 *
 * Usage in views:   <?= csrfField() ?>       (calls Csrf::field())
 * Usage in Router:  Csrf::verify()           (called automatically for every POST)
 */
class Csrf
{
    private const KEY = '_csrf_token';

    /**
     * Return the current session's CSRF token, generating one if it doesn't exist yet.
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /**
     * Verify the CSRF token submitted with the current POST request.
     *
     * Accepts the token from:
     *   1. $_POST['_csrf_token']          (standard hidden field)
     *   2. HTTP_X_CSRF_TOKEN header       (future AJAX support)
     *
     * On failure: sends 419, renders an error page, and exits.
     */
    public static function verify(): void
    {
        $submitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$submitted || !hash_equals(self::token(), $submitted)) {
            http_response_code(419);
            // render() may not be available yet if called before bootstrap finishes,
            // but in practice Csrf::verify() is called from Router::dispatch()
            // which runs after full bootstrap.
            render('errors/csrf', ['pageTitle' => 'Érvénytelen kérés']);
            exit;
        }
    }

    /**
     * Return a hidden HTML input field carrying the CSRF token.
     * Call via the csrfField() global helper defined in bootstrap.php.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
