<?php
/**
 * PHPUnit bootstrap – minimal setup without HTTP session.
 *
 * Loads all application classes without starting a session or running
 * the first-boot invite logic. Suitable for unit and integration tests.
 */

define('ROOT', dirname(__DIR__));

// Load .env for local test runs (CI injects vars directly)
if (is_file(ROOT . '/.env')) {
    $lines = file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '' && getenv($key) === false && !isset($_ENV[$key])) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Core services
require_once ROOT . '/src/Core/Database.php';
require_once ROOT . '/src/Core/Lang.php';
require_once ROOT . '/src/Core/Session.php';
require_once ROOT . '/src/Core/Router.php';
require_once ROOT . '/src/Core/Csrf.php';
require_once ROOT . '/src/Core/RateLimiter.php';
require_once ROOT . '/src/Core/Queue.php';

// Models
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

// Load English language for tests (avoids Hungarian-only assertions)
Lang::load(ROOT . '/lang/en.json', 'en');
