<div class="page-header">
    <a href="/" class="back-link">← <?= e(Lang::t('nav.dashboard')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('group.create_title')) ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/groups/create" class="form-stack">
        <div class="field">
            <label for="name"><?= e(Lang::t('group.name_label')) ?></label>
            <input type="text" id="name" name="name" required autofocus maxlength="255">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(Lang::t('group.create_button')) ?></button>
            <a href="/" class="btn btn-ghost"><?= e(Lang::t('event.cancel_button')) ?></a>
        </div>
    </form>
</div>
