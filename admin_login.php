<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/db.php';

$errors = [];

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_logged_in':
            $errors[] = "Please log in to access the admin panel.";
            break;
        case 'session_expired':
            $errors[] = "Your session has expired due to inactivity. Please log in again.";
            break;
        case 'ip_mismatch':
            $errors[] = "Security alert: IP address change detected. Please log in again.";
            break;
        case 'browser_mismatch':
            $errors[] = "Security alert: Browser change detected. Please log in again.";
            break;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = "Please log in to access the admin panel. Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("
            SELECT admin_id, username, password_hash
            FROM admin_tbl
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['admin_id'];
            $_SESSION['admin_username']  = $admin['username'];

            $_SESSION['admin_last_activity']     = time();
            $_SESSION['admin_last_regeneration'] = time();
            $_SESSION['admin_ip']                = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['admin_user_agent']        = $_SERVER['HTTP_USER_AGENT'] ?? '';

            unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);

            header("Location: dashboard.php");
            exit;

        } else {
            $errors[] = "Please input correct credentials.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login | Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="/style.css">

  <style>
    * { box-sizing: border-box; }

    body {
      margin:0;
      font-family:"Poppins",sans-serif;
      background:linear-gradient(135deg, var(--soft-pink) 0%, var(--lavender) 100%);
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      padding:16px;
    }

    .card {
      background:#fff;
      padding:32px 34px 28px;
      width:100%;
      max-width:420px;
      border-radius:22px;
      box-shadow:0 12px 35px rgba(0,0,0,0.12);
      display:flex;
      flex-direction:column;
      text-align:left;
      gap:14px;
    }

    h2 {
      margin:0;
      text-align:center;
      font-size:1.7rem;
      color:var(--charcoal);
    }

    .subtitle {
      margin:0;
      text-align:center;
      font-size:.9rem;
      color:#777;
    }

    label {
      font-size:.9rem;
      color:#444;
      margin-bottom:6px;
      display:block;
    }

    input {
      width:100%;
      padding:11px 14px;
      border-radius:14px;
      border:1px solid #ddd;
      outline:none;
      font-size:.95rem;
      margin-bottom:12px;
      font-family:"Poppins",sans-serif;
    }

    input:focus {
      border-color:var(--lavender);
      box-shadow:0 0 0 1px rgba(200,162,200,0.35);
    }

    .btn.primary {
      width:100%;
      padding:12px;
      border-radius:999px;
      background:var(--lavender);
      color:#fff;
      font-weight:600;
      border:none;
      font-size:.95rem;
      cursor:pointer;
      box-shadow:0 6px 14px rgba(200,162,200,0.35);
    }
    .btn.primary:hover {
      background:#b590c0;
    }

    .error-box {
      background:#ffe5ef;
      border-radius:999px;
      padding:12px 20px;
      color:#d3204b;
      font-size:.85rem;
      font-weight:600;
      text-align:center;
      border:1px solid #ffb6cf;
      box-shadow:0 8px 20px rgba(255,105,180,0.25);
    }

    .back-btn {
      width:100%;
      text-align:center;
      border-radius:999px;
      padding:12px 16px;
      background:var(--cream);
      color:var(--charcoal);
      box-shadow:0 6px 14px rgba(0,0,0,0.08);
      text-decoration:none;
      transition:.2s;
      font-size:.9rem;
    }

    .back-btn:hover {
      background:var(--soft-pink);
    }

    .security-badge {
      text-align:center;
      margin-top:12px;
      padding-top:12px;
      border-top:1px solid #f2e7ff;
      font-size:.75rem;
      color:#999;
    }
  </style>
</head>
<body>

<div class="card">
  <h2>Admin Login</h2>
  <p class="subtitle">For administrators only.</p>

  <?php if ($errors): ?>
    <div class="error-box">
      <?= htmlspecialchars(end($errors), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <label>Admin Username</label>
    <input type="text" name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" class="btn primary">Log In</button>
  </form>

  <a class="back-btn" href="/login_choice.php">Back to Login Options</a>

  <div class="security-badge">
    Protected with enhanced security
  </div>
</div>

</body>
</html>
