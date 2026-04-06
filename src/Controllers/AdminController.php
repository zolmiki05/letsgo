<?php
/**
 * AdminController – admin panel: invite codes, user management, app settings.
 */
class AdminController
{
    // -------------------------------------------------------------------------
    // Invite codes
    // -------------------------------------------------------------------------

    public function invites(array $params): void
    {
        $userId = requireAdmin();
        render('admin/invites', [
            'pageTitle' => Lang::t('admin.invites_title'),
            'invites'   => AppInvite::forAdmin($userId),
            'success'   => Session::flash('success'),
        ]);
    }

    public function generateInvite(array $params): void
    {
        $userId = requireAdmin();
        AppInvite::create($userId);
        Session::flash('success', Lang::t('admin.invite_generated'));
        redirect('/admin/invites');
    }

    // -------------------------------------------------------------------------
    // User management
    // -------------------------------------------------------------------------

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

    public function unbanUser(array $params): void
    {
        requireAdmin();
        $targetId = (int)$params['id'];
        User::unban($targetId);
        Session::flash('success', Lang::t('admin.user_unbanned'));
        redirect('/admin/users');
    }

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

    // -------------------------------------------------------------------------
    // App settings
    // -------------------------------------------------------------------------

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

    public function saveSettings(array $params): void
    {
        requireAdmin();
        Setting::set('registration_open',       isset($_POST['registration_open'])       ? '1' : '0');
        Setting::set('users_can_create_groups', isset($_POST['users_can_create_groups']) ? '1' : '0');
        Session::flash('success', Lang::t('admin.settings_saved'));
        redirect('/admin/settings');
    }
}
