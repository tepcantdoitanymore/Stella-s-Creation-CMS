<?php
session_start();

header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['customer_id'])) {
    header("Location: /login_choice.php");
    exit;
}

require_once __DIR__ . '/db.php';

$cid         = (int)$_SESSION['customer_id'];
$isLogged    = true;
$notifCount  = 0;
$currentPage = ''; // no main nav highlighted

// Notification count
$notifStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders_tbl
    WHERE customer_id = ?
      AND status IN ('Pending','Approved','Completed', 'Ready', 'Refunded')
");
$notifStmt->execute([$cid]);
$notifCount = (int)$notifStmt->fetchColumn();

// Get current customer info
$stmt = $pdo->prepare("SELECT fullname, email, phone FROM customers_tbl WHERE customer_id = ? LIMIT 1");
$stmt->execute([$cid]);
$info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$fullname = $info['fullname'] ?? '';
$email    = $info['email'] ?? '';
$phone    = $info['phone'] ?? '';

$successMsg = '';
$errorMsg   = '';

// what mode? view or edit
$mode = (isset($_GET['edit']) && $_GET['edit'] === '1') ? 'edit' : 'view';

// handle form submit (edit mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = 'edit'; // stay in edit while validating

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if ($fullname === '' || $email === '' || $phone === '') {
        $errorMsg = 'Please fill out all fields.';
    } else {
        $update = $pdo->prepare("
            UPDATE customers_tbl
               SET fullname = ?, email = ?, phone = ?
             WHERE customer_id = ?
            LIMIT 1
        ");
        $update->execute([$fullname, $email, $phone, $cid]);

        // Update session name for greeting
        $_SESSION['customer_name'] = $fullname;

        // redirect to view mode to avoid resubmit
        header("Location: /account_info.php?updated=1");
        exit;
    }
}

// after redirect from successful update
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $successMsg = 'Account information updated successfully.';
    $mode = 'view';
}

