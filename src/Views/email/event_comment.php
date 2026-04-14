<p style="margin:0 0 4px;font-size:.78rem;font-weight:600;color:#3451D1;text-transform:uppercase;letter-spacing:.07em">
  <?= e($group['name']) ?> · <?= e($event['title']) ?>
</p>
<h2 style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#0B1120;line-height:1.25;letter-spacing:-.025em;font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif">
  <?= e(Lang::t('email.comment_title')) ?>
</h2>
<p style="margin:0 0 16px;color:#46567A;font-size:.9375rem;line-height:1.6">
  <?= e(Lang::t('email.comment_body', [
      'author' => $author['username'] ?? $author['email'],
      'title'  => $event['title'],
  ])) ?>
</p>

<!-- Comment body -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 20px">
  <tr>
    <td style="background:#F2F5FC;border:1px solid #C8D3EC;border-left:3px solid #3451D1;border-radius:6px;padding:14px 16px">
      <p style="margin:0;color:#0B1120;font-size:.9375rem;line-height:1.65;white-space:pre-wrap"><?= e($commentBody) ?></p>
      <p style="margin:8px 0 0;font-size:.78rem;color:#8A9DC4;font-style:italic">— <?= e($author['username'] ?? $author['email']) ?></p>
    </td>
  </tr>
</table>

<!-- CTA button -->
<table cellpadding="0" cellspacing="0" border="0" style="margin-top:8px">
  <tr>
    <td style="border-radius:8px;background:#3451D1;box-shadow:0 1px 3px rgba(52,81,209,.30),inset 0 1px 0 rgba(255,255,255,.12)">
      <a href="<?= e($eventUrl) ?>#comments"
         style="display:inline-block;padding:12px 28px;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:.9375rem">
        <?= e(Lang::t('email.view_event_btn')) ?> →
      </a>
    </td>
  </tr>
</table>
