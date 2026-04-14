<?php
/**
 * Partial – event data box (inline-CSS, email-client compatible).
 * Expects: $event, $slots (may be empty).
 */
$displaySlots = $slots ?? [];
if (empty($displaySlots) && ($event['event_date'] || $event['date_text'])) {
    $displaySlots = [['slot_date' => $event['event_date'], 'slot_text' => $event['date_text']]];
}

// Signal design system — light mode status colors
$statusColors = [
    'IDEA'       => ['bg' => '#EFF6FF', 'color' => '#1E40AF'],
    'DISCUSSING' => ['bg' => '#FFFBEB', 'color' => '#92400E'],
    'FINAL'      => ['bg' => '#ECFDF5', 'color' => '#065F46'],
    'CANCELLED'  => ['bg' => '#FFF1F2', 'color' => '#9F1239'],
];
$sc = $statusColors[$event['status']] ?? ['bg' => '#F2F5FC', 'color' => '#46567A'];

function _emailRow(string $label, string $value): string {
    return '<tr>'
        . '<td style="padding:9px 14px;border-bottom:1px solid #DAE3F5;width:38%;vertical-align:top">'
        .   '<span style="font-size:.72rem;font-weight:600;color:#8A9DC4;text-transform:uppercase;letter-spacing:.06em">' . $label . '</span>'
        . '</td>'
        . '<td style="padding:9px 14px;border-bottom:1px solid #DAE3F5;vertical-align:top">'
        .   '<span style="font-size:.875rem;color:#0B1120;line-height:1.5">' . $value . '</span>'
        . '</td>'
        . '</tr>';
}
?>

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F2F5FC;border:1px solid #C8D3EC;border-radius:12px;overflow:hidden;margin:16px 0;font-size:.875rem">

  <?php if (!empty($displaySlots)): ?>
  <?php if (count($displaySlots) === 1): $s = $displaySlots[0]; ?>
  <?= _emailRow(e(Lang::t('event.field_time_slots')),
      $s['slot_date'] ? e(fmtDate($s['slot_date'])) : e($s['slot_text'])) ?>
  <?php else: ?>
  <?php
  $slotHtml = '<ul style="margin:0;padding-left:1.1rem">';
  foreach ($displaySlots as $s) {
      $slotHtml .= '<li style="margin-bottom:2px">'
          . ($s['slot_date'] ? e(fmtDate($s['slot_date'])) : e($s['slot_text']))
          . '</li>';
  }
  $slotHtml .= '</ul>';
  echo _emailRow(e(Lang::t('event.field_time_slots')), $slotHtml);
  ?>
  <?php endif; ?>
  <?php endif; ?>

  <?php if (!empty($event['location'])): ?>
  <?= _emailRow(e(Lang::t('event.field_location')), e($event['location'])) ?>
  <?php endif; ?>

  <?php if (!empty($event['deadline_signup']) || !empty($event['deadline_signup_text'])): ?>
  <?= _emailRow(
      e(Lang::t('event.field_deadline_signup')),
      $event['deadline_signup'] ? e(fmtDate($event['deadline_signup'])) : e($event['deadline_signup_text'])
  ) ?>
  <?php endif; ?>

  <?php if (!empty($event['deadline_decision']) || !empty($event['deadline_decision_text'])): ?>
  <?= _emailRow(
      e(Lang::t('event.field_deadline_decision')),
      $event['deadline_decision'] ? e(fmtDate($event['deadline_decision'])) : e($event['deadline_decision_text'])
  ) ?>
  <?php endif; ?>

  <?php if (!empty($event['cost'])): ?>
  <?= _emailRow(e(Lang::t('event.field_cost')), e($event['cost'])) ?>
  <?php endif; ?>

  <?php if (!empty($event['description'])): ?>
  <?= _emailRow(e(Lang::t('event.field_description')), nl2br(e($event['description']))) ?>
  <?php endif; ?>

  <?php if (!empty($event['notes'])): ?>
  <?= _emailRow(e(Lang::t('event.field_notes')), nl2br(e($event['notes']))) ?>
  <?php endif; ?>

  <!-- Status row — no bottom border -->
  <tr>
    <td style="padding:9px 14px;width:38%;vertical-align:middle">
      <span style="font-size:.72rem;font-weight:600;color:#8A9DC4;text-transform:uppercase;letter-spacing:.06em">
        <?= e(Lang::t('event.field_status')) ?>
      </span>
    </td>
    <td style="padding:9px 14px;vertical-align:middle">
      <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;
                   background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
        <?= e(Lang::t('event.status.' . $event['status'])) ?>
      </span>
    </td>
  </tr>

</table>
