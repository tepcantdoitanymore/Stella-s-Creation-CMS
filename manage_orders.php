<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/admin_security.php';
require_once __DIR__ . '/db.php';

/* ---------- GALLERY JSON ENDPOINT ---------- */
if (isset($_GET['action']) && $_GET['action'] === 'gallery') {
  $oid = (int)($_GET['id'] ?? 0);
  $stmt = $pdo->prepare("SELECT front_design, back_design FROM orders_tbl WHERE order_id=?");
  $stmt->execute([$oid]);
  $o = $stmt->fetch(PDO::FETCH_ASSOC);
  $rows = [];
  if ($o) {
    $base = '/uploads/orders/';
    if (!empty($o['front_design'])) $rows[] = $base . $o['front_design'];
    if (!empty($o['back_design']))  $rows[] = $base . $o['back_design'];
    $g = $pdo->prepare("SELECT filename FROM uploads_tbl WHERE order_id=? ORDER BY upload_id");
    $g->execute([$oid]);
    foreach ($g as $r) $rows[] = $base . $r['filename'];
  }
  header('Content-Type: application/json');
  echo json_encode($rows);
  exit;
}

/* ---------- UPDATE STATUS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
  $oid    = (int)$_POST['order_id'];
  $status = trim($_POST['status']);
  $ok     = ['Pending','Approved','Completed','Ready','Cancelled','Refunded'];

  if (!in_array($status, $ok, true)) {
    $status = 'Pending';
  }

  $u = $pdo->prepare("UPDATE orders_tbl SET status=? WHERE order_id=?");
  $u->execute([$status, $oid]);

  header('Location: manage_orders.php');
  exit;
}

/* ---------- FILTER & SORT ---------- */
$validStatuses = ['','Pending','Approved','Completed','Ready','Cancelled','Refunded'];
$filterStatus = $_GET['status'] ?? '';
if (!in_array($filterStatus, $validStatuses, true)) $filterStatus = '';

$validSort = ['date_desc','date_asc','name_asc','name_desc'];
$sort = $_GET['sort'] ?? 'date_desc';
if (!in_array($sort, $validSort, true)) $sort = 'date_desc';

$orderBy = 'o.order_date DESC, o.order_id DESC';
if ($sort === 'date_asc')  $orderBy = 'o.order_date ASC, o.order_id ASC';
if ($sort === 'name_asc')  $orderBy = 'cust_name ASC, o.order_date DESC';
if ($sort === 'name_desc') $orderBy = 'cust_name DESC, o.order_date DESC';

$where  = '';
$params = [];
if ($filterStatus !== '') {
  $where    = 'WHERE o.status = ?';
  $params[] = $filterStatus;
}

/* ---------- LOAD ORDERS ---------- */
$sql = "
SELECT 
  o.order_id,
  o.order_date,
  o.customer_id,
  o.customer_name,
  o.contact_number, 
  o.product_id,
  o.quantity,
  o.front_design,
  o.back_design,
  o.status,
  p.name AS product_name,
  COALESCE(c.fullname, o.customer_name) AS cust_name,
  (COALESCE(u.cnt, 0) 
    + CASE WHEN COALESCE(o.front_design, '') != '' THEN 1 ELSE 0 END
    + CASE WHEN COALESCE(o.back_design, '') != '' THEN 1 ELSE 0 END) AS photo_count
FROM orders_tbl o
JOIN products_tbl p 
  ON p.product_id = o.product_id
LEFT JOIN customers_tbl c 
  ON c.customer_id = o.customer_id
