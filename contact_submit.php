<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Simple contact form handler using PHP mail()

// Get form values
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
if ($name === '' || $email === '' || $message === '') {
    die('All fields are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Invalid email format.');
}

// Where you want to receive contact messages
$adminEmail = 'stellaluna022506@gmail.com'; // ← change if needed

// Email to admin
$subject = 'New Contact Message - Stella’s Creation';
$body  = "You received a new message from your website contact form:\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n\n";
$body .= "Message:\n$message\n";

// Headers
$headers  = "From: {$name} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";

// Send to admin
$mailSent = mail($adminEmail, $subject, $body, $headers);

// Optional: simple autoresponse to customer
if ($mailSent) {
    $autoSubject = "We received your message ❤️";
    $autoBody  = "Hi {$name},\n\n";
    $autoBody .= "Thank you for reaching out to Stella’s Creation!\n";
    $autoBody .= "We’ve received your message and will get back to you as soon as we can.\n\n";
    $autoBody .= "With love,\nStella’s Creation\n";

    $autoHeaders  = "From: Stella’s Creation <{$adminEmail}>\r\n";
    $autoHeaders .= "Reply-To: {$adminEmail}\r\n";

    @mail($email, $autoSubject, $autoBody, $autoHeaders);
}

// Redirect to a simple thank-you message
// (You can make a cute thank-you page later and change this URL)
header('Location: /contact.php?sent=1');
exit;
