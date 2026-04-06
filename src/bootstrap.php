<?php
/**
 * Bootstrap – loads all classes, initialises session and language.
 * Required by the front controller before any routing happens.
 */

define('ROOT', dirname(__DIR__));

// Load .env file if present (fallback for running outside Docker)
_loadDotEnv(ROOT . '/.env');

// Core services
require_once ROOT . '/src/Core/Database.php';
require_once ROOT . '/src/Core/Session.php';
require_once ROOT . '/src/Core/Lang.php';
require_once ROOT . '/src/Core/Router.php';

// Models (AppInvite must be loaded before User, as User::create uses it)
require_once ROOT . '/src/Models/AppInvite.php';
require_once ROOT . '/src/Models/User.php';
require_once ROOT . '/src/Models/Group.php';
require_once ROOT . '/src/Models/Invite.php';
require_once ROOT . '/src/Models/Event.php';
require_once ROOT . '/src/Models/Response.php';

// Controllers
require_once ROOT . '/src/Controllers/AuthController.php';
require_once ROOT . '/src/Controllers/AdminController.php';
require_once ROOT . '/src/Controllers/DashboardController.php';
require_once ROOT . '/src/Controllers/GroupController.php';
require_once ROOT . '/src/Controllers/EventController.php';
require_once ROOT . '/src/Controllers/InviteController.php';

// Initialise session and load Hungarian language strings
Session::start();
Lang::load(ROOT . '/lang/hu.json');

// ---------------------------------------------------------------------------
// First-boot setup: if no users exist, generate and publish an app invite code
// ---------------------------------------------------------------------------
_maybeGenerateSetupInvite();

// ---------------------------------------------------------------------------
// Helper functions available to all controllers and views
// ---------------------------------------------------------------------------

/**
 * Parse a .env file and populate $_ENV / putenv for keys not already set.
 * Does not override variables already injected by Docker or the OS.
 * Supports: KEY=value, KEY="value", KEY='value', and # comments.
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
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        // Never overwrite values already set by Docker / the host environment
        if ($key !== '' && getenv($key) === false && !isset($_ENV[$key])) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Return the application's base URL.
 * Uses APP_URL env var if set; otherwise auto-detects from the HTTP request.
 * Useful when running behind a reverse proxy with a custom domain.
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
 * Generate a first-boot app invite code if no users exist in the database.
 * The code is written to storage/app_setup.log and to the PHP error log
 * (visible via `docker logs letsgo_app`).
 */
function _maybeGenerateSetupInvite(): void
{
    try {
        if (!AppInvite::noUsersExist()) {
            return; // users already exist, nothing to do
        }

        // Check whether a system-generated invite already exists and is still unused
        $db   = Database::getInstance();
        $stmt = $db->query(
            'SELECT token FROM app_invites WHERE created_by IS NULL AND used_by IS NULL LIMIT 1'
        );
        $existing  = $stmt->fetchColumn();
        $isNewCode = $existing === false;
        $token     = $existing ?: AppInvite::create(null);

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

        // Write to Apache error log → visible in `docker logs letsgo_app`
        if ($isNewCode) {
            error_log($message);
        }

        // Best-effort file write (may silently fail if storage/ is not writable)
        $logPath = ROOT . '/storage/app_setup.log';
        @file_put_contents($logPath, $message . PHP_EOL, LOCK_EX);

    } catch (Throwable $e) {
        error_log('[Letsgo setup] Could not generate invite code: ' . $e->getMessage());
    }
}

/**
 * Render a view within the shared layout.
 *
 * @param string $view  Path relative to src/Views/, without .php extension
 * @param array  $data  Variables passed to both the layout and the view
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
 * Issue an HTTP redirect and halt execution.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Require an authenticated session. Redirects to /login if not logged in.
 *
 * @return int The current user's ID
 */
function requireAuth(): int
{
    $userId = Session::userId();
    if ($userId === null) {
        redirect('/login');
    }
    return $userId;
}

/**
 * Require admin privileges. Redirects to / if the user is not an admin.
 *
 * @return int The current user's ID
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
 * Escape a value for safe HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a date string for display (Y-m-d → Hungarian format).
 * Returns an empty string for null/empty input.
 */
function fmtDate(?string $date): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('Y. m. d.', $ts) : $date;
}
