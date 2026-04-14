<!-- Info banner -->
<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#EEF2FF;border:1px solid #BFD0F7;border-radius:12px;margin:0 0 20px">
  <tr>
    <td style="padding:20px 22px">
      <div style="font-size:1.6rem;margin-bottom:10px">👋</div>
      <div style="font-size:.78rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">
        <?= e($group['name']) ?>
      </div>
      <div style="font-size:.9375rem;font-weight:700;color:#0B1120;margin-bottom:4px;letter-spacing:-.015em">
        <?= e(Lang::t('email.member_joined_title')) ?>
      </div>
      <div style="font-size:.875rem;color:#46567A;line-height:1.6">
        <?= e(Lang::t('email.member_joined_body', [
            'name'  => $newMember['username'] ?? $newMember['email'],
            'group' => $group['name'],
        ])) ?>
      </div>
    </td>
  </tr>
</table>

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
