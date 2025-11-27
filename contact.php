<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$isLogged     = !empty($_SESSION['customer_id']);
$currentPage  = 'contact';
$notifCount   = 0;

if ($isLogged) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders_tbl 
        WHERE customer_id = ? 
          AND status IN ('Pending','Approved','Completed', 'Ready' ,'Refunded')
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $notifCount = (int)$stmt->fetchColumn();
}

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$loginChoice   = "/login_choice.php";
$cartIconHref  = $isLogged ? '/cart.php'         : $loginChoice;
$notifIconHref = $isLogged ? '/my_orders.php'    : $loginChoice;
$userIconHref  = $isLogged ? '/account_info.php' : $loginChoice;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contact · Stella’s Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <!-- use relative path like index.php -->
  <link rel="stylesheet" href="style.css?v=1">

  <style>
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

    /* Burger animation when sidebar opens */
    .burger.open span:nth-child(1) {
      transform: translateY(9px) rotate(45deg);
    }
    .burger.open span:nth-child(2) {
      opacity: 0;
    }
    .burger.open span:nth-child(3) {
      transform: translateY(-9px) rotate(-45deg);
    }

    /* SHOW BURGER ON MOBILE */
    @media (max-width: 768px) {
      .nav-main {
        display: none !important;
      }
      .burger {
        display: flex !important;
      }
    }

    /* NAVBAR SPACE */
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
    <?php if ($isLogged): ?>
      <a href="/my_orders.php">My Orders</a>
    <?php endif; ?>

    <!-- 🔐 Cart redirects to login_choice when logged out -->
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

      <!-- 🔐 Bell → login_choice.php -->
      <button class="icon-btn nav-notif-btn"
        aria-label="Notifications"
        onclick="window.location.href='<?= htmlspecialchars($notifIconHref) ?>'">
        <i class="fa-regular fa-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </button>

      <!-- 🔐 User Icon -->
      <?php if ($isLogged): ?>
        <div class="user-dropdown-wrap">
          <button class="icon-btn user-menu-btn">
            <i class="fa-regular fa-user"></i>
          </button>

          <div class="user-dropdown">
            <div class="user-greeting">
              Hello, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>!
            </div>
            <a href="/track_order.php">Track Order</a>
            <a href="/my_orders.php">My Orders</a>
            <a href="/account_info.php">Account Info</a>
            <a href="/change_password.php">Change Password</a>
            <a href="/customer_logout.php" class="logout">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= $loginChoice ?>" class="icon-btn">
          <i class="fa-regular fa-user"></i>
        </a>
      <?php endif; ?>

      <!-- 🔐 Cart Icon -->
      <a href="<?= htmlspecialchars($cartIconHref) ?>" class="icon-btn">
        <i class="fa-solid fa-bag-shopping"></i>
      </a>

      <button class="burger" id="burgerBtn">
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

<main class="page-main">
  <div class="container">
    <div class="card">
      <h2>Contact Us</h2>
      <p class="small">Have questions about your order or custom designs? Send us a message.</p>

      <form method="post" action="contact_submit.php">
        <div class="cd-field">
          <label for="name">Name</label>
          <input type="text" name="name" id="name" required>
        </div>

        <div class="cd-field">
          <label for="email">Email</label>
          <input type="text" name="email" id="email" required>
        </div>

        <div class="cd-field">
          <label for="message">Message</label>
          <textarea name="message" id="message" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn primary">Send Message</button>

        <?php if (!empty($_GET['sent'])): ?>
          <div class="alert success">
            Thank you! We’ve received your message and will get back to you soon.
          </div>
        <?php endif; ?>
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

  // 🔹 Same behavior/animation as other pages
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

  const userBtn = document.querySelector('.user-menu-btn');
  const userDropdown = document.querySelector('.user-dropdown');

  document.addEventListener('click', (e) => {
    if (!userBtn || !userDropdown) return;

    if (userBtn.contains(e.target)) {
      userDropdown.classList.toggle('open');
    } else if (!userDropdown.contains(e.target)) {
      userDropdown.classList.remove('open');
    }
  });
});
</script>
</body>
</html>
