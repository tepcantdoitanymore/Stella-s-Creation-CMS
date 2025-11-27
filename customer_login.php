<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  $q = $pdo->prepare("SELECT customer_id, fullname, email, phone, password_hash FROM customers_tbl WHERE email = ? LIMIT 1");
  $q->execute([$email]);
  $u = $q->fetch();

  if ($u && password_verify($pass, $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['customer_id']   = $u['customer_id'];
    $_SESSION['customer_name'] = $u['fullname'];
    header('Location: /index.php');
    exit;
  } else {
    $err = 'Invalid email or password.';
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Customer Login | Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- same fonts + main stylesheet as index -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="/style.css">

  <style>
    /* we keep your main palette from style.css */
    * {
      box-sizing:border-box;
    }

    body {
      margin:0;
      font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      /* soft pink -> lavender gradient using existing vars */
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
      margin-bottom:18px;
      border-radius:12px;
      border:1px solid #ccc;
      outline:none;
      font-size:.95rem;
    }

    input:focus {
      border-color:var(--lavender);
      box-shadow:0 0 0 2px rgba(200,162,200,0.18);
    }

    /* reuse your .btn.primary colors – we just make them full width */
    .auth-btn {
      width:100%;
      display:inline-block;
      text-align:center;
      margin-top:4px;
    }

    .create-account {
      margin-top:10px;
      font-size:.9rem;
    }

    .create-account a {
      color:var(--rose);
      font-weight:500;
      text-decoration:none;
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
    }

    .back-btn:hover {
      background:var(--soft-pink);
    }

    .error {
      background:#ffe0e8;
      color:#b10033;
      padding:10px 12px;
      border-radius:10px;
      margin-bottom:12px;
      font-size:.85rem;
      text-align:left;
    }
  </style>

</head>
<body>

<div class="card">
  <h2>Customer Login</h2>
  <p class="subtitle">Log in to view your orders and place new ones.</p>

  <?php if ($err): ?>
    <div class="error"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Email</label>
    <input name="email" type="email" required>

    <label>Password</label>
    <input name="password" type="password" required>

    <p class="forgot-password" style="text-align:right;margin-top:-10px;margin-bottom:12px;font-size:.85rem;">
      <a href="/forgot_password.php" style="color:var(--rose);font-weight:500;">Forgot your password?</a>
    </p>

    <!-- main CTA uses SAME style as Order Now: .btn.primary -->
    <button class="btn primary auth-btn" type="submit">Log In</button>

    <p class="create-account">
      New here? <a href="/customer_register.php">Create an account</a>
    </p>

    <a class="back-btn" href="/login_choice.php">Back to Login Options</a>
  </form>
</div>

</body>
</html>
