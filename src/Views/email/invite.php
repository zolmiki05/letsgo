<h2 style="margin:0 0 12px;font-size:1.2rem;color:#1a1a2e"><?= e(Lang::t('email.invite_title')) ?></h2>
<p style="margin:0 0 20px;color:#555;line-height:1.65;font-size:.93rem"><?= e(Lang::t('email.invite_body')) ?></p>

<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f0f0ff;border:2px dashed #a5b4fc;border-radius:10px;margin:0 0 20px">
  <tr>
    <td style="padding:18px 24px;text-align:center">
      <div style="font-size:.72rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">
        Meghívó kód
      </div>
      <div style="font-size:2rem;font-weight:800;letter-spacing:.3em;color:#4338ca;font-family:monospace">
        <?= e($token) ?>
      </div>
    </td>
  </tr>
</table>

<p style="margin:0 0 20px;color:#777;font-size:.85rem;line-height:1.6"><?= e(Lang::t('email.invite_hint')) ?></p>

<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8)">
      <a href="<?= e($registerUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#fff;text-decoration:none;font-weight:700;font-size:.95rem;letter-spacing:.01em">
        <?= e(Lang::t('email.invite_register_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
