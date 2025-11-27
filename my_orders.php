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

$cid       = (int)$_SESSION['customer_id'];
$isLogged  = true;
$cartIconHref = "/cart.php";

$allowedStatuses = ['Pending','Approved','Completed','Ready','Cancelled','Refunded'];

$currentStatus = $_GET['status'] ?? 'Pending';
if (!in_array($currentStatus, $allowedStatuses, true)) {
    $currentStatus = 'Pending';
}

$counts = array_fill_keys($allowedStatuses, 0);

$countStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS c
    FROM orders_tbl
    WHERE customer_id = ?
      AND status IN ('Pending','Approved','Completed','Ready','Cancelled','Refunded')
    GROUP BY status
");
$countStmt->execute([$cid]);
while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    $st = $row['status'];
    if (isset($counts[$st])) {
        $counts[$st] = (int)$row['c'];
    }
}

$notifStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders_tbl
    WHERE customer_id = ?
      AND status IN ('Pending','Approved','Completed','Ready','Refunded')
");
$notifStmt->execute([$cid]);
$notifCount = (int)$notifStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT 
        o.order_id,
        o.order_date,
        o.status,
        o.quantity,
        p.name  AS product_name,
        p.price AS product_price,
        p.image AS product_image,
        (o.quantity * p.price) AS total_price
    FROM orders_tbl o
    JOIN products_tbl p ON p.product_id = o.product_id
    WHERE o.customer_id = ?
      AND o.status = ?
    ORDER BY o.order_date DESC, o.order_id DESC
