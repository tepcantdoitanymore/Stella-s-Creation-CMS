<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: /login_choice.php');
    exit;
}

/* ---------- HANDLE CART UPDATES ( + / - / remove ) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($pid && isset($_SESSION['cart'][$pid])) {
        switch ($action) {
            case 'inc':
                $_SESSION['cart'][$pid]++;
                break;
            case 'dec':
                $_SESSION['cart'][$pid]--;
                if ($_SESSION['cart'][$pid] <= 0) {
                    unset($_SESSION['cart'][$pid]);
                    // also clear photos / notes for that product
                    unset($_SESSION['cart_photos'][$pid], $_SESSION['cart_notes'][$pid]);
                }
                break;
            case 'remove':
                unset($_SESSION['cart'][$pid]);
                unset($_SESSION['cart_photos'][$pid], $_SESSION['cart_notes'][$pid]);
                break;
        }
    }

    header('Location: /cart.php');
    exit;
}

/* ---------- NAV + NOTIFS SETUP ---------- */
$isLogged    = !empty($_SESSION['customer_id']);
$notifCount  = 0;
$currentPage = '';

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

/* ---------- IMAGE HELPER ---------- */
if (!function_exists('product_image')) {
  function product_image($name, $img) {
    $n = strtolower((string)$name);

    if (strpos($n, 'instax mini') !== false) {
      return '/product_images/Instax Mini.png';
    }
    if (strpos($n, 'instax small') !== false) {
      return '/product_images/Instax Small.png';
    }
    if (strpos($n, 'instax square') !== false) {
      return '/product_images/Instax Square.png';
    }
    if (strpos($n, 'instax wide') !== false) {
      return '/product_images/Instax Wide.png';
    }

    if (strpos($n, 'spotify') !== false && strpos($n, 'keychain') !== false) {
      return '/product_images/Keychain (spotify).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'instax') !== false) {
      return '/product_images/Keychain (instax).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'big') !== false) {
      return '/product_images/Keychain (big).png';
    }
    if (strpos($n, 'keychain') !== false && strpos($n, 'small') !== false) {
      return '/product_images/Keychain (small).png';
    }

    if (strpos($n,'holographic') !== false || strpos($n,'rainbow') !== false) {
      return '/product_images/holo_rainbow.png';
    }
    if (strpos($n,'glitter') !== false) {
      return '/product_images/glitter.png';
    }
    if (strpos($n,'glossy') !== false) {
      return '/product_images/glossy.png';
    }
    if (strpos($n,'leather') !== false) {
      return '/product_images/leather.png';
    }
    if (strpos($n,'matte') !== false) {
      return '/product_images/matte.png';
    }

    if ($img) {
      if (strpos($img, 'http') === 0) {
        return $img;
      }
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

/* 🔐 Cart icon: login_choice if not logged in */
$cartIconHref = $isLogged ? '/cart.php' : '/login_choice.php';

/* ---------- FETCH CART PRODUCTS ---------- */
$cart        = $_SESSION['cart'] ?? [];
$cartPhotos  = $_SESSION['cart_photos'] ?? [];
$cartNotes   = $_SESSION['cart_notes'] ?? [];
$productRows = [];
$total       = 0;
$byId        = [];

// where temporary cart photos are stored
$cartUploadDir = __DIR__ . '/uploads/cart_photos';

if ($cart) {
    $ids          = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT product_id, name, price, image 
                           FROM products_tbl 
                           WHERE product_id IN ($placeholders)");
    $stmt->execute($ids);
    $productRows = $stmt->fetchAll();

    foreach ($productRows as $row) {
        $byId[(int)$row['product_id']] = $row;
    }

    foreach ($cart as $pid => $qty) {
        if (!isset($byId[$pid])) continue;
        $total += $byId[$pid]['price'] * $qty;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Cart — Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">
  <style>
    .cart-page{max-width:900px;margin:40px auto;padding:0 16px;}
    .cart-page h2{margin-bottom:12px;}

    /* Bulk order notice */
    .bulk-notice {
      background: linear-gradient(135deg, #fff6f0 0%, #ffe9f5 100%);
      border: 2px solid #C8A2C8;
      border-radius: 16px;
      padding: 16px 20px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .bulk-notice i {
      font-size: 1.5rem;
      color: #C8A2C8;
    }
    .bulk-notice-text { flex: 1; }
    .bulk-notice-text strong {
      display: block;
      font-size: 1rem;
      color: #5a3568;
      margin-bottom: 4px;
    }
    .bulk-notice-text span {
      font-size: 0.85rem;
      color: #777;
    }

    .cart-item{
      display:flex;
      align-items:center;
      gap:14px;
      padding:10px 0;
      border-bottom:1px solid rgba(0,0,0,0.06);
      transition: box-shadow 0.2s, background 0.2s;
    }
    .cart-item img{width:72px;height:72px;object-fit:cover;border-radius:16px;}

    .cart-main{flex:1;}
    .cart-name{font-weight:500;}
    .cart-qty{font-size:.85rem;color:rgba(0,0,0,0.7);margin-top:4px;}

    .qty-controls{
      display:inline-flex;
      align-items:center;
      border-radius:999px;
      border:1px solid rgba(0,0,0,0.1);
      overflow:hidden;
      background:#fff;
    }
    .qty-controls button{
      border:0;
      background:transparent;
      padding:4px 10px;
      font-size:16px;
      cursor:pointer;
      line-height:1;
    }
    .qty-controls span{
      min-width:32px;
      text-align:center;
      font-size:0.9rem;
    }

    .cart-actions {
      display: flex;
      gap: 8px;
      margin-top: 8px;
      flex-wrap: wrap;
    }

    .cart-action-btn {
      border: 0;
      background: #C8A2C8;
      color: #fff;
      font-size: 0.75rem;
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 999px;
      transition: all 0.2s ease;
      font-weight: 500;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    .cart-action-btn:hover {
      background: #b08fb0;
      transform: translateY(-1px);
    }

    .cart-action-btn.remove {
      background: #d9534f;
    }

    .cart-action-btn.remove:hover {
      background: #c9302c;
    }

    .cart-note-tag{
      display:inline-block;
      margin-top:4px;
      padding:2px 10px;
      font-size:0.75rem;
      border-radius:999px;
      background:#ffe9f5;
      color:#5a3568;
    }

    .cart-photo-status {
      font-size:0.78rem;
      margin-top:4px;
      color:#999;
    }
    .cart-photo-status.ready{
      color:#2e7d32;
    }

    .cart-price{
      font-weight:600;
      color:#E75480;
      white-space:nowrap;
    }

    .cart-empty{padding:20px 0;text-align:center;font-size:.95rem;}

    .cart-summary{text-align:right;margin-top:18px;}
    .cart-total{font-weight:600;font-size:1.05rem;}
    .cart-checkout{margin-top:10px;}
    .cart-checkout .btn{
      padding:8px 20px;
      font-size:.95rem;
    }

    .cart-item-missing{
      background:#fff8e7;
      box-shadow:0 0 0 2px #ffcc80;
    }

    /* Modal shared */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      animation: fadeIn 0.2s ease;
    }
    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background: white;
      padding: 24px;
      border-radius: 20px;
      max-width: 500px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      animation: slideUp 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid #f0f0f0;
    }
    .modal-header h3 {
      margin: 0;
      color: #5a3568;
      font-size: 1.2rem;
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #999;
      transition: color 0.2s;
    }
    .modal-close:hover { color: #333; }

    .modal-body { margin-bottom: 20px; }

    /* Upload modal */
    .upload-area {
      border: 2px dashed #C8A2C8;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background: #fafafa;
    }
    .upload-area:hover {
      background: #f5f0f5;
      border-color: #b08fb0;
    }
    .upload-area i {
      font-size: 2rem;
      color: #C8A2C8;
      margin-bottom: 10px;
    }
    .upload-area p {
      margin: 8px 0;
      color: #666;
      font-size: 0.9rem;
    }

    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 10px;
      margin-top: 16px;
    }
    .preview-item {
      position: relative;
      border-radius: 8px;
      overflow: hidden;
    }
    .preview-item img {
      width: 100%;
      height: 100px;
      object-fit: cover;
    }
    .preview-remove {
      position: absolute;
      top: 4px;
      right: 4px;
      background: rgba(217, 83, 79, 0.9);
      color: white;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }
    .modal-footer .btn {
      padding: 8px 20px;
      font-size: 0.9rem;
      border-radius: 999px;
    }

    /* Note modal */
    #noteText {
      width:100%;
      min-height:90px;
      border-radius:10px;
      border:1px solid #ddd;
      padding:8px 10px;
      font-family:'Poppins',sans-serif;
      font-size:.9rem;
      resize:vertical;
      box-sizing:border-box;
    }
    #noteCounter {
      font-size:11px;
      color:#777;
      margin-top:4px;
      display:block;
      text-align:right;
    }

    @media (max-width:600px){
      .cart-actions {
        flex-direction: column;
      }
      .bulk-notice {
        flex-direction: column;
        text-align: center;
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

<!-- SIDEBAR -->
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
    <a href="<?= $cartIconHref ?>">Cart</a>
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

<!-- NAVBAR -->
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
      <button class="icon-btn" id="searchToggle" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>

      <button 
        class="icon-btn nav-notif-btn" 
        aria-label="Notifications" 
        onclick="window.location.href='/my_orders.php'">
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
      <?php else: ?>
        <!-- 🔐 user icon -> login_choice -->
        <a href="/login_choice.php" class="icon-btn" aria-label="Login">
          <i class="fa-regular fa-user"></i>
        </a>
      <?php endif; ?>

      <a href="<?= $cartIconHref ?>" class="icon-btn" aria-label="Cart">
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

<main class="page-main">
  <div class="cart-page">
    <h2>Your Cart</h2>

    <!-- Bulk Order Notice -->
    <div class="bulk-notice">
      <i class="fa-solid fa-boxes-stacked"></i>
      <div class="bulk-notice-text">
        <strong>This page is intended for bulk orders</strong>
        <span>Perfect for large quantities of the same design. Upload your photos and we'll handle the rest!</span>
      </div>
    </div>

    <?php if (!$cart): ?>
      <div class="cart-empty">
        Your cart is empty. <a href="/index.php">Shop products</a>
      </div>
    <?php else: ?>
      <?php foreach ($cart as $pid => $qty): ?>
        <?php 
          if (!isset($byId[$pid])) continue;
          $row       = $byId[$pid];
          $lineTotal = $row['price'] * $qty;
          $imgSrc    = product_image($row['name'], $row['image']);

          // --- check actual existing photo files for this product in cart ---
          $rawPhotos   = $cartPhotos[$pid] ?? [];
          $validPhotos = [];

          if (is_array($rawPhotos)) {
              foreach ($rawPhotos as $fn) {
                  $fn   = trim((string)$fn);
                  if ($fn === '') continue;
                  $full = rtrim($cartUploadDir,'/').'/'.$fn;
                  if (is_file($full)) {
                      $validPhotos[] = $fn;
                  }
              }
          }

          // sync cleaned list back to session so consistent across pages
          $cartPhotos[$pid] = $validPhotos;
          $_SESSION['cart_photos'] = $cartPhotos;

          $hasPhotos = count($validPhotos) > 0;

          $noteText  = $cartNotes[$pid] ?? '';
        ?>
        <form method="post"
              class="cart-item"
              data-product-id="<?= (int)$pid ?>"
              data-has-photos="<?= $hasPhotos ? '1' : '0' ?>">
          <img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
          <div class="cart-main">
            <div class="cart-name"><?= htmlspecialchars($row['name']) ?></div>

            <div class="cart-qty">
              Quantity:
              <span class="qty-controls">
                <button type="submit" name="action" value="dec">−</button>
                <span><?= (int)$qty ?></span>
                <button type="submit" name="action" value="inc">+</button>
              </span>
            </div>

            <div class="cart-actions">
              <button type="button"
                      class="cart-action-btn"
                      onclick="openModifyModal(<?= (int)$pid ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-images"></i> Modify Photos
              </button>

              <button type="button"
                      class="cart-action-btn"
                      onclick="openNoteModal(<?= (int)$pid ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($noteText, ENT_QUOTES) ?>')">
                <i class="fa-regular fa-note-sticky"></i> Note
              </button>

              <button type="submit" name="action" value="remove" class="cart-action-btn remove">
                <i class="fa-solid fa-trash"></i> Remove
              </button>
            </div>

            <div class="cart-photo-status <?= $hasPhotos ? 'ready' : '' ?>">
              <?= $hasPhotos ? '✅ Photos attached' : '❌ Photos not yet attached (required before checkout)' ?>
            </div>

            <?php if ($noteText): ?>
              <div class="cart-note-tag">Note added</div>
            <?php endif; ?>
          </div>
          <div class="cart-price">₱<?= number_format($lineTotal, 2) ?></div>
          <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
        </form>
      <?php endforeach; ?>

      <div class="cart-summary">
        <div class="cart-total">
          Total: ₱<?= number_format($total, 2) ?>
        </div>
        <div class="cart-checkout">
          <a href="/checkout.php" class="btn primary" id="checkoutBtn">Checkout</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modify Photos Modal -->
<div class="modal" id="modifyModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Modify Photos</h3>
      <button class="modal-close" onclick="closeModifyModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p style="color: #666; font-size: 0.9rem; margin-bottom: 16px;" id="modalProductName"></p>
      
      <div class="upload-area" onclick="document.getElementById('photoUpload').click()">
        <i class="fa-solid fa-cloud-upload-alt"></i>
        <p><strong>Click to upload photos</strong></p>
        <p style="font-size: 0.8rem; color: #999;">or drag and drop</p>
      </div>
      <input type="file" id="photoUpload" multiple accept="image/*" style="display: none;" onchange="handlePhotoUpload(event)">
      
      <div class="preview-grid" id="photoPreview"></div>
    </div>
    <div class="modal-footer">
      <button class="btn secondary" onclick="closeModifyModal()">Cancel</button>
      <button class="btn primary" onclick="savePhotos()">Save Photos</button>
    </div>
  </div>
</div>

<!-- Note Modal -->
<div class="modal" id="noteModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Add Note</h3>
      <button class="modal-close" onclick="closeNoteModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p style="color:#666;font-size:.9rem;margin-bottom:10px;" id="noteProductName"></p>
      <textarea id="noteText" placeholder="Add any special instructions (optional, up to 100 words)"></textarea>
      <span id="noteCounter">0 / 100 words</span>
    </div>
    <div class="modal-footer">
      <button class="btn secondary" onclick="closeNoteModal()">Cancel</button>
      <button class="btn primary" onclick="saveNote()">Save Note</button>
    </div>
  </div>
</div>

<footer class="site-footer">
  <div class="container footer-inner">
    <div>© <?= date('Y') ?> Stella's Creation · Made with love in PH</div>
  </div>
</footer>

<script>
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

  const userMenuBtn  = document.querySelector('.user-menu-btn');
  const userDropdown = document.querySelector('.user-dropdown');

  if (userMenuBtn && userDropdown) {
    document.addEventListener('click', function(e) {
      if (userMenuBtn.contains(e.target)) {
        userDropdown.classList.toggle('open');
      } else if (!userDropdown.contains(e.target)) {
        userDropdown.classList.remove('open');
      }
    });
  }

  // ---------- Modals + upload / notes ----------
  let currentPhotoProductId = null;
  let currentNoteProductId  = null;
  let newPhotos             = []; // new uploads this session only

  function clearPreview() {
    const previewGrid = document.getElementById('photoPreview');
    if (previewGrid) previewGrid.innerHTML = '';
  }

  function renderExistingPhoto(productId, filename, url) {
    const previewGrid = document.getElementById('photoPreview');
    if (!previewGrid) return;

    const item = document.createElement('div');
    item.className = 'preview-item';
    item.dataset.existing = '1';
    item.dataset.filename = filename;
    item.innerHTML = `
      <img src="${url}" alt="Photo">
      <button class="preview-remove" onclick="deleteExistingPhoto(${productId}, '${filename.replace(/'/g,"\\'")}', this)">×</button>
    `;
    previewGrid.appendChild(item);
  }

  function loadExistingPhotos(productId) {
    clearPreview();

    fetch('/get_cart_photos.php?product_id=' + productId)
      .then(r => r.json())
      .then(data => {
        if (data && data.success && Array.isArray(data.photos)) {
          data.photos.forEach(p => {
            renderExistingPhoto(productId, p.filename, p.url);
          });
        }
      })
      .catch(() => {});
  }

  function openModifyModal(productId, productName) {
    currentPhotoProductId = productId;
    newPhotos = [];
    document.getElementById('photoUpload').value = '';
    document.getElementById('modalProductName').textContent = `Product: ${productName}`;
    document.getElementById('modifyModal').classList.add('active');
    document.body.style.overflow = 'hidden';

    loadExistingPhotos(productId);
  }

  function closeModifyModal() {
    document.getElementById('modifyModal').classList.remove('active');
    document.body.style.overflow = '';
    newPhotos = [];
    clearPreview();
    const input = document.getElementById('photoUpload');
    if (input) input.value = '';
  }

  function handlePhotoUpload(event) {
    const files = event.target.files;
    const previewGrid = document.getElementById('photoPreview');
    if (!files || !previewGrid) return;

    for (let file of files) {
      if (!file) continue;
      newPhotos.push(file);

      const reader = new FileReader();
      reader.onload = function(e) {
        const item = document.createElement('div');
        item.className = 'preview-item';
        item.dataset.existing = '0';
        item.innerHTML = `
          <img src="${e.target.result}" alt="Preview">
          <button class="preview-remove" onclick="removeNewPhoto(this)">×</button>
        `;
        previewGrid.appendChild(item);
      };
      reader.readAsDataURL(file);
    }
  }

  function removeNewPhoto(button) {
    const item  = button.parentElement;
    const grid  = item.parentElement;
    const index = Array.from(grid.children).indexOf(item);

    // count only new items when calculating index in newPhotos
    let newIdx = -1;
    let seenNew = -1;
    Array.from(grid.children).forEach((child, i) => {
      if (child.dataset.existing === '0') {
        seenNew++;
        if (child === item) newIdx = seenNew;
      }
    });

    if (newIdx >= 0) {
      newPhotos.splice(newIdx, 1);
    }
    item.remove();
  }

  function deleteExistingPhoto(productId, filename, buttonEl) {
    if (!confirm('Remove this photo from the order?')) return;

    const fd = new FormData();
    fd.append('product_id', productId);
    fd.append('filename', filename);

    fetch('/delete_cart_photo.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        // remove from preview
        if (buttonEl && buttonEl.parentElement) {
          buttonEl.parentElement.remove();
        }

        // update status sa cart row
        const item = document.querySelector('.cart-item[data-product-id="'+productId+'"]');
        if (item) {
          const status = item.querySelector('.cart-photo-status');
          if (data.remaining > 0) {
            item.dataset.hasPhotos = '1';
            if (status) {
              status.textContent = '✅ Photos attached';
              status.classList.add('ready');
            }
          } else {
            item.dataset.hasPhotos = '0';
            if (status) {
              status.textContent = '❌ Photos not yet attached (required before checkout)';
              status.classList.remove('ready');
            }
          }
        }
      } else {
        alert(data.error || 'Unable to delete this photo.');
      }
    })
    .catch(() => {
      alert('Unable to delete this photo.');
    });
  }

  function savePhotos() {
    if (!currentPhotoProductId) {
      alert('Something went wrong. Please close and reopen the modal.');
      return;
    }
    if (newPhotos.length === 0) {
      alert('Please upload at least one new photo, or close if you are done.');
      return;
    }

    const fd = new FormData();
    fd.append('product_id', currentPhotoProductId);
    newPhotos.forEach(file => {
      fd.append('photos[]', file);
    });

    fetch('/save_cart_photos.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        alert('Photos saved for this product!');
        const item = document.querySelector('.cart-item[data-product-id="'+currentPhotoProductId+'"]');
        if (item) {
          item.dataset.hasPhotos = '1';
          const status = item.querySelector('.cart-photo-status');
          if (status) {
            status.textContent = '✅ Photos attached';
            status.classList.add('ready');
          }
        }
        closeModifyModal();
      } else {
        alert(data.error || 'There was a problem saving your photos. Please try again.');
      }
    })
    .catch(() => {
      alert('Upload failed. Please try again.');
    });
  }

  function countWords(str) {
    if (!str.trim()) return 0;
    return str.trim().split(/\s+/).filter(Boolean).length;
  }

  function openNoteModal(productId, productName, existingNote) {
    currentNoteProductId = productId;
    const noteText = document.getElementById('noteText');
    const noteCounter = document.getElementById('noteCounter');
    document.getElementById('noteProductName').textContent = `Product: ${productName}`;
    noteText.value = existingNote || '';
    const words = countWords(noteText.value);
    noteCounter.textContent = `${words} / 100 words`;
    document.getElementById('noteModal').classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeNoteModal() {
    document.getElementById('noteModal').classList.remove('active');
    document.body.style.overflow = '';
  }

  document.getElementById('noteText')?.addEventListener('input', function() {
    let text = this.value;
    let words = countWords(text);
    if (words > 100) {
      const arr = text.trim().split(/\s+/).filter(Boolean).slice(0,100);
      this.value = arr.join(' ') + ' ';
      words = 100;
    }
    document.getElementById('noteCounter').textContent = `${words} / 100 words`;
  });

  function saveNote() {
    if (!currentNoteProductId) {
      alert('Something went wrong. Please close and reopen the modal.');
      return;
    }
    const textEl = document.getElementById('noteText');
    const note = textEl.value.trim();
    const words = countWords(note);
    if (words > 100) {
      alert('Note can be up to 100 words only.');
      return;
    }

    const fd = new FormData();
    fd.append('product_id', currentNoteProductId);
    fd.append('note', note);

    fetch('/save_cart_note.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        const item = document.querySelector('.cart-item[data-product-id="'+currentNoteProductId+'"]');
        if (item) {
          let tag = item.querySelector('.cart-note-tag');
          if (!tag && note) {
            tag = document.createElement('div');
            tag.className = 'cart-note-tag';
            tag.textContent = 'Note added';
            item.querySelector('.cart-main').appendChild(tag);
          }
          if (tag && !note) {
            tag.remove();
          }
        }
        closeNoteModal();
      } else {
        alert(data.error || 'Unable to save note. Please try again.');
      }
    })
    .catch(() => {
      alert('Unable to save note. Please try again.');
    });
  }

  document.getElementById('modifyModal').addEventListener('click', function(e) {
    if (e.target === this) closeModifyModal();
  });
  document.getElementById('noteModal').addEventListener('click', function(e) {
    if (e.target === this) closeNoteModal();
  });

  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function(e) {
      const items = document.querySelectorAll('.cart-item');
      for (const item of items) {
        if (item.dataset.hasPhotos !== '1') {
          e.preventDefault();
          alert('Please attach photos for each product before checking out.');
          item.classList.add('cart-item-missing');
          item.scrollIntoView({ behavior:'smooth', block:'center' });
          setTimeout(() => item.classList.remove('cart-item-missing'), 1500);
          return false;
        }
      }
      return true;
    });
  }

  window.openModifyModal = openModifyModal;
  window.closeModifyModal = closeModifyModal;
  window.handlePhotoUpload = handlePhotoUpload;
  window.removeNewPhoto = removeNewPhoto;
  window.deleteExistingPhoto = deleteExistingPhoto;
  window.savePhotos = savePhotos;
  window.openNoteModal = openNoteModal;
  window.closeNoteModal = closeNoteModal;
  window.saveNote = saveNote;
</script>

</body>
</html>
