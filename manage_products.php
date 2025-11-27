<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/admin_security.php';
require_once __DIR__ . '/db.php';

$flashMessage = '';
$flashType = 'success'; // success or error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // TOGGLE ACTIVE / HIDDEN
    if (isset($_POST['toggle_id'])) {
        $pid = (int)$_POST['toggle_id'];
        $stmt = $pdo->prepare("UPDATE products_tbl SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE product_id = ?");
        $stmt->execute([$pid]);
        $flashMessage = 'Product visibility updated.';
        $flashType = 'success';
    }

    // DELETE PRODUCT
    if (isset($_POST['delete_id'])) {
        $pid = (int)$_POST['delete_id'];
        
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT image FROM products_tbl WHERE product_id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products_tbl WHERE product_id = ?");
        $stmt->execute([$pid]);
        
        // Delete image file if exists
        if ($product && !empty($product['image'])) {
            $imagePath = __DIR__ . '/' . $product['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $flashMessage = 'Product deleted successfully.';
        $flashType = 'success';
    }
}

// ------------------ FILTERS & SORT ------------------
$group = $_GET['group'] ?? 'all';
$allowedGroups = ['all','keychain','instax','photocard','refmagnet'];
if (!in_array($group, $allowedGroups, true)) {
    $group = 'all';
}

$sort = $_GET['sort'] ?? 'newest';
$allowedSorts = ['newest','oldest','price_low','price_high'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$search = trim($_GET['q'] ?? '');

// build WHERE
$whereParts = [];
$params     = [];

if ($group !== 'all') {
    $keywordMap = [
        'keychain'   => 'keychain',
        'instax'     => 'instax',
        'photocard'  => 'photocard',
        'refmagnet'  => 'ref magnet'
    ];
    $kw = $keywordMap[$group] ?? '';
    if ($kw !== '') {
        $whereParts[] = "name LIKE ?";
        $params[]     = '%' . $kw . '%';
    }
}

if ($search !== '') {
    $whereParts[] = "(name LIKE ? OR description LIKE ?)";
    $params[]     = '%' . $search . '%';
    $params[]     = '%' . $search . '%';
}

$whereSql = '';
if ($whereParts) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
}

// ORDER BY
$orderBy = 'product_id DESC'; // newest
if ($sort === 'oldest') {
    $orderBy = 'product_id ASC';
} elseif ($sort === 'price_low') {
    $orderBy = 'price ASC, product_id ASC';
} elseif ($sort === 'price_high') {
    $orderBy = 'price DESC, product_id DESC';
}

// load products
$sql = "
  SELECT product_id, name, description, price, is_active, image
  FROM products_tbl
  {$whereSql}
  ORDER BY {$orderBy}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'products';
$adminName   = $_SESSION['admin_username'] ?? 'Admin';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Products | Stella's Creation</title>
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

    .flash{
      margin-top:12px;
      padding:8px 12px;
      border-radius:999px;
      font-size:.85rem;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }
    .flash.success{
      background:#fff3fe;
      border:1px solid #f2cfff;
      color:#704075;
    }
    .flash.error{
      background:#fff0f0;
      border:1px solid #ffcccc;
      color:#a03030;
    }

    .panel{
      background:#fff;
      border-radius:18px;
      padding:16px 18px 18px;
      box-shadow:0 10px 26px rgba(0,0,0,.05);
      text-align:left;
      margin-top:20px;
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

    /* FILTER BAR */
    .product-filters{
      display:flex;
      flex-wrap:wrap;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-bottom:10px;
    }
    .filter-chips{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
    }
    .chip-filter{
      display:inline-flex;
      align-items:center;
      gap:5px;
      padding:5px 12px;
      border-radius:999px;
      font-size:.78rem;
      border:1px solid #eddcff;
      background:#faf5ff;
      color:#73588e;
      text-decoration:none;
      white-space:nowrap;
    }
    .chip-filter.active{
      background:var(--lavender);
      color:var(--white);
      border-color:var(--lavender);
      box-shadow:0 3px 8px rgba(200,162,200,0.4);
    }

    .filter-search{
      display:flex;
      align-items:center;
      gap:6px;
      flex-wrap:wrap;
    }
    .filter-search select{
      border-radius:999px;
      border:1px solid #eddcff;
      padding:6px 10px;
      font-size:.78rem;
      background:#fff;
      outline:none;
      min-width:150px;
      color:#3A3A3A;
    }
    .filter-search .search-pill{
      display:flex;
      align-items:center;
      border-radius:999px;
      padding:4px 10px;
      border:1px solid #eddcff;
      background:#fff;
      gap:6px;
    }
    .filter-search input[type="text"]{
      border:none;
      outline:none;
      font-size:.8rem;
      min-width:150px;
      background:transparent;
    }

    .products-table{
      width:100%;
      border-collapse:collapse;
      font-size:.85rem;
    }
    .products-table th,
    .products-table td{
      padding:8px 8px;
      border-bottom:1px solid #f1e4ff;
      text-align:left;
      vertical-align:top;
    }
    .products-table thead th{
      font-size:.8rem;
      text-transform:uppercase;
      letter-spacing:.04em;
      color:#8d7ea4;
      background:#faf4ff;
    }

    .product-img-thumb{
      width:50px;
      height:50px;
      object-fit:cover;
      border-radius:8px;
      border:1px solid #f1e4ff;
    }

    .badge{
      display:inline-flex;
      align-items:center;
      padding:2px 9px;
      border-radius:999px;
      font-size:.75rem;
      font-weight:500;
    }
    .badge.on{
      background:#eafff1;
      color:#2c7b4b;
      border:1px solid #b8ecc8;
    }
    .badge.off{
      background:#ffeef4;
      color:#b24565;
      border:1px solid #ffc4da;
    }

    .btn-toggle{
      border-radius:999px;
      padding:4px 10px;
      border:1px solid #ead7ff;
      background:#faf4ff;
      font-size:.75rem;
      cursor:pointer;
      margin-top:4px;
    }
    .btn-toggle:hover{
      background:var(--rose);
      color:var(--white);
      border-color:var(--rose);
    }

    .btn-delete{
      border-radius:999px;
      padding:4px 10px;
      border:1px solid #ffcccc;
      background:#fff5f5;
      font-size:.75rem;
      cursor:pointer;
      margin-top:4px;
      color:#c53030;
    }
    .btn-delete:hover{
      background:#c53030;
      color:var(--white);
      border-color:#c53030;
    }

    .action-buttons{
      display:flex;
      gap:6px;
      flex-wrap:wrap;
    }

    @media (max-width:768px){
      .admin-wrap{
        padding:0 16px 28px;
      }

      .filter-search input[type="text"]{
        min-width:120px;
      }

      .products-table{
        border-collapse:separate;
        border-spacing:0 8px;
        font-size:0.8rem;
      }
      .products-table thead{display:none;}
      .products-table,
      .products-table tbody,
      .products-table tr,
      .products-table td{
        display:block;
        width:100%;
      }
      .products-table tr{
        background:#fff;
        border-radius:14px;
        padding:8px 10px 10px;
        box-shadow:0 6px 18px rgba(0,0,0,0.04);
        border:1px solid #f1e4ff;
      }
      .products-table td{
        border-bottom:none;
        padding:4px 0;
      }
      .products-table td::before{
        content:attr(data-label);
        display:block;
        font-size:0.7rem;
        text-transform:uppercase;
        letter-spacing:0.04em;
        color:#9a87b6;
        margin-bottom:1px;
      }
      .products-table td[data-label="ID"]{
        font-weight:600;
        font-size:0.85rem;
      }
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
      <a href="dashboard.php" class="nav-link">Dashboard</a>
      <a href="manage_products.php" class="nav-link nav-active">Products</a>
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
        <h1>Manage Products</h1>
        <p class="admin-header-sub">
          View and manage product visibility without touching the customer shop layout.
        </p>
      </div>
      <div class="admin-tag"><?= date('M j, Y'); ?></div>
    </section>

    <?php if ($flashMessage): ?>
      <div class="flash <?= $flashType ?>">
        <i class="fa-solid fa-<?= $flashType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
        <span><?= htmlspecialchars($flashMessage) ?></span>
      </div>
    <?php endif; ?>

    <!-- PRODUCT LIST PANEL -->
    <div class="panel">
      <div class="panel-title">Product list</div>
      <div class="panel-caption">Quick view of everything visible in the shop.</div>

      <!-- FILTER BAR -->
      <div class="product-filters">
        <div class="filter-chips">
          <?php
          if (!function_exists('chipLink')) {
            function chipLink($label, $icon, $value, $currentGroup, $sort, $search) {
              $params = [
                'group' => $value,
                'sort'  => $sort
              ];
              if ($search !== '') $params['q'] = $search;
              $url    = 'manage_products.php?' . http_build_query($params);
              $active = $currentGroup === $value ? 'active' : '';
              echo '<a href="'.htmlspecialchars($url).'" class="chip-filter '.$active.'">'.
                   '<i class="'.$icon.'"></i> '.$label.
                   '</a>';
            }
          }
          ?>

          <?php chipLink('All',       'fa-solid fa-layer-group', 'all',       $group, $sort, $search); ?>
          <?php chipLink('Keychain',  'fa-solid fa-key',         'keychain',  $group, $sort, $search); ?>
          <?php chipLink('Instax',    'fa-solid fa-camera-retro','instax',    $group, $sort, $search); ?>
          <?php chipLink('Photocard', 'fa-solid fa-image',       'photocard', $group, $sort, $search); ?>
          <?php chipLink('Ref Magnet','fa-solid fa-magnet',      'refmagnet', $group, $sort, $search); ?>
        </div>

        <form method="get" class="filter-search">
          <input type="hidden" name="group" value="<?= htmlspecialchars($group) ?>">

          <select name="sort" onchange="this.form.submit()">
            <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Newest first (ID)</option>
            <option value="oldest"     <?= $sort==='oldest'?'selected':'' ?>>Oldest first (ID)</option>
            <option value="price_low"  <?= $sort==='price_low'?'selected':'' ?>>Price: low to high</option>
            <option value="price_high" <?= $sort==='price_high'?'selected':'' ?>>Price: high to low</option>
          </select>

          <div class="search-pill">
            <i class="fa-solid fa-magnifying-glass" style="font-size:.8rem;color:#b49ad6;"></i>
            <input type="text" name="q" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
          </div>
        </form>
      </div>

      <div style="overflow-x:auto;">
        <table class="products-table">
          <thead>
          <tr>
            <th style="width:60px;">Image</th>
            <th style="width:50px;">ID</th>
            <th>Name</th>
            <th style="width:80px;">Price</th>
            <th>Status</th>
            <th style="width:120px;">Actions</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td data-label="Image">
                <?php if (!empty($p['image'])): ?>
                  <img src="/<?= htmlspecialchars($p['image']) ?>" alt="Product" class="product-img-thumb">
                <?php else: ?>
                  <div style="width:50px;height:50px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-image" style="color:#ccc;"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="ID">#<?= (int)$p['product_id'] ?></td>
              <td data-label="Name">
                <div style="font-weight:500;"><?= htmlspecialchars($p['name']) ?></div>
                <?php if (!empty($p['description'])): ?>
                  <div style="font-size:.78rem;color:#777;margin-top:2px;">
                    <?= nl2br(htmlspecialchars($p['description'])) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Price">₱<?= number_format((float)$p['price'], 2) ?></td>
              <td data-label="Status">
                <?php if ((int)$p['is_active'] === 1): ?>
                  <span class="badge on">Active</span>
                <?php else: ?>
                  <span class="badge off">Hidden</span>
                <?php endif; ?>
              </td>
              <td data-label="Actions">
                <div class="action-buttons">
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="toggle_id" value="<?= (int)$p['product_id'] ?>">
                    <button type="submit" class="btn-toggle">
                      <?= (int)$p['is_active'] === 1 ? 'Hide' : 'Show' ?>
                    </button>
                  </form>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                    <input type="hidden" name="delete_id" value="<?= (int)$p['product_id'] ?>">
                    <button type="submit" class="btn-delete">
                      <i class="fa-solid fa-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$products): ?>
            <tr>
              <td colspan="6" style="text-align:center;color:#777;padding:16px 4px;">
                No products found — try adjusting your filters ✨
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
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
});

// Cross-tab forced logout for admin
window.addEventListener('storage', function (e) {
  if (e.key === 'adminForceLogout') {
      window.location.href = 'admin_login.php';
  }
});
</script>
</body>
</html>