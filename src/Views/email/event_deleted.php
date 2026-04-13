<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin:0 0 20px">
  <tr>
    <td style="padding:18px 22px">
      <div style="font-size:.78rem;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">
        <?= e($group['name']) ?> · <?= e(Lang::t('email.event_deleted_title')) ?>
      </div>
      <div style="font-size:1.05rem;font-weight:700;color:#7f1d1d;margin-bottom:4px">
        <?= e($event['title']) ?>
      </div>
      <div style="font-size:.88rem;color:#b91c1c;line-height:1.5">
        <?= e(Lang::t('email.event_deleted_body', [
            'title' => $event['title'],
            'group' => $group['name'],
        ])) ?>
      </div>
    </td>
  </tr>
</table>

<?php if (!empty($event['description']) || !empty($event['location'])): ?>
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f8f8fc;border:1px solid #e8e8f0;border-radius:8px;overflow:hidden;margin:0 0 20px">
  <?php if (!empty($event['location'])): ?>
  <tr>
    <td style="padding:8px 14px;border-bottom:1px solid #f0f0f5;width:38%">
      <span style="font-size:.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.06em">
        <?= e(Lang::t('event.field_location')) ?>
      </span>
    </td>
    <td style="padding:8px 14px;border-bottom:1px solid #f0f0f5">
      <span style="font-size:.88rem;color:#222"><?= e($event['location']) ?></span>
    </td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($event['description'])): ?>
  <tr>
    <td style="padding:8px 14px;width:38%;vertical-align:top">
      <span style="font-size:.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.06em">
        <?= e(Lang::t('event.field_description')) ?>
      </span>
    </td>
    <td style="padding:8px 14px;vertical-align:top">
      <span style="font-size:.88rem;color:#222;line-height:1.5"><?= nl2br(e($event['description'])) ?></span>
    </td>
  </tr>
  <?php endif; ?>
</table>
<?php endif; ?>

<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:#6366f1">
      <a href="<?= e($groupUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#fff;text-decoration:none;font-weight:700;font-size:.92rem">
        <?= e(Lang::t('email.view_group_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
