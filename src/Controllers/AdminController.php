<?php
/**
 * AdminController – admin-only management panel.
 *
 * All methods call requireAdmin() which verifies:
 *   1. The user is authenticated (redirects to /login otherwise).
 *   2. The user has is_admin = 1 (redirects to / otherwise).
 *
 * Route summary:
 *   GET  /admin/settings           → settings()        App settings overview page
 *   POST /admin/settings           → saveSettings()    Persist setting toggles
 *   GET  /admin/invites            → invites()         Invite code management
 *   POST /admin/invites/generate   → generateInvite()  Create a new invite code
 *   GET  /admin/users              → users()           User list with ban/delete controls
 *   POST /admin/users/{id}/ban     → banUser()         Set is_banned = 1
 *   POST /admin/users/{id}/unban   → unbanUser()       Set is_banned = 0
 *   POST /admin/users/{id}/delete  → deleteUser()      Hard-delete user and cascade
 *
 * Safety guards:
 *   - Admins cannot ban or delete their own account.
 *   - banUser/deleteUser compare target ID against the current session user ID.
 */
class AdminController
{
    // ── App settings ──────────────────────────────────────────────────────────

    /**
     * Display the admin settings overview page.
     * Shows toggles for registration_open and users_can_create_groups,
     * plus links to the invite-code and user-management sub-pages.
     */
    public function settings(array $params): void
    {
        requireAdmin();
        render('admin/settings', [
            'pageTitle'            => Lang::t('admin.settings_title'),
            'registrationOpen'     => Setting::isRegistrationOpen(),
            'usersCanCreateGroups' => Setting::usersCanCreateGroups(),
            'success'              => Session::flash('success'),
        ]);
    }

    /**
     * Persist the submitted settings toggles.
     *
     * HTML checkboxes are absent from the POST body when unchecked, so the
     * logic is: isset() → '1', not set → '0'.
     * Redirects back with a success flash message.
     */
    public function saveSettings(array $params): void
    {
        requireAdmin();
        // Checkbox: present = enabled ('1'), absent = disabled ('0')
        Setting::set('registration_open',       isset($_POST['registration_open'])       ? '1' : '0');
        Setting::set('users_can_create_groups', isset($_POST['users_can_create_groups']) ? '1' : '0');
        Session::flash('success', Lang::t('admin.settings_saved'));
        redirect('/admin/settings');
    }

    // ── Invite codes ──────────────────────────────────────────────────────────

    /**
     * Display the invite-code management page.
     * Lists all codes created by this admin (and system-generated codes).
     */
    public function invites(array $params): void
    {
        $userId = requireAdmin();
        render('admin/invites', [
            'pageTitle' => Lang::t('admin.invites_title'),
            'invites'   => AppInvite::forAdmin($userId),
            'success'   => Session::flash('success'),
        ]);
    }

    /**
     * Generate a new single-use registration invite code.
     * Redirects back to the invite list with a success flash.
     */
    public function generateInvite(array $params): void
    {
        $userId = requireAdmin();
        $token  = AppInvite::create($userId);

        // Optionally email the code to a specified address
        $emailTo = trim($_POST['invite_email'] ?? '');
        if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            Mailer::sendInvite($emailTo, $token);
        }

        Session::flash('success', Lang::t('admin.invite_generated'));
        redirect('/admin/invites');
    }

    // ── User management ───────────────────────────────────────────────────────

    /**
     * Display the user list with ban/unban/delete controls.
     * Passes the current admin's ID so the view can hide self-action buttons.
     */
    public function users(array $params): void
    {
        $currentUserId = requireAdmin();
        render('admin/users', [
            'pageTitle'     => Lang::t('admin.users_title'),
            'users'         => User::all(),
            'currentUserId' => $currentUserId,
            'success'       => Session::flash('success'),
            'error'         => Session::flash('error'),
        ]);
    }

    /**
     * Ban a user (set is_banned = 1), preventing future logins.
     * Self-banning is blocked with an error flash.
     */
    public function banUser(array $params): void
    {
        $currentUserId = requireAdmin();
        $targetId      = (int)$params['id'];

        if ($targetId === $currentUserId) {
            Session::flash('error', Lang::t('admin.errors.cannot_self'));
            redirect('/admin/users');
        }

        User::ban($targetId);
        Session::flash('success', Lang::t('admin.user_banned'));
        redirect('/admin/users');
    }

    /**
     * Unban a user (set is_banned = 0), restoring their ability to log in.
     */
    public function unbanUser(array $params): void
    {
        requireAdmin();
        $targetId = (int)$params['id'];
        User::unban($targetId);
        Session::flash('success', Lang::t('admin.user_unbanned'));
        redirect('/admin/users');
    }

    /**
     * Permanently delete a user and all their data (cascade via FK).
     *
     * Cascade-deleted: owned groups → events, group_members, invites, responses.
     * Self-deletion is blocked with an error flash.
     */
    public function deleteUser(array $params): void
    {
        $currentUserId = requireAdmin();
        $targetId      = (int)$params['id'];

        if ($targetId === $currentUserId) {
            Session::flash('error', Lang::t('admin.errors.cannot_self'));
            redirect('/admin/users');
        }

        User::delete($targetId);
        Session::flash('success', Lang::t('admin.user_deleted'));
        redirect('/admin/users');
    }
}
