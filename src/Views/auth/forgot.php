<div class="auth-wrap">
    <div class="auth-brand"><?= e(Lang::t('app.name')) ?></div>
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title"><?= e(Lang::t('auth.forgot_title')) ?></h1>
            <p class="auth-subtitle"><?= e(Lang::t('auth.forgot_subtitle')) ?></p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="/forgot-password" class="form-stack">
            <?= csrfField() ?>
            <div class="field">
                <label for="email"><?= e(Lang::t('auth.email')) ?></label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="email@cím.hu">
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <?= e(Lang::t('auth.forgot_submit')) ?>
            </button>
        </form>
        <?php endif; ?>

        <p class="auth-switch">
            <a href="/login"><?= e(Lang::t('auth.forgot_back_login')) ?></a>
        </p>
    </div>
</div>
