<div class="auth-wrap">
    <div class="auth-brand"><?= e(Lang::t('app.name')) ?></div>
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= e(Lang::t('auth.register_title')) ?></h1>
            <p class="auth-subtitle"><?= e(Lang::t('auth.register_subtitle')) ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" class="form-stack">
            <div class="field">
                <label for="invite_token"><?= e(Lang::t('auth.invite_code')) ?></label>
                <input type="text" id="invite_token" name="invite_token"
                       required autofocus autocomplete="off"
                       value="<?= e($prefillToken) ?>"
                       placeholder="pl. A1B2C3D4E5F6"
                       style="font-family: monospace; letter-spacing: .08em; text-transform: uppercase;">
            </div>
            <div class="field">
                <label for="email"><?= e(Lang::t('auth.email')) ?></label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="field">
                <label for="password"><?= e(Lang::t('auth.password')) ?></label>
                <input type="password" id="password" name="password" required autocomplete="new-password" minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-full"><?= e(Lang::t('auth.register_button')) ?></button>
        </form>

        <p class="auth-switch">
            <a href="/login"><?= e(Lang::t('auth.register_link')) ?></a>
        </p>
    </div>
</div>
