<?php
/**
 * AdminController – admin-only actions: app invite code management.
 */
class AdminController
{
    /** Display admin panel with invite code list and generator. */
    public function invites(array $params): void
    {
        $userId = requireAdmin();

        render('admin/invites', [
            'pageTitle' => Lang::t('admin.invites_title'),
            'invites'   => AppInvite::forAdmin($userId),
            'success'   => Session::flash('success'),
        ]);
    }

    /** Generate a new invite code and redirect back. */
    public function generateInvite(array $params): void
    {
        $userId = requireAdmin();
        AppInvite::create($userId);
        Session::flash('success', Lang::t('admin.invite_generated'));
        redirect('/admin/invites');
    }
}
