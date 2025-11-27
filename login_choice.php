<?php
session_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login Options | Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- same fonts + main stylesheet as index -->
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

    .btn-row {
      display:flex;
      flex-direction:column;
      gap:10px;
      margin-top:8px;
    }

    /* pareho sa imong auth-btn sa register */
    .auth-btn {
      width:100%;
      display:inline-block;
      text-align:center;
    }

    .note {
      margin-top:16px;
      font-size:.85rem;
      color:#666;
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
  </style>
</head>
<body>

<div class="card">
  <h2>Log In</h2>
  <p class="subtitle">Please choose how you’d like to log in.</p>

  <div class="btn-row">
    <!-- ADMIN LOGIN PAGE -->
    <a href="/admin_login.php" class="btn ghost auth-btn">
      Log in as Admin
    </a>

    <!-- CUSTOMER LOGIN PAGE (same look as Order Now / Sign Up) -->
    <a href="/customer_login.php" class="btn primary auth-btn">
      Log in as Customer
    </a>
  </div>

  <p class="note">
    New here? You can create a customer account after choosing “Log in as Customer”.
  </p>

  <a class="back-btn" href="/index.php">Back to Home</a>
</div>

</body>
</html>
