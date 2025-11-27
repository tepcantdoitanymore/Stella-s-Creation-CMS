<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/admin_security.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'], $_POST['order_id'])) {
    $deleteId = (int)$_POST['order_id'];

    // Get front/back filenames
    $stmt = $pdo->prepare("
        SELECT front_design, back_design 
        FROM orders_tbl 
        WHERE order_id = ?
    ");
    $stmt->execute([$deleteId]);
    $orderRow = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get extra uploaded files
    $g = $pdo->prepare("SELECT filename FROM uploads_tbl WHERE order_id = ?");
    $g->execute([$deleteId]);
    $extraFiles = $g->fetchAll(PDO::FETCH_COLUMN);

    $diskBase = __DIR__ . '/uploads/orders/';

    if ($orderRow) {
        foreach (['front_design','back_design'] as $col) {
            if (!empty($orderRow[$col])) {
                $f = $diskBase . $orderRow[$col];
                if (is_file($f)) @unlink($f);
            }
        }
    }

    foreach ($extraFiles as $fn) {
        $f = $diskBase . $fn;
        if (is_file($f)) @unlink($f);
    }

    $pdo->prepare("DELETE FROM uploads_tbl WHERE order_id = ?")->execute([$deleteId]);
    $pdo->prepare("DELETE FROM orders_tbl   WHERE order_id = ?")->execute([$deleteId]);

    header('Location: manage_orders.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        p.name AS product_name
    FROM orders_tbl o 
    JOIN products_tbl p ON p.product_id = o.product_id 
    WHERE o.order_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$o) {
    http_response_code(404);
    echo "Order not found.";
    exit;
}
$flashMessage = '';

$webBase  = '/uploads/orders/';
$frontUrl = !empty($o['front_design']) ? $webBase . $o['front_design'] : '';
$backUrl  = !empty($o['back_design'])  ? $webBase . $o['back_design']  : '';

$g = $pdo->prepare("SELECT role, filename FROM uploads_tbl WHERE order_id = ? ORDER BY upload_id");
$g->execute([$o['order_id']]);
$gallery = $g->fetchAll(PDO::FETCH_ASSOC);

$designFiles = [];
foreach ($gallery as $img) {
    $role = $img['role'];
    if (in_array($role, ['payment_proof','gcash'], true)) {
        continue;
    }
    $designFiles[] = $webBase . $img['filename'];
}

$needsFrontBack = false;
$nameLower = strtolower($o['product_name'] ?? '');
if (strpos($nameLower, 'keychain') !== false || strpos($nameLower, 'photocard') !== false) {
    $needsFrontBack = true;
}

function parse_notes_lines(?string $notes): array {
    $notes = trim((string)$notes);
    if ($notes === '') return [];
    $parts = preg_split('/[\r\n;]+/', $notes);
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

function render_note_line(string $line): string {
    $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
    return preg_replace_callback(
        '/#([0-9a-fA-F]{6})/',
        function ($m) {
            $hex = '#' . $m[1];
            $hexEsc = htmlspecialchars($hex, ENT_QUOTES, 'UTF-8');
            $dot = '<span class="color-dot" style="background:' . $hexEsc . ';"></span>';
            return $dot . '<span class="color-code">' . $hexEsc . '</span>';
        },
        $safe
    );
}

$noteLines = parse_notes_lines($o['notes'] ?? '');
$dateLabel = date('F j, Y', strtotime($o['order_date']));
$currentPage = 'orders';

$gcashRefText = '';
foreach ($noteLines as $line) {
    $lower = strtolower($line);
    if (strpos($lower, 'gcash') !== false && (strpos($lower, 'ref') !== false || strpos($lower, 'reference') !== false)) {
        $gcashRefText = $line;
        break;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order #<?= (int)$o['order_id'] ?> | Stella's Creation</title>
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
    .order-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:12px;
    }
    .order-title{
      font-size:1.4rem;
      font-weight:600;
      display:flex;
      align-items:center;
      gap:6px;
    }
    .order-sub{
      font-size:.9rem;
      color:#666;
      margin-top:2px;
    }
    .pill-date{
      align-self:flex-start;
      font-size:.8rem;
      padding:4px 12px;
      border-radius:999px;
      background:#fff;
      border:1px solid #f2d9ff;
      color:#8454a0;
    }
    .order-grid{
      display:grid;
      grid-template-columns: minmax(0,1.2fr) minmax(0,1.4fr);
      gap:18px;
    }
    @media (max-width:900px){
      .order-grid{grid-template-columns:1fr;}
    }
    .card{
      background:#fff;
      border-radius:18px;
      padding:18px 18px 16px;
      box-shadow:0 10px 26px rgba(0,0,0,.05);
      text-align:left;
    }
    .card-title{
      font-size:1rem;
      font-weight:600;
      margin-bottom:4px;
    }
    .card-caption{
      font-size:.85rem;
      color:#777;
      margin-bottom:10px;
    }
    .kv-label{
      font-size:.8rem;
      font-weight:600;
      color:#6b587b;
      margin-top:8px;
    }
    .kv-value{
      font-size:.9rem;
      margin-top:2px;
    }
    .status-badge{
      display:inline-flex;
      align-items:center;
      padding:3px 10px;
      border-radius:999px;
      font-size:.78rem;
      font-weight:500;
    }
    .status-Pending   {background:#fff4cc;border:1px solid #f2d98d;color:#856019;}
    .status-Approved  {background:#eaf7ff;border:1px solid #bfe4ff;color:#215c8f;}
    .status-Completed {background:#eaffea;border:1px solid #b8e6b8;color:#1f7a3f;}
    .status-Ready     {background:#f4eaff;border:1px solid #C8A2C8;color:#6d4d8a;}
    .status-Canceled  {background:#ffecec;border:1px solid #ffb3b3;color:#b33939;}
    .status-Cancelled {background:#ffecec;border:1px solid #ffb3b3;color:#b33939;}
    .status-Refunded  {background:#fff3e0;border:1px solid #ffb74d;color:#e65100;}
    .notes-list{
      margin-top:6px;
      padding-left:18px;
      font-size:.9rem;
    }
    .notes-list li{margin-bottom:3px;}
    .color-dot{
      display:inline-block;
      width:14px;
      height:14px;
      border-radius:50%;
      border:2px solid #f3d7ff;
      margin-right:4px;
      vertical-align:middle;
    }
    .color-code{
      font-weight:500;
      font-size:.85rem;
      margin-left:1px;
    }
    .summary-footer{
      margin-top:14px;
      display:flex;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }
    .btn-pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:7px 18px;
      border-radius:999px;
      border:1px solid #e4c8f1;
      background:#f9efff;
      font-size:.85rem;
      text-decoration:none;
      color:#7a5a92;
      box-shadow:0 4px 10px rgba(0,0,0,0.04);
      cursor:pointer;
    }
    .btn-pill:hover{background:#f1e2ff;}
    .btn-danger{
      background:#ffe1ec;
      border-color:#ffc4d7;
      color:#b13f6c;
    }
    .btn-danger:hover{background:#ffcfe0;}
    .thumb-box{
      border-radius:16px;
      border:1px dashed #eddafc;
      background:#fdf7ff;
      padding:10px;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:120px;
      text-align:center;
      font-size:.85rem;
      color:#9c8aa8;
    }
    .thumb-box img{
      max-width:100%;
      border-radius:12px;
      box-shadow:0 6px 16px rgba(0,0,0,0.08);
    }
    .upload-block{
      margin-top:14px;
    }
    .empty-upload-box{
      border-radius:16px;
      border:1px dashed rgba(231,84,128,0.25);
      background:#fff8fe;
      padding:24px;
      text-align:center;
      font-size:.9rem;
      color:#999;
    }
    .upload-grid{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:6px;
    }
    .upload-grid img{
      width:90px;
      height:90px;
      object-fit:cover;
      border-radius:16px;
      box-shadow:0 4px 10px rgba(0,0,0,0.12);
    }
    .gcash-ref-block{
      margin-top:18px;
    }
    .gcash-ref-label{
      font-size:.95rem;
      font-weight:600;
      color:#6b587b;
      margin-bottom:6px;
      letter-spacing:0.02em;
    }
    .gcash-ref-box{
      padding:10px 12px;
      border-radius:14px;
      background:#fff8fe;
      border:1px dashed rgba(231,84,128,0.35);
      font-size:.9rem;
      color:#3A3A3A;
    }
    .flash{
      margin:10px 0;
      padding:8px 12px;
      border-radius:999px;
      font-size:.85rem;
      background:#fff3fe;
      border:1px solid #f2cfff;
      color:#704075;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

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
    @media (max-width: 768px) {
      .nav-main {
        display: none !important;
      }
      .burger {
        display: flex !important;
      }
      .admin-wrap{
        padding:0 16px 28px;
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
      <div class="brand-text">Stella's <span>Creation</span> · Admin</div>
    </div>
    <button class="icon-btn close-btn" id="sidebarClose">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="manage_products.php">Products</a>
    <a href="manage_orders.php" class="nav-active">Orders</a>
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
      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="manage_products.php" class="nav-link">Products</a>
      <a href="manage_orders.php" class="nav-link nav-active">Orders</a>
      <a href="/index.php" class="nav-link" target="_blank">Open Shop</a>
      <a href="logout.php" class="nav-link logout-link">Logout</a>
    </nav>
    <div class="nav-icons">
      <button class="burger" id="burgerBtn">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
<main class="page-main">
  <div class="admin-wrap">
    <section class="order-header">
      <div>
        <div class="order-title">
          Order #<?= (int)$o['order_id'] ?> <span>📦</span>
        </div>
        <div class="order-sub">Detailed view of this customer order and their uploads.</div>
      </div>
      <div class="pill-date"><?= htmlspecialchars($dateLabel) ?></div>
    </section>

    <?php if ($flashMessage): ?>
      <div class="flash">
        <i class="fa-solid fa-circle-info"></i>
        <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <?php if ($o['status'] === 'Ready'): ?>
      <div style="background:#fceeff;border:1px solid #f2c8ff;border-radius:18px;padding:14px 18px;margin-bottom:18px;font-size:.9rem;color:#6d4075;">
        <strong>✨ This order is marked as Ready.</strong> Coordinate pickup/meet-up with the customer via <a href="https://www.facebook.com/stellascreation.ph" target="_blank" style="color:#C8A2C8;font-weight:600;text-decoration:none;">Facebook</a>.
      </div>
    <?php endif; ?>

    <?php if ($o['status'] === 'Refunded'): ?>
      <div style="background:#fff3e0;border:1px solid #ffb74d;border-radius:18px;padding:14px 18px;margin-bottom:18px;font-size:.9rem;color:#b85a0a;">
        <strong>⚠ This order is marked as Refunded.</strong>
        Payment was returned due to an incorrect amount or payment details.
      </div>
    <?php endif; ?>

    <section class="order-grid">
      <div class="card">
        <div class="card-title">Order summary</div>
        <div class="card-caption">Quick snapshot of customer and product details.</div>

        <div class="kv-label">Customer:</div>
        <div class="kv-value"><?= htmlspecialchars($o['customer_name']) ?></div>

        <div class="kv-label">Contact:</div>
        <div class="kv-value">
          <?= htmlspecialchars($o['contact_number']) ?>
          <?php if (!empty($o['email'])): ?>
            &nbsp;•&nbsp;<?= htmlspecialchars($o['email']) ?>
          <?php endif; ?>
        </div>

        <div class="kv-label">Product:</div>
        <div class="kv-value">
          <?= htmlspecialchars($o['product_name']) ?> (x<?= (int)$o['quantity'] ?>)
        </div>

        <div class="kv-label">Status:</div>
        <div class="kv-value">
          <span class="status-badge status-<?= htmlspecialchars($o['status']) ?>">
            <?= htmlspecialchars($o['status']) ?>
          </span>
        </div>

        <?php if ($noteLines): ?>
          <div class="kv-label">Notes:</div>
          <ul class="notes-list">
            <?php foreach ($noteLines as $line): ?>
              <li><?= render_note_line($line) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="summary-footer">
          <a href="manage_orders.php" class="btn-pill">
            <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>Back to orders list
          </a>

          <?php if ($o['status'] === 'Cancelled'): ?>
            <form method="post" onsubmit="return confirm('Remove this order completely? This cannot be undone.');">
              <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
              <button type="submit" name="delete_order" class="btn-pill btn-danger">
                <i class="fa-solid fa-trash" style="margin-right:6px;"></i>Remove order
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Design files &amp; payment details</div>
        <div class="card-caption">Review customer design uploads and GCash reference number.</div>

        <?php if ($needsFrontBack): ?>
          <div class="kv-label">Front design</div>
          <div class="thumb-box">
            <?php if ($frontUrl): ?>
              <img src="<?= htmlspecialchars($frontUrl) ?>" alt="Front design">
            <?php else: ?>
              No front design uploaded yet.
            <?php endif; ?>
          </div>

          <div class="kv-label" style="margin-top:12px;">Back design</div>
          <div class="thumb-box">
            <?php if ($backUrl): ?>
              <img src="<?= htmlspecialchars($backUrl) ?>" alt="Back design">
            <?php else: ?>
              No back design uploaded yet.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="upload-block" style="margin-top:16px;">
          <div class="kv-label" style="margin-top:0;">Design photos (cart uploads)</div>
          <?php if (!empty($designFiles)): ?>
            <div class="upload-grid">
              <?php foreach ($designFiles as $src): ?>
                <a href="<?= htmlspecialchars($src) ?>" target="_blank">
                  <img src="<?= htmlspecialchars($src) ?>" alt="Design photo">
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-upload-box">
              No design photos uploaded yet.
            </div>
          <?php endif; ?>
        </div>

        <?php if ($gcashRefText !== ''): ?>
          <div class="gcash-ref-block">
            <div class="gcash-ref-label">GCash reference number</div>
            <div class="gcash-ref-box">
              <?= htmlspecialchars($gcashRefText, ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
        <?php endif; ?>
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

  // cross-tab forced logout
  window.addEventListener('storage', function (e) {
    if (e.key === 'adminForceLogout') {
      window.location.href = 'admin_login.php';
    }
  });
});
</script>
</body>
</html>
