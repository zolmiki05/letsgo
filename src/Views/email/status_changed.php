<?php
$statusColors = [
    'IDEA'       => ['bg' => '#f0f4ff', 'color' => '#6366f1', 'border' => '#c7d2fe'],
    'DISCUSSING' => ['bg' => '#fff7ed', 'color' => '#f59e0b', 'border' => '#fde68a'],
    'FINAL'      => ['bg' => '#f0fdf4', 'color' => '#22c55e', 'border' => '#bbf7d0'],
    'CANCELLED'  => ['bg' => '#fef2f2', 'color' => '#ef4444', 'border' => '#fecaca'],
];
$old = $statusColors[$oldStatus]   ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'border' => '#e5e7eb'];
$new = $statusColors[$event['status']] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'border' => '#e5e7eb'];
?>
<p style="margin:0 0 4px;font-size:.78rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.07em">
  <?= e($group['name']) ?> · <?= e(Lang::t('email.status_changed_title')) ?>
</p>
<h2 style="margin:0 0 16px;font-size:1.15rem;color:#1a1a2e"><?= e($event['title']) ?></h2>

<!-- Status transition -->
<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px">
  <tr valign="middle">
    <td style="background:<?= $old['bg'] ?>;border:1px solid <?= $old['border'] ?>;border-radius:20px;padding:5px 14px">
      <span style="font-size:.82rem;font-weight:700;color:<?= $old['color'] ?>">
        <?= e(Lang::t('event.status.' . $oldStatus)) ?>
      </span>
    </td>
    <td style="padding:0 10px;font-size:1.1rem;color:#aaa">→</td>
    <td style="background:<?= $new['bg'] ?>;border:1px solid <?= $new['border'] ?>;border-radius:20px;padding:5px 14px">
      <span style="font-size:.82rem;font-weight:700;color:<?= $new['color'] ?>">
        <?= e(Lang::t('event.status.' . $event['status'])) ?>
      </span>
    </td>
  </tr>
</table>

<p style="margin:0 0 20px;color:#555;font-size:.9rem;line-height:1.6">
  <?= e(Lang::t('email.status_changed_body', [
      'actor'      => $actor['username'] ?? $actor['email'],
      'title'      => $event['title'],
      'old_status' => Lang::t('event.status.' . $oldStatus),
      'new_status' => Lang::t('event.status.' . $event['status']),
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
