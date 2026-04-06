<?php
/**
 * InviteController – handles group join via invite token.
 * Both GET (show join page) and POST (confirm join) are handled here.
 */
class InviteController
{
    public function join(array $params): void
    {
        $userId = requireAuth();
        $token  = trim($_GET['token'] ?? $_POST['token'] ?? '');

        if (!$token) {
            render('invite/join', [
                'pageTitle' => Lang::t('invite.title'),
                'group'     => null,
                'invite'    => null,
                'token'     => '',
                'error'     => Lang::t('invite.errors.invalid_token'),
                'alreadyMember' => false,
            ]);
            return;
        }

        $invite = Invite::findByToken($token);

        if (!$invite) {
            render('invite/join', [
                'pageTitle' => Lang::t('invite.title'),
                'group'     => null,
                'invite'    => null,
                'token'     => $token,
                'error'     => Lang::t('invite.errors.not_found'),
                'alreadyMember' => false,
            ]);
            return;
        }

        if (strtotime($invite['expires_at']) < time()) {
            render('invite/join', [
                'pageTitle' => Lang::t('invite.title'),
                'group'     => Group::findById((int)$invite['group_id']),
                'invite'    => $invite,
                'token'     => $token,
                'error'     => Lang::t('invite.errors.expired_token'),
                'alreadyMember' => false,
            ]);
            return;
        }

        $groupId = (int)$invite['group_id'];
        $group   = Group::findById($groupId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Group::addMember($groupId, $userId);
            redirect('/groups/' . $groupId);
        }

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
