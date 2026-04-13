<?php
/**
 * InviteController – group join flow via invite token.
 *
 * Route summary:
 *   GET  /join  → join()  Show the join confirmation page
 *   POST /join  → join()  Confirm and add user to group
 *
 * Both methods are handled by the same action; the HTTP method determines
 * whether to render the confirmation page (GET) or execute the join (POST).
 *
 * Token lifecycle:
 *   1. Group owner generates a token via GroupController::generateInvite().
 *   2. Owner shares the link: /join?token=<32-char-hex>
 *   3. Any authenticated user opens the link.
 *   4. GET: confirmation page shown (group name, expiry).
 *   5. POST: user added to group via Group::addMember() (INSERT IGNORE).
 *   6. Redirect to the group page.
 *
 * Unauthenticated users:
 *   requireAuth() saves /join?token=… to session['redirect_after_login'],
 *   redirects to /login, and after login the user lands back on the invite page.
 *   This prevents token loss when following a link while not logged in.
 *
 * Error cases handled:
 *   - Missing token              → error view
 *   - Token not found in DB      → error view
 *   - Token expired (> 24h old)  → error view with group name visible
 *   - User already a member      → confirmation page with "already member" notice
 */
class InviteController
{
    /**
     * Handle the group join flow (GET: show confirmation; POST: execute join).
     *
     * The token is read from GET or POST parameters to support both request
     * methods without code duplication.
     */
    public function join(array $params): void
    {
        // requireAuth() will save the current URL (including ?token=…) to session
        // if the user is not logged in, so they return here after login.
        $userId = requireAuth();

        // Read token from either GET (link click) or POST (form submission)
        $token = trim($_GET['token'] ?? $_POST['token'] ?? '');

        // ── Missing token ─────────────────────────────────────────────────────
        if (!$token) {
            render('invite/join', [
                'pageTitle'     => Lang::t('invite.title'),
                'group'         => null,
                'invite'        => null,
                'token'         => '',
                'error'         => Lang::t('invite.errors.invalid_token'),
                'alreadyMember' => false,
            ]);
            return;
        }

        $invite = Invite::findByToken($token);

        // ── Token not found ───────────────────────────────────────────────────
        if (!$invite) {
            render('invite/join', [
                'pageTitle'     => Lang::t('invite.title'),
                'group'         => null,
                'invite'        => null,
                'token'         => $token,
                'error'         => Lang::t('invite.errors.not_found'),
                'alreadyMember' => false,
            ]);
            return;
        }

        // ── Expired token ─────────────────────────────────────────────────────
        if (strtotime($invite['expires_at']) < time()) {
            render('invite/join', [
                'pageTitle'     => Lang::t('invite.title'),
                'group'         => Group::findById((int)$invite['group_id']),
                'invite'        => $invite,
                'token'         => $token,
                'error'         => Lang::t('invite.errors.expired_token'),
                'alreadyMember' => false,
            ]);
            return;
        }

        $groupId = (int)$invite['group_id'];
        $group   = Group::findById($groupId);

        // ── POST: execute the join ────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $alreadyMember = Group::isMember($groupId, $userId);
            Group::addMember($groupId, $userId); // INSERT IGNORE – idempotent

            // Notify existing members only if this is a genuine new join
            if (!$alreadyMember) {
                $newMember = User::findById($userId);
                $members   = Group::members($groupId);
                if ($newMember) Mailer::sendMemberJoined($group, $members, $newMember);
            }

            redirect('/groups/' . $groupId);
        }

        // ── GET: show the confirmation page ───────────────────────────────────
        render('invite/join', [
            'pageTitle'     => Lang::t('invite.title'),
            'group'         => $group,
            'invite'        => $invite,
            'token'         => $token,
            'error'         => null,
            'alreadyMember' => Group::isMember($groupId, $userId),
        ]);
    }
}
