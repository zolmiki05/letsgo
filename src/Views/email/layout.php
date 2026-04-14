<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= e(Lang::t('app.name')) ?></title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
  body { margin:0; padding:0; background-color:#E4E9F4;
         font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
         -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
  .email-brand { font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif; }
</style>
</head>
<body style="margin:0;padding:0;background-color:#E4E9F4">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#E4E9F4;padding:32px 16px">
<tr><td align="center">

  <!-- Card -->
  <table width="560" cellpadding="0" cellspacing="0" border="0"
         style="max-width:560px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #C8D3EC;box-shadow:0 4px 24px rgba(11,17,32,.10),0 1px 4px rgba(11,17,32,.06)">

    <!-- Header -->
    <tr>
      <td style="background:#3451D1;padding:24px 32px">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td>
              <div class="email-brand"
                   style="font-family:'Bricolage Grotesque','DM Sans',Helvetica,Arial,sans-serif;font-size:1.375rem;font-weight:800;color:#FFFFFF;letter-spacing:-.04em;line-height:1">
                <?= e(Lang::t('app.name')) ?>
              </div>
              <div style="font-size:.78rem;color:rgba(255,255,255,.70);margin-top:4px;font-weight:500;letter-spacing:.01em">
                <?= e(Lang::t('app.tagline')) ?>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Body -->
    <tr>
      <td style="padding:32px 32px 24px">
        <?= $content ?>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="background:#F2F5FC;border-top:1px solid #DAE3F5;padding:16px 32px">
        <p style="margin:0;font-size:.75rem;color:#8A9DC4;line-height:1.6">
          <?= e(Lang::t('email.footer')) ?>
        </p>
      </td>
    </tr>

  </table>

</td></tr>
</table>

</body>
</html>
