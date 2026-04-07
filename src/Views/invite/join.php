<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= e(Lang::t('invite.title')) ?></h1>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <p class="auth-switch"><a href="/"><?= e(Lang::t('errors.back_home')) ?></a></p>

        <?php elseif ($alreadyMember && $group): ?>
        <div class="alert alert-success"><?= e(Lang::t('invite.already_member')) ?></div>
        <a href="/groups/<?= (int)$group['id'] ?>" class="btn btn-primary btn-full">
            <?= e(Lang::t('invite.go_to_group')) ?>
        </a>

        <?php elseif ($group): ?>
        <p class="invite-group-name">
            <?= e(Lang::t('invite.subtitle')) ?>:<br>
            <strong><?= e($group['name']) ?></strong>
        </p>
        <form method="POST" action="/join">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <button type="submit" class="btn btn-primary btn-full"><?= e(Lang::t('invite.join_button')) ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
