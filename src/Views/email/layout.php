<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= e(Lang::t('app.name')) ?></title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f0f0f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f0f6;padding:32px 16px">
<tr><td align="center">

  <!-- Card -->
  <table width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.10)">

    <!-- Header -->
    <tr>
      <td style="background:linear-gradient(135deg,#6366f1 0%,#818cf8 100%);padding:28px 32px">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td>
              <div style="font-size:1.4rem;font-weight:800;color:#ffffff;letter-spacing:-.5px;line-height:1">
                <?= e(Lang::t('app.name')) ?>
              </div>
              <div style="font-size:.8rem;color:rgba(255,255,255,.75);margin-top:4px">
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
      <td style="background:#f8f8fb;border-top:1px solid #ebebf2;padding:16px 32px">
        <p style="margin:0;font-size:.75rem;color:#aaa;line-height:1.6">
          <?= e(Lang::t('email.footer')) ?>
        </p>
      </td>
    </tr>

  </table>

</td></tr>
</table>

</body>
</html>
