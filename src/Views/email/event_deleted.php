<!-- Danger banner -->
<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#FFF1F2;border:1px solid #FECDD3;border-radius:12px;margin:0 0 20px">
  <tr>
    <td style="padding:20px 22px">
      <div style="font-size:.78rem;font-weight:600;color:#9F1239;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">
        <?= e($group['name']) ?> · <?= e(Lang::t('email.event_deleted_title')) ?>
      </div>
      <div style="font-size:1rem;font-weight:800;color:#7F1D1D;margin-bottom:4px;letter-spacing:-.02em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
        <?= e($event['title']) ?>
      </div>
      <div style="font-size:.875rem;color:#BE123C;line-height:1.6">
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
       style="background:#F2F5FC;border:1px solid #C8D3EC;border-radius:12px;overflow:hidden;margin:0 0 20px">
  <?php if (!empty($event['location'])): ?>
  <tr>
    <td style="padding:9px 14px;border-bottom:1px solid #DAE3F5;width:38%">
      <span style="font-size:.72rem;font-weight:600;color:#8A9DC4;text-transform:uppercase;letter-spacing:.06em">
        <?= e(Lang::t('event.field_location')) ?>
      </span>
    </td>
    <td style="padding:9px 14px;border-bottom:1px solid #DAE3F5">
      <span style="font-size:.875rem;color:#0B1120"><?= e($event['location']) ?></span>
    </td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($event['description'])): ?>
  <tr>
    <td style="padding:9px 14px;width:38%;vertical-align:top">
      <span style="font-size:.72rem;font-weight:600;color:#8A9DC4;text-transform:uppercase;letter-spacing:.06em">
        <?= e(Lang::t('event.field_description')) ?>
      </span>
    </td>
    <td style="padding:9px 14px;vertical-align:top">
      <span style="font-size:.875rem;color:#0B1120;line-height:1.5"><?= nl2br(e($event['description'])) ?></span>
    </td>
  </tr>
  <?php endif; ?>
</table>
<?php endif; ?>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($groupUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem">
        <?= e(Lang::t('email.view_group_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
