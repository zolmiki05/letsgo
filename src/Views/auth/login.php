<div class="auth-wrap">
    <div class="auth-brand"><?= e(Lang::t('app.name')) ?></div>
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= e(Lang::t('auth.login_title')) ?></h1>
            <p class="auth-subtitle"><?= e(Lang::t('auth.login_subtitle')) ?></p>
        </div>

        <?php if (!empty($setupToken)): ?>
        <div class="setup-banner">
            <div class="setup-banner-label"><?= e(Lang::t('auth.setup_first_boot')) ?></div>
            <div class="setup-banner-code"><?= e($setupToken) ?></div>
            <a href="/register?invite=<?= urlencode($setupToken) ?>" class="btn btn-primary btn-full" style="margin-top:.75rem">
                <?= e(Lang::t('auth.setup_register_now')) ?>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" class="form-stack">
            <?= csrfField() ?>
            <div class="field">
                <label for="identifier"><?= e(Lang::t('auth.identifier')) ?></label>
                <input type="text" id="identifier" name="identifier" required autocomplete="username" autofocus
                       placeholder="<?= e(Lang::t('auth.identifier_placeholder')) ?>">
            </div>
            <div class="field">
                <label for="password"><?= e(Lang::t('auth.password')) ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full"><?= e(Lang::t('auth.login_button')) ?></button>
        </form>

        <p class="auth-switch">
            <a href="/register"><?= e(Lang::t('auth.login_link')) ?></a>
            &nbsp;·&nbsp;
            <a href="/forgot-password"><?= e(Lang::t('auth.forgot_link')) ?></a>
        </p>
    </div>
</div>
