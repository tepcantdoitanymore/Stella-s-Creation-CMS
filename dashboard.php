<?php
    
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/admin_security.php';

require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Manila');
$today = date("D, M d, Y");

$adminName = $_SESSION['admin_username'] ?? 'Admin';

$prod          = 0;
$total_orders  = 0;
$orders_today  = 0;
$statusCounts  = [
    'Pending'   => 0,
    'Approved'  => 0,
    'Ready'     => 0,
    'Completed' => 0,
    'Cancelled' => 0,
    'Refunded'  => 0,
];

// prepare weekly data skeleton for last 7 days
$weeklyData = [];
$todayObj = new DateTime('today');
for ($i = 6; $i >= 0; $i--) {
    $d = clone $todayObj;
    $d->modify("-{$i} days");
    $key = $d->format('Y-m-d');
    $weeklyData[$key] = [
        'date_key' => $key,
        'label'    => $d->format('M j'),
        'short'    => $d->format('D'),
        'count'    => 0,
    ];
}

try {
    // Active products
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM products_tbl WHERE is_active = 1");
    $prod = (int)($stmt->fetch()['c'] ?? 0);

    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM orders_tbl");
    $total_orders = (int)($stmt->fetch()['c'] ?? 0);

    // Orders today
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM orders_tbl WHERE DATE(order_date) = CURDATE()");
    $orders_today = (int)($stmt->fetch()['c'] ?? 0);

    // Orders by status
    $stmt = $pdo->query("SELECT status, COUNT(*) AS c FROM orders_tbl GROUP BY status");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $status = $row['status'];
        $count  = (int)$row['c'];
        if (isset($statusCounts[$status])) {
            $statusCounts[$status] = $count;
        }
    }

    // Weekly orders (last 7 days)
    $stmt = $pdo->query("
        SELECT DATE(order_date) AS d, COUNT(*) AS c
        FROM orders_tbl
        WHERE order_date >= CURDATE() - INTERVAL 6 DAY
        GROUP BY DATE(order_date)
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $k = $row['d'];
        if (isset($weeklyData[$k])) {
            $weeklyData[$k]['count'] = (int)$row['c'];
        }
    }
} catch (Throwable $e) {
    echo "<pre>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
}

$maxWeekly = 0;
foreach ($weeklyData as $d) {
    if ($d['count'] > $maxWeekly) $maxWeekly = $d['count'];
}
if ($maxWeekly <= 0) $maxWeekly = 1;

