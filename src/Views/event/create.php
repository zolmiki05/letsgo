<div class="page-header">
    <a href="/groups/<?= (int)$group['id'] ?>" class="back-link"><?= e(Lang::t('event.back_to_group')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('event.create_title')) ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="/groups/<?= (int)$group['id'] ?>/events/create" class="form-stack">

        <div class="field">
            <label for="title"><?= e(Lang::t('event.field_title')) ?></label>
            <input type="text" id="title" name="title" required autofocus maxlength="255">
        </div>

        <div class="field">
            <label for="description">
                <?= e(Lang::t('event.field_description')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <textarea id="description" name="description" rows="3"></textarea>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="location">
                    <?= e(Lang::t('event.field_location')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <input type="text" id="location" name="location" maxlength="255">
            </div>
            <div class="field">
                <label for="date_text">
                    <?= e(Lang::t('event.field_date')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <input type="text" id="date_text" name="date_text" maxlength="255" placeholder="pl. 2025. jún. 14.">
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="deadline_signup">
                    <?= e(Lang::t('event.field_deadline_signup')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <input type="date" id="deadline_signup" name="deadline_signup">
            </div>
            <div class="field">
                <label for="deadline_decision">
                    <?= e(Lang::t('event.field_deadline_decision')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <input type="date" id="deadline_decision" name="deadline_decision">
            </div>
        </div>

        <div class="field">
            <label for="cost">
                <?= e(Lang::t('event.field_cost')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <input type="text" id="cost" name="cost" maxlength="100" placeholder="pl. ~5 000 Ft / fő">
        </div>

        <div class="field">
            <label for="notes">
                <?= e(Lang::t('event.field_notes')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(Lang::t('event.save_button')) ?></button>
            <a href="/groups/<?= (int)$group['id'] ?>" class="btn btn-ghost"><?= e(Lang::t('event.cancel_button')) ?></a>
        </div>
    </form>
</div>
