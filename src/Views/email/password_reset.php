<h2 style="margin:0 0 12px;font-size:1.2rem;color:#1a1a2e"><?= e(Lang::t('email.password_reset_title')) ?></h2>
<p style="margin:0 0 20px;color:#555;font-size:.93rem;line-height:1.65">
  <?= e(Lang::t('email.password_reset_body')) ?>
</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px">
  <tr>
    <td style="border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8)">
      <a href="<?= e($resetUrl) ?>"
         style="display:inline-block;padding:13px 30px;color:#fff;text-decoration:none;font-weight:700;font-size:.95rem">
        <?= e(Lang::t('email.password_reset_btn')) ?> →
      </a>
    </td>
  </tr>
</table>

<p style="margin:0 0 8px;font-size:.8rem;color:#aaa;line-height:1.5">
  <?= e(Lang::t('email.password_reset_expiry')) ?>
</p>
<p style="margin:0;font-size:.8rem;color:#aaa;line-height:1.5;border-left:3px solid #f0f0f0;padding-left:10px">
  <?= e(Lang::t('email.password_reset_ignore')) ?>
</p>
