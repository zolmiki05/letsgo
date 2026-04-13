<p style="margin:0 0 4px;font-size:.78rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.07em">
  <?= e($group['name']) ?>
</p>
<h2 style="margin:0 0 8px;font-size:1.25rem;color:#1a1a2e;line-height:1.3">
  <?= e($event['title']) ?>
</h2>
<p style="margin:0 0 20px;color:#555;font-size:.9rem;line-height:1.6">
  <?= e(Lang::t('email.event_created_body', [
      'creator' => $creator['username'] ?? $creator['email'],
      'group'   => $group['name'],
  ])) ?>
</p>

<?php include ROOT . '/src/Views/email/_event_data.php'; ?>

<table cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
  <tr>
    <td style="border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8)">
      <a href="<?= e($eventUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#fff;text-decoration:none;font-weight:700;font-size:.92rem">
        <?= e(Lang::t('email.view_event_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
