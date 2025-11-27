<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();

// PREVENT PAGE CACHING
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (empty($_SESSION['customer_id'])) {
  header('Location: /login_choice.php');
  exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer_config.php';

$adminEmail = 'stellaluna022506@gmail.com';

$customerId   = (int)($_SESSION['customer_id'] ?? 0);

/* Always get real name from DB so same ra sa admin side */
$customerName = $_SESSION['customer_name'] ?? null;

if (!$customerName && $customerId) {
    $stm = $pdo->prepare("SELECT fullname FROM customers_tbl WHERE customer_id = ?");
    $stm->execute([$customerId]);
    $customerName = $stm->fetchColumn();
}

/* Fallback lang kung wala gyud name sa DB */
if (!$customerName) {
    $customerName = 'Customer #' . $customerId;
}

/* Sync back to session para magamit sa lain pages */
$_SESSION['customer_name'] = $customerName;


// ---------- SUCCESS MESSAGE (SHOW AS MODAL) ----------
$successMessage = $_SESSION['checkout_success'] ?? '';
if ($successMessage) {
  unset($_SESSION['checkout_success']);
}

// ---------- BASIC CHECKS ----------
$cart = $_SESSION['cart'] ?? [];
if (!$cart && !$successMessage) {
  header('Location: /cart.php');
  exit;
}

if (!isset($_SESSION['checkout_token'])) {
  $_SESSION['checkout_token'] = bin2hex(random_bytes(16));
}

$cartPhotos = $_SESSION['cart_photos'] ?? [];
$cartNotes  = $_SESSION['cart_notes'] ?? [];

/* ---------- NAV + NOTIFS SETUP (match cart.php) ---------- */
$isLogged    = !empty($_SESSION['customer_id']);
$notifCount  = 0;
$currentPage = '';

$notifStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM orders_tbl
  WHERE customer_id = ?
    AND status IN ('Pending','Processing','Completed', 'Ready','Refunded')
");
$notifStmt->execute([$customerId]);
$notifCount = (int)$notifStmt->fetchColumn();

if (!function_exists('nav_link_class')) {
  function nav_link_class($page){
    global $currentPage;
    return $currentPage === $page ? 'nav-link nav-active' : 'nav-link';
  }
}

$cartIconHref = $isLogged ? "/cart.php" : "/customer_login.php";

// ---------- FETCH CART PRODUCTS ----------
$productsById = [];

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT product_id, name, price, image
        FROM products_tbl
        WHERE product_id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
      $productsById[(int)$r['product_id']] = $r;
    }
}

function product_image($name, $img) {
  $n = strtolower((string)$name);

  if ($img) {
    if (strpos($img,'http') === 0) return $img;
    return '/uploads/products/'.$img;
  }

  if (strpos($n, 'instax mini') !== false)   return '/product_images/Instax Mini.png';
  if (strpos($n, 'instax small') !== false)  return '/product_images/Instax Small.png';
  if (strpos($n, 'instax square') !== false) return '/product_images/Instax Square.png';
  if (strpos($n, 'instax wide') !== false)   return '/product_images/Instax Wide.png';

  if (strpos($n,'spotify') !== false && strpos($n,'keychain') !== false)
    return '/product_images/Keychain (spotify).png';
  if (strpos($n,'keychain') !== false && strpos($n,'instax') !== false)
    return '/product_images/Keychain (instax).png';
  if (strpos($n,'keychain') !== false && strpos($n,'big') !== false)
    return '/product_images/Keychain (big).png';
  if (strpos($n,'keychain') !== false && strpos($n,'small') !== false)
    return '/product_images/Keychain (small).png';

  if (strpos($n,'holographic') !== false || strpos($n,'rainbow') !== false)
    return '/product_images/holo_rainbow.png';
  if (strpos($n,'glitter') !== false) return '/product_images/glitter.png';
  if (strpos($n,'glossy') !== false)  return '/product_images/glossy.png';
  if (strpos($n,'leather') !== false) return '/product_images/leather.png';
  if (strpos($n,'matte') !== false)   return '/product_images/matte.png';

  return 'https://via.placeholder.com/600x400.png?text=Stella%27s+Creation';
}

