<!-- Success banner -->
<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:12px;margin:0 0 20px">
  <tr>
    <td style="padding:20px 22px">
      <div style="font-size:1.5rem;margin-bottom:8px">🔒</div>
      <div style="font-size:.9375rem;font-weight:700;color:#065F46;margin-bottom:4px;letter-spacing:-.015em">
        <?= e(Lang::t('email.password_changed_title')) ?>
      </div>
      <div style="font-size:.875rem;color:#059669;line-height:1.6">
        <?= e(Lang::t('email.password_changed_body')) ?>
      </div>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:.82rem;color:#8A9DC4;line-height:1.6;border-left:3px solid #DAE3F5;padding-left:12px">
  <?= e(Lang::t('email.password_changed_hint')) ?>
</p>