");
$stmt->execute([$cid, $currentStatus]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ordersTotal = 0;
foreach ($orders as $o) {
    $ordersTotal += (float)$o['total_price'];
}

if (!function_exists('nav_link_class')) {
    function nav_link_class($page){
        $currentPage = ''; // we don't highlight main nav on this page
        return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
    }
}

if (!function_exists('product_image')) {
    function product_image($name, $img) {
        $n = strtolower((string)$name);

        if (strpos($n, 'instax mini') !== false)  return '/product_images/Instax Mini.png';
        if (strpos($n, 'instax small') !== false) return '/product_images/Instax Small.png';
        if (strpos($n, 'instax square') !== false)return '/product_images/Instax Square.png';
        if (strpos($n, 'instax wide') !== false)  return '/product_images/Instax Wide.png';

        if (strpos($n, 'spotify') !== false && strpos($n, 'keychain') !== false)
            return '/product_images/Keychain (spotify).png';
        if (strpos($n, 'keychain') !== false && strpos($n, 'instax') !== false)
            return '/product_images/Keychain (instax).png';
        if (strpos($n, 'keychain') !== false && strpos($n, 'big') !== false)
            return '/product_images/Keychain (big).png';
        if (strpos($n, 'keychain') !== false && strpos($n, 'small') !== false)
            return '/product_images/Keychain (small).png';

        if (strpos($n,'holographic') !== false || strpos($n,'rainbow') !== false)
            return '/product_images/holo_rainbow.png';
        if (strpos($n,'glitter') !== false) return '/product_images/glitter.png';
        if (strpos($n,'glossy')  !== false) return '/product_images/glossy.png';
        if (strpos($n,'leather') !== false) return '/product_images/leather.png';
        if (strpos($n,'matte')   !== false) return '/product_images/matte.png';

        if ($img) {
            if (strpos($img, 'http') === 0) return $img;
            return '/uploads/products/' . $img;
        }

        return 'https://via.placeholder.com/600x400.png?text=Stella%27s+Creation';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Orders · Stella's Creation</title>
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
    }

    body {
      background: var(--cream);
    }

    .status-bar-wrap {
      margin-bottom: 18px;
    }
    .status-bar-label {
      font-size: .9rem;
      color: #8a80a0;
      margin-bottom: 6px;
    }

    .status-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;

      scrollbar-color: var(--lavender) #f7efff;
      scrollbar-width: thin;
    }
    .status-tabs::-webkit-scrollbar {
      height: 6px;
    }
    .status-tabs::-webkit-scrollbar-track {
      background: #f7efff;
      border-radius: 999px;
    }
    .status-tabs::-webkit-scrollbar-thumb {
      background: var(--lavender);
      border-radius: 999px;
    }

    .status-chip {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 22px;
      border-radius: 999px;
      border: 1px solid rgba(200,162,200,0.4);
      background: #fff;
      color: #8b6ac7;
      text-decoration: none;
      font-size: .95rem;
      font-weight: 500;
      transition: background .18s ease, border-color .18s ease, color .18s ease, transform .08s ease;
      white-space: nowrap;
    }
    .status-chip:hover {
      transform: translateY(-1px);
      background: #faf5ff;
    }
    .status-chip i {
      font-size: .95rem;
    }
    .status-chip .count-badge {
      min-width: 26px;
      height: 26px;
      border-radius: 999px;
      font-size: .8rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f7edff;
      color: #8b6ac7;
      font-weight: 600;
    }

    .status-chip.active {
      color: #3A3A3A;
      transform: translateY(0);
    }
    .status-chip.active .count-badge {
      background: rgba(255,255,255,0.6);
      color: inherit;
    }

    .status-chip.Pending.active {
      background:#fff7d6;
      border-color:#f2d98d;
      color:#8a6c12;
    }
    .status-chip.Approved.active {
      background:#e0f0ff;
      border-color:#a8d5ff;
      color:#1e5a96;
    }
    .status-chip.Completed.active {
      background:#d4f4dd;
      border-color:#8ed9a3;
      color:#1f6b3a;
    }
    .status-chip.Ready.active {
      background:#f4eaff;
      border-color:#C8A2C8;
      color:#6d4d8a;
    }
    .status-chip.Cancelled.active {
      background:#ffe0e6;
      border-color:#ff9eb3;
      color:#c72043;
    }
    .status-chip.Refunded.active {
      background:#fff3e0;
      border-color:#ffb74d;
      color:#e65100;
    }

    .orders-card {
      background:#fff;
      border-radius:20px;
      border:1px solid #f2e7ff;
      box-shadow:none;
      padding:18px 20px 20px;
    }
    .orders-card h2 {
      margin-top:0;
      margin-bottom:4px;
    }
    .orders-card .hint {
      font-size:.85rem;
      color:#9b86af;
      margin-bottom:12px;
    }

    .orders-list {
      display:flex;
      flex-direction:column;
      gap:12px;
      margin-top:8px;
    }

    .order-card {
      display:grid;
      grid-template-columns: 70px minmax(0,1.7fr) auto;
      column-gap:14px;
      row-gap:4px;
      padding:10px 12px;
      border-radius:16px;
      border:1px solid #f2e7ff;
      background:#fff;
    }

    .order-thumb img {
      width:64px;
      height:64px;
      border-radius:14px;
      object-fit:cover;
      display:block;
    }

    .order-main {
      display:flex;
      flex-direction:column;
      gap:2px;
    }
    .order-id-date {
      font-size:.8rem;
      color:#9b86af;
    }
    .order-id-date strong {
      color:#5d4a7a;
      margin-right:6px;
    }
    .order-product-name {
      font-size:.95rem;
      font-weight:500;
      color:#3A3A3A;
    }
    .order-qty {
      font-size:.78rem;
      color:#7f7f7f;
    }

    .order-meta {
      text-align:right;
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      gap:6px;
    }
    .order-price {
      font-weight:600;
      font-size:.95rem;
      color:#5a3c6f;
      white-space:nowrap;
    }
    .status-pill {
      display:inline-block;
      padding:3px 10px;
      border-radius:999px;
      font-size:.72rem;
      font-weight:600;
    }
    .status-pill.Pending   { background:#fff7d6; border:1px solid #f2d98d; color:#8a6c12; }
    .status-pill.Approved  { background:#e0f0ff; border:1px solid #a8d5ff; color:#1e5a96; }
    .status-pill.Completed { background:#d4f4dd; border:1px solid #8ed9a3; color:#1f6b3a; }
    .status-pill.Ready     { background:#f4eaff; border:1px solid #C8A2C8; color:#6d4d8a; }
    .status-pill.Cancelled { background:#ffe0e6; border:1px solid #ff9eb3; color:#c72043; }
    .status-pill.Refunded  { background:#fff3e0; border:1px solid #ffb74d; color:#e65100; }

    .orders-empty {
      font-size:.9rem;
      color:#777;
      margin-top:6px;
    }

    .orders-total {
      margin-top:12px;
      text-align:right;
      font-size:.95rem;
      color:#6b587b;
    }

    .order-note-line {
      grid-column:1 / -1;
      font-size:.78rem;
      margin-top:4px;
      color:#E75480;
    }
    .order-note-line a {
      color:#E75480;
      font-weight:600;
      text-decoration:underline;
    }

    .order-note-refunded {
      grid-column:1 / -1;
      font-size:.78rem;
      margin-top:4px;
      color:#c72043;
    }

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
      background:var(--charcoal);
      border-radius:4px;
      transition:0.3s ease;
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
    .navbar {
      padding-right:15px;
      padding-left:10px;
    }

    @media (max-width:768px) {
      .nav-main { display:none !important; }
      .burger { display:flex !important; }

      .status-tabs {
        overflow-x: auto;
        padding-bottom: 4px;
        flex-wrap: nowrap;             /* keep chips in one row */
        -webkit-overflow-scrolling: touch;
      }
    }

  </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand-inline">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella's <span>Creation</span></div>
    </div>
    <button class="icon-btn close-btn" id="sidebarClose"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <nav class="sidebar-nav">
    <a href="/index.php">Home</a>
    <a href="/about.php">About</a>
    <a href="/shop.php">Shop</a>
    <a href="/contact.php">Contact</a>
    <a href="/my_orders.php">My Orders</a>
    <a href="<?= htmlspecialchars($cartIconHref) ?>">Cart</a>
  </nav>
</aside>

<header class="site-header">
  <div class="container navbar">
    <div class="nav-left">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella's <span>Creation</span></div>
    </div>

    <nav class="nav-main">
      <a href="/index.php"   class="<?= nav_link_class('home') ?>">Home</a>
      <a href="/about.php"   class="<?= nav_link_class('about') ?>">About</a>
      <a href="/shop.php"    class="<?= nav_link_class('shop') ?>">Shop</a>
      <a href="/contact.php" class="<?= nav_link_class('contact') ?>">Contact</a>
    </nav>

    <div class="nav-icons">
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
              Hello, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>!
            </div>
            <a href="/track_order.php">Track Order</a>
            <a href="/my_orders.php">My Orders</a>
            <a href="/account_info.php">Account Info</a>
            <a href="/change_password.php">Change Password</a>
            <a href="/customer_logout.php" class="logout">Logout</a>
          </div>
        </div>
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
  <div class="container">

    <div class="status-bar-wrap">
      
      <div class="status-tabs">

        <?php
        $icons = [
          'Pending'   => 'fa-clock',
          'Approved'  => 'fa-clipboard-check',
          'Completed' => 'fa-circle-check',
          'Ready'     => 'fa-gift',
          'Cancelled' => 'fa-circle-xmark',
          'Refunded'  => 'fa-rotate-left',
        ];
        foreach ($allowedStatuses as $st):
          $isActive = ($st === $currentStatus);
          $count    = $counts[$st] ?? 0;
          $chipClasses = 'status-chip ' . $st . ($isActive ? ' active' : '');
        ?>
          <a href="?status=<?= urlencode($st) ?>" class="<?= $chipClasses ?>">
            <i class="fa-solid <?= $icons[$st] ?? 'fa-circle' ?>"></i>
            <span><?= htmlspecialchars($st) ?></span>
            <span class="count-badge"><?= (int)$count ?></span>
          </a>
        <?php endforeach; ?>

      </div>
    </div>

    <div class="orders-card">
      <h2>My Orders · <?= htmlspecialchars($currentStatus) ?></h2>
      <div class="hint">
        Showing all orders with status <strong><?= htmlspecialchars($currentStatus) ?></strong>.
      </div>

      <?php if (!$orders): ?>
        <p class="orders-empty">No orders under <strong><?= htmlspecialchars($currentStatus) ?></strong> yet.</p>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($orders as $o): ?>
            <?php
              $orderDateFormatted = '';
              if (!empty($o['order_date'])) {
                  $orderDateFormatted = date("F j, Y", strtotime($o['order_date']));
              }
              $imgSrc = product_image($o['product_name'], $o['product_image']);
            ?>
            <div class="order-card">
              <div class="order-thumb">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($o['product_name']) ?>">
              </div>

              <div class="order-main">
                <div class="order-id-date">
                  <strong>#<?= (int)$o['order_id'] ?></strong>
                  <?= htmlspecialchars($orderDateFormatted) ?>
                </div>
                <div class="order-product-name">
                  <?= htmlspecialchars($o['product_name']) ?>
                </div>
                <div class="order-qty">
                  Qty: <?= (int)$o['quantity'] ?>
                </div>
              </div>

              <div class="order-meta">
                <div class="order-price">
                  ₱<?= number_format((float)$o['total_price'], 2) ?>
                </div>
                <span class="status-pill <?= htmlspecialchars($o['status']) ?>">
                  <?= htmlspecialchars($o['status']) ?>
                </span>
              </div>

              <?php if ($o['status'] === 'Ready'): ?>
                <div class="order-note-line">
                  Your order is ready for pickup/meet-up. Message us on
                  <a href="https://www.facebook.com/stellascreation.ph" target="_blank">Facebook</a>
                  for the details.
                </div>
              <?php endif; ?>

              <?php if ($o['status'] === 'Refunded'): ?>
                <div class="order-note-refunded">
                  This order has been refunded. For questions, please message us on
                  <a href="https://www.facebook.com/stellascreation.ph" target="_blank">Facebook</a>.
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="orders-total">
          <strong>Total for <?= htmlspecialchars($currentStatus) ?>:</strong>
          ₱<?= number_format($ordersTotal, 2) ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella's Creation · Made with love in PH</div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const burgerBtn       = document.getElementById('burgerBtn');
  const sidebar         = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarClose    = document.getElementById('sidebarClose');

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

  const userBtn = document.querySelector('.user-menu-btn');
  const userDropdown = document.querySelector('.user-dropdown');
  if (userBtn && userDropdown) {
    userBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => {
      userDropdown.classList.remove('open');
    });
    userDropdown.addEventListener('click', (e) => e.stopPropagation());
  }
});
</script>

</body>
</html>