function save_upload($file, $destDir) {
  if (!isset($file['error'])) throw new RuntimeException('Invalid upload');
  if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error');

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $ok  = ['jpg','jpeg','png','webp'];
  if (!in_array($ext, $ok, true)) throw new RuntimeException('Invalid file type');
  if ($file['size'] > 8*1024*1024) throw new RuntimeException('File too large');

  if (!is_dir($destDir)) mkdir($destDir, 0755, true);
  $name = bin2hex(random_bytes(8)).'.'.$ext;
  $path = rtrim($destDir,'/').'/'.$name;

  if (!move_uploaded_file($file['tmp_name'], $path)) {
    throw new RuntimeException('Failed to move upload');
  }
  return $name;
}

$error = '';

$deliveryTypeOld    = 'meetup';
$meetupPlaceOld     = '';
$deliveryAddressOld = '';
$noteToSellerOld    = '';
$gcashRefOld        = '';

// ---------- HANDLE POST / PLACE ORDER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $deliveryTypeOld    = $_POST['delivery_type'] ?? 'meetup';
  $meetupPlaceOld     = $_POST['meetup_place'] ?? '';
  $deliveryAddressOld = trim($_POST['delivery_address'] ?? '');
  $noteToSellerOld    = trim($_POST['note_to_seller'] ?? '');
  $gcashRefOld        = trim($_POST['gcash_ref'] ?? '');

  try {
    if (!isset($_POST['checkout_token']) || $_POST['checkout_token'] !== $_SESSION['checkout_token']) {
      throw new RuntimeException('Invalid form submission. Please try again.');
    }

    unset($_SESSION['checkout_token']);

    $deliveryType    = $deliveryTypeOld;
    $meetupPlace     = $meetupPlaceOld;
    $deliveryAddress = $deliveryAddressOld;
    $mop             = 'GCash';
    $shippingFee     = 0;
    $noteToSeller    = $noteToSellerOld;
    $gcashRef        = $gcashRefOld;

    if (!$deliveryType) {
      throw new RuntimeException('Please choose a delivery option.');
    }

    foreach ($cart as $pid => $qty) {
      $pid = (int)$pid;
      if (!isset($productsById[$pid])) continue;
      if (empty($cartPhotos[$pid]) || !is_array($cartPhotos[$pid])) {
        $pname = $productsById[$pid]['name'] ?? 'this item';
        throw new RuntimeException('Please upload photos for "' . $pname . '" in your cart before checking out.');
      }
    }

    $extraNotes = [];

    if ($deliveryType === 'meetup') {
      if (!$meetupPlace) {
        throw new RuntimeException('Please choose a meet-up place.');
      }
      $extraNotes[] = "Delivery: Meet up at {$meetupPlace}";
    } elseif ($deliveryType === 'pickup') {
      $extraNotes[] = "Delivery: Pickup at Capehan Magsaysay Davao del Sur";
    } elseif ($deliveryType === 'delivery') {
      if ($deliveryAddress === '') {
        throw new RuntimeException('Please enter a delivery address.');
      }
      $shippingFee = 20;
      $extraNotes[] = "Delivery: To address - {$deliveryAddress} (Please include ₱20 delivery fee in your GCash payment.)";
    }

    $extraNotes[] = "Mode of payment: {$mop}";

    if ($noteToSeller !== '') {
      $words = str_word_count($noteToSeller);
      if ($words > 150) {
        throw new RuntimeException('Note to seller must be 150 words or less.');
      }
      // Still keep in notes (for DB), but we’ll format it separately in the email below.
      $extraNotes[] = "Note to Seller: {$noteToSeller}";
    }

    if ($gcashRef === '') {
      throw new RuntimeException('Please enter your GCash reference number (last digits).');
    }
    if (!preg_match('/^[0-9]{3,15}$/', $gcashRef)) {
      throw new RuntimeException('Please enter numbers only for the GCash reference (last digits).');
    }
    $extraNotes[] = "GCash Ref (last digits): {$gcashRef}";

    // For DB storage – keep everything combined
    $notes = $extraNotes ? implode('; ', $extraNotes) : null;

    $uploadDir     = __DIR__ . '/uploads/orders';
    $cartUploadDir = __DIR__ . '/uploads/cart_photos';

    $itemLines        = [];
    $grandTotal       = 0;
    $firstOrderId     = null;
    $orderIdsByProdId = [];

    foreach ($cart as $pid => $qty) {
      $pid = (int)$pid;
      $qty = (int)$qty;
      if ($qty <= 0) continue;
      if (!isset($productsById[$pid])) continue;

      $prod      = $productsById[$pid];
      $pname     = $prod['name'];
      $price     = (float)$prod['price'];
      $lineTotal = $price * $qty;
      $grandTotal += $lineTotal;

      $orderNotes = $notes;
      if (isset($cartNotes[$pid]) && trim($cartNotes[$pid]) !== '') {
        $cartNote   = trim($cartNotes[$pid]);
        $orderNotes = $orderNotes
          ? $orderNotes . '; Cart Note: ' . $cartNote
          : 'Cart Note: ' . $cartNote;
      }

      $ins = $pdo->prepare("
        INSERT INTO orders_tbl (
          customer_id,
          customer_name,
          contact_number,
          email,
          product_id,
          quantity,
          front_design,
          back_design,
          notes,
          status
        ) VALUES (?,?,?,?,?,?,?,?,?, 'Pending')
      ");

      $ins->execute([
        $customerId,
        $customerName,
        '',
        null,
        $pid,
        $qty,
        null,
        null,
        $orderNotes,
      ]);

      $orderId = (int)$pdo->lastInsertId();

      if ($firstOrderId === null) {
        $firstOrderId = $orderId;
      }

      $orderIdsByProdId[$pid] = $orderId;

      $itemLines[] = "#{$orderId} – ".htmlspecialchars($pname,ENT_QUOTES,'UTF-8')
                   ." x {$qty} (₱".number_format($lineTotal,2).")";
    }

    if ($firstOrderId) {
      $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");

      foreach ($cartPhotos as $pid => $files) {
        $pid = (int)$pid;
        if (!isset($orderIdsByProdId[$pid])) continue;
        $orderId = $orderIdsByProdId[$pid];

        if (!is_array($files)) continue;

        foreach ($files as $filename) {
          $src = rtrim($cartUploadDir, '/').'/'.$filename;
          if (!is_file($src)) continue;

          $ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          $newName = bin2hex(random_bytes(8)).'.'.$ext;
          $dest    = rtrim($uploadDir, '/').'/'.$newName;

          if (@rename($src, $dest)) {
            $up->execute([$orderId, 'gallery', $newName]);
          }
        }
      }
    }

       $grandTotalWithShip = $grandTotal + $shippingFee;

    // ---------- BUILD CUTE ADMIN EMAIL (using shared template) ----------
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host    = $_SERVER['HTTP_HOST'] ?? 'stellascreation.shop';
    $baseUrl = $scheme . $host;

    // dashboard link
    $adminLink = $baseUrl . '/dashboard.php';

    // Items list HTML
    $itemsHtml = '';
    foreach ($itemLines as $line) {
      $itemsHtml .= '<li style="margin-bottom:4px;">'.$line.'</li>';
    }

    // Options list – exclude "Note to Seller" so it’s not duplicated
    $optionsForEmail = [];
    foreach ($extraNotes as $noteLine) {
      if (stripos($noteLine, 'Note to Seller:') === 0) {
        continue;
      }
      $optionsForEmail[] = $noteLine;
    }

    if ($optionsForEmail) {
      $optionsHtml = '<ul style="margin:6px 0 0;padding-left:18px;font-size:13px;color:#555;">';
      foreach ($optionsForEmail as $opt) {
        $optionsHtml .= '<li>'.htmlspecialchars($opt, ENT_QUOTES, 'UTF-8').'</li>';
      }
      $optionsHtml .= '</ul>';
    } else {
      $optionsHtml = '<p style="margin:6px 0 0;font-size:13px;color:#777;"><em>None</em></p>';
    }

    // Note to seller section
    if ($noteToSeller !== '') {
      $noteHtml = nl2br(htmlspecialchars($noteToSeller, ENT_QUOTES, 'UTF-8'));
    } else {
      $noteHtml = '<em>None</em>';
    }

    $safeCustomer = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');

    // 👉 Content sulod sa email body (WITHOUT header/footer & button)
    $adminInnerHtml = '
      <p style="margin:0 0 8px;">You just received a new cart checkout:</p>
      <p style="margin:0 0 10px;">
        <strong>Customer:</strong> '.$safeCustomer.'
      </p>

      <div style="margin:10px 0 6px;font-weight:600;font-size:14px;color:#2d3748;">
        Items
      </div>
      <ul style="margin:4px 0 10px;padding-left:18px;font-size:13px;color:#555;">
        '.$itemsHtml.'
      </ul>

      <p style="margin:8px 0 2px;font-size:13px;">
        <strong>Subtotal:</strong> ₱'.number_format($grandTotal,2).'
      </p>';

    if ($shippingFee > 0) {
      $adminInnerHtml .= '
      <p style="margin:2px 0;font-size:13px;">
        <strong>Shipping Fee:</strong> ₱'.number_format($shippingFee,2).'
      </p>';
    }

    $adminInnerHtml .= '
      <p style="margin:2px 0 10px;font-size:13px;">
        <strong>Total:</strong> ₱'.number_format($grandTotalWithShip,2).'
      </p>

      <div style="margin:10px 0 4px;font-weight:600;font-size:14px;color:#2d3748;">
        Options
      </div>
      '.$optionsHtml.'

      <div style="margin:14px 0 4px;font-weight:600;font-size:14px;color:#2d3748;">
        Note to Seller
      </div>
      <p style="margin:4px 0 10px;font-size:13px;color:#555;">
        '.$noteHtml.'
      </p>

      <p style="margin:8px 0 4px;font-size:13px;color:#555;">
        <strong>Status:</strong> Pending
      </p>
    ';

    // Subject para sa Gmail
    $emailSubject = "New Cart Checkout - Stella's Creation";

    // Wrap content gamit ang shared template + admin button
    $adminHtml = buildScEmail($emailSubject, $adminInnerHtml, true, $adminLink);

    // send pretty admin email
    sendMailMessage($adminEmail, $emailSubject, $adminHtml);


    // send pretty admin email
    sendMailMessage($adminEmail, "New Cart Checkout - Stella's Creation", $adminHtml);

    $_SESSION['cart'] = [];
    unset($_SESSION['cart_photos'], $_SESSION['cart_notes']);

    $_SESSION['checkout_success'] = "Thank you! Your order has been received. We'll review your payment and GCash reference, then confirm your order via your account.";

    header('Location: /checkout.php');
    exit;
  }
  catch (Throwable $e) {
    $error = $e->getMessage();
    $_SESSION['checkout_token'] = bin2hex(random_bytes(16));
  }
}

