<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['customer_id'])) {
  header('Location: /login_choice.php');
  exit;
}

require_once __DIR__ . '/db.php';

$cid         = (int)$_SESSION['customer_id'];
$isLogged    = true;
$currentPage = ''; // no nav item highlighted

// Notification count
$notifCount = 0;
$notifStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM orders_tbl
  WHERE customer_id = ?
    AND status IN ('Pending','Approved','Completed', 'Ready' ,'Refunded')
");
$notifStmt->execute([$cid]);
$notifCount = (int)$notifStmt->fetchColumn();

// nav helper
if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$cartIconHref = "/cart.php";
$msg = "";
$msgClass = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current = $_POST['current_password'] ?? '';
  $new     = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($current === '' || $new === '' || $confirm === '') {
    $msg = "Please fill in all fields.";
    $msgClass = "err";
  } elseif ($new !== $confirm) {
    $msg = "New password and confirmation do not match.";
    $msgClass = "err";
  } else {
    // Get existing hash
    $stmt = $pdo->prepare("
      SELECT password_hash
      FROM customers_tbl
      WHERE customer_id = ?
      LIMIT 1
    ");
    $stmt->execute([$cid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password_hash'])) {
      $msg = "Current password is incorrect.";
      $msgClass = "err";
    } else {
      $newHash = password_hash($new, PASSWORD_DEFAULT);
      $update  = $pdo->prepare("
        UPDATE customers_tbl
           SET password_hash = ?
         WHERE customer_id = ?
         LIMIT 1
      ");
      $update->execute([$newHash, $cid]);
      $msg = "Password updated successfully.";
      $msgClass = "ok";
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Change Password · Stella’s Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">

  <style>
    :root {
      --soft-pink:#F8C8DC;
      --lavender:#C8A2C8;
      --cream:#FFF8E7;
      --charcoal:#3A3A3A;
      --rose:#E75480;
      --white:#ffffff;
      scrollbar-gutter: stable; /* iwas wiggle sa navbar */
    }
    html {
      overflow-y: scroll;
    }
    body {
      overflow-x: hidden;
    }

    .pw-page-main {
      padding: 40px 0 70px;
    }
    .pw-card {
      max-width: 520px;
      margin: 0 auto;
      padding: 24px 28px 26px;
      border-radius: 26px;
      background: #fff;
      box-shadow: 0 18px 45px rgba(0,0,0,.04);
      border:1px solid #f5e3ff;
    }
    .pw-card h2 {
      font-size: 1.4rem;
      margin-bottom: 8px;
    }
    .pw-sub {
      font-size:0.85rem;
      color:#7b6c82;
      margin-bottom:16px;
    }
    .pw-form .field {
      display: flex;
      flex-direction: column;
      margin-bottom: 12px;
      font-size: 0.9rem;
    }
    .pw-form label {
      margin-bottom: 4px;
      font-weight: 500;
      color: #444;
    }
    .pw-form input[type="password"] {
      border-radius: 999px;
      border: 1px solid #ead8f0;
      padding: 8px 12px;
      font-size: 0.9rem;
      background:#fefbff;
      outline:none;
      transition:0.18s ease;
    }
    .pw-form input[type="password"]:focus {
      border-color:#c8a2c8;
      box-shadow:0 0 0 3px rgba(200,162,200,0.18);
      background:#fff;
    }
    .pw-actions {
      text-align: right;
      margin-top: 10px;
    }

    .flash-msg {
      margin-top: 10px;
      font-size: 0.85rem;
      border-radius:999px;
      padding:7px 12px;
      display:inline-block;
    }
    .flash-msg.ok {
      color:#336f93;
      background:#e9f8ff;
      border:1px solid #c2e6ff;
    }
    .flash-msg.err {
      color:#b24a63;
      background:#ffe5ea;
      border:1px solid #ffc5d1;
    }

    @media (max-width:600px){
      .pw-card{
        padding:20px 18px 22px;
      }
    }

    /* BURGER BUTTON — same look/animation as contact/account_info/index */
    .burger {
      display: none;
      width: 28px;
      height: 22px;
      flex-direction: column;
      justify-content: space-between;
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
      margin-right: 10px; /* para dili dikit sa kilid */
    }

    .burger span {
      width: 100%;
      height: 3px;
      background: var(--dark, #333);
      border-radius: 4px;
      transition: 0.3s ease;
    }

    .burger.open span:nth-child(1) {
      transform: translateY(9px) rotate(45deg);
    }
    .burger.open span:nth-child(2) {
      opacity: 0;
    }
    .burger.open span:nth-child(3) {
      transform: translateY(-9px) rotate(-45deg);
    }

    @media (max-width: 768px) {
      .nav-main {
        display: none !important;
      }
      .burger {
        display: flex !important;
      }
    }

    .navbar {
      padding-right: 15px;
      padding-left: 10px;
    }
  </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand-inline">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella’s <span>Creation</span></div>
    </div>
    <button class="icon-btn close-btn" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <nav class="sidebar-nav">
    <a href="/index.php">Home</a>
    <a href="/about.php">About</a>
    <a href="/shop.php">Shop</a>
    <a href="/contact.php">Contact</a>
    <a href="/my_orders.php">My Orders</a>
    <a href="/account_info.php">Account Info</a>
    <a href="/change_password.php">Change Password</a>
    <a href="<?= htmlspecialchars($cartIconHref) ?>">Cart</a>
  </nav>

  <div class="sidebar-search">
    <input type="text" placeholder="Search keychains, photocards..." id="sidebarSearchInput">
    <button type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <div class="sidebar-social">
    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
    <a href="#"><i class="fa-brands fa-instagram"></i></a>
    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
  </div>
</aside>

<header class="site-header">
  <div class="container navbar">
    <div class="nav-left">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella’s <span>Creation</span></div>
    </div>

    <nav class="nav-main">
      <a href="/index.php"   class="<?= nav_link_class('home') ?>">Home</a>
      <a href="/about.php"   class="<?= nav_link_class('about') ?>">About</a>
      <a href="/shop.php"    class="<?= nav_link_class('shop') ?>">Shop</a>
      <a href="/contact.php" class="<?= nav_link_class('contact') ?>">Contact</a>
    </nav>

    <div class="nav-icons">
      <button class="icon-btn" id="searchToggle" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>

      <button 
        class="icon-btn nav-notif-btn" 
        aria-label="Notifications" 
        onclick="window.location.href='/my_orders.php'"
      >
        <i class="fa-regular fa-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </button>

      <div class="user-dropdown-wrap">
        <button class="icon-btn user-menu-btn" type="button" aria-label="User Menu">
          <i class="fa-regular fa-user"></i>
        </button>

        <div class="user-dropdown">
          <div class="user-greeting">
            <?php
              $name = $_SESSION['customer_name'] ?? 'User';
              $name = ucwords(strtolower($name));
            ?>
            Hello, <?= htmlspecialchars($name) ?>!
          </div>

          <a href="/track_order.php">Track Order</a>
          <a href="/my_orders.php">My Orders</a>
          <a href="/account_info.php">Account Info</a>
          <a href="/change_password.php">Change Password</a>
          <a href="/customer_logout.php" class="logout">Logout</a>
        </div>
      </div>

      <a href="<?= htmlspecialchars($cartIconHref) ?>" class="icon-btn" aria-label="Cart">
        <i class="fa-solid fa-bag-shopping"></i>
      </a>

      <button class="burger" id="burgerBtn" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="search-bar-wrap" id="searchBarWrap">
  <div class="container">
    <form class="search-bar" method="get" action="/shop.php">
      <input type="text" name="q" placeholder="Search keychains, photocards, ref magnets...">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
  </div>
</div>

<main class="page-main pw-page-main">
  <div class="container">
    <div class="pw-card">
      <h2>Change Password</h2>
      <p class="pw-sub">For security, please enter your current password first.</p>

      <?php if ($msg): ?>
        <div class="flash-msg <?= $msgClass ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="post" class="pw-form">
        <div class="field">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="field">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" required>
        </div>

        <div class="field">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="pw-actions">
          <button type="submit" class="btn primary">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella’s Creation · Made with love in PH</div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const burgerBtn       = document.getElementById('burgerBtn');
  const sidebar         = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarClose    = document.getElementById('sidebarClose');
  const searchToggle    = document.getElementById('searchToggle');
  const searchBarWrap   = document.getElementById('searchBarWrap');
  const userMenuBtn     = document.querySelector('.user-menu-btn');
  const userDropdown    = document.querySelector('.user-dropdown');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (sidebarBackdrop) sidebarBackdrop.classList.add('open');
  }
  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('open');
  }

  // same behavior/animation as contact/account_info
  if (burgerBtn) {
    burgerBtn.addEventListener('click', () => {
      burgerBtn.classList.toggle('open');
      openSidebar();
    });
  }

  if (sidebarClose) {
    sidebarClose.addEventListener('click', () => {
      if (burgerBtn) burgerBtn.classList.remove('open');
      closeSidebar();
    });
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', () => {
      if (burgerBtn) burgerBtn.classList.remove('open');
      closeSidebar();
    });
  }

  if (searchToggle && searchBarWrap) {
    searchToggle.addEventListener('click', () => {
      searchBarWrap.classList.toggle('open');
    });
  }

  // User dropdown toggle
  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
      if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
        userDropdown.classList.remove('open');
      }
    });
  }
});
</script>

</body>
</html>
