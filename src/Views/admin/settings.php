<div class="page-header">
    <a href="/" class="back-link">← <?= e(Lang::t('nav.dashboard')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('admin.settings_title')) ?></h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1rem">
    <h2 class="card-title"><?= e(Lang::t('admin.settings_section_app')) ?></h2>
    <form method="POST" action="/admin/settings" class="form-stack">

        <label class="toggle-row">
            <div class="toggle-info">
                <span class="toggle-label"><?= e(Lang::t('admin.setting_registration_open')) ?></span>
                <span class="toggle-desc text-muted"><?= e(Lang::t('admin.setting_registration_open_desc')) ?></span>
            </div>
            <input type="checkbox" name="registration_open" value="1" class="toggle-checkbox"
                   <?= $registrationOpen ? 'checked' : '' ?>>
        </label>

        <label class="toggle-row">
            <div class="toggle-info">
                <span class="toggle-label"><?= e(Lang::t('admin.setting_users_can_create_groups')) ?></span>
                <span class="toggle-desc text-muted"><?= e(Lang::t('admin.setting_users_can_create_groups_desc')) ?></span>
            </div>
            <input type="checkbox" name="users_can_create_groups" value="1" class="toggle-checkbox"
                   <?= $usersCanCreateGroups ? 'checked' : '' ?>>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(Lang::t('admin.save_settings_button')) ?></button>
        </div>
    </form>
</div>

<div class="card" style="margin-bottom:1rem">
    <h2 class="card-title"><?= e(Lang::t('admin.users_title')) ?></h2>
    <p class="text-muted" style="margin-bottom:1rem"><?= e(Lang::t('admin.users_manage_desc')) ?></p>
    <a href="/admin/users" class="btn btn-primary"><?= e(Lang::t('admin.manage_users_button')) ?></a>
</div>

<div class="card">
    <h2 class="card-title"><?= e(Lang::t('admin.invites_title')) ?></h2>
    <p class="text-muted" style="margin-bottom:1rem"><?= e(Lang::t('admin.generate_desc')) ?></p>
    <a href="/admin/invites" class="btn btn-ghost"><?= e(Lang::t('admin.manage_invites_button')) ?></a>
</div>
