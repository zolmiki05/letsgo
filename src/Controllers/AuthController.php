<?php
/**
 * AuthController – account registration, login, logout, and password management.
 *
 * Route summary:
 *   GET  /login           → loginForm()        Show the login page
 *   POST /login           → login()            Process credentials
 *   GET  /register        → registerForm()     Show the registration page
 *   POST /register        → register()         Create a new account
 *   POST /logout          → logout()           Destroy the session
 *   GET  /profile/password → passwordForm()    Show the password-change form
 *   POST /profile/password → changePassword()  Process the password change
 *
 * Registration modes:
 *   1. Invite-only (default): a valid AppInvite code is required.
 *   2. Open: no code required (admin enables via Setting 'registration_open').
 *   The first-ever account always requires a code (system-generated setup code)
 *   and is automatically granted admin rights.
 *
 * Login:
 *   The identifier field accepts either an email address or a username.
 *   Banned accounts receive a specific error message.
 *
 * Invite link preservation:
 *   If an unauthenticated user follows a group invite link (/join?token=…),
 *   requireAuth() saves the URL to session['redirect_after_login']. After
 *   a successful login, the user is redirected back to the invite link
 *   instead of the dashboard.
 */
class AuthController
{
    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * Display the login form.
     * Redirects already-authenticated users to the dashboard.
     * Shows the first-boot setup code banner when no users exist yet.
     */
    public function loginForm(array $params): void
    {
        if (Session::userId()) redirect('/');

        render('auth/login', [
            'pageTitle'  => Lang::t('auth.login_title'),
            'error'      => Session::flash('error'),
            'setupToken' => self::getSetupToken(), // null when users already exist
        ]);
    }

    /**
     * Process login form submission.
     *
     * Validates that identifier and password are non-empty, then delegates
     * credential checking to User::verify() which handles email/username lookup
     * and bcrypt comparison. On success, regenerates the session ID and redirects
     * to the originally intended URL (invite link) or the dashboard.
     */
    public function login(array $params): void
    {
        // Support both field names: new 'identifier' and legacy 'email'
        $identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (!$identifier) {
            Session::flash('error', Lang::t('auth.errors.identifier_required'));
            redirect('/login');
        }
        if (!$password) {
            Session::flash('error', Lang::t('auth.errors.password_required'));
            redirect('/login');
        }

        // Rate-limit: 10 failed attempts per 5 min per IP aborts with 429
        RateLimiter::check('login');

        $user = User::verify($identifier, $password);
        if (!$user) {
            RateLimiter::hit('login');
            // Provide a specific message for banned accounts (better UX than generic error)
            $found = User::findByIdentifier($identifier);
            if ($found && (int)($found['is_banned'] ?? 0) === 1) {
                Session::flash('error', Lang::t('auth.errors.account_banned'));
            } else {
                Session::flash('error', Lang::t('auth.errors.invalid_credentials'));
            }
            redirect('/login');
        }

        // Successful login: regenerate session ID (session fixation prevention)
        Session::login((int)$user['id']);

        // Redirect to the page the user was trying to reach before being sent to login
        // (typically a group invite link saved by requireAuth())
        $intended = Session::get('redirect_after_login');
        if ($intended) {
            Session::set('redirect_after_login', null); // consume the saved URL
            redirect($intended);
        }
        redirect('/');
    }

    // ── Registration ──────────────────────────────────────────────────────────

    /**
     * Display the registration form.
     * Redirects already-authenticated users to the dashboard.
     * Pre-fills the invite code field when a code is present in the URL.
     */
    public function registerForm(array $params): void
    {
        if (Session::userId()) redirect('/');

        $setupToken   = self::getSetupToken();
        // Pre-fill from ?invite= URL param, or fall back to the first-boot setup code
        $prefillToken = trim($_GET['invite'] ?? '') ?: ($setupToken ?? '');

        render('auth/register', [
            'pageTitle'        => Lang::t('auth.register_title'),
            'error'            => Session::flash('error'),
            'prefillToken'     => $prefillToken,
            'setupToken'       => $setupToken,
            'registrationOpen' => Setting::isRegistrationOpen(), // controls invite field visibility
        ]);
    }

    /**
     * Process registration form submission.
     *
     * Validation order:
     *   1. Invite code (if required)
     *   2. Email format and uniqueness
     *   3. Username format and uniqueness
     *   4. Password minimum length
     *
     * On success: account created, invite code consumed (if used), user logged in.
     */
    public function register(array $params): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $token    = trim($_POST['invite_token'] ?? '');

        // An invite is required when: registration is closed OR no users exist yet
        // (the first user always needs the system setup code)
        $needsInvite = !Setting::isRegistrationOpen() || AppInvite::noUsersExist();

        if ($needsInvite) {
            if (!$token) {
                Session::flash('error', Lang::t('auth.errors.invite_required'));
                redirect('/register');
            }
            $invite = AppInvite::findValid($token);
            if (!$invite) {
                Session::flash('error', Lang::t('auth.errors.invite_invalid'));
                redirect('/register');
            }
        }

        // Email validation
        if (!$email) {
            Session::flash('error', Lang::t('auth.errors.email_required'));
            redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', Lang::t('auth.errors.email_invalid'));
            redirect('/register');
        }

