<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;margin:0 0 20px">
  <tr>
    <td style="padding:18px 22px">
      <div style="font-size:1.5rem;margin-bottom:8px">🔒</div>
      <div style="font-size:1rem;font-weight:700;color:#166534;margin-bottom:4px">
        <?= e(Lang::t('email.password_changed_title')) ?>
      </div>
      <div style="font-size:.88rem;color:#16a34a;line-height:1.5">
        <?= e(Lang::t('email.password_changed_body')) ?>
      </div>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:.82rem;color:#999;line-height:1.6;border-left:3px solid #f0f0f0;padding-left:12px">
  <?= e(Lang::t('email.password_changed_hint')) ?>
</p>
