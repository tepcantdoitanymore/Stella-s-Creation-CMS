<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/PHPMailer-master/src/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/phpmailer/PHPMailer-master/src/SMTP.php';

/**
 * Send a cute HTML email using Gmail SMTP.
 *
 * @param string $to              Recipient email address
 * @param string $subject         Email subject
 * @param string $body            Plain-text body (we keep line breaks & bullets)
 * @param bool   $addAdminButton  If true, adds "Open Admin Dashboard" button
 *
 * Example:
 *   // Normal email
 *   sendMail($email, 'Subject here', $textBody);
 *
 *   // New cart checkout admin email with dashboard button
 *   sendMail('youremail@gmail.com',
 *            'New Cart Checkout - Stella\'s Creation',
 *            $orderSummaryText,
 *            true);
 */
function sendMail($to, $subject, $body, $addAdminButton = false)
{
    $mail = new PHPMailer(true);

    try {
        // ============ SMTP CONFIG ============
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // TODO: SET THESE TO YOUR REAL GMAIL + APP PASSWORD
        $mail->Username   = 'YOUR_GMAIL@gmail.com';          // your Gmail
        $mail->Password   = 'YOUR_16_CHAR_APP_PASSWORD';     // Gmail App Password

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ============ FROM / TO ============
        $mail->setFrom('YOUR_GMAIL@gmail.com', "Stella's Creation");
        $mail->addAddress($to);

        $mail->isHTML(true);

        // ============ BUILD CUTE HTML BODY ============

        // Determine current host (fallback to your real domain)
        $host = $_SERVER['HTTP_HOST'] ?? 'stellascreation.shop';

        // Your dashboard is at root: /dashboard.php
        $adminLink = 'https://' . $host . '/dashboard.php';

        // Keep line breaks & all characters (like • bullets)
        $bodyHtml = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

        // Optional admin button
        $adminButtonHtml = '';
        if ($addAdminButton) {
            $adminButtonHtml = '
              <div style="text-align:center;margin-top:10px;">
                <a href="' . $adminLink . '"
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

        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

        $mail->Subject = $subject;

        // Main cute wrapper – but your details stay exactly in order
        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>' . $safeSubject . '</title>
</head>
<body style="margin:0;padding:0;background:#fff7fb;font-family:Poppins,Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
         style="background:#fff7fb;padding:24px 0;">
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
                ' . $safeSubject . '
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:20px 24px 16px;font-size:14px;color:#444;">
              <div style="white-space:normal;">
                ' . $bodyHtml . '
              </div>
              ' . $adminButtonHtml . '
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

        return $mail->send();
    } catch (Exception $e) {
        // If you want to debug, you can log this:
        // error_log('Mailer Error: ' . $e->getMessage());
        return false;
    }
}
