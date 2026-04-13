<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f0f4ff;border:1px solid #c7d2fe;border-radius:10px;margin:0 0 20px">
  <tr>
    <td style="padding:20px 22px">
      <div style="font-size:1.6rem;margin-bottom:10px">👋</div>
      <div style="font-size:.78rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">
        <?= e($group['name']) ?>
      </div>
      <div style="font-size:1rem;font-weight:700;color:#1a1a2e;margin-bottom:4px">
        <?= e(Lang::t('email.member_joined_title')) ?>
      </div>
      <div style="font-size:.9rem;color:#4338ca;line-height:1.5">
        <?= e(Lang::t('email.member_joined_body', [
            'name'  => $newMember['username'] ?? $newMember['email'],
            'group' => $group['name'],
        ])) ?>
      </div>
    </td>
  </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8)">
      <a href="<?= e($groupUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#fff;text-decoration:none;font-weight:700;font-size:.92rem">
        <?= e(Lang::t('email.view_group_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
