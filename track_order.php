<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$isLogged = !empty($_SESSION['customer_id']);
$notifCount = 0;
$currentPage = ''; // no nav item underlined on this page

if ($isLogged) {
    $cid = (int)$_SESSION['customer_id'];
    $stmtNotif = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders_tbl
        WHERE customer_id = ?
          AND status IN ('Pending','Approved','Completed','Ready', 'Refunded')
    ");
    $stmtNotif->execute([$cid]);
    $notifCount = (int)$stmtNotif->fetchColumn();
}

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$cartIconHref = $isLogged ? "/cart.php" : "/customer_login.php";

$order    = null;
$thumbs   = [];
$msg      = '';
$isError  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id    = (int)($_POST['order_id'] ?? 0);
  $phone = trim($_POST['contact_number'] ?? '');

  if ($id && $phone !== '') {
    $q = $pdo->prepare("
      SELECT o.*, p.name AS product_name 
      FROM orders_tbl o 
      JOIN products_tbl p ON p.product_id = o.product_id 
      WHERE o.order_id = ? AND o.contact_number = ? 
      LIMIT 1
    ");
    $q->execute([$id, $phone]);
    $order = $q->fetch(PDO::FETCH_ASSOC);

    if ($order) {
      if (!empty($order['front_design'])) {
        $thumbs[] = '/uploads/orders/' . $order['front_design'];
      }
      if (!empty($order['back_design'])) {
        $thumbs[] = '/uploads/orders/' . $order['back_design'];
      }

      $g = $pdo->prepare("
        SELECT filename 
        FROM uploads_tbl 
        WHERE order_id = ? 
        ORDER BY upload_id
      ");
      $g->execute([$order['order_id']]);
      foreach ($g as $row) {
        $thumbs[] = '/uploads/orders/' . $row['filename'];
      }

      $msg = 'We found your order 💌';
    } else {
      $msg = 'No matching order found. Please check your Order ID and Contact Number.';
      $isError = true;
    }
  } else {
    $msg = 'Enter both Order ID and Contact Number.';
    $isError = true;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Track Your Order · Stella’s Creation</title>
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
      scrollbar-gutter: stable; /* prevent navbar wiggle */
    }

    html {
      overflow-y: scroll;        /* always reserve scrollbar space */
    }

    body {
      overflow-x: hidden;
    }

    .track-wrap {
      margin-top: 10px;
    }

    .track-header {
      margin-bottom: 10px;
    }

    .track-header h2 {
      font-size: 1.6rem;
      margin-bottom: 4px;
    }

    .track-header p {
      font-size: 0.9rem;
      color: #7b6c82;
    }

    .track-card {
      background:#ffffff;
      border-radius:22px;
      padding:20px 22px 22px;
      box-shadow:0 18px 40px rgba(0,0,0,0.05);
      border:1px solid #f5e3ff;
      margin-top:16px;
    }

    .track-form {
      display:flex;
      flex-wrap:wrap;
      gap:18px;
      align-items:flex-end;
    }

    .track-field {
      flex:1 1 180px;
      min-width:0;
    }

    .track-field label {
      display:block;
      font-size:0.85rem;
      font-weight:500;
      color:#5b4a68;
      margin-bottom:4px;
    }

    .track-field input {
      width:100%;
      border-radius:999px;
      border:1px solid #ecdfff;
      padding:8px 12px;
      font-family:inherit;
      font-size:0.9rem;
      outline:none;
      background:#fefbff;
      transition:0.2s ease;
    }

    .track-field input:focus {
      border-color:#c8a2c8;
      box-shadow:0 0 0 3px rgba(200,162,200,0.2);
      background:#fff;
    }

    .track-btn-wrap {
      flex:0 0 auto;
    }

    .track-btn {
      border:0;
      border-radius:999px;
      padding:10px 24px;
      font-size:0.9rem;
      font-weight:500;
      background:linear-gradient(135deg,#F8C8DC,#C8A2C8);
      color:#fff;
      cursor:pointer;
      box-shadow:0 12px 24px rgba(200,162,200,0.4);
      transition:transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
      white-space:nowrap;
    }

    .track-btn:hover {
      transform:translateY(-1px);
      box-shadow:0 16px 30px rgba(200,162,200,0.6);
      filter:brightness(1.03);
    }

    .track-btn:active {
      transform:translateY(0);
      box-shadow:0 8px 18px rgba(200,162,200,0.5);
    }

    .track-message {
      margin-top:12px;
      font-size:0.88rem;
      padding:8px 12px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      gap:8px;
    }

    .track-message.error {
      background:#ffe5ea;
      color:#b24a63;
      border:1px solid #ffc5d1;
    }

    .track-message.ok {
      background:#e9f8ff;
      color:#336f93;
      border:1px solid #c3e7ff;
    }

    .track-message i {
      font-size:0.9rem;
    }

    .order-summary-card {
      margin-top:18px;
      background:#ffffff;
      border-radius:22px;
      padding:18px 20px 20px;
      border:1px solid #f5e3ff;
      box-shadow:0 18px 40px rgba(0,0,0,0.04);
    }

    .order-summary-top {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px;
      margin-bottom:12px;
      flex-wrap:wrap;
    }

    .order-summary-id {
      font-weight:600;
      font-size:1.05rem;
      color:#5d4a7a;
    }

    .order-summary-date {
      font-size:0.85rem;
      color:#9b86af;
      margin-top:2px;
    }

    .order-summary-status {
      text-align:right;
    }

    .status-pill {
      display:inline-block;
      padding:4px 12px;
      border-radius:999px;
      font-size:0.75rem;
      font-weight:600;
    }
    .status-pill.Pending {
      background:#fff5cc;
      color:#a67a00;
    }
    .status-pill.Processing {
      background:#fff0dc;
      color:#b86b12;
    }
    .status-pill.Approved {
      background:#e7f4ff;
      color:#356a9e;
    }
    .status-pill.Completed {
      background:#eaffea;
      color:#2b7a3f;
    }
    .status-pill.Canceled,
    .status-pill.Cancelled {
      background:#ffe6eb;
      color:#b94664;
    }
    .status-pill.Ready {
      background:#f4eaff;
      color:#6d4d8a;
    }

    .order-summary-main {
      font-size:0.92rem;
      color:#3a3a3a;
      display:flex;
      flex-wrap:wrap;
      gap:12px 20px;
      align-items:center;
    }

    .order-summary-main b {
      font-weight:600;
    }

    .thumbs-wrap {
      margin-top:16px;
    }

    .thumbs-title {
      font-size:0.88rem;
      font-weight:600;
      color:#5b4a68;
      margin-bottom:8px;
    }

    .thumbs-row {
      display:flex;
      gap:12px;
      flex-wrap:wrap;
    }

    .thumb-item {
      width:96px;
      height:96px;
      border-radius:18px;
      overflow:hidden;
      border:1px solid #f1ddff;
      background:#fdf7ff;
      position:relative;
      cursor:pointer;
      box-shadow:0 6px 16px rgba(0,0,0,0.05);
      transition:transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .thumb-item img {
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }

    .thumb-item:hover {
      transform:translateY(-2px) scale(1.03);
      box-shadow:0 12px 24px rgba(200,162,200,0.45);
      border-color:#d6b4ff;
    }

    .thumb-badge {
      position:absolute;
      bottom:6px;
      left:6px;
      padding:2px 7px;
      font-size:0.65rem;
      border-radius:999px;
      background:rgba(0,0,0,0.55);
      color:#fff;
    }

    .orders-empty-note {
      margin-top:6px;
      font-size:0.85rem;
      color:#777;
    }

    @media (max-width:768px){
      .track-form {
        flex-direction:column;
        align-items:stretch;
      }
      .track-btn-wrap {
        width:100%;
      }
      .track-btn {
        width:100%;
        text-align:center;
        justify-content:center;
      }
      .order-summary-top {
        flex-direction:column;
        align-items:flex-start;
      }
      .order-summary-status {
        text-align:left;
      }
    }

    /* BURGER BUTTON — same as contact.php / index.php */
    .burger {
      display:none;
      width:28px;
      height:22px;
      flex-direction:column;
      justify-content:space-between;
      background:none;
      border:none;
      padding:0;
      cursor:pointer;
      margin-right:10px;
    }

    .burger span {
      width:100%;
      height:3px;
      background:var(--dark, #333);
      border-radius:4px;
      transition:0.3s ease;
    }

    .burger.open span:nth-child(1) {
      transform:translateY(9px) rotate(45deg);
    }
    .burger.open span:nth-child(2) {
      opacity:0;
    }
    .burger.open span:nth-child(3) {
      transform:translateY(-9px) rotate(-45deg);
    }

    @media (max-width:768px) {
      .nav-main {
        display:none !important;
      }
      .burger {
        display:flex !important;
      }
    }

    .navbar {
      padding-right:15px;
      padding-left:10px;
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
          <button class="icon-btn user-menu-btn" aria-label="User Menu">
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

<main class="page-main">
  <div class="container track-wrap">
    <div class="track-header">
      <h2>Track Your Order</h2>
      <p>Enter your Order ID and the contact number you used at checkout.</p>
    </div>

    <div class="track-card">
      <form method="post" class="track-form">
        <div class="track-field">
          <label for="order_id">Order ID</label>
          <input type="number" id="order_id" name="order_id" min="1" required>
        </div>

        <div class="track-field">
          <label for="contact_number">Contact Number</label>
          <input type="tel" id="contact_number" name="contact_number"
                 required placeholder="e.g., 09123456789">
        </div>

        <div class="track-btn-wrap">
          <button class="track-btn" type="submit">
            Check Status
          </button>
        </div>
      </form>

      <?php if ($msg): ?>
        <div class="track-message <?= $isError ? 'error' : 'ok' ?>">
          <i class="fa-solid <?= $isError ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
          <span><?= htmlspecialchars($msg) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($order): ?>
      <?php
        $formattedDate = '';
        if (!empty($order['order_date'])) {
          $formattedDate = date("F j, Y", strtotime($order['order_date']));
        }
      ?>
      <div class="order-summary-card">
        <div class="order-summary-top">
          <div>
            <div class="order-summary-id">Order #<?= (int)$order['order_id'] ?></div>
            <?php if ($formattedDate): ?>
              <div class="order-summary-date"><?= htmlspecialchars($formattedDate) ?></div>
            <?php endif; ?>
          </div>
          <div class="order-summary-status">
            <span class="status-pill <?= htmlspecialchars($order['status']) ?>">
              <?= htmlspecialchars($order['status']) ?>
            </span>
          </div>
        </div>

        <div class="order-summary-main">
          <div><b>Product:</b> <?= htmlspecialchars($order['product_name']) ?></div>
          <div><b>Qty:</b> <?= (int)$order['quantity'] ?></div>
          <?php if (isset($order['quantity'], $order['price'])): ?>
            <div>
              <b>Total:</b> ₱<?= number_format((float)$order['quantity'] * (float)$order['price'], 2) ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($thumbs): ?>
          <div class="thumbs-wrap">
            <div class="thumbs-title">Order Photos</div>
            <div class="thumbs-row">
              <?php foreach ($thumbs as $index => $src): ?>
                <div class="thumb-item">
                  <img src="<?= htmlspecialchars($src) ?>" alt="Order photo <?= $index+1 ?>">
                  <?php if ($index === 0): ?>
                    <div class="thumb-badge">Main</div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <p class="orders-empty-note">
            No photos found for this order yet. If you uploaded designs, they may still be processing. ✨
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
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

  // same burger behavior / animation as contact.php
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