LEFT JOIN (
    SELECT order_id, COUNT(*) AS cnt 
    FROM uploads_tbl 
    GROUP BY order_id
) u 
  ON u.order_id = o.order_id
{$where}
ORDER BY {$orderBy}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'orders';
$adminName   = $_SESSION['admin_username'] ?? 'Admin';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Orders | Stella's Creation</title>
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

    .panel{
      background:#fff;
      border-radius:18px;
      padding:16px 18px 18px;
      box-shadow:0 10px 26px rgba(0,0,0,.05);
      text-align:left;
      margin-top:18px;
    }
    .panel-title{
      font-size:1rem;
      font-weight:600;
      margin-bottom:4px;
    }
    .panel-caption{
      font-size:.8rem;
      color:#888;
      margin-bottom:10px;
    }

    .toolbar{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
      margin-bottom:12px;
      font-size:.85rem;
    }
    .toolbar label{
      display:flex;
      align-items:center;
      gap:6px;
    }
    .toolbar select{
      border-radius:999px;
      border:1px solid #ead7ff;
      padding:4px 10px;
      font-family:inherit;
      font-size:.85rem;
      background:#fff;
    }
    .btn-filter{
      border-radius:999px;
      border:none;
      padding:6px 14px;
      font-size:.85rem;
      background:linear-gradient(135deg,#F8C8DC,#C8A2C8);
      color:#fff;
      cursor:pointer;
      font-weight:500;
    }
    .reset-link{
      font-size:.8rem;
      color:#7a68b1;
      text-decoration:none;
    }

    .orders-table{
      width:100%;
      border-collapse:collapse;
      font-size:.84rem;
    }
    .orders-table th,
    .orders-table td{
      padding:8px 8px;
      border-bottom:1px solid #f1e4ff;
      text-align:left;
      vertical-align:top;
    }
    .orders-table thead th{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.04em;
      color:#8d7ea4;
      background:#faf4ff;
      position:sticky;
      top:0;
      z-index:1;
    }
    .thumb{
      width:64px;
      height:64px;
      border-radius:10px;
      object-fit:cover;
      border:1px solid #e4d5ff;
    }

    .badge{
      display:inline-flex;
      align-items:center;
      padding:2px 9px;
      border-radius:999px;
      font-size:.75rem;
      font-weight:500;
    }
    .Pending    { background:#fff7d6; border:1px solid #f2d98d; color:#8a6c12; }
    .Approved   { background:#e0f0ff; border:1px solid #a8d5ff; color:#1e5a96; }
    .Completed  { background:#d4f4dd; border:1px solid #8ed9a3; color:#1f6b3a; }
    .Ready      { background:#f4eaff; border:1px solid #C8A2C8; color:#6d4d8a; }
    .Cancelled  { background:#ffe0e6; border:1px solid #ff9eb3; color:#c72043; }
    .Refunded   { background:#fff3e0; border:1px solid #ffb74d; color:#e65100; }

    .status-select{
      margin-top:6px;
      border-radius:999px;
      border:1px solid #ead7ff;
      padding:4px 8px;
      font-size:.78rem;
      background:#fff;
      font-family:inherit;
    }

    .btn-gallery{
      border-radius:999px;
      border:1px solid #ead7ff;
      padding:4px 10px;
      font-size:.75rem;
      background:#faf4ff;
      cursor:pointer;
      margin-top:4px;
    }
    .btn-gallery:hover{
      background:#f0e4ff;
    }

    .link-open{
      font-size:.8rem;
      color:#7c63c4;
      text-decoration:none;
    }

    /* modal */
    .modal-backdrop{
      position:fixed;
      inset:0;
      background:rgba(0,0,0,0.6);
      opacity:0;
      visibility:hidden;
      transition:.2s ease;
      z-index:60;
    }
    .modal-backdrop.show{
      opacity:1;
      visibility:visible;
    }
    .modal{
      position:fixed;
      inset:0;
      display:flex;
      align-items:center;
      justify-content:center;
      opacity:0;
      visibility:hidden;
      transition:.2s ease;
      z-index:61;
    }
    .modal.show{
      opacity:1;
      visibility:visible;
    }
    .modal-inner{
      background:#fff;
      max-width:90vw;
      max-height:90vh;
      padding:14px;
      overflow:auto;
      border-radius:16px;
      box-shadow:0 12px 30px rgba(0,0,0,0.25);
    }
    .gallery{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:10px;
    }
    .gallery img{
      max-width:220px;
      height:auto;
      border-radius:10px;
      border:1px solid #eee;
    }
    .closebtn{
      float:right;
      cursor:pointer;
      border:none;
      background:#f6f0ff;
      padding:4px 10px;
      border-radius:999px;
      font-size:.8rem;
    }

    /* 🌸 BURGER BUTTON */
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
      margin-right: 10px;
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

    /* NAVBAR SPACE */
    .navbar {
      padding-right: 15px;
      padding-left: 10px;
    }

    @media (max-width:768px){
      .admin-wrap{
        padding:0 16px 28px;
      }

      .orders-table{
        border-collapse:separate;
        border-spacing:0 8px;
        font-size:0.8rem;
      }
      .orders-table thead{
        display:none;
      }
      .orders-table,
      .orders-table tbody,
      .orders-table tr,
      .orders-table td{
        display:block;
        width:100%;
      }
      .orders-table tr{
        background:#fff;
        border-radius:14px;
        padding:8px 10px 10px;
        box-shadow:0 6px 18px rgba(0,0,0,0.04);
        border:1px solid #f1e4ff;
      }
      .orders-table td{
        border-bottom:none;
        padding:4px 0;
      }
      .orders-table td::before{
        content:attr(data-label);
        display:block;
        font-size:0.7rem;
        text-transform:uppercase;
        letter-spacing:0.04em;
        color:#9a87b6;
        margin-bottom:1px;
      }
      .orders-table td[data-label="ID"]{
        font-weight:600;
        font-size:0.85rem;
      }
      .thumb{
        width:52px;
        height:52px;
      }

      .nav-main {
        display: none !important;
      }
      .burger {
        display: flex !important;
      }
    }
  </style>
</head>
<body>

<!-- SIDE NAV -->
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

<!-- TOP NAV -->
<header class="site-header">
  <div class="container navbar">
    <div class="nav-left">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella's <span>Creation</span> · Admin</div>
    </div>
    <nav class="nav-main">
      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="manage_products.php" class="nav-link">Products</a>
      <a href="manage_orders.php" class="nav-link nav-active">Orders</a>
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
        <h1>Manage Orders</h1>
        <p class="admin-header-sub">
          Filter by status, check photos, and update progress in one place.
        </p>
      </div>
      <div class="admin-tag"><?= date('M j, Y'); ?></div>
    </section>

    <section class="panel">
      <div class="panel-title">Orders list</div>
      <div class="panel-caption">
        Use the filters to quickly find which orders need attention.
      </div>

      <form method="get" class="toolbar">
        <label>
          Status
          <select name="status">
            <option value="" <?= $filterStatus===''?'selected':'' ?>>All</option>
            <option value="Pending"   <?= $filterStatus==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Approved"  <?= $filterStatus==='Approved'?'selected':'' ?>>Approved</option>
            <option value="Completed" <?= $filterStatus==='Completed'?'selected':'' ?>>Completed</option>
            <option value="Ready"     <?= $filterStatus==='Ready'?'selected':'' ?>>Ready</option>
            <option value="Cancelled" <?= $filterStatus==='Cancelled'?'selected':'' ?>>Cancelled</option>
            <option value="Refunded"  <?= $filterStatus==='Refunded'?'selected':'' ?>>Refunded</option>
          </select>
        </label>
        <label>
          Sort
          <select name="sort">
            <option value="date_desc" <?= $sort==='date_desc'?'selected':'' ?>>Newest first</option>
            <option value="date_asc"  <?= $sort==='date_asc'?'selected':'' ?>>Oldest first</option>
            <option value="name_asc"  <?= $sort==='name_asc'?'selected':'' ?>>Customer A→Z</option>
            <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Customer Z→A</option>
          </select>
        </label>
        <button type="submit" class="btn-filter">Apply</button>
        <?php if ($filterStatus!=='' || $sort!=='date_desc'): ?>
          <a href="manage_orders.php" class="reset-link">Reset</a>
        <?php endif; ?>
      </form>

      <div style="overflow-x:auto; margin-top:8px;">
        <table class="orders-table">
          <thead>
            <tr>
              <th style="width:55px;">ID</th>
              <th style="width:115px;">Date</th>
              <th>Customer</th>
              <th>Product</th>
              <th style="width:50px;">Qty</th>
              <th style="width:90px;">Photos</th>
              <th style="width:80px;">Thumbnail</th>
              <th style="width:140px;">Status</th>
              <th style="width:70px;">View</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <?php
              $thumb = '';
              if (!empty($o['front_design']))      $thumb = '/uploads/orders/' . $o['front_design'];
              elseif (!empty($o['back_design']))   $thumb = '/uploads/orders/' . $o['back_design'];

              $orderDateFormatted = '';
              if (!empty($o['order_date'])) {
                $orderDateFormatted = date("F j, Y", strtotime($o['order_date']));
              }

              // pick best name: fullname > customer_name > "Customer #ID"
              $customerLabel = $o['cust_name'] ?? '';
              if ($customerLabel === null || $customerLabel === '') {
                $customerLabel = 'Customer #' . (int)$o['customer_id'];
              }

              // safety: ensure status is valid
              $validStatusesRow = ['Pending','Approved','Completed','Ready','Cancelled','Refunded'];
              $o['status'] = trim((string)$o['status']);
              if (!in_array($o['status'], $validStatusesRow, true)) {
                $o['status'] = 'Pending';
              }
              $statusClass = $o['status'];
            ?>
            <tr>
              <td data-label="ID">#<?= (int)$o['order_id'] ?></td>
              <td data-label="Date"><?= htmlspecialchars($orderDateFormatted) ?></td>
              <td data-label="Customer">
                <div style="font-weight:500;"><?= htmlspecialchars($customerLabel) ?></div>
                <div style="color:#666; font-size:12px;"><?= htmlspecialchars($o['contact_number']) ?></div>
              </td>
              <td data-label="Product"><?= htmlspecialchars($o['product_name']) ?></td>
              <td data-label="Qty"><?= (int)$o['quantity'] ?></td>
              <td data-label="Photos">
                <?= (int)$o['photo_count'] ?> files
                <br>
                <button type="button" class="btn-gallery" onclick="openGallery(<?= (int)$o['order_id'] ?>)">
                  View gallery
                </button>
              </td>
              <td data-label="Thumbnail">
                <?php if ($thumb): ?>
                  <img class="thumb" src="<?= htmlspecialchars($thumb) ?>" alt="thumb">
                <?php else: ?>
                  <span style="color:#888;">—</span>
                <?php endif; ?>
              </td>
              <td data-label="Status">
                <span class="badge <?= htmlspecialchars($statusClass) ?>">
                  <?= htmlspecialchars($o['status']) ?>
                </span>
                <form method="post">
                  <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                  <select name="status" class="status-select" onchange="this.form.submit()">
                    <?php foreach (['Pending','Approved','Completed','Ready','Cancelled','Refunded'] as $s): ?>
                      <option value="<?= $s ?>" <?= $s===$o['status']?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td data-label="View">
                <a class="link-open" href="view_order.php?id=<?= (int)$o['order_id'] ?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$orders): ?>
            <tr>
              <td colspan="9" style="text-align:center;color:#777;padding:16px 4px;">
                No orders found for this filter.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella's Creation · Made with love in PH</div>
  </div>
</footer>

<!-- MODAL FOR GALLERY -->
<div class="modal-backdrop" id="backdrop"></div>
<div class="modal" id="modal">
  <div class="modal-inner">
    <button class="closebtn" onclick="closeGallery()">Close ✕</button>
    <h3 style="margin-top:8px;">Order gallery</h3>
    <div class="gallery" id="gallery"></div>
  </div>
</div>

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

  // cross-tab forced logout
  window.addEventListener('storage', function (e) {
    if (e.key === 'adminForceLogout') {
      window.location.href = 'admin_login.php';
    }
  });
});

// gallery modal
function openGallery(orderId){
  fetch('manage_orders.php?action=gallery&id=' + orderId)
    .then(r => r.json())
    .then(list => {
      const wrap = document.getElementById('gallery');
      wrap.innerHTML = '';
      if (!list || !list.length){
        wrap.innerHTML = '<div style="color:#666;">No images uploaded.</div>';
      } else {
        list.forEach(u => {
          const img = document.createElement('img');
          img.src = u;
          wrap.appendChild(img);
        });
      }
      document.getElementById('backdrop').classList.add('show');
      document.getElementById('modal').classList.add('show');
    })
    .catch(() => { alert('Unable to load gallery.'); });
}
function closeGallery(){
  document.getElementById('backdrop').classList.remove('show');
  document.getElementById('modal').classList.remove('show');
}
</script>

</body>
</html>
