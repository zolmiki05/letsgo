<div class="page-header">
    <a href="/groups/<?= (int)$event['group_id'] ?>" class="back-link"><?= e(Lang::t('event.back_to_group')) ?></a>
    <div class="page-header-title-row">
        <h1 class="page-title"><?= e($event['title']) ?></h1>
        <span class="status-badge status-<?= strtolower(e($event['status'])) ?>">
            <?= e(Lang::t('event.status.' . $event['status'])) ?>
        </span>
    </div>
    <div class="event-meta-line">
        <?= e(Lang::t('event.created_by')) ?>:
        <strong><?= $creator ? e($creator['email']) : '—' ?></strong>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="event-layout">

    <!-- Details -->
    <div class="event-main">
        <div class="card">
            <h2 class="card-title"><?= e(Lang::t('event.details')) ?></h2>
            <dl class="detail-list">
                <?php if ($event['description']): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_description')) ?></dt>
                    <dd><?= nl2br(e($event['description'])) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($event['location']): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_location')) ?></dt>
                    <dd><?= e($event['location']) ?></dd>
                </div>
                <?php endif; ?>
                <?php
                // Proposed time slots (new system); fall back to legacy event_date/date_text
                $displaySlots = $slots;
                if (empty($displaySlots) && ($event['event_date'] || $event['date_text'])) {
                    $displaySlots = [['slot_date' => $event['event_date'], 'slot_text' => $event['date_text']]];
                }
                ?>
                <?php if (!empty($displaySlots)): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_time_slots')) ?></dt>
                    <dd>
                        <?php if (count($displaySlots) === 1): ?>
                            <?php $s = $displaySlots[0]; ?>
                            <?= $s['slot_date'] ? e(fmtDate($s['slot_date'])) : e($s['slot_text']) ?>
                        <?php else: ?>
                            <ul class="slot-list">
                            <?php foreach ($displaySlots as $s): ?>
                                <li><?= $s['slot_date'] ? e(fmtDate($s['slot_date'])) : e($s['slot_text']) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php endif; ?>
                <?php if ($event['deadline_signup'] || ($event['deadline_signup_text'] ?? '')): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_deadline_signup')) ?></dt>
                    <dd><?= $event['deadline_signup'] ? e(fmtDate($event['deadline_signup'])) : e($event['deadline_signup_text']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($event['deadline_decision'] || ($event['deadline_decision_text'] ?? '')): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_deadline_decision')) ?></dt>
                    <dd><?= $event['deadline_decision'] ? e(fmtDate($event['deadline_decision'])) : e($event['deadline_decision_text']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($event['cost']): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_cost')) ?></dt>
                    <dd><?= e($event['cost']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($event['notes']): ?>
                <div class="detail-row">
                    <dt><?= e(Lang::t('event.field_notes')) ?></dt>
                    <dd><?= nl2br(e($event['notes'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Creator actions -->
        <?php if ($isCreator): ?>
        <div class="card">
            <a href="/events/<?= (int)$event['id'] ?>/edit" class="btn btn-outline btn-full">
                <?= e(Lang::t('event.edit_button')) ?>
            </a>
        </div>
        <div class="card">
            <h2 class="card-title"><?= e(Lang::t('event.update_status')) ?></h2>
            <form method="POST" action="/events/<?= (int)$event['id'] ?>/status" class="status-form">
                <?= csrfField() ?>
                <div class="status-options">
                    <?php foreach (['IDEA','DISCUSSING','FINAL','CANCELLED'] as $s): ?>
                    <label class="status-option status-option-<?= strtolower($s) ?> <?= $event['status'] === $s ? 'selected' : '' ?>">
                        <input type="radio" name="status" value="<?= $s ?>" <?= $event['status'] === $s ? 'checked' : '' ?>>
                        <?= e(Lang::t('event.status.' . $s)) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><?= e(Lang::t('event.save_button')) ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Delete (creator only) -->
        <?php if ($isCreator): ?>
        <div class="card card-danger">
            <form method="POST" action="/events/<?= (int)$event['id'] ?>/delete"
                  onsubmit="return confirm('<?= e(Lang::t('event.delete_confirm')) ?>')">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-danger btn-full"><?= e(Lang::t('event.delete_button')) ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Slot voting (only if there are multiple slots) -->
        <?php if (count($slots) > 1): ?>
        <div class="card" id="slot-votes">
            <h2 class="card-title"><?= e(Lang::t('event.slot_vote_title')) ?></h2>
            <p class="text-muted" style="margin-bottom:.75rem;font-size:.875rem"><?= e(Lang::t('event.slot_vote_hint')) ?></p>
            <div class="slot-vote-grid">
                <?php foreach ($slots as $sl): ?>
                <?php
                $slotId    = (int)$sl['id'];
                $myVote    = $mySlotVotes[$slotId] ?? null;
                $totals    = null;
                foreach ($slotTotals as $t) { if ((int)$t['slot_id'] === $slotId) { $totals = $t; break; } }
                ?>
                <div class="slot-vote-row">
                    <span class="slot-vote-label">
                        <?= $sl['slot_date'] ? e(fmtDate($sl['slot_date'])) : e($sl['slot_text']) ?>
                    </span>
                    <div class="slot-vote-actions">
                        <form method="POST" action="/events/<?= (int)$event['id'] ?>/vote-slot" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="slot_id" value="<?= $slotId ?>">
                            <input type="hidden" name="available" value="1">
                            <button type="submit" class="btn btn-sm <?= $myVote === 1 ? 'btn-success' : 'btn-outline' ?>">
                                ✓ <?= $totals ? (int)$totals['yes'] : 0 ?>
                            </button>
                        </form>
                        <form method="POST" action="/events/<?= (int)$event['id'] ?>/vote-slot" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="slot_id" value="<?= $slotId ?>">
                            <input type="hidden" name="available" value="0">
                            <button type="submit" class="btn btn-sm <?= $myVote === 0 ? 'btn-danger' : 'btn-outline' ?>">
                                ✗ <?= $totals ? (int)$totals['no'] : 0 ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Feedback form -->
        <div class="card">
            <h2 class="card-title">
                <?= $myResp ? e(Lang::t('event.feedback_modify')) : e(Lang::t('event.feedback_your')) ?>
            </h2>
            <form method="POST" action="/events/<?= (int)$event['id'] ?>/feedback" class="form-stack">
                <?= csrfField() ?>

                <div class="slider-group">
                    <label for="interest_level"><?= e(Lang::t('event.feedback_interest')) ?></label>
                    <div class="slider-row">
                        <span class="slider-min">1</span>
                        <input type="range" id="interest_level" name="interest_level"
                               min="1" max="10" value="<?= $myResp ? (int)$myResp['interest_level'] : 5 ?>"
                               class="slider" oninput="updateVal(this,'interest-val')">
                        <span class="slider-max">10</span>
                        <span class="slider-value" id="interest-val"><?= $myResp ? (int)$myResp['interest_level'] : 5 ?></span>
                    </div>
                </div>

                <div class="slider-group">
                    <label for="mood_level"><?= e(Lang::t('event.feedback_mood')) ?></label>
                    <div class="slider-row">
                        <span class="slider-min">1</span>
                        <input type="range" id="mood_level" name="mood_level"
                               min="1" max="10" value="<?= $myResp ? (int)$myResp['mood_level'] : 5 ?>"
                               class="slider" oninput="updateVal(this,'mood-val')">
                        <span class="slider-max">10</span>
                        <span class="slider-value" id="mood-val"><?= $myResp ? (int)$myResp['mood_level'] : 5 ?></span>
                    </div>
                </div>

                <div class="slider-group">
                    <label for="willingness_level"><?= e(Lang::t('event.feedback_willingness')) ?></label>
                    <div class="slider-row">
                        <span class="slider-min">1</span>
                        <input type="range" id="willingness_level" name="willingness_level"
                               min="1" max="10" value="<?= $myResp ? (int)$myResp['willingness_level'] : 5 ?>"
                               class="slider" oninput="updateVal(this,'will-val')">
                        <span class="slider-max">10</span>
                        <span class="slider-value" id="will-val"><?= $myResp ? (int)$myResp['willingness_level'] : 5 ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?= e(Lang::t('event.feedback_save')) ?></button>
            </form>
        </div>
    </div>

    <!-- Comments section -->
    <div class="event-comments" id="comments">
        <div class="card">
            <h2 class="card-title"><?= e(Lang::t('event.comments_title')) ?></h2>

            <?php if (!empty($comments)): ?>
            <ul class="comment-list">
                <?php foreach ($comments as $c): ?>
                <li class="comment-item">
                    <div class="comment-header">
                        <span class="comment-author"><?= e($c['author']) ?></span>
                        <span class="comment-date text-muted"><?= e(fmtDate($c['created_at'])) ?></span>
                        <?php if ((int)$c['user_id'] === Session::userId() || User::isAdmin(Session::userId())): ?>
                        <form method="POST"
                              action="/events/<?= (int)$event['id'] ?>/comments/<?= (int)$c['id'] ?>/delete"
                              class="inline-form"
                              onsubmit="return confirm('<?= e(Lang::t('event.comment_delete_confirm')) ?>')">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-ghost btn-xs"><?= e(Lang::t('event.comment_delete')) ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <p class="comment-body"><?= nl2br(e($c['body'])) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted" style="margin-bottom:1rem"><?= e(Lang::t('event.no_comments')) ?></p>
            <?php endif; ?>

            <form method="POST" action="/events/<?= (int)$event['id'] ?>/comments" class="form-stack" style="margin-top:1rem">
                <?= csrfField() ?>
                <div class="field">
                    <textarea name="body" rows="3" maxlength="2000"
                              placeholder="<?= e(Lang::t('event.comment_placeholder')) ?>"
                              class="form-textarea" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><?= e(Lang::t('event.comment_submit')) ?></button>
            </form>
        </div>
    </div>

    <!-- Sidebar: averages + all responses -->
    <aside class="event-sidebar">

        <?php if ($averages): ?>
        <div class="card card-averages">
            <h3 class="card-title">
                <?= e(Lang::t('event.feedback_average')) ?>
                <span class="response-count"><?= (int)$averages['count'] ?> <?= e(Lang::t('event.feedback_responses_count')) ?></span>
            </h3>
            <div class="avg-bars">
                <div class="avg-item">
                    <span class="avg-label"><?= e(Lang::t('event.feedback_interest')) ?></span>
                    <div class="avg-bar-wrap">
                        <div class="avg-bar" style="width:<?= round((float)$averages['avg_interest'] / 10 * 100) ?>%"></div>
                    </div>
                    <span class="avg-num"><?= e($averages['avg_interest']) ?></span>
                </div>
                <div class="avg-item">
                    <span class="avg-label"><?= e(Lang::t('event.feedback_mood')) ?></span>
                    <div class="avg-bar-wrap">
                        <div class="avg-bar" style="width:<?= round((float)$averages['avg_mood'] / 10 * 100) ?>%"></div>
                    </div>
                    <span class="avg-num"><?= e($averages['avg_mood']) ?></span>
                </div>
                <div class="avg-item">
                    <span class="avg-label"><?= e(Lang::t('event.feedback_willingness')) ?></span>
                    <div class="avg-bar-wrap">
                        <div class="avg-bar" style="width:<?= round((float)$averages['avg_willingness'] / 10 * 100) ?>%"></div>
                    </div>
                    <span class="avg-num"><?= e($averages['avg_willingness']) ?></span>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <p class="text-muted"><?= e(Lang::t('event.feedback_no_responses')) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($responses)): ?>
        <div class="card">
            <h3 class="card-title"><?= e(Lang::t('event.feedback_title')) ?></h3>
            <ul class="response-list">
                <?php foreach ($responses as $r): ?>
                <li class="response-item">
                    <div class="response-email"><?= e($r['email']) ?></div>
                    <div class="response-scores">
                        <span class="score-chip" title="<?= e(Lang::t('event.feedback_interest')) ?>"><?= (int)$r['interest_level'] ?></span>
                        <span class="score-chip" title="<?= e(Lang::t('event.feedback_mood')) ?>"><?= (int)$r['mood_level'] ?></span>
                        <span class="score-chip" title="<?= e(Lang::t('event.feedback_willingness')) ?>"><?= (int)$r['willingness_level'] ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

    </aside>
</div>
