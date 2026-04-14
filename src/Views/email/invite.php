<h2 style="margin:0 0 12px;font-size:1.2rem;font-weight:800;color:#0B1120;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e(Lang::t('email.invite_title')) ?>
</h2>
<p style="margin:0 0 20px;color:#46567A;line-height:1.65;font-size:.9375rem">
  <?= e(Lang::t('email.invite_body')) ?>
</p>

<!-- Invite code box -->
<table cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#EEF2FF;border:2px dashed #BFD0F7;border-radius:12px;margin:0 0 20px">
  <tr>
    <td style="padding:20px 24px;text-align:center">
      <div style="font-size:.72rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">
        Meghívó kód
      </div>
      <div style="font-size:2rem;font-weight:800;letter-spacing:.3em;color:#2840B8;font-family:'Courier New',monospace">
        <?= e($token) ?>
      </div>
    </td>
  </tr>
</table>

<p style="margin:0 0 20px;color:#8A9DC4;font-size:.85rem;line-height:1.6">
  <?= e(Lang::t('email.invite_hint')) ?>
</p>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($registerUrl) ?>"
         style="display:inline-block;padding:12px 28px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem;letter-spacing:-.01em">
        <?= e(Lang::t('email.invite_register_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
