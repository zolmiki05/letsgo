<?php
/**
 * AuthController – registration, login, logout, and password change.
 */
class AuthController
{
    public function loginForm(array $params): void
    {
        if (Session::userId()) redirect('/');
        render('auth/login', [
            'pageTitle'  => Lang::t('auth.login_title'),
            'error'      => Session::flash('error'),
            'setupToken' => self::getSetupToken(),
        ]);
    }

    public function login(array $params): void
    {
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

        $user = User::verify($identifier, $password);
        if (!$user) {
            // Check if banned specifically for a better error message
            $found = User::findByIdentifier($identifier);
            if ($found && (int)($found['is_banned'] ?? 0) === 1) {
                Session::flash('error', Lang::t('auth.errors.account_banned'));
            } else {
                Session::flash('error', Lang::t('auth.errors.invalid_credentials'));
            }
            redirect('/login');
        }

        Session::login((int)$user['id']);

        // Redirect to originally intended URL (e.g. invite link)
        $intended = Session::get('redirect_after_login');
        if ($intended) {
            Session::set('redirect_after_login', null);
            redirect($intended);
        }
        redirect('/');
    }

    public function registerForm(array $params): void
    {
        if (Session::userId()) redirect('/');

        if (!Setting::isRegistrationOpen() && !self::inviteRequired()) {
            // Closed and no invite forced: this shouldn't happen, but guard anyway
        }

        $setupToken   = self::getSetupToken();
        $prefillToken = trim($_GET['invite'] ?? '') ?: ($setupToken ?? '');
        render('auth/register', [
            'pageTitle'        => Lang::t('auth.register_title'),
            'error'            => Session::flash('error'),
            'prefillToken'     => $prefillToken,
            'setupToken'       => $setupToken,
            'registrationOpen' => Setting::isRegistrationOpen(),
        ]);
    }

    public function register(array $params): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $token    = trim($_POST['invite_token'] ?? '');

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

        if (!$email) {
            Session::flash('error', Lang::t('auth.errors.email_required'));
            redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', Lang::t('auth.errors.email_invalid'));
            redirect('/register');
        }
        if ($username === '') {
            Session::flash('error', Lang::t('auth.errors.username_required'));
            redirect('/register');
        }
        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,30}$/', $username)) {
            Session::flash('error', Lang::t('auth.errors.username_invalid'));
            redirect('/register');
        }
        if (strlen($password) < 6) {
            Session::flash('error', Lang::t('auth.errors.password_short'));
            redirect('/register');
        }
        if (User::findByEmail($email)) {
            Session::flash('error', Lang::t('auth.errors.email_taken'));
            redirect('/register');
        }
        if (User::usernameExists($username)) {
            Session::flash('error', Lang::t('auth.errors.username_taken'));
            redirect('/register');
        }

        $userId = User::create($email, $password, $username);

        if ($needsInvite && isset($invite)) {
            AppInvite::markUsed($token, $userId);
        }

        Session::login($userId);
        redirect('/');
    }

    public function logout(array $params): void
    {
        Session::logout();
        redirect('/login');
    }

    public function passwordForm(array $params): void
    {
        $userId = requireAuth();
        render('profile/password', [
            'pageTitle' => Lang::t('profile.change_password_title'),
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    public function changePassword(array $params): void
    {
        $userId  = requireAuth();
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = User::findById($userId);
        // Re-read full row for password_hash
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            Session::flash('error', Lang::t('profile.errors.current_wrong'));
            redirect('/profile/password');
        }
        if (strlen($new) < 6) {
            Session::flash('error', Lang::t('auth.errors.password_short'));
            redirect('/profile/password');
        }
        if ($new !== $confirm) {
            Session::flash('error', Lang::t('profile.errors.passwords_mismatch'));
            redirect('/profile/password');
        }

        User::changePassword($userId, $new);
        Session::flash('success', Lang::t('profile.password_changed'));
        redirect('/profile/password');
    }

    /**
     * Return the system-generated invite token if no users exist yet, else null.
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

    private static function inviteRequired(): bool
    {
        return !Setting::isRegistrationOpen();
    }
}
