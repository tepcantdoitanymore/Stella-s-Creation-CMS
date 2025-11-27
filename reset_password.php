<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';
$err   = '';
$done  = false;
$row   = null; // make sure it's defined

if ($token === '') {
  $err = 'Invalid or missing reset link.';
} else {
  $stmt = $pdo->prepare("
    SELECT pr.customer_id, pr.expires_at, c.email, c.fullname
    FROM password_resets pr
    JOIN customers_tbl c ON c.customer_id = pr.customer_id
    WHERE pr.token = ?
    LIMIT 1
  ");
  $stmt->execute([$token]);
  $row = $stmt->fetch();

  if (!$row) {
    $err = 'Invalid or expired reset link.';
  } else {
    if (strtotime($row['expires_at']) < time()) {
      $err = 'This reset link has expired. Please request a new one.';
      $row = null; // treat as invalid so form won't show
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $row) {
  $newPass = $_POST['password'] ?? '';
  $confirm = $_POST['password_confirm'] ?? '';

  if (strlen($newPass) < 6) {
    $err = 'Password must be at least 6 characters.';
  } elseif ($newPass !== $confirm) {
    $err = 'Passwords do not match.';
  } else {
    $hash = password_hash($newPass, PASSWORD_DEFAULT);

    $upd = $pdo->prepare("UPDATE customers_tbl SET password_hash = ? WHERE customer_id = ?");
    $upd->execute([$hash, $row['customer_id']]);

    $del = $pdo->prepare("DELETE FROM password_resets WHERE customer_id = ?");
    $del->execute([$row['customer_id']]);

    $done = true;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reset Password | Stella’s Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts + shared global styles -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="/style.css">

  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: linear-gradient(135deg, var(--soft-pink) 0%, var(--lavender) 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 16px;
    }

    .card {
      background: #fff;
      padding: 30px 32px 26px;
      width: 100%;
      max-width: 420px;
      border-radius: 22px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.12);
      text-align: center;
    }

    h2 {
      margin: 0 0 4px;
      font-size: 1.7rem;
      color: var(--charcoal);
    }

    .subtitle {
      margin: 0 0 16px;
      font-size: 0.9rem;
      color: #777;
    }

    label {
      text-align: left;
      width: 100%;
      display: block;
      font-size: .9rem;
      color: #444;
      margin-bottom: 4px;
    }

    input {
      width: 100%;
      padding: 11px 12px;
      margin-bottom: 16px;
      border-radius: 12px;
      border: 1px solid #ccc;
      outline: none;
      font-size: .95rem;
    }

    input:focus {
      border-color: var(--lavender);
      box-shadow: 0 0 0 2px rgba(200,162,200,0.18);
    }

    .auth-btn {
      width: 100%;
      display: inline-block;
      text-align: center;
      margin-top: 4px;
    }

    .back-btn {
      margin-top: 14px;
      display: inline-block;
      width: 100%;
      text-align: center;
      border-radius: 999px;
      font-size: .85rem;
      padding: 10px 16px;
      background: var(--cream);
      color: var(--charcoal);
      box-shadow: 0 4px 10px rgba(0,0,0,0.06);
      text-decoration: none;
    }

    .back-btn:hover {
      background: var(--soft-pink);
    }

    .message {
      padding: 10px 12px;
      border-radius: 10px;
      margin-bottom: 12px;
      font-size: .85rem;
      text-align: left;
    }
  </style>
</head>

<body>

<div class="card">
  <h2>Reset Password</h2>
  <p class="subtitle">Create a new password for your account.</p>

  <?php if ($done): ?>
    <div class="message" style="background:#e7ffe7;color:#2d7a2d;">
      Your password has been updated!  
      <a href="/customer_login.php" style="color:#6a3aa9;font-weight:600;">Log in</a>
    </div>

  <?php elseif (!$row): ?>
    <!-- Token invalid or expired -->
    <?php if ($err): ?>
      <div class="message" style="background:#ffe5e5;color:#a30000;">
        <?= htmlspecialchars($err) ?>
      </div>
    <?php else: ?>
      <div class="message" style="background:#ffe5e5;color:#a30000;">
        This reset link is invalid or has expired.
      </div>
    <?php endif; ?>

    <a class="back-btn" href="/forgot_password.php">Request a new reset link</a>

  <?php else: ?>
    <!-- Valid token: show errors (if any) + form -->
    <?php if ($err): ?>
      <div class="message" style="background:#ffe5e5;color:#a30000;">
        <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" style="margin-top:14px;">
      <label>New password</label>
      <input type="password" name="password" required>

      <label>Confirm password</label>
      <input type="password" name="password_confirm" required>

      <button class="btn primary auth-btn" type="submit">Update password</button>

      <a class="back-btn" href="/customer_login.php">Back to Login</a>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
