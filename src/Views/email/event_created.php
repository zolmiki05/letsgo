<p style="margin:0 0 4px;font-size:.78rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.07em">
  <?= e($group['name']) ?>
</p>
<h2 style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#0B1120;line-height:1.25;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e($event['title']) ?>
</h2>
<p style="margin:0 0 20px;color:#46567A;font-size:.9375rem;line-height:1.6">
  <?= e(Lang::t('email.event_created_body', [
      'creator' => $creator['username'] ?? $creator['email'],
      'group'   => $group['name'],
  ])) ?>
</p>

<?php include ROOT . '/src/Views/email/_event_data.php'; ?>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($eventUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem">
        <?= e(Lang::t('email.view_event_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
