<?php
require_once __DIR__ . '/mailer_config.php';

$result = sendMailToAdmin(
    "SMTP Test from Stella’s Creation",
    "<b>Hello!</b><br>This is a test email using Gmail SMTP + PHPMailer."
);

if ($result === true) {
    echo "SUCCESS! Check your Gmail inbox.";
} else {
    echo "FAILED: " . $result;
}
