<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$isLogged     = !empty($_SESSION['customer_id']);
$notifCount   = 0;
$currentPage  = 'shop';

if ($isLogged) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders_tbl 
        WHERE customer_id = ? 
          AND status IN ('Pending','Approved','Completed', 'Ready', 'Refunded')
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $notifCount = (int)$stmt->fetchColumn();
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
    if (strpos($n,'glossy') !== false)  return '/product_images/glossy.png';
    if (strpos($n,'leather') !== false) return '/product_images/leather.png';
    if (strpos($n,'matte') !== false)   return '/product_images/matte.png';

    if ($img) {
      if (strpos($img, 'http') === 0) return $img;
      return '/uploads/products/' . $img;
    }

    return 'https://via.placeholder.com/600x400.png?text=Stella%27s+Creation';
  }
}

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

// only show ACTIVE products sa shop
$sql = "SELECT product_id, name, description, price, image 
        FROM products_tbl 
        WHERE is_active = 1";
$params = [];


if ($q !== '') {
  $sql .= " AND name LIKE ?";
  $params[] = '%'.$q.'%';
}

if ($category !== '') {
  $sql .= " AND name LIKE ?";
  $params[] = '%'.$category.'%';
}

$sql .= " ORDER BY product_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* 🔐 Cart + notif destinations */
$cartIconHref  = $isLogged ? '/cart.php'       : '/login_choice.php';
$notifIconHref = $isLogged ? '/my_orders.php'  : '/login_choice.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Shop · Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html {
      height: 100%;
    }
    body {
      min-height: 100%;
      display: flex;
      flex-direction: column;
    }
    .site-header {
      flex-shrink: 0;
    }
    .page-main {
      flex: 1;
      padding-bottom: 60px;
    }
    .site-footer {
      flex-shrink: 0;
      margin-top: auto;
      position: relative;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }
    .section-header-left h3 {
        margin: 0;
    }
    .section-header-left .section-note {
        margin-top: 4px;
        display: block;
    }

    .filter-controls {
        margin-left: auto;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        max-width: 100%;
        width: 100%;
    }
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        align-items: flex-end;
    }
    .filter-search {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid #f1e4ff;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        max-width: 220px;
        width: 100%;
        font-size: 13px;
    }
    .filter-search i {
        font-size: 12px;
        color: #b197d8;
    }
    .filter-search input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: transparent;
    }

    .filter-pills {
        display: flex;
        flex-wrap: nowrap;
        gap: 6px;
        justify-content: flex-end;
        overflow-x: auto;
        max-width: 600px;
        width: auto;
        padding-bottom: 4px;
    }
    .filter-pills::-webkit-scrollbar {
        height: 4px;
    }
    .filter-pills::-webkit-scrollbar-track {
        background: transparent;
    }
    .filter-pills::-webkit-scrollbar-thumb {
        background: #e0b6ff;
        border-radius: 4px;
    }
    .filter-pill {
        border-radius: 999px;
        border: 1px solid #f3d8ff;
        background: #ffffff;
        padding: 5px 11px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        color: #5b4b6b;
        transition: background .18s ease, box-shadow .18s ease, transform .1s ease, border-color .18s ease;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .filter-pill i {
        font-size: 11px;
    }
    .filter-pill:hover {
        background: #fff5ff;
        box-shadow: 0 3px 8px rgba(0,0,0,0.09);
        transform: translateY(-1px);
    }
    .filter-pill.active {
        background: #f9e1ff;
        border-color: #e0b6ff;
        color: #7c2e7f;
        box-shadow: 0 3px 10px rgba(124,46,127,0.18);
    }

    @media (max-width: 768px) {
      .filter-controls {
        align-items: stretch;
      }
      .filter-form {
        align-items: stretch;
      }
      .filter-search {
        max-width: 100%;
      }
      .filter-pills {
        justify-content: flex-start;
      }
    }

    /* 💜 Cute centered toast — same as index */
    .toast {
      position: fixed !important;
      top: 50% !important;
      left: 50% !important;
      bottom: auto !important;
      right: auto !important;
      transform: translate(-50%, -50%) !important;

      background: rgba(200, 162, 200, 0.78);
      color: var(--white) !important;

      padding: 12px 30px !important;
      border-radius: 999px !important;
      font-size: 1.2rem !important;
      font-weight: 600;
      white-space: nowrap;

      display: inline-flex;
      align-items: center;
      justify-content: center;

      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      z-index: 10000;
      transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .toast.show {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      animation: toastPop 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .toast .sparkle {
      display: inline-block;
      font-size: 0.9rem;
      margin-left: 4px;
      animation: sparkleRotate 3s ease-in-out infinite;
    }

    @keyframes sparkleRotate {
      0%, 100% { transform: scale(1) rotate(0deg);   opacity: 1; }
      25%      { transform: scale(1.2) rotate(180deg); opacity: 0.95; }
      50%      { transform: scale(1) rotate(360deg);  opacity: 1; }
      75%      { transform: scale(1.2) rotate(540deg); opacity: 0.95; }
    }

    @keyframes toastPop {
      0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
      }
      50% {
        transform: translate(-50%, -50%) scale(1.05);
      }
      100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
      }
    }

    /* BURGER BUTTON */
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
      <div class="brand-text">Stella's <span>Creation</span></div>
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
</aside>

<header class="site-header">
  <div class="container navbar">
    <div class="nav-left">
      <div class="brand-icon"><i class="fa-solid fa-star"></i></div>
      <div class="brand-text">Stella's <span>Creation</span></div>
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
        onclick="window.location.href='<?= htmlspecialchars($notifIconHref) ?>'"
      >
        <i class="fa-regular fa-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </button>

      <?php if (!empty($_SESSION['customer_id'])): ?>
        <div class="user-dropdown-wrap">
          <button class="icon-btn user-menu-btn" type="button" aria-label="User Menu">
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
        <a href="/login_choice.php" class="icon-btn" aria-label="Login">
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
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search keychains, photocards, ref magnets...">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
  </div>
</div>

<main class="page-main">
  <div class="container">
    <h2>Shop</h2>

    <div class="card">
      <div class="section-header">
        <div class="section-header-left">
          <h3>All Products</h3>
          <?php if ($q !== ''): ?>
            <span class="section-note">Searching for "<?= htmlspecialchars($q) ?>"</span>
          <?php else: ?>
            <span class="section-note">Browse all available items</span>
          <?php endif; ?>
        </div>

        <div class="filter-controls">
          <form method="get" action="/shop.php" class="filter-form" id="filterForm">
            <div class="filter-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search products...">
            </div>

            <input type="hidden" name="category" id="categoryInput" value="<?= htmlspecialchars($category) ?>">

            <div class="filter-pills">
              <?php
                $cat = strtolower($category);
              ?>
              <button type="button" class="filter-pill<?= $cat === '' ? ' active' : '' ?>" data-category="">
                <i class="fa-solid fa-layer-group"></i>
                <span>All</span>
              </button>
              <button type="button" class="filter-pill<?= $cat === 'keychain' ? ' active' : '' ?>" data-category="keychain">
                <i class="fa-solid fa-key"></i>
                <span>Keychain</span>
              </button>
              <button type="button" class="filter-pill<?= $cat === 'instax' ? ' active' : '' ?>" data-category="instax">
                <i class="fa-solid fa-camera-retro"></i>
                <span>Instax</span>
              </button>
              <button type="button" class="filter-pill<?= $cat === 'photocard' ? ' active' : '' ?>" data-category="photocard">
                <i class="fa-regular fa-images"></i>
                <span>Photocard</span>
              </button>
              <button type="button" class="filter-pill<?= $cat === 'ref magnet' ? ' active' : '' ?>" data-category="ref magnet">
                <i class="fa-solid fa-magnet"></i>
                <span>Ref Magnet</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="grid products-grid">
        <?php foreach ($products as $p): ?>
          <?php
            $pid      = (int)$p['product_id'];
            $imgSrc   = product_image($p['name'], $p['image']);

            // 🔐 If not logged in → login_choice.php
            $orderUrl = $isLogged ? "/order_form.php?product_id={$pid}" : "/login_choice.php";
            $cartUrl  = $isLogged ? "/cart_add.php?product_id={$pid}"   : "/login_choice.php";

            $safeName = htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8');
          ?>
          <article class="product">
            <div class="product-image-wrap">
              <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            </div>
            <h4><?= htmlspecialchars($p['name']) ?></h4>
            <div class="small">
              <?= htmlspecialchars($p['description'] ?: 'Personalized, high-quality print.') ?>
            </div>
            <div class="price">₱<?= number_format((float)$p['price'], 2) ?></div>
            <div class="product-actions">
              <a class="btn primary product-order-btn"
                 href="<?= htmlspecialchars($orderUrl) ?>"
                 data-product-id="<?= $pid ?>"
                 data-product-name="<?= $safeName ?>">
                Order Now
              </a>
              <a class="btn ghost btn-cart-simple"
                 href="<?= htmlspecialchars($cartUrl) ?>">
                Add to Cart
              </a>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (!$products): ?>
          <div class="small">No products found.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella's Creation · Made with love in PH</div>
  </div>
</footer>

<div class="cart-drawer-backdrop" id="orderDrawerBackdrop"></div>
<aside class="cart-drawer" id="orderDrawer">
  <div class="cart-drawer-header">
    <h4 id="orderDrawerTitle">Order Now</h4>
    <button class="icon-btn" id="orderDrawerClose">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="cart-drawer-body" style="padding:0;height:calc(100vh - 64px);">
    <iframe id="orderDrawerFrame"
            src=""
            style="border:0;width:100%;height:100%;display:block;border-radius:0 0 18px 18px;"></iframe>
  </div>
</aside>

<div class="toast" id="cartToast">
  Added to your cart! <span class="sparkle">✨</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const isLoggedIn = <?php echo $isLogged ? 'true' : 'false'; ?>;

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

  // 🔹 Match index/cart behavior: animate burger to X and back
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

  const orderDrawer         = document.getElementById('orderDrawer');
  const orderDrawerBackdrop = document.getElementById('orderDrawerBackdrop');
  const orderDrawerClose    = document.getElementById('orderDrawerClose');
  const orderDrawerFrame    = document.getElementById('orderDrawerFrame');
  const orderDrawerTitle    = document.getElementById('orderDrawerTitle');
  const cartToast           = document.getElementById('cartToast');

  function openOrderDrawer(url, title) {
    if (!orderDrawer || !orderDrawerFrame) return;
    orderDrawerFrame.src = url;
    if (orderDrawerTitle && title) orderDrawerTitle.textContent = title;
    orderDrawer.classList.add('open');
    if (orderDrawerBackdrop) orderDrawerBackdrop.classList.add('open');
  }

  function closeOrderDrawer() {
    if (orderDrawer) orderDrawer.classList.remove('open');
    if (orderDrawerBackdrop) orderDrawerBackdrop.classList.remove('open');
    if (orderDrawerFrame) orderDrawerFrame.src = '';
  }

  if (orderDrawerClose)    orderDrawerClose.addEventListener('click', closeOrderDrawer);
  if (orderDrawerBackdrop) orderDrawerBackdrop.addEventListener('click', closeOrderDrawer);

  function showToast(msg) {
    if (!cartToast) return;
    cartToast.innerHTML = msg || 'Added to your cart! <span class="sparkle">✨</span>';
    cartToast.classList.add('show');
    setTimeout(() => cartToast.classList.remove('show'), 2000);
  }

  const productOrderBtns = document.querySelectorAll('.product-order-btn');
  productOrderBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      if (!isLoggedIn) {
        // guest → follow normal link (login_choice.php)
        return;
      }
      e.preventDefault();
      const pid   = this.getAttribute('data-product-id');
      const pname = this.getAttribute('data-product-name') || 'Order Now';
      if (!pid) return;
      openOrderDrawer('/order_form.php?product_id=' + encodeURIComponent(pid), pname);
    });
  });

  const simpleCartButtons = document.querySelectorAll('.btn-cart-simple');
  simpleCartButtons.forEach(btn => {
    btn.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (!href) return;

      // guest with login_choice.php → let it navigate
      if (!isLoggedIn && href.indexOf('login_choice.php') !== -1) {
        return;
      }

      e.preventDefault();
      fetch(href, { credentials: 'same-origin' })
        .then(() => showToast())
        .catch(() => showToast());
    });
  });

  const filterForm = document.getElementById('filterForm');
  const categoryInput = document.getElementById('categoryInput');
  const pillButtons = document.querySelectorAll('.filter-pill');

  pillButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      const cat = this.getAttribute('data-category') || '';
      if (categoryInput) categoryInput.value = cat;
      pillButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      if (filterForm) filterForm.submit();
    });
  });
});
</script>
</body>
</html>