        // Username validation: 3–30 chars, alphanumeric + . _ -
        if ($username === '') {
            Session::flash('error', Lang::t('auth.errors.username_required'));
            redirect('/register');
        }
        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,30}$/', $username)) {
            Session::flash('error', Lang::t('auth.errors.username_invalid'));
            redirect('/register');
        }

        // Password minimum length
        if (strlen($password) < 6) {
            Session::flash('error', Lang::t('auth.errors.password_short'));
            redirect('/register');
        }

        // Uniqueness checks
        if (User::findByEmail($email)) {
            Session::flash('error', Lang::t('auth.errors.email_taken'));
            redirect('/register');
        }
        if (User::usernameExists($username)) {
            Session::flash('error', Lang::t('auth.errors.username_taken'));
            redirect('/register');
        }

        // Create the account; first user is auto-promoted to admin
        $userId = User::create($email, $password, $username);

        // Consume the invite code (if one was used)
        if ($needsInvite && isset($invite)) {
            AppInvite::markUsed($token, $userId);
        }

        Session::login($userId);
        redirect('/');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    /**
     * Destroy the current session and redirect to the login page.
     */
    public function logout(array $params): void
    {
        Session::logout();
        redirect('/login');
    }

    // ── Forgot password ───────────────────────────────────────────────────────

    public function forgotForm(array $params): void
    {
        if (Session::userId()) redirect('/');
        render('auth/forgot', [
            'pageTitle' => Lang::t('auth.forgot_title'),
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    public function forgot(array $params): void
    {
        if (Session::userId()) redirect('/');

        // Rate-limit: 5 attempts per 10 min per IP
        RateLimiter::check('forgot');
        RateLimiter::hit('forgot');

        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', Lang::t('auth.errors.email_invalid'));
            redirect('/forgot-password');
        }

        // Always show the same success message to prevent email enumeration
        $user = User::findByEmail($email);
        if ($user && !(int)($user['is_banned'] ?? 0)) {
            PasswordReset::purgeExpired();
            $token = PasswordReset::create((int)$user['id']);
            Mailer::sendPasswordReset($user, $token);
        }

        Session::flash('success', Lang::t('auth.forgot_sent'));
        redirect('/forgot-password');
    }

    // ── Reset password ────────────────────────────────────────────────────────

    public function resetForm(array $params): void
    {
        if (Session::userId()) redirect('/');

        $token = trim($_GET['token'] ?? '');
        $reset = $token ? PasswordReset::findValid($token) : null;

        if (!$reset) {
            Session::flash('error', Lang::t('auth.errors.reset_token_invalid'));
            redirect('/forgot-password');
        }

        render('auth/reset', [
            'pageTitle' => Lang::t('auth.reset_title'),
            'token'     => $token,
            'error'     => Session::flash('error'),
        ]);
    }

    public function reset(array $params): void
    {
        if (Session::userId()) redirect('/');

        $token   = trim($_POST['token'] ?? '');
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $reset = $token ? PasswordReset::findValid($token) : null;
        if (!$reset) {
            Session::flash('error', Lang::t('auth.errors.reset_token_invalid'));
            redirect('/forgot-password');
        }

        if (strlen($new) < 6) {
            Session::flash('error', Lang::t('auth.errors.password_short'));
            redirect('/reset-password?token=' . urlencode($token));
        }

        if ($new !== $confirm) {
            Session::flash('error', Lang::t('profile.errors.passwords_mismatch'));
            redirect('/reset-password?token=' . urlencode($token));
        }

        User::changePassword((int)$reset['user_id'], $new);
        PasswordReset::consume($token);

        Session::flash('success', Lang::t('auth.reset_success'));
        redirect('/login');
    }

    // ── Password change ───────────────────────────────────────────────────────

    /**
     * Display the password-change form.
     * Requires authentication (requireAuth() redirects to login if not logged in).
     */
    public function passwordForm(array $params): void
    {
        $userId = requireAuth();
        render('profile/password', [
            'pageTitle' => Lang::t('profile.change_password_title'),
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    /**
     * Process the password-change form.
     *
     * Verifies the current password before allowing the change to prevent
     * unauthorized password resets via unattended sessions.
     * Validates that the new password meets the minimum length and that the
     * confirmation matches.
     */
    public function changePassword(array $params): void
    {
        $userId  = requireAuth();
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Fetch the current hash directly (findById doesn't include password_hash)
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        // Current password must be correct
        if (!password_verify($current, $hash)) {
            Session::flash('error', Lang::t('profile.errors.current_wrong'));
            redirect('/profile/password');
        }

        // New password must meet minimum length
        if (strlen($new) < 6) {
            Session::flash('error', Lang::t('auth.errors.password_short'));
            redirect('/profile/password');
        }

        // Confirmation must match
        if ($new !== $confirm) {
            Session::flash('error', Lang::t('profile.errors.passwords_mismatch'));
            redirect('/profile/password');
        }

        User::changePassword($userId, $new);

        $user = User::findById($userId);
        if ($user) Mailer::sendPasswordChanged($user);

        Session::flash('success', Lang::t('profile.password_changed'));
        redirect('/profile/password');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Return the system-generated setup invite token if no users exist yet.
     *
     * Used to surface the first-boot code directly on the login/register pages
     * so the operator doesn't have to check the Docker logs.
     * Returns null once at least one user has been created.
     *
     * @return string|null  Token string, or null.
     */
    private static function getSetupToken(): ?string
    {
        try {
            if (!AppInvite::noUsersExist()) return null;
            $db   = Database::getInstance();
            $stmt = $db->query(
                'SELECT token FROM app_invites WHERE created_by IS NULL AND used_by IS NULL LIMIT 1'
            );
            $token = $stmt->fetchColumn();
            return $token ?: null;
        } catch (Throwable $e) {
            error_log('[Letsgo setup] getSetupToken failed: ' . $e->getMessage());
            return null;
        }
    }
}
