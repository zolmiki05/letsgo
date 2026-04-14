<?php
// Signal design system — light mode status colors (matching app.css)
$statusColors = [
    'IDEA'       => ['bg' => '#EFF6FF', 'color' => '#1E40AF', 'border' => '#BFD0F7'],
    'DISCUSSING' => ['bg' => '#FFFBEB', 'color' => '#92400E', 'border' => '#FDE68A'],
    'FINAL'      => ['bg' => '#ECFDF5', 'color' => '#065F46', 'border' => '#A7F3D0'],
    'CANCELLED'  => ['bg' => '#FFF1F2', 'color' => '#9F1239', 'border' => '#FECDD3'],
];
$old = $statusColors[$oldStatus]       ?? ['bg' => '#F2F5FC', 'color' => '#46567A', 'border' => '#C8D3EC'];
$new = $statusColors[$event['status']] ?? ['bg' => '#F2F5FC', 'color' => '#46567A', 'border' => '#C8D3EC'];
?>
<p style="margin:0 0 4px;font-size:.78rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.07em">
  <?= e($group['name']) ?> · <?= e(Lang::t('email.status_changed_title')) ?>
</p>
<h2 style="margin:0 0 16px;font-size:1.2rem;font-weight:800;color:#0B1120;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e($event['title']) ?>
</h2>

<!-- Status transition -->
<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px">
  <tr valign="middle">
    <td style="background:<?= $old['bg'] ?>;border:1px solid <?= $old['border'] ?>;border-radius:20px;padding:5px 14px">
      <span style="font-size:.8rem;font-weight:600;color:<?= $old['color'] ?>">
        <?= e(Lang::t('event.status.' . $oldStatus)) ?>
      </span>
    </td>
    <td style="padding:0 10px;font-size:1.1rem;color:#8A9DC4">→</td>
    <td style="background:<?= $new['bg'] ?>;border:1px solid <?= $new['border'] ?>;border-radius:20px;padding:5px 14px">
      <span style="font-size:.8rem;font-weight:600;color:<?= $new['color'] ?>">
        <?= e(Lang::t('event.status.' . $event['status'])) ?>
      </span>
    </td>
  </tr>
</table>

<p style="margin:0 0 20px;color:#46567A;font-size:.9375rem;line-height:1.6">
  <?= e(Lang::t('email.status_changed_body', [
      'actor'      => $actor['username'] ?? $actor['email'],
      'title'      => $event['title'],
      'old_status' => Lang::t('event.status.' . $oldStatus),
      'new_status' => Lang::t('event.status.' . $event['status']),
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
