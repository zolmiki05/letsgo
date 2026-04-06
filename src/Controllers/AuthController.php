<?php
/**
 * AuthController – invite-code-gated registration, login, and logout.
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
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email) {
            Session::flash('error', Lang::t('auth.errors.email_required'));
            redirect('/login');
        }
        if (!$password) {
            Session::flash('error', Lang::t('auth.errors.password_required'));
            redirect('/login');
        }

        $user = User::verify($email, $password);
        if (!$user) {
            Session::flash('error', Lang::t('auth.errors.invalid_credentials'));
            redirect('/login');
        }

        Session::login((int)$user['id']);
        redirect('/');
    }

    public function registerForm(array $params): void
    {
        if (Session::userId()) redirect('/');
        $setupToken   = self::getSetupToken();
        $prefillToken = trim($_GET['invite'] ?? '') ?: ($setupToken ?? '');
        render('auth/register', [
            'pageTitle'    => Lang::t('auth.register_title'),
            'error'        => Session::flash('error'),
            'prefillToken' => $prefillToken,
            'setupToken'   => $setupToken,
        ]);
    }

    /**
     * Return the system-generated invite token if no users exist yet, else null.
     * Used to surface the first-boot code directly in the UI.
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

    public function register(array $params): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $token    = trim($_POST['invite_token'] ?? '');

        if (!$token) {
            Session::flash('error', Lang::t('auth.errors.invite_required'));
            redirect('/register');
        }

        $invite = AppInvite::findValid($token);
        if (!$invite) {
            Session::flash('error', Lang::t('auth.errors.invite_invalid'));
            redirect('/register');
        }

        if (!$email) {
            Session::flash('error', Lang::t('auth.errors.email_required'));
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

        $userId = User::create($email, $password);
        AppInvite::markUsed($token, $userId);
        Session::login($userId);
        redirect('/');
    }

    public function logout(array $params): void
    {
        Session::logout();
        redirect('/login');
    }
}
