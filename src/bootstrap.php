<?php
/**
 * Bootstrap – application initialisation.
 *
 * This file is the first thing executed on every request (required by index.php).
 * It is responsible for:
 *   1. Defining the ROOT constant (absolute path to the project root).
 *   2. Loading environment variables from .env (for local development outside Docker).
 *   3. Including all core services, models, and controllers via require_once.
 *   4. Starting the PHP session and loading the Hungarian language strings.
 *   5. Running the first-boot setup routine (_maybeGenerateSetupInvite).
 *   6. Defining global helper functions used by controllers and views.
 *
 * Load order matters:
 *   - AppInvite must be loaded before User (User::create calls AppInvite::noUsersExist).
 *   - Setting must be loaded before User (Setting is used in controllers).
 *   - All models must be loaded before controllers.
 *
 * Global helpers defined here:
 *   _loadDotEnv()             – parse .env file
 *   _maybeGenerateSetupInvite() – first-boot invite code generation
 *   appBaseUrl()              – detect or read the app's public URL
 *   render()                  – render a view inside the shared layout
 *   redirect()                – HTTP redirect + halt
 *   requireAuth()             – auth gate; saves intended URL for post-login redirect
 *   requireAdmin()            – admin gate
 *   e()                       – HTML-escape output
 *   fmtDate()                 – format DB date string for Hungarian display
 */

define('ROOT', dirname(__DIR__));

// Load .env file if present (fallback for running outside Docker)
_loadDotEnv(ROOT . '/.env');

// ── Core services ─────────────────────────────────────────────────────────────
require_once ROOT . '/src/Core/Database.php';
require_once ROOT . '/src/Core/Session.php';
require_once ROOT . '/src/Core/Lang.php';
require_once ROOT . '/src/Core/Router.php';
require_once ROOT . '/src/Core/Csrf.php';
require_once ROOT . '/src/Core/RateLimiter.php';
require_once ROOT . '/src/Core/Queue.php';

// ── Models ────────────────────────────────────────────────────────────────────
// AppInvite must precede User (User::create checks AppInvite::noUsersExist)
require_once ROOT . '/src/Models/AppInvite.php';
require_once ROOT . '/src/Models/Setting.php';
require_once ROOT . '/src/Models/User.php';
require_once ROOT . '/src/Models/Group.php';
require_once ROOT . '/src/Models/Invite.php';
require_once ROOT . '/src/Models/Event.php';
require_once ROOT . '/src/Models/PasswordReset.php';
require_once ROOT . '/src/Models/EventTimeSlot.php';
require_once ROOT . '/src/Models/Response.php';
require_once ROOT . '/src/Models/Comment.php';
require_once ROOT . '/src/Models/SlotVote.php';
require_once ROOT . '/src/Models/NotificationPreference.php';

// ── Core services (continued) ─────────────────────────────────────────────────
require_once ROOT . '/src/Core/Mailer.php';

// ── Controllers ───────────────────────────────────────────────────────────────
require_once ROOT . '/src/Controllers/AuthController.php';
require_once ROOT . '/src/Controllers/AdminController.php';
require_once ROOT . '/src/Controllers/DashboardController.php';
require_once ROOT . '/src/Controllers/GroupController.php';
require_once ROOT . '/src/Controllers/EventController.php';
require_once ROOT . '/src/Controllers/InviteController.php';
require_once ROOT . '/src/Controllers/ProfileController.php';

// ── Runtime initialisation ────────────────────────────────────────────────────
Session::start();                        // must happen before any output

// Load language based on user's saved locale preference (defaults to Hungarian)
$_langUserId = Session::userId();
$_userLocale = 'hu';
if ($_langUserId) {
    $_userRow    = User::findById($_langUserId);
    $_userLocale = $_userRow['locale'] ?? 'hu';
}
$_langFile = ROOT . '/lang/' . $_userLocale . '.json';
if (!is_file($_langFile)) {
    $_langFile   = ROOT . '/lang/hu.json';
    $_userLocale = 'hu';
}
Lang::load($_langFile, $_userLocale);
unset($_langUserId, $_userRow, $_userLocale, $_langFile);

// First-boot: auto-generate setup invite code when no users exist yet
_maybeGenerateSetupInvite();

// =============================================================================
// Helper functions – available globally to all controllers and views
// =============================================================================

/**
 * Parse a .env file and populate $_ENV / putenv for keys not already set.
 *
 * This is a development/fallback mechanism. In production (Docker), environment
 * variables are injected by docker-compose and this function is effectively a
 * no-op because getenv() already returns values for those keys.
 *
 * Supported formats per line:
 *   KEY=value          – bare value
 *   KEY="value"        – double-quoted value
 *   KEY='value'        – single-quoted value
 *   # comment          – ignored
 *   blank lines        – ignored
 *
 * @param string $path  Absolute path to the .env file. Silently returns if missing.
 */
