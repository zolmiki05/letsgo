<div class="page-header">
    <a href="/events/<?= (int)$event['id'] ?>" class="back-link"><?= e(Lang::t('event.back_to_event')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('event.edit_title')) ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php
// Determine current date modes based on which column has a value
$dateMode    = $event['event_date']        ? 'date' : 'text';
$dlSignMode  = $event['deadline_signup']   ? 'date' : 'text';
$dlDecMode   = $event['deadline_decision'] ? 'date' : 'text';
?>

<div class="card">
    <form method="POST" action="/events/<?= (int)$event['id'] ?>/edit" class="form-stack">

        <div class="field">
            <label for="title"><?= e(Lang::t('event.field_title')) ?></label>
            <input type="text" id="title" name="title" required autofocus maxlength="255"
                   value="<?= e($event['title']) ?>">
        </div>

        <div class="field">
            <label for="description">
                <?= e(Lang::t('event.field_description')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <textarea id="description" name="description" rows="3"><?= e($event['description'] ?? '') ?></textarea>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="location">
                    <?= e(Lang::t('event.field_location')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <input type="text" id="location" name="location" maxlength="255"
                       value="<?= e($event['location'] ?? '') ?>">
            </div>

            <!-- Event date -->
            <div class="field">
                <label>
                    <?= e(Lang::t('event.field_date')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <div class="date-mode-toggle">
                    <label class="date-mode-label">
                        <input type="radio" name="date_mode" value="date"
                               onchange="toggleDateMode('date_field')"
                               <?= $dateMode === 'date' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_picker')) ?>
                    </label>
                    <label class="date-mode-label">
                        <input type="radio" name="date_mode" value="text"
                               onchange="toggleDateMode('date_field')"
                               <?= $dateMode === 'text' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_text')) ?>
                    </label>
                </div>
                <input type="date" id="event_date" name="event_date" class="date-input-picker"
                       value="<?= e($event['event_date'] ?? '') ?>"
                       <?= $dateMode === 'text' ? 'style="display:none"' : '' ?>>
                <input type="text" id="date_text" name="date_text" maxlength="255"
                       placeholder="pl. jövő hétvégén, TBD"
                       class="date-input-text"
                       value="<?= e($event['date_text'] ?? '') ?>"
                       <?= $dateMode === 'date' ? 'style="display:none"' : '' ?>>
            </div>
        </div>

        <!-- Deadline signup -->
        <div class="field-row">
            <div class="field">
                <label>
                    <?= e(Lang::t('event.field_deadline_signup')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <div class="date-mode-toggle">
                    <label class="date-mode-label">
                        <input type="radio" name="deadline_signup_mode" value="date"
                               onchange="toggleDateMode('dl_signup')"
                               <?= $dlSignMode === 'date' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_picker')) ?>
                    </label>
                    <label class="date-mode-label">
                        <input type="radio" name="deadline_signup_mode" value="text"
                               onchange="toggleDateMode('dl_signup')"
                               <?= $dlSignMode === 'text' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_text')) ?>
                    </label>
                </div>
                <input type="date" id="deadline_signup" name="deadline_signup" class="dl-signup-picker"
                       value="<?= e($event['deadline_signup'] ?? '') ?>"
                       <?= $dlSignMode === 'text' ? 'style="display:none"' : '' ?>>
                <input type="text" id="deadline_signup_text" name="deadline_signup_text"
                       maxlength="100" placeholder="pl. következő hét"
                       class="dl-signup-text"
                       value="<?= e($event['deadline_signup_text'] ?? '') ?>"
                       <?= $dlSignMode === 'date' ? 'style="display:none"' : '' ?>>
            </div>

            <!-- Deadline decision -->
            <div class="field">
                <label>
                    <?= e(Lang::t('event.field_deadline_decision')) ?>
                    <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
                </label>
                <div class="date-mode-toggle">
                    <label class="date-mode-label">
                        <input type="radio" name="deadline_decision_mode" value="date"
                               onchange="toggleDateMode('dl_decision')"
                               <?= $dlDecMode === 'date' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_picker')) ?>
                    </label>
                    <label class="date-mode-label">
                        <input type="radio" name="deadline_decision_mode" value="text"
                               onchange="toggleDateMode('dl_decision')"
                               <?= $dlDecMode === 'text' ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.date_mode_text')) ?>
                    </label>
                </div>
                <input type="date" id="deadline_decision" name="deadline_decision" class="dl-decision-picker"
                       value="<?= e($event['deadline_decision'] ?? '') ?>"
                       <?= $dlDecMode === 'text' ? 'style="display:none"' : '' ?>>
                <input type="text" id="deadline_decision_text" name="deadline_decision_text"
                       maxlength="100" placeholder="pl. péntekig"
                       class="dl-decision-text"
                       value="<?= e($event['deadline_decision_text'] ?? '') ?>"
                       <?= $dlDecMode === 'date' ? 'style="display:none"' : '' ?>>
            </div>
        </div>

        <div class="field">
            <label for="cost">
                <?= e(Lang::t('event.field_cost')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <input type="text" id="cost" name="cost" maxlength="100" placeholder="pl. ~5 000 Ft / fő"
                   value="<?= e($event['cost'] ?? '') ?>">
        </div>

        <div class="field">
            <label for="notes">
                <?= e(Lang::t('event.field_notes')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <textarea id="notes" name="notes" rows="3"><?= e($event['notes'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e(Lang::t('event.save_button')) ?></button>
            <a href="/events/<?= (int)$event['id'] ?>" class="btn btn-ghost"><?= e(Lang::t('event.cancel_button')) ?></a>
        </div>
    </form>
</div>

<script>
function toggleDateMode(group) {
    const modes = {
        date_field: { picker: '.date-input-picker',   text: '.date-input-text',   radio: 'date_mode' },
        dl_signup:  { picker: '.dl-signup-picker',    text: '.dl-signup-text',    radio: 'deadline_signup_mode' },
        dl_decision:{ picker: '.dl-decision-picker',  text: '.dl-decision-text',  radio: 'deadline_decision_mode' },
    };
    const cfg    = modes[group];
    const picker = document.querySelector(cfg.picker);
    const text   = document.querySelector(cfg.text);
    const val    = document.querySelector('input[name="' + cfg.radio + '"]:checked').value;
    picker.style.display = val === 'date' ? '' : 'none';
    text.style.display   = val === 'text' ? '' : 'none';
    if (val === 'date') { text.value   = ''; }
    else                { picker.value = ''; }
}
</script>