$currentPage = 'dashboard';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard | Stella's Creation</title>
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

    body{
      font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--cream);
      color:var(--charcoal);
      min-height:100vh;
      display:flex;
      flex-direction:column;
    }

    .admin-wrap{
      max-width:1200px;
      width:100%;
      margin:24px auto 40px;
      padding:0 20px 40px;
    }

    .admin-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:14px;
      flex-wrap:wrap;
    }
    .admin-header-main h1{
      font-size:1.6rem;
      font-weight:600;
    }
    .admin-header-sub{
      font-size:.9rem;
      color:#666;
      margin-top:4px;
    }
    .admin-tag{
      align-self:flex-start;
      font-size:.8rem;
      padding:4px 12px;
      border-radius:999px;
      background:#fff;
      border:1px solid #f2d9ff;
      color:#8454a0;
    }

    .cards-row{
      margin-top:18px;
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
      gap:18px;
    }

    .card-stat{
      position:relative;
      background:#fff;
      border-radius:18px;
      padding:18px 22px 22px;
      box-shadow:0 12px 32px rgba(0,0,0,.06);
      overflow:hidden;
      text-align:left;
    }
    .card-stat::after{
      content:"";
      position:absolute;
      right:-35px;
      top:-35px;
      width:80px;
      height:80px;
      background:radial-gradient(circle at 30% 30%,#ffe9f5,rgba(200,162,200,0.06));
    }
    .card-label{
      font-size:.8rem;
      color:#7a6f8e;
      margin-bottom:6px;
      display:flex;
      align-items:center;
      gap:6px;
    }
    .card-label i{
      font-size:.9rem;
      color:#C8A2C8;
    }
    .card-value{
      font-size:1.6rem;
      font-weight:600;
      color:#3a2e4d;
    }
    .card-note{
      font-size:.75rem;
      color:#999;
      margin-top:4px;
    }

    .admin-main-grid{
      margin-top:24px;
      display:grid;
      grid-template-columns:2.1fr 1.3fr;
      gap:24px;
      align-items:flex-start;
    }

    .panel{
      background:#fff;
      border-radius:22px;
      padding:24px 24px 26px;
      box-shadow:0 12px 32px rgba(0,0,0,.05);
      text-align:left;
      width:100%;
    }
    .panel-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      margin-bottom:10px;
    }
    .panel-title{
      font-size:1rem;
      font-weight:600;
    }
    .panel-caption{
      font-size:.8rem;
      color:#888;
      margin-top:2px;
    }

    .week-list{
      margin-top:6px;
      list-style:none;
      padding:0;
    }
    .week-row{
      display:flex;
      align-items:center;
      gap:10px;
      font-size:.8rem;
      margin:6px 0;
    }
    .week-day{
      width:40px;
      color:#7d7292;
    }
    .week-bar-wrap{
      flex:1;
      height:14px;
      border-radius:999px;
      background:#f7edf9;
      overflow:hidden;
    }
    .week-bar{
      height:100%;
      border-radius:999px;
      background:linear-gradient(90deg,#F8C8DC,#C8A2C8);
      width:var(--bar-width,0%);
      transition:width .4s ease;
    }
    .week-count{
      width:30px;
      text-align:right;
      font-weight:600;
      color:#4c3d5a;
    }
    .week-empty{
      font-size:.8rem;
      color:#999;
      margin-top:6px;
    }

    .status-list{
      list-style:none;
      margin:8px 0 0;
      padding:0;
    }
    .status-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:8px 0;
      border-bottom:1px solid #f3ebff;
      font-size:.9rem;
    }
    .status-item:last-child{
      border-bottom:none;
    }
    .status-left{
      display:flex;
      align-items:center;
      gap:8px;
    }
    .status-dot{
      width:10px;
      height:10px;
      border-radius:50%;
    }
    .status-dot.pending{background:#f4b000;}
    .status-dot.approved{background:#4a90e2;}
    .status-dot.ready{background:#C8A2C8;}
    .status-dot.completed{background:#53a451;}
    .status-dot.cancelled{background:#ff5b7a;}
    .status-right{
      font-weight:600;
      color:#4c3d5a;
    }

    .quick-links{
      margin-top:14px;
      display:flex;
      flex-direction:column;
      gap:8px;
    }
    .quick-links a{
      text-decoration:none;
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-size:.85rem;
      padding:8px 10px;
      border-radius:12px;
      background:#faf3ff;
      border:1px solid #f0ddff;
      color:#5c3f7d;
    }
    .quick-links a span.right{
      font-size:.9rem;
    }
    .quick-links a:hover{
      background:#f3e5ff;
    }

    @media (max-width:1024px){
      .admin-main-grid{
        grid-template-columns:1fr;
        gap:20px;
      }
    }
    @media (max-width:768px){
      .admin-wrap{
        padding:0 16px 28px;
      }
      .panel{
        padding:18px 16px 22px;
      }
      .cards-row{
        gap:12px;
      }
    }

    /* 🌸 BURGER BUTTON — same as user/contact + manage_products */
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

    /* SHOW BURGER ON MOBILE, hide nav-main */
    @media (max-width: 768px) {
      .nav-main {
        display: none !important;
      }
      .burger {
        display: flex !important;
      }
    }

    /* NAVBAR SPACE (para dili kaayo dikit sa kilid) */
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
      <div class="brand-text">Stella's <span>Creation</span> · Admin</div>
    </div>
    <button class="icon-btn close-btn" id="sidebarClose">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="manage_products.php">Products</a>
    <a href="manage_orders.php">Orders</a>
    <a href="/index.php" target="_blank">Open Shop</a>
    <a href="logout.php">Logout</a>
  </nav>
</aside>

<header class="site-header">
  <div class="container navbar">
    <div class="nav-left">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella's <span>Creation</span> · Admin</div>
    </div>
    <nav class="nav-main">
      <a href="dashboard.php" class="nav-link nav-active">Dashboard</a>
      <a href="manage_products.php" class="nav-link">Products</a>
      <a href="manage_orders.php" class="nav-link">Orders</a>
      <a href="/index.php" class="nav-link" target="_blank">Open Shop</a>
      <a href="logout.php" class="nav-link logout-link">Logout</a>
    </nav>
    <div class="nav-icons">
      <button class="burger" id="burgerBtn" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<main class="page-main">
  <div class="admin-wrap">
    <section class="admin-header">
      <div class="admin-header-main">
        <h1>Hi, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <p class="admin-header-sub">
          Here's a quick snapshot of what's happening in your store today.
        </p>
      </div>
      <div class="admin-tag"><?= date('M j, Y'); ?></div>
    </section>

    <section class="cards-row">
      <div class="card-stat">
        <div class="card-label"><i class="fa-solid fa-tags"></i> Active products</div>
        <div class="card-value"><?= $prod ?></div>
        <div class="card-note">Products currently visible in the shop.</div>
      </div>
      <div class="card-stat">
        <div class="card-label"><i class="fa-solid fa-bag-shopping"></i> Total orders</div>
        <div class="card-value"><?= $total_orders ?></div>
        <div class="card-note">All-time orders placed through Stella's Creation.</div>
      </div>
      <div class="card-stat">
        <div class="card-label"><i class="fa-regular fa-calendar-check"></i> Orders today</div>
        <div class="card-value"><?= $orders_today ?></div>
        <div class="card-note">Orders placed on <?= date('M j'); ?>.</div>
      </div>
    </section>

    <section class="admin-main-grid">
      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Order analytics (7 days)</div>
            <div class="panel-caption">Mini chart of orders for the past week.</div>
          </div>
        </div>
        <ul class="week-list">
          <?php $hasOrders = false; ?>
          <?php foreach ($weeklyData as $d): ?>
            <?php if ($d['count'] > 0) $hasOrders = true;
              $percent = ($d['count'] / $maxWeekly) * 100;
            ?>
            <li class="week-row">
              <div class="week-day"><?= htmlspecialchars($d['short'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="week-bar-wrap">
                <div class="week-bar" style="--bar-width: <?= $percent ?>%;"></div>
              </div>
              <div class="week-count"><?= $d['count'] ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (!$hasOrders): ?>
          <div class="week-empty">No orders yet this week — manifesting new orders ✨</div>
        <?php endif; ?>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Orders overview</div>
            <div class="panel-caption">Quick look at how your orders are moving.</div>
          </div>
        </div>
        <ul class="status-list">
          <li class="status-item">
            <div class="status-left">
              <span class="status-dot pending"></span>
              <span>Pending</span>
            </div>
            <div class="status-right"><?= $statusCounts['Pending'] ?></div>
          </li>
          <li class="status-item">
            <div class="status-left">
              <span class="status-dot approved"></span>
              <span>Approved</span>
            </div>
            <div class="status-right"><?= $statusCounts['Approved'] ?></div>
          </li>
          <li class="status-item">
            <div class="status-left">
              <span class="status-dot ready"></span>
              <span>Ready</span>
            </div>
            <div class="status-right"><?= $statusCounts['Ready'] ?></div>
          </li>
          <li class="status-item">
            <div class="status-left">
              <span class="status-dot completed"></span>
              <span>Completed</span>
            </div>
            <div class="status-right"><?= $statusCounts['Completed'] ?></div>
          </li>
          <li class="status-item">
            <div class="status-left">
              <span class="status-dot cancelled"></span>
              <span>Cancelled</span>
            </div>
            <div class="status-right"><?= $statusCounts['Cancelled'] ?></div>
          </li>
        </ul>
        <div class="quick-links">
          <a href="manage_orders.php">
            <span>Review recent orders</span>
            <span class="right">→</span>
          </a>
          <a href="manage_products.php">
            <span>Add or edit products</span>
            <span class="right">→</span>
          </a>
          <a href="/index.php" target="_blank">
            <span>Open customer shop</span>
            <span class="right">↗</span>
          </a>
        </div>
      </div>
    </section>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella's Creation · Made with love in PH</div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const burgerBtn       = document.getElementById('burgerBtn');
  const sidebar         = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarClose    = document.getElementById('sidebarClose');

  const openSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (sidebarBackdrop) sidebarBackdrop.classList.add('open');
  };
  const closeSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('open');
  };

  // same behavior/animation as contact.php / manage_products
  if (burgerBtn) {
    burgerBtn.addEventListener('click', () => {
      burgerBtn.classList.toggle('open'); // lines -> X
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

  // cross-tab forced logout
  window.addEventListener('storage', function (e) {
    if (e.key === 'adminForceLogout') {
      window.location.href = 'admin_login.php';
    }
  });

  // periodic session check
  setInterval(() => {
    fetch('check_admin_session.php')
      .then(res => res.json())
      .then(data => {
        if (!data.logged_in) {
          window.location.href = 'admin_login.php';
        }
      })
      .catch(err => console.error('Session check error:', err));
  }, 5000);
});
</script>
</body>
</html>