function _loadDotEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue; // skip blank lines and comments
        }
        if (!str_contains($line, '=')) {
            continue; // skip malformed lines
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'"); // strip surrounding quotes
        // Never overwrite values already set by Docker or the host OS
        if ($key !== '' && getenv($key) === false && !isset($_ENV[$key])) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Return the application's public base URL (scheme + host, no trailing slash).
 *
 * Priority:
 *   1. APP_URL environment variable (set in .env or docker-compose.yml)
 *   2. Auto-detected from the current HTTP request headers
 *
 * The env-var override is important when running behind a reverse proxy that
 * terminates TLS, where $_SERVER['HTTPS'] would incorrectly be 'off'.
 *
 * @return string  e.g. "https://letsgo.example.com" or "http://localhost:8080"
 */
function appBaseUrl(): string
{
    $override = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
    if ($override !== '') {
        return rtrim($override, '/');
    }
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Generate and publish a first-boot application invite code.
 *
 * Called on every request, but exits immediately if any users already exist.
 * When no users exist:
 *   - Creates a system invite code (created_by = NULL) if one doesn't exist yet.
 *   - Logs the code to the PHP error log (visible via `docker logs letsgo_app`).
 *   - Writes the code to storage/app_setup.log for easy retrieval.
 *
 * The system code is also surfaced directly in the UI (login/register pages)
 * by AuthController::getSetupToken() so the operator doesn't need to check logs.
 *
 * After the first account is created, this function becomes permanently inactive.
 */
function _maybeGenerateSetupInvite(): void
{
    try {
        if (!AppInvite::noUsersExist()) {
            return; // users already exist – nothing to do
        }

        // Reuse an existing unused system code rather than creating duplicates
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT token FROM app_invites WHERE created_by IS NULL AND used_by IS NULL LIMIT 1'
        );
        $existing  = $stmt->fetchColumn();
        $isNewCode = $existing === false;
        $token     = $existing ?: AppInvite::create(null); // create only if none exists

        $line    = str_repeat('=', 60);
        $message = implode(PHP_EOL, [
            $line,
            '  LETSGO SETUP',
            '  No users found. Use this invite code to register the',
            '  first (admin) account:',
            '',
            '      ' . $token,
            '',
            '  The code is also saved to: storage/app_setup.log',
            '  After registration this code becomes invalid.',
            $line,
        ]);

        // Log to Apache/PHP error log (only on first generation to avoid log spam)
        if ($isNewCode) {
            error_log($message);
        }

        // Best-effort write to storage/; silently fails if directory is not writable
        $logPath = ROOT . '/storage/app_setup.log';
        @file_put_contents($logPath, $message . PHP_EOL, LOCK_EX);

    } catch (Throwable $e) {
        error_log('[Letsgo setup] Could not generate invite code: ' . $e->getMessage());
    }
}

/**
 * Render a named view inside the shared HTML layout.
 *
 * Wraps the view file with src/Views/layout/header.php and footer.php.
 * All keys in $data are extracted into local variables available inside the view.
 * EXTR_SKIP prevents $data from overwriting variables already defined in scope.
 *
 * @param string $view  View path relative to src/Views/, without the .php extension.
 *                      Examples: 'auth/login', 'group/show', 'errors/404'.
 * @param array  $data  Associative array of variables to expose to the view.
 */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = ROOT . '/src/Views/' . $view . '.php';

    require ROOT . '/src/Views/layout/header.php';
    require $viewFile;
    require ROOT . '/src/Views/layout/footer.php';
}

/**
 * Issue an HTTP redirect and halt execution immediately.
 *
 * @param string $url  Absolute path (e.g. '/login') or full URL.
 * @return never       Always terminates via exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Authentication gate – require a logged-in session.
 *
 * If the user is not authenticated:
 *   - Saves the current request URI (including query string) to session under
 *     'redirect_after_login', so that invite links are not lost on redirect.
 *   - Redirects to /login and halts execution.
 *
 * @return int  The authenticated user's ID.
 */
function requireAuth(): int
{
    $userId = Session::userId();
    if ($userId === null) {
        // Preserve the intended destination for post-login redirect
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if ($uri !== '/login' && $uri !== '/logout') {
            Session::set('redirect_after_login', $uri);
        }
        redirect('/login');
    }
    return $userId;
}

/**
 * Admin gate – require both authentication and admin privileges.
 *
 * Calls requireAuth() first (handles unauthenticated redirects), then checks
 * the is_admin flag. Non-admin users are redirected to the dashboard (/).
 *
 * @return int  The authenticated admin's user ID.
 */
function requireAdmin(): int
{
    $userId = requireAuth();
    if (!User::isAdmin($userId)) {
        redirect('/');
    }
    return $userId;
}

/**
 * Render a hidden CSRF token input field.
 * Shorthand for <?= Csrf::field() ?> in views.
 *
 * @return string  HTML hidden input element.
 */
function csrfField(): string
{
    return Csrf::field();
}

/**
 * HTML-escape a value for safe output in views.
 *
 * Converts special characters to HTML entities using UTF-8 encoding.
 * All user-supplied strings must go through e() before being echoed.
 *
 * @param mixed $value  Any value; cast to string before escaping.
 * @return string  HTML-safe string.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a date string for Hungarian display.
 *
 * Converts a MySQL DATE or DATETIME string (Y-m-d or Y-m-d H:i:s) to the
 * Hungarian convention: "2025. 06. 14."
 * Returns an empty string for null/empty input (graceful no-op for optional fields).
 *
 * @param string|null $date  MySQL date string or null.
 * @return string  Formatted date string, or '' if input is empty/invalid.
 */
function fmtDate(?string $date): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('Y. m. d.', $ts) : $date; // fall back to raw string if parse fails
}