// ---------- CALCULATE SUBTOTAL ----------
$total = 0;
foreach ($cart as $pid => $qty) {
  if (!isset($productsById[$pid])) continue;
  $total += $productsById[$pid]['price'] * $qty;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Checkout — Stella's Creation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/style.css">
  <style>
   html,body{
        margin:0;
        padding:0;
        min-height:100%;
    }
    body{
        font-family:'Poppins',sans-serif;
        display:flex;
        flex-direction:column;
    }

    .page-main{flex:1;padding:30px 12px 40px;}

    .checkout-shell{max-width:1100px;margin:0 auto;width:100%;}

    .checkout-card{
        background:#ffffff;
        border-radius:24px;
        box-shadow:0 18px 45px rgba(200, 162, 200, 0.45);
        padding:32px;
        box-sizing:border-box;
    }

    .checkout-grid{
      display:grid;
      grid-template-columns:350px 1fr;
      gap:32px;
    }

    .checkout-left h2,
    .checkout-right h3{
      margin:0 0 20px;
      font-size:1.5rem;
      color:#2d3748;
      font-weight:600;
    }

    .co-cart-item{
      display:flex;
      align-items:center;
      gap:12px;
      padding:12px 0;
      border-bottom:1px solid #e2e8f0;
    }
    .co-cart-item:last-child{border-bottom:none;}

    .co-cart-item img{
      width:70px;
      height:70px;
      border-radius:12px;
      object-fit:cover;
      box-shadow:0 2px 8px rgba(0,0,0,0.1);
    }

    .co-cart-main{flex:1;}
    .co-name{font-size:.95rem;font-weight:600;color:#2d3748;margin-bottom:4px;}
    .co-qty{font-size:.82rem;color:#718096;}
    .co-price{font-size:1rem;font-weight:700;color:#E75480;white-space:nowrap;}

    .co-total-row{
      text-align:right;
      margin-top:16px;
      padding-top:16px;
      border-top:2px solid #e2e8f0;
      font-weight:700;
      font-size:1.1rem;
      color:#2d3748;
    }

    .order-field{margin-bottom:16px;font-size:.88rem;}
    .order-field label{
      display:block;
      margin-bottom:6px;
      font-weight:600;
      color:#2d3748;
    }
    .order-field input,
    .order-field select,
    .order-field textarea{
      width:100%;
      padding:10px 14px;
      border-radius:12px;
      border:2px solid #e2e8f0;
      font-size:.88rem;
      box-sizing:border-box;
      font-family:'Poppins',sans-serif;
      transition:all 0.3s ease;
    }
    .order-field input:focus,
    .order-field select:focus,
    .order-field textarea:focus{
      outline:none;
      border-color:#C8A2C8;
      box-shadow:0 0 0 3px rgba(200,162,200,0.1);
    }

    .order-section{
      background:#faf8fc;
      border-radius:16px;
      padding:20px;
      margin-top:20px;
      border:1px solid #f0e6f0;
    }
    .order-section h4{
      margin:0 0 16px;
      font-size:1rem;
      color:#2d3748;
      font-weight:600;
    }

    .order-delivery-group{display:flex;flex-direction:column;gap:12px;}

    .order-pill-row{
      position:relative;
      padding:16px 16px 16px 48px;
      border-radius:16px;
      background:#fff;
      border:2px solid #f0e6f0;
      cursor:pointer;
      transition:all 0.3s ease;
    }
    .order-pill-row:hover{
      border-color:#C8A2C8;
      box-shadow:0 4px 12px rgba(200,162,200,0.15);
    }
    .order-pill-row input[type="radio"]{
      position:absolute;
      left:18px;
      top:20px;
      width:18px;
      height:18px;
      cursor:pointer;
      accent-color:#C8A2C8;
    }
    .order-pill-row input[type="radio"]:checked ~ .order-pill-main{
      color:#C8A2C8;
    }

    .order-pill-title{
      font-size:.92rem;
      font-weight:600;
      color:#2d3748;
    }

    .order-mop-pill{
      display:inline-flex;
      align-items:center;
      padding:10px 20px;
      border-radius:999px;
      background:#C8A2C8;
      color:#fff;
      font-size:.88rem;
      font-weight:600;
      box-shadow:0 4px 12px rgba(200,162,200,0.3);
    }

    .note-section{
      background:#fff8e7;
      border:2px dashed #f39c12;
      border-radius:16px;
      padding:20px;
      margin-top:0;
    }
    .note-section h4{
      margin:0 0 12px;
      font-size:.95rem;
      color:#2d3748;
      display:flex;
      align-items:center;
      gap:8px;
      font-weight:600;
    }
    .note-section h4 i{color:#f39c12;}
    .note-section textarea{
      width:100%;
      min-height:90px;
      padding:12px;
      border-radius:12px;
      border:2px solid #f39c12;
      font-size:.86rem;
      box-sizing:border-box;
      resize:vertical;
      font-family:'Poppins',sans-serif;
      background:#fff;
    }
    .note-section textarea:focus{
      outline:none;
      border-color:#e67e22;
      box-shadow:0 0 0 3px rgba(243,156,18,0.1);
    }

    .word-count{
      font-size:.75rem;
      color:#718096;
      text-align:right;
      margin-top:6px;
      font-weight:500;
    }
    .word-count.over{
      color:#e53e3e;
      font-weight:700;
    }

    .order-actions{margin-top:24px;text-align:right;}

    .btn.primary{
      padding:14px 40px;
      background:#C8A2C8;
      color:#fff;
      border:none;
      border-radius:999px;
      font-size:1rem;
      font-weight:600;
      cursor:pointer;
      transition:all 0.3s ease;
      box-shadow:0 4px 20px rgba(200,162,200,0.3);
      font-family:'Poppins',sans-serif;
    }
    .btn.primary:hover{
      background:#E75480;
      transform:translateY(-2px);
      box-shadow:0 6px 25px rgba(231,84,128,0.4);
    }
    .btn.primary:active{
      transform:translateY(0);
    }
    .btn.primary:disabled{
      opacity:0.6;
      cursor:not-allowed;
      transform:none;
    }

    .order-error{
      color:#e53e3e;
      background:#fff5f5;
      border:2px solid #fc8181;
      border-radius:12px;
      padding:12px 16px;
      font-size:.9rem;
      margin-bottom:16px;
      font-weight:500;
    }

    /* SUCCESS MODAL */
    .success-modal-overlay{
      position:fixed;
      top:0;
      left:0;
      right:0;
      bottom:0;
      background:rgba(0,0,0,0.7);
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:10000;
      padding:20px;
      animation:fadeIn 0.3s ease;
    }
    @keyframes fadeIn{
      from{opacity:0;}
      to{opacity:1;}
    }

    .success-modal{
      background:#fff;
      border-radius:24px;
      box-shadow:0 20px 60px rgba(0,0,0,0.3);
      padding:40px;
      max-width:500px;
      width:100%;
      text-align:center;
      animation:slideUp 0.4s ease;
    }
    @keyframes slideUp{
      from{transform:translateY(30px);opacity:0;}
      to{transform:translateY(0);opacity:1;}
    }

    .success-icon{
      font-size:72px;
      color:#48bb78;
      margin-bottom:24px;
      animation:scaleIn 0.5s ease 0.2s both;
    }
    @keyframes scaleIn{
      from{transform:scale(0);opacity:0;}
      to{transform:scale(1);opacity:1;}
    }

    .success-modal h2{
      color:#2d3748;
      margin:0 0 16px;
      font-size:1.8rem;
      font-weight:700;
    }
    .success-modal p{
      color:#718096;
      line-height:1.7;
      margin-bottom:28px;
      font-size:1rem;
    }
    .success-modal .btn{
      display:inline-block;
      padding:14px 36px;
      background:#C8A2C8;
      color:#fff;
      text-decoration:none;
      border-radius:999px;
      font-weight:600;
      font-size:1rem;
      box-shadow:0 4px 20px rgba(200,162,200,0.3);
      transition:all 0.3s ease;
    }
    .success-modal .btn:hover{
      background:#E75480;
      transform:translateY(-2px);
      box-shadow:0 6px 25px rgba(231,84,128,0.4);
    }
    .success-modal .secondary-link{
      display:block;
      margin-top:16px;
      color:#C8A2C8;
      text-decoration:none;
      font-weight:500;
      transition:color 0.3s ease;
    }
    .success-modal .secondary-link:hover{
      color:#E75480;
    }

    @media(max-width:840px){
      .checkout-card{padding:24px;}
      .checkout-grid{grid-template-columns:1fr;}
      .success-modal{padding:32px 24px;}
    }

    .nav-icons .icon-btn{position:relative;}
    .nav-icons .icon-btn .notif-badge{
      position:absolute;
      top:-4px;
      right:-4px;
      min-width:18px;
      height:18px;
      padding:0 4px;
      border-radius:999px;
      background:#e75480;
      color:#fff;
      font-size:11px;
      font-weight:600;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* BURGER BUTTON (same as cart.php) */
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

<!-- SIDEBAR (same structure as cart.php) -->
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

<?php if ($successMessage): ?>
<div class="success-modal-overlay" id="successModal">
  <div class="success-modal">
    <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
    <h2>Order Placed Successfully!</h2>
    <p><?= htmlspecialchars($successMessage) ?></p>
    <a href="/my_orders.php" class="btn">View My Orders</a>
    <a href="/shop.php" class="secondary-link">Continue Shopping</a>
  </div>
</div>
<?php endif; ?>

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
              Hello, <?= htmlspecialchars($customerName) ?>!
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
  <div class="checkout-shell">
    <div class="checkout-card">
      <div class="checkout-grid">
        <div class="checkout-left">
          <h2>Your Cart</h2>
          <?php foreach ($cart as $pid => $qty): ?>
            <?php
              if (!isset($productsById[$pid])) continue;
              $row = $productsById[$pid];
              $img = product_image($row['name'], $row['image']);
              $lineTotal = $row['price'] * $qty;
            ?>
            <div class="co-cart-item">
              <img src="<?= htmlspecialchars($img) ?>" alt="">
              <div class="co-cart-main">
                <div class="co-name"><?= htmlspecialchars($row['name']) ?></div>
                <div class="co-qty">Quantity: <?= (int)$qty ?></div>
              </div>
              <div class="co-price">₱<?= number_format($lineTotal,2) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="co-total-row">
            Subtotal: ₱<?= number_format($total,2) ?>
          </div>
          <p style="font-size:.82rem;color:#718096;margin-top:8px;line-height:1.5;">
            Shipping fee (₱20) will be added if you choose Delivery.
          </p>
        </div>

        <div class="checkout-right">
          <h3>Delivery & Payment</h3>

          <?php if ($error): ?>
            <p class="order-error"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" id="checkoutForm">
            <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token']) ?>">

            <div class="note-section">
              <h4>
                <i class="fa-solid fa-message"></i> Note to Seller (Optional)
              </h4>
              <textarea
                name="note_to_seller"
                id="note_to_seller"
                placeholder="Add any special instructions or notes (up to 150 words)..."
                oninput="updateWordCount()"
              ><?= htmlspecialchars($noteToSellerOld) ?></textarea>
              <div class="word-count" id="wordCount">0 / 150 words</div>
            </div>

            <div class="order-section">
              <h4>Delivery Option</h4>
              <div class="order-delivery-group">
                <label class="order-pill-row">
                  <input type="radio" name="delivery_type" value="meetup"
                         <?= $deliveryTypeOld === 'meetup' ? 'checked' : '' ?>>
                  <div class="order-pill-main">
                    <div class="order-pill-title">Meet up</div>
                    <select name="meetup_place" style="display:none;margin-top:8px;">
                      <option value="">-- Select Meet-up Place --</option>
                      <option value="SMCBI Campus" <?= $meetupPlaceOld==='SMCBI Campus' ? 'selected' : '' ?>>SMCBI Campus</option>
                      <option value="Barayong NHS" <?= $meetupPlaceOld==='Barayong NHS' ? 'selected' : '' ?>>Barayong NHS</option>
                    </select>
                  </div>
                </label>

                <label class="order-pill-row">
                  <input type="radio" name="delivery_type" value="pickup"
                         <?= $deliveryTypeOld === 'pickup' ? 'checked' : '' ?>>
                  <div class="order-pill-main">
                    <div class="order-pill-title">Pickup</div>
                    <input type="text" value="Capehan Magsaysay Davao del Sur" disabled style="display:none;margin-top:8px;">
                  </div>
                </label>

                <label class="order-pill-row">
                  <input type="radio" name="delivery_type" value="delivery"
                         <?= $deliveryTypeOld === 'delivery' ? 'checked' : '' ?>>
                  <div class="order-pill-main">
                    <div class="order-pill-title">Delivery (with shipping fee)</div>
                    <textarea name="delivery_address" rows="2" placeholder="Enter delivery address" disabled style="display:none;margin-top:8px;"><?= htmlspecialchars($deliveryAddressOld) ?></textarea>
                  </div>
                </label>
              </div>

              <div class="order-field" style="margin-top:20px;">
                <label>Mode of Payment</label>
                <div class="order-mop-pill">GCash only (09757944649)</div>
                <p style="font-size:.82rem;margin-top:10px;color:#718096;line-height:1.6;">
                  Payments are accepted via GCash only. If you choose Delivery, please add ₱20 for the shipping fee when you send your GCash payment.
                </p>
              </div>

              <div class="order-field">
                <label for="gcash_ref">GCash reference number (last 5 digits)</label>
                <input
                  type="number"
                  name="gcash_ref"
                  id="gcash_ref"
                  min="0"
                  max="99999"
                  step="1"
                  placeholder="e.g. 12345"
                  value="<?= htmlspecialchars($gcashRefOld) ?>"
                  oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5);"
                  pattern="[0-9]{5}"
                  required
                >
                <div style="font-size:.75rem;color:#718096;margin-top:4px;">Enter exactly 5 digits</div>
              </div>
            </div>

            <div class="order-section">
              <h4>Photos for Your Order</h4>
              <p style="font-size:.82rem;margin-top:8px;color:#718096;line-height:1.6;">
                Photos are attached on the cart page for each product. If you need to change them, go back to your cart and tap <strong>Modify Photos</strong> for that item.
              </p>
            </div>

            <div class="order-actions">
              <button type="submit" class="btn primary" id="submitBtn">Place Order</button>
            </div>
          </form>
        </div>
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
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  /* Sidebar + burger + search (same as cart.php) */
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

  const checkoutForm = document.getElementById('checkoutForm');
  const submitBtn = document.getElementById('submitBtn');
  let isSubmitting = false;

  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
      if (isSubmitting) {
        e.preventDefault();
        return false;
      }

      isSubmitting = true;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
      submitBtn.style.opacity = '0.6';
    });
  }

  const userMenuBtn = document.querySelector('.user-menu-btn');
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

  function updateDeliveryInputs() {
    const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value;
    const meetupSelect = document.querySelector('select[name="meetup_place"]');
    const deliveryTextarea = document.querySelector('textarea[name="delivery_address"]');
    const pickupInput = document.querySelector('input[value="Capehan Magsaysay Davao del Sur"]');

    if (meetupSelect) {
      meetupSelect.style.display = 'none';
      meetupSelect.disabled = true;
    }
    if (deliveryTextarea) {
      deliveryTextarea.style.display = 'none';
      deliveryTextarea.disabled = true;
    }
    if (pickupInput) {
      pickupInput.style.display = 'none';
    }

    if (selectedType === 'meetup' && meetupSelect) {
      meetupSelect.style.display = 'block';
      meetupSelect.disabled = false;
    } else if (selectedType === 'delivery' && deliveryTextarea) {
      deliveryTextarea.style.display = 'block';
      deliveryTextarea.disabled = false;
    } else if (selectedType === 'pickup' && pickupInput) {
      pickupInput.style.display = 'block';
    }
  }

  updateDeliveryInputs();

  document.querySelectorAll('input[name="delivery_type"]').forEach(function(radio) {
    radio.addEventListener('change', updateDeliveryInputs);
  });

  function updateWordCount() {
    const textarea = document.getElementById('note_to_seller');
    const wordCountDiv = document.getElementById('wordCount');

    if (!textarea || !wordCountDiv) return;

    const text = textarea.value.trim();
    const wordCount = text === '' ? 0 : text.split(/\s+/).length;

    wordCountDiv.textContent = `${wordCount} / 150 words`;

    if (wordCount > 150) {
      wordCountDiv.classList.add('over');
    } else {
      wordCountDiv.classList.remove('over');
    }
  }

  updateWordCount();

  // Success modal close functionality
  const successModal = document.getElementById('successModal');
  if (successModal) {
    successModal.addEventListener('click', function(e) {
      if (e.target === successModal) {
        window.location.href = '/shop.php';
      }
    });
  }
</script>
</body>
</html>
