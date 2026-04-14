<?php
/**
 * GroupController – group creation, detail view, invite generation, and deletion.
 *
 * Route summary:
 *   GET  /groups/create           → createForm()      Show the create-group form
 *   POST /groups/create           → create()          Process group creation
 *   GET  /groups/{id}             → show()            Group detail page
 *   POST /groups/{id}/rename      → rename()          Rename group (owner only)
 *   POST /groups/{id}/delete      → delete()          Delete group (owner only)
 *   POST /groups/{id}/invite      → generateInvite()  Generate a 24h join link (owner only)
 *
 * Access rules:
 *   - Any logged-in user can create groups, unless the admin has disabled
 *     group creation for non-admins (Setting 'users_can_create_groups').
 *   - The group detail page is accessible only to members.
 *   - Rename, delete and invite-generation are restricted to the group owner (owner_id).
 */
class GroupController
{
    // ── Group detail ──────────────────────────────────────────────────────────

    /**
     * Display the group detail page.
     *
     * Shows:
     *   - Event list (all events in the group, status badges)
     *   - Member sidebar (member list with owner badge)
     *   - Invite link management (owner only, 24h token with copy button)
     *   - Danger zone with delete button (owner only)
     *
     * Returns 404 if the group doesn't exist; redirects to / with an error if
     * the current user is not a member.
     */
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
            'invite'    => Invite::latestForGroup($groupId), // null if no active invite
            'isAdmin'   => (int)$group['owner_id'] === $userId,
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    // ── Group creation ────────────────────────────────────────────────────────

    /**
     * Display the group creation form.
     *
     * Checks the 'users_can_create_groups' setting; non-admin users are
     * redirected to / with an error if group creation is disabled.
     */
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

    /**
     * Process the group creation form submission.
     *
     * Validates name is non-empty.
     * Enforces the 'users_can_create_groups' setting (checked again to prevent
     * direct POST bypasses).
     * Redirects to the new group's detail page on success.
     */
    public function create(array $params): void
    {
        $userId = requireAuth();
        if (!Setting::usersCanCreateGroups() && !User::isAdmin($userId)) {
            Session::flash('error', Lang::t('group.errors.creation_disabled'));
            redirect('/');
        }

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            Session::flash('error', Lang::t('group.errors.name_required'));
            redirect('/groups/create');
        }

        $groupId = Group::create($name, $userId);
        redirect('/groups/' . $groupId);
    }

    // ── Group rename ─────────────────────────────────────────────────────────

    /**
     * Rename a group (owner only).
     * Validates the new name is non-empty, then updates and redirects back.
     */
    public function rename(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group) redirect('/');

        if ((int)$group['owner_id'] !== $userId) {
            Session::flash('error', Lang::t('group.errors.not_admin'));
            redirect('/groups/' . $groupId);
        }

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            Session::flash('error', Lang::t('group.errors.name_required'));
            redirect('/groups/' . $groupId);
        }

        Group::rename($groupId, $name);
        Session::flash('success', Lang::t('group.renamed'));
        redirect('/groups/' . $groupId);
    }

    // ── Group deletion ────────────────────────────────────────────────────────

    /**
     * Delete a group (owner only).
     *
     * All related data is removed via FK ON DELETE CASCADE:
     * group_members, invites, events (and their responses).
     * Redirects to the dashboard after deletion.
     */
    public function delete(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group) redirect('/');

        if ((int)$group['owner_id'] !== $userId) {
            Session::flash('error', Lang::t('group.errors.not_admin'));
            redirect('/groups/' . $groupId);
        }

        Group::delete($groupId);
        redirect('/');
    }

    // ── Archive / Unarchive ───────────────────────────────────────────────────

    /**
     * Archive a group (owner only).
     * Archived groups are hidden from the main dashboard but can still be accessed directly.
     */
    public function archive(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group || (int)$group['owner_id'] !== $userId) {
            Session::flash('error', Lang::t('group.errors.not_admin'));
            redirect('/groups/' . $groupId);
        }

        Group::archive($groupId);
        Session::flash('success', Lang::t('group.archived'));
        redirect('/');
    }

    /**
     * Unarchive a group (owner only).
     * Moves the group back to the active dashboard.
     */
    public function unarchive(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group || (int)$group['owner_id'] !== $userId) {
            Session::flash('error', Lang::t('group.errors.not_admin'));
            redirect('/');
        }

        Group::unarchive($groupId);
        Session::flash('success', Lang::t('group.unarchived'));
        redirect('/groups/' . $groupId);
    }

    // ── Invite link generation ────────────────────────────────────────────────

    /**
     * Generate a new 24-hour group invite token (owner only).
     *
     * A new token is always created; the previous token (if any) remains valid
     * until its own expiry. Only the latest active token is shown in the UI.
     * Redirects back to the group page with a success flash.
     */
    public function generateInvite(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group || (int)$group['owner_id'] !== $userId) {
            redirect('/groups/' . $groupId);
        }

        Invite::create($groupId, $userId);
        Session::flash('success', Lang::t('group.invite_title') . ' — ' . Lang::t('group.invite_active'));
        redirect('/groups/' . $groupId);
    }
}
