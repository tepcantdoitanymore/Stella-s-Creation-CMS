<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$isLogged    = !empty($_SESSION['customer_id']);
$notifCount  = 0;
$currentPage = 'about';

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

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$cartIconHref  = $isLogged ? '/cart.php'      : '/login_choice.php';
$notifIconHref = $isLogged ? '/my_orders.php' : '/login_choice.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>About · Stella’s Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">
  <style>
.about-page-main { padding:40px 0 60px; }
.about-section { padding:60px 7%; }
.about-card {
  display:flex;
  gap:40px;
  background:#fdf5f6;
  border-radius:24px;
  padding:32px;
  box-shadow:0 18px 40px rgba(0,0,0,0.06);
}
.about-photo img {
  width:100%;
  max-width:420px;
  height:420px;
  object-fit:cover;
  border-radius:20px;
}
.about-content {
  flex:1;
  display:flex;
  flex-direction:column;
  justify-content:center;
}
.about-tag {
  font-size:0.8rem;
  text-transform:uppercase;
  color:#b1958e;
  margin-bottom:8px;
  letter-spacing:0.16em;
}
.about-name {
  font-size:2.1rem;
  font-weight:700;
  color:#3b2a35;
  margin-bottom:12px;
}
.about-intro {
  font-weight:600;
  color:#5b4451;
  margin-bottom:10px;
}
.about-body,
.about-more {
  color:#7b6472;
  line-height:1.7;
  font-size:0.96rem;
  margin-bottom:20px;
}
.about-more { display:none; }
.btn-about {
  padding:10px 24px;
  border-radius:999px;
  background:#f1c4d8;
  color:#3b2a35;
  border:none;
  font-weight:600;
  font-size:0.9rem;
  cursor:pointer;
  box-shadow:0 8px 18px rgba(0,0,0,0.06);
}
.btn-about:hover { background:#e6b0cc; }
.about-social-links {
  margin-top:40px;
  text-align:center;
  padding-bottom:40px;
}
.about-social-links a {
  margin:0 14px;
  font-size:1.8rem;
  color:#b1958e;
  transition:0.2s;
}
.about-social-links a:hover { color:#3b2a35; }

@media (max-width:900px) {
  .about-card {
    flex-direction:column;
    padding:26px 22px;
  }
  .about-photo img {
    max-width:100%;
    height:260px;
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
        onclick="window.location.href='<?= htmlspecialchars($notifIconHref) ?>'"
      >
        <i class="fa-regular fa-bell"></i>
        <?php if (!empty($notifCount)): ?>
          <span class="notif-badge"><?= $notifCount ?></span>
        <?php endif; ?>
      </button>

      <?php if (!empty($_SESSION['customer_id'])): ?>
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
      <input type="text" name="q" placeholder="Search keychains, photocards, ref magnets...">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
  </div>
</div>

<main class="about-page-main">
  <section class="about-section">
    <div class="about-card">
      <div class="about-photo">
        <img src="/images/teptep.png" alt="Owner - Tiffany Laganse">
      </div>

      <div class="about-content">
        <h3 class="about-tag">Owner</h3>
        <h2 class="about-name">Tiffany Laganse</h2>

        <p class="about-intro">
          Hi, I’m Tiffany — the face behind Stella’s Creation, where your memories are turned into cute keepsakes.
        </p>

        <p class="about-body">
          Stella’s Creation started with a simple love for printing photos and decorating small gifts for friends.
          What began as a hobby slowly grew into a small business...
        </p>

        <div class="about-more" id="aboutMore">
          <p>
            Each item is made carefully — from cleaning your photos, adjusting the colors, to laminating and assembling
            every piece by hand...
          </p>
          <p>
            My goal is to keep Stella’s Creation student-friendly, gift-friendly, and always approachable...
          </p>
        </div>

        <button class="btn-about" id="readMoreBtn">Read more</button>
      </div>
    </div>
  </section>
</main>

<div class="about-social-links">
  <a href="https://www.facebook.com/abella.tiffany" target="_blank">
    <i class="fa-brands fa-facebook-f"></i>
  </a>
  <a href="https://www.instagram.com/tiffaee/#" target="_blank">
    <i class="fa-brands fa-instagram"></i>
  </a>
  <a href="https://www.tiktok.com/@tiffany0_duh" target="_blank">
    <i class="fa-brands fa-tiktok"></i>
  </a>
</div>

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
  const readMoreBtn     = document.getElementById('readMoreBtn');
  const aboutMore       = document.getElementById('aboutMore');

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

  // 🔹 Burger: toggle animation + open sidebar
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

  if (readMoreBtn && aboutMore) {
    readMoreBtn.addEventListener('click', () => {
      const isShown = aboutMore.style.display === 'block';
      aboutMore.style.display = isShown ? 'none' : 'block';
      readMoreBtn.textContent = isShown ? 'Read more' : 'Read less';
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
