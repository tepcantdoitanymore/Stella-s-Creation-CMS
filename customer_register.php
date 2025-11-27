<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$err = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address.";
    } elseif (!preg_match('/@gmail\.com$/i', $email)) {
        $err = "Please register using a Gmail address so you can recover your password easily.";
    } elseif (!preg_match('/^[0-9]{11}$/', $phone)) {
        $err = "Phone number must be exactly 11 digits.";
    } elseif (strlen($password) < 6) {
        $err = "Password must be at least 6 characters.";
    } elseif ($fullname === '') {
        $err = "Please fill in all fields.";
    } else {
        $q = $pdo->prepare("SELECT customer_id FROM customers_tbl WHERE email = ? LIMIT 1");
        $q->execute([$email]);
        if ($q->fetch()) {
            $err = "This email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("
                INSERT INTO customers_tbl (fullname, email, phone, password_hash)
                VALUES (?, ?, ?, ?)
            ");
            $ins->execute([$fullname, $email, $phone, $hash]);
            $success = true;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create Account | Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="/style.css">

  <style>
    * { box-sizing: border-box; }

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
      padding:30px 32px;
      width:100%;
      max-width:420px;
      border-radius:22px;
      box-shadow:0 12px 35px rgba(0,0,0,0.12);
      text-align:center;
    }

    h2 {
      margin:0 0 6px;
      font-size:1.7rem;
      color:var(--charcoal);
    }

    .subtitle {
      font-size:.9rem;
      color:#777;
      margin-bottom:10px;
    }

    .gmail-helper-text {
      font-size:.82rem;
      color:#8a6d8f;
      margin-top:-2px;
      margin-bottom:18px;
    }

    form { margin-top:4px; }

    .field-group {
      text-align:left;
      margin-bottom:14px;
      position:relative;
    }

    label {
      display:block;
      font-size:.9rem;
      color:#444;
      margin-bottom:4px;
    }

    input {
      width:100%;
      padding:11px 12px;
      border-radius:12px;
      border:1px solid #ccc;
      font-size:.95rem;
      outline:none;
    }

    input:focus {
      border-color:var(--lavender);
      box-shadow:0 0 0 2px rgba(200,162,200,0.18);
    }

    .auth-btn {
      width:100%;
      display:inline-block;
      margin-top:2px;
    }

    .error {
      background:#ffe5e5;
      color:#a30000;
      padding:10px 12px;
      border-radius:10px;
      margin-bottom:14px;
      font-size:.85rem;
      text-align:left;
    }

    .success {
      background:#e7ffe7;
      color:#1f7a1f;
      padding:12px;
      border-radius:10px;
      margin-bottom:14px;
      font-size:.9rem;
      text-align:left;
    }

    .login-link {
      margin-top:12px;
      font-size:.9rem;
    }
    .login-link a {
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
      padding:10px 16px;
      background:var(--cream);
      color:var(--charcoal);
      box-shadow:0 4px 10px rgba(0,0,0,0.06);
      font-size:.85rem;
      text-decoration:none;
    }
    .back-btn:hover {
      background:var(--soft-pink);
    }

    .hint-bubble {
      position:absolute;
      left:0;
      right:0;
      top:90%;
      padding:8px 10px;
      font-size:.78rem;
      background:#fff5ff;
      color:#7a4a8a;
      border-radius:10px;
      box-shadow:0 6px 16px rgba(0,0,0,0.08);
      opacity:0;
      pointer-events:none;
      transform:translateY(4px);
      transition:opacity .3s ease, transform .3s ease;
      z-index:50;
    }

    .hint-bubble.show {
      opacity:1;
      transform:translateY(0);
    }
  </style>
</head>

<body>
<div class="card">
  <h2>Create Account</h2>
  <p class="subtitle">Join Stella's Creation to place and track your orders easily.</p>

  <?php if ($err): ?>
    <div class="error"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="success">
      Account created successfully!  
      You can now <a href="/customer_login.php" style="color:#6a3aa9;font-weight:600;">log in</a>.
    </div>
  <?php else: ?>

  <form method="post" autocomplete="off">

    <div class="field-group">
      <label>Full Name</label>
      <input type="text" name="fullname"
             value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>Email</label>
      <input type="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <div class="hint-bubble" id="gmailHint">
        Please use a registered Gmail address so you can recover your password easily.
      </div>
    </div>

    <div class="field-group">
      <label>Phone</label>
      <input type="text" name="phone"
             maxlength="11"
             pattern="\d{11}"
             oninput="this.value = this.value.replace(/\D/g, '').slice(0,11);"
             value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
    </div>

    <div class="field-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <button type="submit" class="btn primary auth-btn">Sign Up</button>

    <div class="login-link">
      Already have an account? <a href="/customer_login.php">Log in</a>
    </div>

    <a href="/login_choice.php" class="back-btn">Back to Login Options</a>
  </form>

  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.querySelector('input[name="email"]');
    const hint = document.getElementById('gmailHint');
    let hintTimeout;

    if (emailInput && hint) {
        emailInput.addEventListener('focus', function () {
            clearTimeout(hintTimeout);
            hint.classList.add('show');
            hintTimeout = setTimeout(() => {
                hint.classList.remove('show');
            }, 3000);
        });
    }
});
</script>

</body>
</html>
