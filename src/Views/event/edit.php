<div class="page-header">
    <a href="/events/<?= (int)$event['id'] ?>" class="back-link"><?= e(Lang::t('event.back_to_event')) ?></a>
    <h1 class="page-title"><?= e(Lang::t('event.edit_title')) ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php
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

        <div class="field">
            <label for="location">
                <?= e(Lang::t('event.field_location')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <input type="text" id="location" name="location" maxlength="255"
                   value="<?= e($event['location'] ?? '') ?>">
        </div>

        <!-- Proposed time slots -->
        <div class="field">
            <label>
                <?= e(Lang::t('event.field_time_slots')) ?>
                <span class="field-optional"><?= e(Lang::t('event.optional')) ?></span>
            </label>
            <div id="slots-container">
                <?php foreach ($slots as $i => $slot):
                    $mode = $slot['slot_date'] ? 'date' : 'text';
                ?>
                <div class="slot-row" data-slot-index="<?= $i ?>">
                    <div class="date-mode-toggle">
                        <label class="date-mode-label">
                            <input type="radio" name="slot_mode[<?= $i ?>]" value="date"
                                   onchange="toggleSlotMode(<?= $i ?>)"
                                   <?= $mode === 'date' ? 'checked' : '' ?>>
                            <?= e(Lang::t('event.date_mode_picker')) ?>
                        </label>
                        <label class="date-mode-label">
                            <input type="radio" name="slot_mode[<?= $i ?>]" value="text"
                                   onchange="toggleSlotMode(<?= $i ?>)"
                                   <?= $mode === 'text' ? 'checked' : '' ?>>
                            <?= e(Lang::t('event.date_mode_text')) ?>
                        </label>
                    </div>
                    <input type="date" name="slot_date[<?= $i ?>]"
                           value="<?= e($slot['slot_date'] ?? '') ?>"
                           <?= $mode === 'text' ? 'style="display:none"' : '' ?>>
                    <input type="text" name="slot_text[<?= $i ?>]"
                           value="<?= e($slot['slot_text'] ?? '') ?>"
                           maxlength="255" placeholder="pl. jövő hétvégén, TBD"
                           <?= $mode === 'date' ? 'style="display:none"' : '' ?>>
                    <button type="button" class="btn btn-ghost btn-sm slot-remove-btn"
                            onclick="removeSlot(this)">
                        <?= e(Lang::t('event.remove_time_slot')) ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-ghost btn-sm slot-add-btn" onclick="addSlot()">
                <?= e(Lang::t('event.add_time_slot')) ?>
            </button>
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
// ── Deadline date-mode toggle (unchanged) ─────────────────────────────────
function toggleDateMode(group) {
    const modes = {
        dl_signup:  { picker: '.dl-signup-picker', text: '.dl-signup-text', radio: 'deadline_signup_mode' },
        dl_decision:{ picker: '.dl-decision-picker', text: '.dl-decision-text', radio: 'deadline_decision_mode' },
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

// ── Proposed time slots ───────────────────────────────────────────────────
// Start counter after last server-rendered slot index
let slotCounter = <?= max(-1, count($slots) - 1) ?>;

function createSlotRow(idx, mode, dateVal, textVal) {
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.dataset.slotIndex = idx;

    const modeDate = (mode === 'date');
    row.innerHTML =
        '<div class="date-mode-toggle">' +
            '<label class="date-mode-label">' +
                '<input type="radio" name="slot_mode[' + idx + ']" value="date"' + (modeDate ? ' checked' : '') +
                ' onchange="toggleSlotMode(' + idx + ')">' +
                <?= json_encode(Lang::t('event.date_mode_picker')) ?> +
            '</label>' +
            '<label class="date-mode-label">' +
                '<input type="radio" name="slot_mode[' + idx + ']" value="text"' + (!modeDate ? ' checked' : '') +
                ' onchange="toggleSlotMode(' + idx + ')">' +
                <?= json_encode(Lang::t('event.date_mode_text')) ?> +
            '</label>' +
        '</div>' +
        '<input type="date" name="slot_date[' + idx + ']" value="' + escAttr(dateVal) + '"' +
               (!modeDate ? ' style="display:none"' : '') + '>' +
        '<input type="text" name="slot_text[' + idx + ']" value="' + escAttr(textVal) + '"' +
               ' maxlength="255" placeholder="pl. jövő hétvégén, TBD"' +
               (modeDate ? ' style="display:none"' : '') + '>' +
        '<button type="button" class="btn btn-ghost btn-sm slot-remove-btn" onclick="removeSlot(this)">' +
            <?= json_encode(Lang::t('event.remove_time_slot')) ?> +
        '</button>';

    return row;
}

function addSlot() {
    slotCounter++;
    const row = createSlotRow(slotCounter, 'date', '', '');
    document.getElementById('slots-container').appendChild(row);
}

function removeSlot(btn) {
    btn.closest('.slot-row').remove();
}

function toggleSlotMode(idx) {
    const checked = document.querySelector('input[name="slot_mode[' + idx + ']"]:checked');
    if (!checked) return;
    const mode   = checked.value;
    const picker = document.querySelector('input[name="slot_date[' + idx + ']"]');
    const text   = document.querySelector('input[name="slot_text[' + idx + ']"]');
    picker.style.display = mode === 'date' ? '' : 'none';
    text.style.display   = mode === 'text' ? '' : 'none';
    if (mode === 'date') { text.value   = ''; }
    else                 { picker.value = ''; }
}

function escAttr(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
}
</script>
