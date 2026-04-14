<p style="margin:0 0 4px;font-size:.78rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.07em">
  <?= e(Lang::t('email.feedback_received_title')) ?>
</p>
<h2 style="margin:0 0 8px;font-size:1.1rem;font-weight:800;color:#0B1120;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e($event['title']) ?>
</h2>
<p style="margin:0 0 20px;color:#46567A;font-size:.9375rem;line-height:1.6">
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
<!-- Feedback scores -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F2F5FC;border:1px solid #C8D3EC;border-radius:12px;overflow:hidden;margin:0 0 20px">
  <?php foreach ($scores as $i => $s):
    $pct  = $s['val'] * 10;
    $last = ($i === count($scores) - 1);
    $border = $last ? '' : 'border-bottom:1px solid #DAE3F5;';
  ?>
  <tr>
    <td style="padding:10px 14px;<?= $border ?>width:44%;vertical-align:middle">
      <span style="font-size:.875rem;color:#46567A;font-weight:500"><?= e($s['label']) ?></span>
    </td>
    <td style="padding:10px 14px;<?= $border ?>vertical-align:middle">
      <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td style="width:<?= $pct ?>%;background:#3451D1;border-radius:4px;height:8px;vertical-align:middle">&nbsp;</td>
          <?php if ($pct < 100): ?>
          <td style="width:<?= 100-$pct ?>%;background:#DAE3F5;border-radius:4px;height:8px">&nbsp;</td>
          <?php endif; ?>
          <td style="padding-left:8px;white-space:nowrap;width:1px">
            <span style="font-size:.875rem;font-weight:700;color:#3451D1"><?= $s['val'] ?><span style="font-weight:400;color:#8A9DC4">/10</span></span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($eventUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem">
        <?= e(Lang::t('email.view_event_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
