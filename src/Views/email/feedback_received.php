<p style="margin:0 0 4px;font-size:.78rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.07em">
  <?= e(Lang::t('email.feedback_received_title')) ?>
</p>
<h2 style="margin:0 0 8px;font-size:1.1rem;color:#1a1a2e"><?= e($event['title']) ?></h2>
<p style="margin:0 0 20px;color:#555;font-size:.9rem;line-height:1.6">
  <?= e(Lang::t('email.feedback_received_body', [
      'actor' => $actor['username'] ?? $actor['email'],
      'title' => $event['title'],
  ])) ?>
</p>

<?php
$scores = [
    ['label' => Lang::t('email.feedback_interest'),    'val' => (int)$response['interest_level']],
    ['label' => Lang::t('email.feedback_mood'),        'val' => (int)$response['mood_level']],
    ['label' => Lang::t('email.feedback_willingness'), 'val' => (int)$response['willingness_level']],
];
?>
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f8f8fc;border:1px solid #e8e8f0;border-radius:10px;overflow:hidden;margin:0 0 20px">
  <?php foreach ($scores as $i => $s):
    $pct  = $s['val'] * 10;
    $last = ($i === count($scores) - 1);
    $border = $last ? '' : 'border-bottom:1px solid #f0f0f5';
  ?>
  <tr>
    <td style="padding:10px 14px;<?= $border ?>;width:48%;vertical-align:middle">
      <span style="font-size:.8rem;color:#555"><?= e($s['label']) ?></span>
    </td>
    <td style="padding:10px 14px;<?= $border ?>;vertical-align:middle">
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td style="width:<?= $pct ?>%;background:linear-gradient(90deg,#6366f1,#a5b4fc);border-radius:4px;height:8px;vertical-align:middle">&nbsp;</td>
          <?php if ($pct < 100): ?>
          <td style="width:<?= 100-$pct ?>%;background:#ebebf2;border-radius:4px;height:8px">&nbsp;</td>
          <?php endif; ?>
          <td style="padding-left:8px;white-space:nowrap;width:1px">
            <span style="font-size:.88rem;font-weight:700;color:#6366f1"><?= $s['val'] ?><span style="font-weight:400;color:#aaa">/10</span></span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8)">
      <a href="<?= e($eventUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#fff;text-decoration:none;font-weight:700;font-size:.92rem">
        <?= e(Lang::t('email.view_event_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
