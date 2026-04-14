<div class="page-header">
    <h1 class="page-title"><?= e(Lang::t('profile.notifications_title')) ?></h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/profile/notifications" class="form-stack">
        <?= csrfField() ?>

        <p class="text-muted" style="margin-bottom:1rem;"><?= e(Lang::t('profile.notifications_hint')) ?></p>

        <?php foreach ($types as $type): ?>
        <label class="checkbox-row">
            <input type="checkbox"
                   name="enabled_types[]"
                   value="<?= e($type) ?>"
                   <?= !in_array($type, $disabled, true) ? 'checked' : '' ?>>
            <?= e(Lang::t('profile.notification_type.' . $type)) ?>
        </label>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary"><?= e(Lang::t('profile.notifications_save')) ?></button>
    </form>
</div>