// nav helper
if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$cartIconHref = "/cart.php";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Account Info · Stella’s Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">

  <style>
    :root{
      --soft-pink:#F8C8DC;
      --lavender:#C8A2C8;
      --cream:#FFF8E7;
      --charcoal:#3A3A3A;
      --rose:#E75480;
      --white:#ffffff;
      scrollbar-gutter: stable; /* prevent navbar wiggle */
    }

    html {
      overflow-y: scroll; /* always reserve scrollbar */
    }
    body {
      overflow-x: hidden;
    }

    .account-page-main {
      padding: 40px 0 70px;
    }
    .account-card {
      max-width: 520px;
      margin: 0 auto;
      padding: 24px 26px 26px;
      border-radius: 26px;
      background: #fff;
      box-shadow: 0 18px 45px rgba(0,0,0,.04);
      border:1px solid #f5e3ff;
    }
    .account-header-title{
      display:flex;
      flex-direction:column;
      gap:4px;
      margin-bottom:16px;
    }
    .account-card h2 {
      font-size: 1.4rem;
    }
    .account-sub {
      font-size:0.85rem;
      color:#7b6c82;
    }

    .account-form .field {
      display: flex;
      flex-direction: column;
      margin-bottom: 12px;
      font-size: 0.9rem;
    }
    .account-form label {
      margin-bottom: 4px;
      font-weight: 500;
      color: #444;
    }
    .account-form input[type="text"],
    .account-form input[type="email"],
    .account-form input[type="tel"] {
      border-radius: 999px;
      border: 1px solid #ead8f0;
      padding: 8px 12px;
      font-size: 0.9rem;
      background:#fefbff;
      outline:none;
      transition:0.18s ease;
    }
    .account-form input:focus{
      border-color:#c8a2c8;
      box-shadow:0 0 0 3px rgba(200,162,200,0.18);
      background:#fff;
    }

    .account-actions {
      text-align: right;
      margin-top: 10px;
    }

    .account-msg {
      margin-top: 12px;
      font-size: 0.85rem;
      border-radius:999px;
      padding:7px 12px;
      display:inline-block;
    }
    .account-msg.ok {
      color:#336f93;
      background:#e9f8ff;
      border:1px solid #c2e6ff;
    }
    .account-msg.err {
      color:#b24a63;
      background:#ffe5ea;
      border:1px solid #ffc5d1;
    }

    /* view mode styling */
    .account-display {
      display:flex;
      flex-direction:column;
      gap:10px;
    }
    .display-row {
      display:flex;
      flex-direction:column;
      font-size:0.9rem;
    }
    .display-label {
      font-weight:500;
      color:#555;
      margin-bottom:3px;
    }
    .display-value {
      padding:8px 12px;
      border-radius:999px;
      background:#fefbff;
      border:1px solid #f0e3ff;
      color:#333;
    }
    .display-value.muted{
      color:#999;
      font-style:italic;
    }

    .btn-view-edit {
      border:0;
      border-radius:999px;
      padding:10px 22px;
      font-size:0.9rem;
      font-weight:500;
      background:linear-gradient(135deg,#F8C8DC,#C8A2C8);
      color:#fff;
      cursor:pointer;
      box-shadow:0 12px 24px rgba(200,162,200,0.4);
      transition:transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }
    .btn-view-edit:hover{
      transform:translateY(-1px);
      box-shadow:0 16px 30px rgba(200,162,200,0.6);
      filter:brightness(1.03);
    }
    .btn-view-edit:active{
      transform:translateY(0);
      box-shadow:0 8px 18px rgba(200,162,200,0.5);
    }

    @media (max-width:600px){
      .account-card{
        padding:20px 18px 22px;
      }
    }

    /* BURGER BUTTON — same look/animation as contact/index */
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
      <a href="/index.php" class="<?= nav_link_class('home') ?>">Home</a>
      <a href="/about.php" class="<?= nav_link_class('about') ?>">About</a>
      <a href="/shop.php" class="<?= nav_link_class('shop') ?>">Shop</a>
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

      <?php if ($isLogged): ?>
        <div class="user-dropdown-wrap">
          <button class="icon-btn user-menu-btn" type="button" aria-label="User Menu">
            <i class="fa-regular fa-user"></i>
          </button>
          <div class="user-dropdown">
            <div class="user-greeting">
              <?php
                $name = $_SESSION['customer_name'] ?? $fullname ?: 'User';
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
      <?php else: ?>
        <a href="/customer_login.php" class="icon-btn" aria-label="Login">
          <i class="fa-regular fa-user"></i>
        </a>
      <?php endif; ?>

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

<main class="page-main account-page-main">
  <div class="container">
    <div class="account-card">
      <div class="account-header-title">
        <h2>Account Info</h2>
        <?php if ($mode === 'view'): ?>
          <p class="account-sub">Review your saved details. Tap “Update Info” to make changes.</p>
        <?php else: ?>
          <p class="account-sub">Update your details below and save your changes.</p>
        <?php endif; ?>
      </div>

      <?php if ($successMsg): ?>
        <div class="account-msg ok"><?= htmlspecialchars($successMsg) ?></div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
        <div class="account-msg err"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <?php if ($mode === 'view'): ?>
        <div class="account-display">
          <div class="display-row">
            <span class="display-label">Full Name</span>
            <div class="display-value <?= $fullname === '' ? 'muted' : '' ?>">
              <?= $fullname !== '' ? htmlspecialchars($fullname) : 'Not set' ?>
            </div>
          </div>

          <div class="display-row">
            <span class="display-label">Email</span>
            <div class="display-value <?= $email === '' ? 'muted' : '' ?>">
              <?= $email !== '' ? htmlspecialchars($email) : 'Not set' ?>
            </div>
          </div>

          <div class="display-row">
            <span class="display-label">Phone</span>
            <div class="display-value <?= $phone === '' ? 'muted' : '' ?>">
              <?= $phone !== '' ? htmlspecialchars($phone) : 'Not set' ?>
            </div>
          </div>
        </div>

        <div class="account-actions">
          <a href="/account_info.php?edit=1" class="btn-view-edit">Update Info</a>
        </div>
      <?php else: ?>
        <form method="post" class="account-form">
          <div class="field">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname"
                   value="<?= htmlspecialchars($fullname) ?>" required>
          </div>

          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($email) ?>" required>
          </div>

          <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($phone) ?>" required>
          </div>

          <div class="account-actions">
            <button type="submit" class="btn-view-edit">Save Changes</button>
          </div>
        </form>
      <?php endif; ?>
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

  // same behavior/animation as contact.php
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

  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
        userDropdown.classList.remove('open');
      }
    });
  }
});
</script>

</body>
</html>
