<div class="auth-wrap">
    <div class="auth-brand"><?= e(Lang::t('app.name')) ?></div>
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= e(Lang::t('auth.reset_title')) ?></h1>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/reset-password" class="form-stack">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="field">
                <label for="new_password"><?= e(Lang::t('auth.reset_new_password')) ?></label>
                <input type="password" id="new_password" name="new_password"
                       required autofocus autocomplete="new-password">
            </div>
            <div class="field">
                <label for="confirm_password"><?= e(Lang::t('auth.reset_confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password"
                       required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <?= e(Lang::t('auth.reset_submit')) ?>
            </button>
        </form>
    </div>
</div>
