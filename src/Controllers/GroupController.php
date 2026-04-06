<?php
/**
 * GroupController – group creation, detail view, invite generation, and deletion.
 */
class GroupController
{
    public function show(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group) {
            http_response_code(404);
            render('errors/404', ['pageTitle' => '404']);
            return;
        }
        if (!Group::isMember($groupId, $userId)) {
            Session::flash('error', Lang::t('group.errors.not_member'));
            redirect('/');
        }

        render('group/show', [
            'pageTitle' => e($group['name']),
            'group'     => $group,
            'events'    => Event::forGroup($groupId),
            'members'   => Group::members($groupId),
            'invite'    => Invite::latestForGroup($groupId),
            'isAdmin'   => (int)$group['owner_id'] === $userId,
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    public function createForm(array $params): void
    {
        $userId = requireAuth();
        if (!Setting::usersCanCreateGroups() && !User::isAdmin($userId)) {
            Session::flash('error', Lang::t('group.errors.creation_disabled'));
            redirect('/');
        }
        render('group/create', [
            'pageTitle' => Lang::t('group.create_title'),
            'error'     => Session::flash('error'),
        ]);
    }

    public function create(array $params): void
    {
        $userId = requireAuth();
        if (!Setting::usersCanCreateGroups() && !User::isAdmin($userId)) {
            Session::flash('error', Lang::t('group.errors.creation_disabled'));
            redirect('/');
        }
        $name   = trim($_POST['name'] ?? '');

        if (!$name) {
            Session::flash('error', Lang::t('group.errors.name_required'));
            redirect('/groups/create');
        }

        $groupId = Group::create($name, $userId);
        redirect('/groups/' . $groupId);
    }

    public function delete(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group) {
            redirect('/');
        }
        if ((int)$group['owner_id'] !== $userId) {
            Session::flash('error', Lang::t('group.errors.not_admin'));
            redirect('/groups/' . $groupId);
        }

        Group::delete($groupId);
        redirect('/');
    }

    public function generateInvite(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group || (int)$group['owner_id'] !== $userId) {
            redirect('/groups/' . $groupId);
        }

        Invite::create($groupId, $userId);
        Session::flash('success', 'invite_generated');
        redirect('/groups/' . $groupId);
    }
}
