<div class="page-header">
    <a href="/admin/settings" class="back-link">← <?= e(Lang::t('admin.settings_title')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('admin.users_title')) ?></h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th><?= e(Lang::t('auth.username')) ?></th>
                <th><?= e(Lang::t('auth.email')) ?></th>
                <th><?= e(Lang::t('admin.user_status')) ?></th>
                <th><?= e(Lang::t('admin.user_registered')) ?></th>
                <th><?= e(Lang::t('admin.user_actions')) ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr class="<?= (int)$u['is_banned'] ? 'row-banned' : '' ?>">
                <td><?= (int)$u['id'] ?></td>
                <td><?= e($u['username'] ?? '—') ?></td>
                <td><?= e($u['email']) ?></td>
                <td>
                    <?php if ((int)$u['is_admin']): ?>
                        <span class="badge badge-admin"><?= e(Lang::t('admin.role_admin')) ?></span>
                    <?php endif; ?>
                    <?php if ((int)$u['is_banned']): ?>
                        <span class="badge badge-banned"><?= e(Lang::t('admin.status_banned')) ?></span>
                    <?php else: ?>
                        <span class="badge badge-available"><?= e(Lang::t('admin.status_active')) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e(fmtDate($u['created_at'] ?? '')) ?></td>
                <td class="action-cell">
                    <?php if ((int)$u['id'] !== $currentUserId): ?>
                        <?php if ((int)$u['is_banned']): ?>
                        <form method="POST" action="/admin/users/<?= (int)$u['id'] ?>/unban" class="inline-form">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-ghost"><?= e(Lang::t('admin.unban_button')) ?></button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="/admin/users/<?= (int)$u['id'] ?>/ban" class="inline-form">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-warning"><?= e(Lang::t('admin.ban_button')) ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" action="/admin/users/<?= (int)$u['id'] ?>/delete" class="inline-form"
                              onsubmit="return confirm('<?= e(Lang::t('admin.delete_user_confirm')) ?>')">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger"><?= e(Lang::t('admin.delete_button')) ?></button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted"><?= e(Lang::t('admin.current_user')) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
