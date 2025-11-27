<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$isLogged = !empty($_SESSION['customer_id']);
$notifCount = 0;
$currentPage = 'home';

if ($isLogged) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders_tbl
        WHERE customer_id = ?
          AND status IN ('Pending','Approved','Ready','Completed','Refunded')
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $notifCount = (int)$stmt->fetchColumn();
}

$products = $pdo->query("
    SELECT product_id, name, description, price, image 
    FROM products_tbl 
    WHERE is_active = 1
    ORDER BY product_id DESC
    LIMIT 6
")->fetchAll();

/**
 * Return a display image for a product name / image field.
 * Adjusted for Hostinger:
 *  - Correct capitalization (Instax Wide.png, Photocard.png, etc.)
 *  - Relative paths, no leading slash (product_images/..., uploads/products/...)
 */
if (!function_exists('product_image')) {
  function product_image($name, $img) {
    $n = strtolower((string)$name);

    // INSTAX TYPES
    if (strpos($n, 'instax mini') !== false)   return 'product_images/Instax Mini.png';
    if (strpos($n, 'instax small') !== false)  return 'product_images/Instax Small.png';
    if (strpos($n, 'instax square') !== false) return 'product_images/Instax Square.png';
    if (strpos($n, 'instax wide') !== false)   return 'product_images/Instax Wide.png';

    // KEYCHAINS
    if (strpos($n, 'spotify') !== false && strpos($n, 'keychain') !== false) {
      return 'product_images/Keychain (spotify).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'instax') !== false) {
      return 'product_images/Keychain (instax).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'big') !== false) {
      return 'product_images/Keychain (big).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'small') !== false) {
      return 'product_images/Keychain (small).png';
    }

    // REF MAGNET FINISHES
    if (strpos($n,'holographic') !== false || strpos($n,'rainbow') !== false) {
      return 'product_images/holo_rainbow.png';
    }
    if (strpos($n,'glitter') !== false) return 'product_images/glitter.png';
    if (strpos($n,'glossy')  !== false) return 'product_images/glossy.png';
    if (strpos($n,'leather') !== false) return 'product_images/leather.png';
    if (strpos($n,'matte')   !== false) return 'product_images/matte.png';

    // CUSTOM UPLOADED PRODUCT IMAGE
    if ($img) {
      if (strpos($img, 'http') === 0) return $img;              // full URL stored
      return 'uploads/products/' . $img;                        // relative path
    }

    // FALLBACK PLACEHOLDER
    return 'https://via.placeholder.com/600x400.png?text=Stella%27s+Creation';
  }
}

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

// CTA destinations (keep same behavior as before)
$ctaKeychainHref  = $isLogged ? '/order_form.php?group=keychain'   : '/login_choice.php';
$ctaPhotocardHref = $isLogged ? '/order_form.php?group=photocard'  : '/login_choice.php';
$ctaRefMagHref    = $isLogged ? '/order_form.php?group=ref_magnet' : '/login_choice.php';

$cartIconHref     = $isLogged ? '/cart.php'       : '/login_choice.php';
$notifIconHref    = $isLogged ? '/my_orders.php'  : '/login_choice.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Stella's Creation — Custom Keychains, Photocards & Magnets</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <!-- Use relative path so it works the same on InfinityFree & Hostinger -->
  <link rel="stylesheet" href="style.css?v=1">

  <style>
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
    <?php if (!$isLogged): ?>
      <a href="/login_choice.php">Log In</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-search">
    <input type="text" placeholder="Search keychains, photocards..." id="sidebarSearchInput">
    <button type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <div class="sidebar-social">
    <a href="https://www.facebook.com/abella.tiffany" target="_blank" rel="noopener">
      <i class="fa-brands fa-facebook-f"></i>
    </a>
    <a href="https://www.instagram.com/tiffaee/" target="_blank" rel="noopener">
      <i class="fa-brands fa-instagram"></i>
    </a>
    <a href="https://www.tiktok.com/@tiffany0_duh" target="_blank" rel="noopener">
      <i class="fa-brands fa-tiktok"></i>
    </a>
  </div>
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
      <button class="icon-btn" id="searchToggle">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>

      <button
        class="icon-btn nav-notif-btn"
        onclick="window.location.href='<?= htmlspecialchars($notifIconHref) ?>'">
        <i class="fa-regular fa-bell"></i>
        <?php if (!empty($notifCount)): ?>
          <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </button>

      <?php if ($isLogged): ?>
        <div class="user-dropdown-wrap">
          <button class="icon-btn user-menu-btn"><i class="fa-regular fa-user"></i></button>

          <div class="user-dropdown">
            <div class="user-greeting">Hello, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>!</div>
            <a href="/track_order.php">Track Order</a>
            <a href="/my_orders.php">My Orders</a>
            <a href="/account_info.php">Account Info</a>
            <a href="/change_password.php">Change Password</a>
            <a href="/customer_logout.php" class="logout">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="/login_choice.php" class="icon-btn"><i class="fa-regular fa-user"></i></a>
      <?php endif; ?>

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
  <section class="hero">
    <div class="hero-text">
      <p class="hero-tagline">Personalized. Pretty. Affordable.</p>
      <h1>Your memories, made cute &amp; personalized.</h1>
      <p class="hero-sub">
        Keychains, photocards, and ref magnets made just for you. Affordable, student-friendly, and perfect for gifts.
      </p>

      <div class="hero-cta">
        <a class="btn primary hero-order-btn"
           href="<?= htmlspecialchars($ctaKeychainHref) ?>"
           data-order-group="keychain">
          Order Keychains
        </a>

        <a class="btn ghost hero-order-btn"
           href="<?= htmlspecialchars($ctaPhotocardHref) ?>"
           data-order-group="photocard">
          Order Photocards
        </a>

        <a class="btn ghost hero-order-btn"
           href="<?= htmlspecialchars($ctaRefMagHref) ?>"
           data-order-group="ref_magnet">
          Order Ref Magnets
        </a>
      </div>
    </div>

    <div class="hero-media">
      <div class="hero-card hero-card-main"></div>
      <div class="hero-card hero-card-1"></div>
      <div class="hero-card hero-card-2"></div>
    </div>
  </section>

  <section class="product-section">
    <div class="container">
      <div class="card">
        <div class="section-header">
          <h3>Popular Products</h3>
          <span class="section-note">Best-sellers from your classmates &amp; friends</span>
        </div>

        <div class="grid products-grid">
          <?php foreach ($products as $p): ?>
            <?php
              $pid      = (int)$p['product_id'];
              $imgSrc   = product_image($p['name'], $p['image']);
              $safeName = htmlspecialchars($p['name']);
              $priceVal = number_format((float)$p['price'], 2);
              $orderUrl = $isLogged ? "/order_form.php?product_id={$pid}" : "/login_choice.php";
              $cartUrl  = $isLogged ? "/cart_add.php?product_id={$pid}"  : "/login_choice.php";
            ?>
            <article class="product">
              <div class="product-image-wrap">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= $safeName ?>">
              </div>
              <h4><?= $safeName ?></h4>
              <div class="small">
                <?= htmlspecialchars($p['description'] ?: 'Personalized, high-quality print.') ?>
              </div>
              <div class="price">₱<?= $priceVal ?></div>

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
  </section>
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
    <button class="icon-btn" id="orderDrawerClose"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="cart-drawer-body" style="padding:0;height:calc(100vh - 64px);">
    <iframe id="orderDrawerFrame" src="" style="width:100%;height:100%;border:0;"></iframe>
  </div>
</aside>

<div class="toast" id="cartToast">Added to your cart! <span class="sparkle">✨</span></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const isLoggedIn = <?= $isLogged ? 'true' : 'false' ?>;

  const burgerBtn = document.getElementById('burgerBtn');
  const sidebar = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarClose = document.getElementById('sidebarClose');

  const openSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.add('open');
    sidebarBackdrop.classList.add('open');
  };
  const closeSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    sidebarBackdrop.classList.remove('open');
  };

  burgerBtn?.addEventListener('click', () => {
    burgerBtn.classList.toggle('open');
    openSidebar();
  });
  sidebarClose?.addEventListener('click', () => {
    burgerBtn.classList.remove('open');
    closeSidebar();
  });
  sidebarBackdrop?.addEventListener('click', () => {
    burgerBtn.classList.remove('open');
    closeSidebar();
  });

  const heroButtons = document.querySelectorAll('.hero-order-btn');
  heroButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      if (!isLoggedIn) return;
      e.preventDefault();
      const group = btn.dataset.orderGroup;
      openOrderDrawer(`/order_form.php?group=${encodeURIComponent(group)}`, "Order Now");
    });
  });

  const productButtons = document.querySelectorAll('.product-order-btn');
  productButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      if (!isLoggedIn) return;
      e.preventDefault();
      const id = btn.dataset.productId;
      openOrderDrawer(`/order_form.php?product_id=${encodeURIComponent(id)}`, "Order Now");
    });
  });

  const cartButtons = document.querySelectorAll('.btn-cart-simple');
  cartButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      const href = btn.getAttribute('href');
      if (!href) return;

      if (!isLoggedIn && href.indexOf('login_choice.php') !== -1) {
        return;
      }

      e.preventDefault();
      fetch(href, { credentials: 'same-origin' })
        .then(() => showToast())
        .catch(() => showToast());
    });
  });

  const searchToggle = document.getElementById('searchToggle');
  const searchBarWrap = document.getElementById('searchBarWrap');
  searchToggle?.addEventListener('click', () => {
    searchBarWrap.classList.toggle('open');
  });

  const orderDrawer = document.getElementById('orderDrawer');
  const orderDrawerBackdrop = document.getElementById('orderDrawerBackdrop');
  const orderDrawerClose = document.getElementById('orderDrawerClose');
  const orderDrawerFrame = document.getElementById('orderDrawerFrame');
  const orderDrawerTitle = document.getElementById('orderDrawerTitle');

  const openOrderDrawer = (url, title) => {
    if (!orderDrawer || !orderDrawerFrame) return;
    orderDrawerFrame.src = url;
    orderDrawerTitle.textContent = title;
    orderDrawer.classList.add('open');
    orderDrawerBackdrop.classList.add('open');
  };

  const closeOrderDrawer = () => {
    if (!orderDrawer) return;
    orderDrawer.classList.remove('open');
    orderDrawerBackdrop.classList.remove('open');
    if (orderDrawerFrame) orderDrawerFrame.src = "";
  };

  orderDrawerClose?.addEventListener('click', closeOrderDrawer);
  orderDrawerBackdrop?.addEventListener('click', closeOrderDrawer);

  const cartToast = document.getElementById('cartToast');
  function showToast(msg) {
    if (!cartToast) return;
    cartToast.innerHTML = msg || 'Added to your cart! <span class="sparkle">✨</span>';
    cartToast.classList.add('show');
    setTimeout(() => cartToast.classList.remove('show'), 2000);
  }

  const userMenuBtn = document.querySelector('.user-menu-btn');
  const userDropdown = document.querySelector('.user-dropdown');

  document.addEventListener('click', (e) => {
    if (userMenuBtn && userDropdown) {
      if (userMenuBtn.contains(e.target)) {
        userDropdown.classList.toggle('open');
      } else if (!userDropdown.contains(e.target)) {
        userDropdown.classList.remove('open');
      }
    }
  });
});
</script>

</body>
</html>
