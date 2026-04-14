<div class="page-header">
    <a href="/" class="back-link">← <?= e(Lang::t('nav.dashboard')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('profile.change_password_title')) ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card" style="max-width:480px">
    <form method="POST" action="/profile/password" class="form-stack">
        <?= csrfField() ?>
        <div class="field">
            <label for="current_password"><?= e(Lang::t('profile.current_password')) ?></label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label for="new_password"><?= e(Lang::t('profile.new_password')) ?></label>
            <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="6">
        </div>
        <div class="field">
            <label for="confirm_password"><?= e(Lang::t('profile.confirm_password')) ?></label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="6">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(Lang::t('profile.save_button')) ?></button>
            <a href="/" class="btn btn-ghost"><?= e(Lang::t('event.cancel_button')) ?></a>
        </div>
    </form>
</div>
