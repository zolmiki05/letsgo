<?php $baseUrl = appBaseUrl(); ?>

<div class="page-header">
    <a href="/" class="back-link">← <?= e(Lang::t('nav.dashboard')) ?></a>
    <h1 class="page-title"><?= e($group['name']) ?></h1>
    <?php if ($isAdmin): ?>
    <span class="badge badge-admin"><?= e(Lang::t('group.admin_label')) ?></span>
    <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><?= e(Lang::t('group.invite_title')) ?> — <?= e(Lang::t('group.invite_active')) ?></div>
<?php endif; ?>

<div class="group-layout">

    <!-- Events column -->
    <section class="group-events">
        <div class="section-header">
            <h2 class="section-title"><?= e(Lang::t('group.events_title')) ?></h2>
            <a href="/groups/<?= (int)$group['id'] ?>/events/create" class="btn btn-primary btn-sm">
                <?= e(Lang::t('group.new_event')) ?>
            </a>
        </div>

        <?php if (empty($events)): ?>
        <div class="empty-state empty-state-sm">
            <p><?= e(Lang::t('group.no_events')) ?></p>
        </div>
        <?php else: ?>
        <div class="event-list">
            <?php foreach ($events as $ev): ?>
            <a href="/events/<?= (int)$ev['id'] ?>" class="event-card status-<?= strtolower(e($ev['status'])) ?>">
                <div class="event-card-top">
                    <span class="event-title"><?= e($ev['title']) ?></span>
                    <span class="status-badge status-<?= strtolower(e($ev['status'])) ?>">
                        <?= e(Lang::t('event.status.' . $ev['status'])) ?>
                    </span>
                </div>
                <div class="event-card-meta">
                    <?php if ($ev['date_text']): ?>
                    <span><?= e($ev['date_text']) ?></span>
                    <?php endif; ?>
                    <?php if ($ev['location']): ?>
                    <span>· <?= e($ev['location']) ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Sidebar -->
    <aside class="group-sidebar">

        <!-- Members -->
        <div class="card">
            <h3 class="card-title"><?= e(Lang::t('group.members_title')) ?></h3>
            <ul class="member-list">
                <?php foreach ($members as $m): ?>
                <li class="member-item">
                    <span class="member-email"><?= e($m['email']) ?></span>
                    <?php if ((int)$m['id'] === (int)$group['owner_id']): ?>
                    <span class="badge badge-admin"><?= e(Lang::t('group.admin_label')) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Invite link -->
        <div class="card">
            <h3 class="card-title"><?= e(Lang::t('group.invite_title')) ?></h3>
            <?php if ($invite): ?>
            <?php $inviteUrl = $baseUrl . '/join?token=' . urlencode($invite['token']); ?>
            <div class="invite-box">
                <input type="text" class="invite-input" id="inviteUrl" value="<?= e($inviteUrl) ?>" readonly>
                <button class="btn btn-sm btn-outline" onclick="copyInvite()" id="copyBtn">
                    <?= e(Lang::t('group.invite_copy')) ?>
                </button>
            </div>
            <p class="invite-expires">
                <?= e(Lang::t('group.invite_expires')) ?>
                <?= e(fmtDate($invite['expires_at'])) ?>
            </p>
            <?php endif; ?>
            <form method="POST" action="/groups/<?= (int)$group['id'] ?>/invite">
                <button type="submit" class="btn btn-outline btn-sm btn-full">
                    <?= e(Lang::t('group.invite_generate')) ?>
                </button>
            </form>
        </div>

        <!-- Danger zone -->
        <div class="card card-danger">
            <form method="POST" action="/groups/<?= (int)$group['id'] ?>/delete"
                  onsubmit="return confirm('<?= e(Lang::t('group.delete_confirm')) ?>')">
                <button type="submit" class="btn btn-danger btn-full">
                    <?= e(Lang::t('group.delete_button')) ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

    </aside>
</div>

<script>
function copyInvite() {
    const input = document.getElementById('inviteUrl');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.textContent = '<?= e(Lang::t('group.invite_copied')) ?>';
        setTimeout(() => { btn.textContent = '<?= e(Lang::t('group.invite_copy')) ?>'; }, 2000);
    });
}
</script>
