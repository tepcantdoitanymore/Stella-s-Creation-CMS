<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

$to      = 'stellaluna022506@gmail.com';
$subject = 'Test email from InfinityFree';
$body    = "Hello Stella,\n\nThis is a simple test email from test_mail.php.\n";
$headers = "From: Test Sender <no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'example.com') . ">\r\n";

$result = mail($to, $subject, $body, $headers);

if ($result) {
    echo "mail() returned TRUE. If you don't see the email, check spam folder.";
} else {
    echo "mail() returned FALSE. PHP mail() might be disabled or blocked.";
}
