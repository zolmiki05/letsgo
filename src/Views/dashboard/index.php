<div class="page-header">
    <h1 class="page-title"><?= e(Lang::t('dashboard.title')) ?></h1>
    <a href="/groups/create" class="btn btn-primary"><?= e(Lang::t('dashboard.create_group')) ?></a>
</div>

<?php if (!empty($deadlines)): ?>
<section class="deadline-section">
    <h2 class="section-title"><?= e(Lang::t('dashboard.upcoming_deadlines')) ?></h2>
    <div class="deadline-list">
        <?php foreach ($deadlines as $dl): ?>
        <a href="/events/<?= (int)$dl['id'] ?>" class="deadline-card">
            <div class="deadline-meta"><?= e($dl['group_name']) ?></div>
            <div class="deadline-title"><?= e($dl['title']) ?></div>
            <div class="deadline-dates">
                <?php if ($dl['deadline_signup']): ?>
                <span class="deadline-chip signup"><?= e(Lang::t('dashboard.deadline_signup')) ?>: <?= e(fmtDate($dl['deadline_signup'])) ?></span>
                <?php endif; ?>
                <?php if ($dl['deadline_decision']): ?>
                <span class="deadline-chip decision"><?= e(Lang::t('dashboard.deadline_decision')) ?>: <?= e(fmtDate($dl['deadline_decision'])) ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (empty($groups)): ?>
<div class="empty-state">
    <div class="empty-icon">✦</div>
    <p><?= e(Lang::t('dashboard.no_groups')) ?></p>
    <a href="/groups/create" class="btn btn-primary"><?= e(Lang::t('dashboard.create_group')) ?></a>
</div>
<?php else: ?>
<div class="group-grid">
    <?php foreach ($groups as $group): ?>
    <a href="/groups/<?= (int)$group['id'] ?>" class="group-card">
        <div class="group-card-name"><?= e($group['name']) ?></div>
        <div class="group-card-meta"><?= (int)$group['member_count'] ?> <?= e(Lang::t('dashboard.members_count')) ?></div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($archivedGroups)): ?>
<section class="archived-section" style="margin-top:2.5rem">
    <h2 class="section-title" style="color:var(--text-muted)"><?= e(Lang::t('dashboard.archived_groups')) ?></h2>
    <div class="group-grid group-grid-archived">
        <?php foreach ($archivedGroups as $group): ?>
        <div class="group-card group-card-archived" style="opacity:.65">
            <a href="/groups/<?= (int)$group['id'] ?>" class="group-card-name"><?= e($group['name']) ?></a>
            <div class="group-card-meta"><?= (int)$group['member_count'] ?> <?= e(Lang::t('dashboard.members_count')) ?></div>
            <form method="POST" action="/groups/<?= (int)$group['id'] ?>/unarchive" class="inline-form" style="margin-top:.5rem">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-ghost btn-xs"><?= e(Lang::t('group.unarchive_button')) ?></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
