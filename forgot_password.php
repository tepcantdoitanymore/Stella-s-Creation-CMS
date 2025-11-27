<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer_config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        // Look up customer by email
        $q = $pdo->prepare("SELECT customer_id, fullname, email FROM customers_tbl WHERE email = ? LIMIT 1");
        $q->execute([$email]);
        $user = $q->fetch();

        if ($user) {
            // Generate token and expiry
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Clear any old tokens for this user
            $pdo->prepare("DELETE FROM password_resets WHERE customer_id = ?")
                ->execute([$user['customer_id']]);

            // Save new reset token
            $ins = $pdo->prepare("
                INSERT INTO password_resets (customer_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$user['customer_id'], $token, $expiresAt]);

            // 🔗 Use your HOSTINGER domain here
            $baseUrl   = 'https://stellascreation.shop';
            $resetLink = $baseUrl . '/reset_password.php?token=' . urlencode($token);

            // Email content
            $subject  = "Reset your Stella’s Creation password";
            $safeName = htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

            $htmlBody = "
                <p>Hi <strong>{$safeName}</strong>,</p>
                <p>We received a request to reset your password for <strong>Stella’s Creation</strong>.</p>
                <p style=\"margin:16px 0;\">
                  <a href=\"{$safeLink}\" style=\"
                    display:inline-block;
                    padding:10px 18px;
                    background:#C8A2C8;
                    color:#ffffff;
                    text-decoration:none;
                    border-radius:999px;
                    font-weight:600;
                    font-family:Arial, sans-serif;
                  \">Reset your password</a>
                </p>
                <p>If the button doesn’t work, copy and paste this link into your browser:</p>
                <p><a href=\"{$safeLink}\">{$safeLink}</a></p>
                <p>This link will expire in 1 hour. If you didn’t request this, you can safely ignore this email.</p>
                <p>Love,<br>Stella's Creation</p>
            ";

            $altBody  = "Hi {$user['fullname']},\n\n"
                      . "We received a request to reset your password for Stella’s Creation.\n\n"
                      . "Reset link (valid for 1 hour):\n{$resetLink}\n\n"
                      . "If you didn’t request this, you can ignore this email.";

            // Send the email
            sendMailMessage($user['email'], $subject, $htmlBody, $altBody);
        }

        // Always show generic message (for security)
        $message = 'If that email is registered, a reset link has been sent.';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Forgot Password | Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts + shared styles -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="/style.css">

  <style>
    * {
      box-sizing:border-box;
    }

    body {
      margin:0;
      font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:linear-gradient(135deg, var(--soft-pink) 0%, var(--lavender) 100%);
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      padding:16px;
    }

    .card {
      background:#fff;
      padding:30px 32px 26px;
      width:100%;
      max-width:420px;
      border-radius:22px;
      box-shadow:0 12px 35px rgba(0,0,0,0.12);
      text-align:center;
    }

    h2 {
      margin:0 0 4px;
      font-size:1.7rem;
      color:var(--charcoal);
    }

    .subtitle {
      margin:0 0 16px;
      font-size:.9rem;
      color:#777;
    }

    form { margin-top:4px; }

    label {
      text-align:left;
      width:100%;
      display:block;
      font-size:.9rem;
      color:#444;
      margin-bottom:4px;
    }

    input {
      width:100%;
      padding:11px 12px;
      margin-bottom:16px;
      border-radius:12px;
      border:1px solid #ccc;
      outline:none;
      font-size:.95rem;
    }

    input:focus {
      border-color:var(--lavender);
      box-shadow:0 0 0 2px rgba(200,162,200,0.18);
    }

    .auth-btn {
      width:100%;
      display:inline-block;
      text-align:center;
      margin-top:4px;
    }

    .back-btn {
      margin-top:14px;
      display:inline-block;
      width:100%;
      text-align:center;
      border-radius:999px;
      font-size:.85rem;
      padding:10px 16px;
      background:var(--cream);
      color:var(--charcoal);
      box-shadow:0 4px 10px rgba(0,0,0,0.06);
      text-decoration:none;
    }

    .back-btn:hover {
      background:var(--soft-pink);
    }

    .message {
      padding:10px 12px;
      border-radius:10px;
      margin-bottom:12px;
      font-size:.85rem;
      background:#f2e3ff;
      color:#583a7a;
      text-align:left;
    }
  </style>
</head>

<body>

<div class="card">
  <h2>Forgot Password</h2>
  <p class="subtitle">Enter your email to receive a reset link.</p>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <label>Email</label>
    <input name="email" type="email" required>

    <button class="btn primary auth-btn" type="submit">Send Reset Link</button>

    <a class="back-btn" href="/customer_login.php">Back to Login</a>
  </form>
</div>

</body>
</html>
