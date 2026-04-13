<div class="page-header">
    <a href="/" class="back-link">← <?= e(Lang::t('nav.dashboard')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('admin.invites_title')) ?></h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1rem;">
    <h2 class="card-title"><?= e(Lang::t('admin.generate_title')) ?></h2>
    <p class="text-muted" style="margin-bottom: 1rem;"><?= e(Lang::t('admin.generate_desc')) ?></p>
    <form method="POST" action="/admin/invites/generate" class="form-stack">
        <div class="field">
            <label for="invite_email"><?= e(Lang::t('email.send_invite_to')) ?></label>
            <input type="email" id="invite_email" name="invite_email"
                   placeholder="pl. valaki@email.hu" maxlength="255">
        </div>
        <button type="submit" class="btn btn-primary"><?= e(Lang::t('admin.generate_button')) ?></button>
    </form>
</div>

<div class="card">
    <h2 class="card-title"><?= e(Lang::t('admin.all_codes')) ?></h2>

    <?php if (empty($invites)): ?>
    <p class="text-muted"><?= e(Lang::t('admin.no_codes')) ?></p>
    <?php else: ?>
    <ul class="invite-code-list">
        <?php foreach ($invites as $inv): ?>
        <li class="invite-code-item <?= $inv['used_by'] ? 'used' : 'active' ?>">
            <span class="invite-code-token"><?= e($inv['token']) ?></span>
            <div class="invite-code-meta">
                <?php if ($inv['used_by']): ?>
                <span class="badge badge-used"><?= e(Lang::t('admin.code_used')) ?></span>
                <span class="invite-code-user"><?= e($inv['used_by_email'] ?? '—') ?></span>
                <?php else: ?>
                <span class="badge badge-available"><?= e(Lang::t('admin.code_available')) ?></span>
                <?php endif; ?>
                <span class="invite-code-date"><?= e(fmtDate($inv['created_at'])) ?></span>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
