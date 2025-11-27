<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/PHPMailer-master/src/SMTP.php';


function makeMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'stellaluna022506@gmail.com';
    $mail->Password   = 'jdzo ljdn gkvw tkac'; // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom('stellaluna022506@gmail.com', "Stella's Creation");
    $mail->isHTML(true);
    return $mail;
}


function buildScEmail(
    string $subject,
    string $innerHtml,
    bool $addAdminButton = false,
    string $adminLink = ''
): string {
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

    $buttonHtml = '';
    if ($addAdminButton && $adminLink !== '') {
        $buttonHtml = '
          <div style="text-align:center;margin-top:14px;">
            <a href="'.htmlspecialchars($adminLink, ENT_QUOTES, 'UTF-8').'"
               style="
                 display:inline-block;
                 padding:11px 24px;
                 border-radius:999px;
                 background:#f28ac9;
                 color:#ffffff;
                 font-weight:600;
                 font-size:14px;
                 text-decoration:none;
               ">
              Open Admin Dashboard
            </a>
          </div>';
    }

    return '
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>'.$safeSubject.'</title>
</head>
<body style="margin:0;padding:0;background:#fff7fb;font-family:Poppins,Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
               style="max-width:520px;background:#ffffff;border-radius:18px;
                      box-shadow:0 8px 24px rgba(0,0,0,0.06);overflow:hidden;">
          <tr>
            <td style="background:#f9c9de;padding:16px 24px;text-align:center;">
              <div style="font-size:13px;letter-spacing:0.15em;text-transform:uppercase;
                          color:#6a3050;margin-bottom:4px;">
                Stella\'s Creation
              </div>
              <div style="font-size:20px;font-weight:700;color:#3a3a3a;">
                '.$safeSubject.'
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:18px 24px 14px;font-size:14px;color:#444;">
              '.$innerHtml.'
              '.$buttonHtml.'
            </td>
          </tr>

          <tr>
            <td style="padding:10px 18px 14px;text-align:center;font-size:11px;
                       color:#999;background:#fff1f6;">
              Made with ♡ by Stella\'s Creation
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function sendMailMessage(string $to, string $subject, string $htmlBody, string $altBody = ''): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    $mail = makeMailer();
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $altBody ?: strip_tags($htmlBody);

    return $mail->send();
}
