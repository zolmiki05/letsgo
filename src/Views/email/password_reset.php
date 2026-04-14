<h2 style="margin:0 0 12px;font-size:1.2rem;font-weight:800;color:#0B1120;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e(Lang::t('email.password_reset_title')) ?>
</h2>
<p style="margin:0 0 20px;color:#46567A;font-size:.9375rem;line-height:1.65">
  <?= e(Lang::t('email.password_reset_body')) ?>
</p>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($resetUrl) ?>"
         style="display:inline-block;padding:13px 30px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem">
        <?= e(Lang::t('email.password_reset_btn')) ?> →
      </a>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:.8rem;color:#8A9DC4;line-height:1.5">
  <?= e(Lang::t('email.password_reset_expiry')) ?>
</p>
<p style="margin:0;font-size:.8rem;color:#8A9DC4;line-height:1.5;border-left:3px solid #DAE3F5;padding-left:10px">
  <?= e(Lang::t('email.password_reset_ignore')) ?>
</p>
