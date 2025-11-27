<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
$customerId = $_SESSION['customer_id'] ?? null;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer_config.php';

$adminEmail = 'stellaluna022506@gmail.com';

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
  if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('Failed to move upload');
  return $name;
}

function requiredPhotoCountByName(string $pname): int {
  if (preg_match('/\b(\d+)\s*pcs\b/i', $pname, $m)) return (int)$m[1];
  return 0;
}

function countUploaded(array $files): int {
  if (!isset($files['name'])) return 0;
  if (is_array($files['name'])) {
    $cnt = 0;
    $n = count($files['name']);
    for ($i=0; $i<$n; $i++) {
      $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
      if ($err !== UPLOAD_ERR_NO_FILE) $cnt++;
    }
    return $cnt;
  } else {
    return ($files['error'] === UPLOAD_ERR_NO_FILE) ? 0 : 1;
  }
}

function getProductImage(string $pname): ?string {
  if (stripos($pname, 'Photobooth') !== false && stripos($pname, 'big') !== false) {
    return '/product_images/Keychain (big).png';
  }
  if (stripos($pname, 'Photobooth') !== false && stripos($pname, 'small') !== false) {
    return '/product_images/Keychain (small).png';
  }
  if (stripos($pname, 'Photobooth') !== false && stripos($pname, 'spotify') !== false) {
    return '/product_images/Keychain (spotify).png';
  }
  if (stripos($pname, 'Photobooth') !== false && stripos($pname, 'Keychain') !== false) {
    return '/product_images/Keychain (photobooth).png';
  }
  if (stripos($pname, 'Keychain') !== false && stripos($pname, 'instax') !== false) {
    return '/product_images/Keychain (instax).png';
  }
  if (stripos($pname, 'Instax Mini') !== false) return '/product_images/Instax Mini.png';
  if (stripos($pname, 'Instax Small') !== false) return '/product_images/Instax Small.png';
  if (stripos($pname, 'Instax Square') !== false) return '/product_images/Instax Square.png';
  if (stripos($pname, 'Instax Wide') !== false) return '/product_images/Instax Wide.png';
  if (stripos($pname, 'Photocard') !== false || stripos($pname, 'Photo Card') !== false)
    return '/product_images/Photocard.png';
  if (stripos($pname, 'Ref Magnet') !== false)
    return '/product_images/refmagnet.png';
  return null;
}

$group = $_GET['group'] ?? null;

$sql = "SELECT product_id, name, price FROM products_tbl WHERE is_active=1";
if ($group === 'photocard') {
  $sql .= " AND (name LIKE '%Photo Card%' OR name LIKE '%Photocard%')";
} elseif ($group === 'ref_magnet') {
  $sql .= " AND name LIKE '%Ref Magnet%'";
} elseif ($group === 'keychain') {
  $sql .= " AND name LIKE '%Keychain%'";
}
$sql .= " ORDER BY name";

$productsRaw = $pdo->query($sql)->fetchAll();

$defaultProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$defaultProduct   = null;
if ($defaultProductId) {
  $tmpSt = $pdo->prepare("SELECT product_id, name, price FROM products_tbl WHERE product_id=? AND is_active=1 LIMIT 1");
  $tmpSt->execute([$defaultProductId]);
  $defaultProduct = $tmpSt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$forcedPhotoTop = '';
if ($defaultProductId) {
  foreach ($productsRaw as $raw) {
    if ((int)$raw['product_id'] === $defaultProductId) {
      $ln = strtolower($raw['name']);
      if (strpos($ln, 'holo') !== false || strpos($ln, 'rainbow') !== false) {
        $forcedPhotoTop = 'Holo Rainbow';
      } elseif (strpos($ln, 'leather') !== false) {
        $forcedPhotoTop = 'Leather';
      } elseif (strpos($ln, 'glossy') !== false) {
        $forcedPhotoTop = 'Glossy';
      } elseif (strpos($ln, 'glitter') !== false) {
        $forcedPhotoTop = 'Glitter';
      } elseif (strpos($ln, 'matte') !== false) {
        $forcedPhotoTop = 'Matte';
      }
      break;
    }
  }
}

$products = [];
$seenPhotocard = false;
$seenRefMagnet = false;

foreach ($productsRaw as $p) {
  $name = $p['name'];
  if (stripos($name, 'Photocard') !== false || stripos($name, 'Photo Card') !== false) {
    if ($seenPhotocard) continue;
    $seenPhotocard = true;
    $p['name'] = 'Photocard';
  } elseif (stripos($name, 'Ref Magnet') !== false) {
    if ($seenRefMagnet) continue;
    $seenRefMagnet = true;
    $p['name'] = 'Ref Magnet';
  }
  $products[] = $p;
}

$success = '';
$error   = '';

$placeAnotherHref = 'order_form.php';
$q = [];
if ($group) $q['group'] = $group;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
  $q['product_id'] = (int)$_POST['product_id'];
} elseif ($defaultProductId) {
  $q['product_id'] = $defaultProductId;
}
if (!empty($q)) {
  $placeAnotherHref .= '?' . http_build_query($q);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // ✅ Use real customer name instead of "Customer #ID"
    $customer = 'Guest';

    if ($customerId) {
      // Try from session first
      if (!empty($_SESSION['customer_name'])) {
        $customer = $_SESSION['customer_name'];
      } else {
        // Fetch from DB if not in session
        $stm = $pdo->prepare("SELECT fullname FROM customers_tbl WHERE customer_id = ?");
        $stm->execute([$customerId]);
        $foundName = $stm->fetchColumn();

        if ($foundName) {
          $customer = $foundName;
          $_SESSION['customer_name'] = $foundName; // sync to session
        } else {
          $customer = 'Customer #' . $customerId;
        }
      }
    }

    $contact  = '';
    $email    = '';


    $product  = (int)($_POST['product_id'] ?? 0);
    $qty      = max(1, (int)($_POST['quantity'] ?? 1));

    $deliveryType    = $_POST['delivery_type'] ?? null;
    $meetupPlace     = $_POST['meetup_place'] ?? null;
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $mop             = $_POST['mop'] ?? 'GCash';
    $gcashRefNumber  = trim($_POST['gcash_ref_number'] ?? '');

    $photoTopType       = trim($_POST['photo_top_type'] ?? '');
    $keychainColor      = trim($_POST['keychain_color'] ?? '');
    $templateChoice     = trim($_POST['template_choice'] ?? '');
    $spotifySongTitle   = trim($_POST['spotify_song_title'] ?? '');
    $spotifySongArtist  = trim($_POST['spotify_song_artist'] ?? '');
    $instaxMessage      = trim($_POST['instax_message'] ?? '');
    $shippingFee        = 0;

    if (!$product) {
      throw new RuntimeException('Please select a product.');
    }

    $prodStmt = $pdo->prepare("SELECT product_id, name, price FROM products_tbl WHERE product_id = ? AND is_active = 1 LIMIT 1");
    $prodStmt->execute([$product]);
    $selected = $prodStmt->fetch(PDO::FETCH_ASSOC);
    if (!$selected) {
      throw new RuntimeException('Invalid product.');
    }

    $pname     = $selected['name'];
    $basePrice = (float)$selected['price'];

    $isPhotocardProduct     = stripos($pname, 'Photocard') !== false || stripos($pname, 'Photo Card') !== false;
    $isRefMagnetProduct     = stripos($pname, 'Ref Magnet') !== false;
    $isSpotifyKeychainProd  = stripos($pname, 'Keychain') !== false && stripos($pname, 'Spotify') !== false;
    $isInstaxKeychainProd   = stripos($pname, 'Keychain') !== false && stripos($pname, 'Instax') !== false;
    $isPhotoboothBigProd    = stripos($pname, 'Photobooth Keychain') !== false && stripos($pname, 'Big') !== false;
    $isPhotoboothSmallProd  = stripos($pname, 'Photobooth Keychain') !== false && stripos($pname, 'Small') !== false;

    $photoPrices = [
      'photocard' => [
        'Holo Rainbow' => 12,
        'Leather'      => 8,
        'Glossy'       => 6,
        'Glitter'      => 6,
        'Matte'        => 6,
      ],
      'ref_magnet' => [
        'Holo Rainbow' => 18,
        'Leather'      => 15,
        'Glossy'       => 12,
        'Glitter'      => 15,
        'Matte'        => 12,
      ],
    ];

    $price      = $basePrice;
    $extraNotes = [];

    if ($photoTopType !== '') {
      $extraNotes[] = "Photo Top: {$photoTopType}";
      if ($isPhotocardProduct && isset($photoPrices['photocard'][$photoTopType])) {
        $price = $photoPrices['photocard'][$photoTopType];
      } elseif ($isRefMagnetProduct && isset($photoPrices['ref_magnet'][$photoTopType])) {
        $price = $photoPrices['ref_magnet'][$photoTopType];
      }
    }

    if ($keychainColor !== '') {
      $parts = explode('|', $keychainColor, 2);
      $hex   = $parts[0] ?? '';
      $label = $parts[1] ?? $hex;
      $extraNotes[] = "Keychain Color: {$label} ({$hex})";
    }

    if ($templateChoice !== '') {
      $extraNotes[] = "Template: {$templateChoice}";
    }

    if ($isSpotifyKeychainProd) {
      if ($spotifySongTitle === '' || $spotifySongArtist === '') {
        throw new RuntimeException('Please enter the song title and artist for your Spotify keychain.');
      }
      $extraNotes[] = "Spotify Song: {$spotifySongTitle} – {$spotifySongArtist}";
    }

    if ($isInstaxKeychainProd) {
      if ($instaxMessage === '') {
        throw new RuntimeException('Please enter a short message for your Instax keychain.');
      }
      $words = preg_split('/\s+/', trim($instaxMessage));
      $wordCount = $instaxMessage === '' ? 0 : count($words);
      if ($wordCount > 20) {
        throw new RuntimeException('Short message must be 20 words or less.');
      }
      $extraNotes[] = "Instax Message: {$instaxMessage}";
    }

    if (!$deliveryType) {
      throw new RuntimeException('Please choose a delivery option.');
    }

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

    if ($mop) {
      $extraNotes[] = "Mode of payment: {$mop}";
    }

    // Validate GCash reference number
    if ($mop === 'GCash') {
      if ($gcashRefNumber === '') {
        throw new RuntimeException('Please enter the last 5 digits of your GCash reference number.');
      }
      if (!preg_match('/^\d{5}$/', $gcashRefNumber)) {
        throw new RuntimeException('GCash reference number must be exactly 5 digits.');
      }
      $extraNotes[] = "GCash Ref (last digits): {$gcashRefNumber}";
    }

    $notes = $extraNotes ? implode('; ', $extraNotes) : null;

    $total = $qty * $price + $shippingFee;

    $isFrontBack   = (stripos($pname, 'Acrylic Keychain Instax') !== false) || (stripos($pname, 'Photocard') !== false);
    $isInstaxMulti = (stripos($pname, 'Instax') !== false) && (stripos($pname, 'Keychain') === false);

    $stmt = $pdo->prepare("INSERT INTO orders_tbl (
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
    ) VALUES (?,?,?,?,?,?,?,?,?, 'Pending')");

    $stmt->execute([
      $customerId,
      $customer,
      $contact,
      $email ?: null,
      $product,
      $qty,
      null,
      null,
      $notes,
    ]);

    $orderId   = (int)$pdo->lastInsertId();
    $uploadDir = __DIR__ . '/uploads/orders';
    $frontName = null;
    $backName  = null;

    if ($isFrontBack) {
      if (empty($_FILES['front_design']) || $_FILES['front_design']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Front design required');
      }
      $frontName = save_upload($_FILES['front_design'], $uploadDir);
      if (!empty($_FILES['back_design']) && $_FILES['back_design']['error'] !== UPLOAD_ERR_NO_FILE) {
        $backName = save_upload($_FILES['back_design'], $uploadDir);
      }
      $pdo->prepare("UPDATE orders_tbl SET front_design=?, back_design=? WHERE order_id=?")
          ->execute([$frontName, $backName, $orderId]);

      $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");
      $up->execute([$orderId, 'front', $frontName]);
      if ($backName) $up->execute([$orderId, 'back', $backName]);
    }
    elseif ($isPhotoboothBigProd || $isPhotoboothSmallProd) {
      $required = $isPhotoboothBigProd ? 4 : 6;
      $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");
      for ($i=1; $i<=$required; $i++) {
        $field = 'photo'.$i;
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
          throw new RuntimeException("Missing photo {$i}");
        }
        $fn = save_upload($_FILES[$field], $uploadDir);
        $up->execute([$orderId, 'gallery', $fn]);
      }
    }
    elseif ($isInstaxMulti) {
      $required       = requiredPhotoCountByName($pname);
      $uploadedCount  = countUploaded($_FILES['photos']);
      if ($required > 0 && $uploadedCount !== $required) {
        throw new RuntimeException("This product requires exactly {$required} photos. You uploaded {$uploadedCount}.");
      }
      if ($required === 0 && $uploadedCount < 1) {
        throw new RuntimeException("At least one photo required.");
      }
      $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");
      $n  = count($_FILES['photos']['name']);
      for ($i=0;$i<$n;$i++) {
        $tmp = [
          'name'     => $_FILES['photos']['name'][$i],
          'type'     => $_FILES['photos']['type'][$i],
          'tmp_name' => $_FILES['photos']['tmp_name'][$i],
          'error'    => $_FILES['photos']['error'][$i],
          'size'     => $_FILES['photos']['size'][$i],
        ];
        if ($tmp['error'] === UPLOAD_ERR_NO_FILE) continue;
        $fn = save_upload($tmp, $uploadDir);
        $up->execute([$orderId, 'gallery', $fn]);
      }
    }
    else {
      $uploadedCount = countUploaded($_FILES['photos']);
      if ($isSpotifyKeychainProd) {
        if ($uploadedCount !== 3) {
          throw new RuntimeException("Spotify keychain requires exactly 3 photos. You uploaded {$uploadedCount}.");
        }
      } elseif ($isInstaxKeychainProd) {
        if ($uploadedCount < 1) {
          throw new RuntimeException('Instax keychain requires at least 1 photo.');
        }
      } else {
        if ($uploadedCount < 1) {
          throw new RuntimeException('At least one photo required.');
        }
      }
      $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");
      $n  = count($_FILES['photos']['name']);
      for ($i=0;$i<$n;$i++) {
        $tmp = [
          'name'     => $_FILES['photos']['name'][$i],
          'type'     => $_FILES['photos']['type'][$i],
          'tmp_name' => $_FILES['photos']['tmp_name'][$i],
          'error'    => $_FILES['photos']['error'][$i],
          'size'     => $_FILES['photos']['size'][$i],
        ];
        if ($tmp['error'] === UPLOAD_ERR_NO_FILE) continue;
        $fn = save_upload($tmp, $uploadDir);
        $up->execute([$orderId, 'gallery', $fn]);
      }
    }

    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host      = $_SERVER['HTTP_HOST'] ?? 'stellascreation.shop';
    $baseUrl   = $scheme . $host;

    $adminLink = $baseUrl . '/dashboard.php';

    // ------- build "Options" list (same style idea as checkout) -------
    $optionsHtml = '';
    if ($notes) {
        $parts = explode(';', $notes);
        $optionsHtml .= '<ul style="margin:4px 0 10px;padding-left:18px;font-size:13px;color:#555;">';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $optionsHtml .= '<li>'.htmlspecialchars($part, ENT_QUOTES, 'UTF-8').'</li>';
        }
        $optionsHtml .= '</ul>';
    } else {
        $optionsHtml = '<p style="margin:4px 0 10px;font-size:13px;color:#777;"><em>None</em></p>';
    }

    $safeCustomer = htmlspecialchars($customer, ENT_QUOTES, 'UTF-8');
    $safeProduct  = htmlspecialchars($pname, ENT_QUOTES, 'UTF-8');

    // ------- FULL CUTE EMAIL LAYOUT (same as checkout.php) -------
    $adminHtml = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Order</title>
</head>
<body style="margin:0;padding:0;background:#fff7fb;font-family:Poppins,Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
               style="max-width:520px;background:#ffffff;border-radius:18px;
                      box-shadow:0 8px 24px rgba(0,0,0,0.06);overflow:hidden;">
          <tr>
            <td style="background:#f9c9de;padding:16px 24px;text-align:center;">
              <div style="font-size:13px;letter-spacing:0.15em;text-transform:uppercase;
                          color:#6a3050;margin-bottom:4px;">
                Stella\'s Creation
              </div>
              <div style="font-size:20px;font-weight:700;color:#3a3a3a;">
                New Order
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:18px 24px 10px;font-size:14px;color:#444;">
              <p style="margin:0 0 8px;">You just received a new order:</p>

              <p style="margin:0 0 6px;">
                <strong>Order ID:</strong> '.$orderId.'
              </p>
              <p style="margin:0 0 6px;">
                <strong>Customer:</strong> '.$safeCustomer.'
              </p>
              <p style="margin:0 0 6px;">
                <strong>Product:</strong> '.$safeProduct.'
              </p>
              <p style="margin:0 0 6px;">
                <strong>Quantity:</strong> '.$qty.'
              </p>
              <p style="margin:0 0 10px;">
                <strong>Total:</strong> ₱'.number_format($total, 2).'
              </p>';

    if ($shippingFee > 0) {
        $adminHtml .= '
              <p style="margin:0 0 8px;font-size:13px;color:#555;">
                <strong>Includes Shipping Fee:</strong> ₱'.number_format($shippingFee, 2).'
              </p>';
    }

    $adminHtml .= '
              <div style="margin:10px 0 4px;font-weight:600;font-size:14px;color:#2d3748;">
                Options
              </div>
              '.$optionsHtml.'

              <p style="margin:8px 0 4px;font-size:13px;color:#555;">
                <strong>Status:</strong> Pending
              </p>

              <div style="text-align:center;margin-top:14px;">
                <a href="'.$adminLink.'"
                   style="
                     display:inline-block;
                     padding:11px 24px;
                     border-radius:999px;
                     background:#f28ac9;
                     color:#ffffff;
                     font-weight:600;
                     font-size:14px;
                     text-decoration:none;
                   ">
                  Open Admin Dashboard
                </a>
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:10px 18px 14px;text-align:center;font-size:11px;
                       color:#999;background:#fff1f6;">
              Made with ♡ by Stella\'s Creation
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

    // send pretty admin email (same style as checkout.php)
    sendMailMessage($adminEmail, "New Order - Stella's Creation", $adminHtml);

    $success = true;

  }catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Place an Order</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="/style.css">
<style>
html, body {
  margin:0;
  padding:0;
  height:100%;
}
body {
  font-family:'Poppins',sans-serif;
  background:#ffe9f5;
}

.order-shell {
  min-height:100vh;
  padding:24px 12px;
  box-sizing:border-box;
  display:flex;
  justify-content:center;
  align-items:center;
}

.order-card {
  background:#ffffff;
  border-radius:24px;
  box-shadow:0 10px 30px rgba(0,0,0,0.08);
  padding:20px 20px 22px;
  max-width:540px;
  width:100%;
  max-height:calc(100vh - 48px);
  overflow-y:auto;
  margin:0 auto;
  box-sizing:border-box;
  position:relative;
}

.order-card h2 {
  margin:0 0 16px;
  font-size:1.35rem;
}
.order-success {
  text-align:center;
  padding:40px 20px;
}
.order-success h3 {
  color:#C8A2C8;
  font-size:1.8rem;
  margin:0 0 16px;
  font-weight:600;
}
.order-success p {
  color:#5a3568;
  font-size:1rem;
  line-height:1.6;
  margin:10px 0;
}
.order-success .sparkle {
  font-size:2rem;
  display:inline-block;
  animation:sparkle 1.5s ease-in-out infinite;
}
@keyframes sparkle {
  0%, 100% { transform:scale(1) rotate(0deg); opacity:1; }
  50% { transform:scale(1.2) rotate(10deg); opacity:0.8; }
}
.btn-place-another {
  display:inline-block;
  margin-top:24px;
  padding:12px 32px;
  background:#C8A2C8;
  color:#fff;
  text-decoration:none;
  border-radius:999px;
  font-weight:600;
  font-size:0.95rem;
  transition:all 0.3s ease;
  box-shadow:0 4px 12px rgba(200,162,200,0.3);
}
.btn-place-another:hover {
  background:#b08fb0;
  transform:translateY(-2px);
  box-shadow:0 6px 16px rgba(200,162,200,0.4);
}
.order-error {
  color:#d0342c;
  font-size:.92rem;
  padding:10px;
  background:#ffebee;
  border-radius:8px;
  margin-bottom:12px;
}
.order-field {
  margin-bottom:12px;
  font-size:.88rem;
}
.order-field label {
  display:block;
  margin-bottom:4px;
  font-weight:500;
}
.order-field input,
.order-field select,
.order-field textarea {
  width:100%;
  padding:8px 10px;
  border-radius:8px;
  border:1px solid #ddd;
  font-size:.86rem;
  box-sizing:border-box;
  font-family:'Poppins',sans-serif;
  resize:vertical;
}
.order-field textarea {
  min-height:80px;
}
.order-grid-2 {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}
.order-section {
  border:1px solid #eee;
  border-radius:14px;
  padding:14px 12px 16px;
  margin-top:12px;
  background:#fafafa;
}
.order-section h4 {
  margin:0 0 12px;
  font-size:.95rem;
  color:#333;
  font-weight:600;
}
#productPreview {
  margin-bottom:10px;
  text-align:center;
}
#productImage {
  max-width:100%;
  height:auto;
  border-radius:14px;
  box-shadow:0 8px 20px rgba(0,0,0,0.10);
}
.order-actions {
  margin-top:16px;
  display:flex;
  justify-content:flex-end;
}
.btn.primary {
  border-radius:999px;
  padding:10px 24px;
  font-size:.9rem;
}
.order-note-small {
  font-size:11px;
  color:#777;
  margin-top:4px;
}
.order-total-bar {
  margin-top:16px;
  padding:12px 16px;
  border-radius:18px;
  background:#fff6fb;
  border:1px dashed #f4b5d3;
  display:flex;
  flex-direction:column;
  gap:4px;
}
.order-total-label {
  font-size:.8rem;
  text-transform:uppercase;
  letter-spacing:.04em;
  color:#aa6f96;
  font-weight:600;
}
.order-total-amount {
  font-size:1.15rem;
  font-weight:700;
  color:#5a3568;
}
.order-total-note {
  font-size:11px;
  color:#777;
  line-height:1.4;
}
.delivery-option-wrap {
  margin-bottom:10px;
}
.delivery-option-header {
  display:flex;
  align-items:center;
  padding:12px 14px;
  background:#FFF8E7;
  border:1px solid rgba(0,0,0,0.04);
  border-radius:14px;
  cursor:pointer;
  transition:all 0.2s;
}
.delivery-option-header:hover {
  background:#fff3d6;
}
.delivery-option-header input[type="radio"] {
  margin-right:10px;
  cursor:pointer;
}
.delivery-option-title {
  font-size:.88rem;
  font-weight:600;
  color:#3A3A3A;
  flex:1;
}
.delivery-option-content {
  max-height:0;
  overflow:hidden;
  transition:max-height 0.3s ease;
  padding:0 14px;
}
.delivery-option-content.active {
  max-height:400px;
  padding:12px 14px;
}
.delivery-option-content select,
.delivery-option-content textarea,
.delivery-option-content input[type="text"] {
  width:100%;
  border-radius:9px;
  border:1px solid #e2e2e2;
  padding:9px 11px;
  font-size:.86rem;
  background:#fff;
  margin-top:8px;
  box-sizing:border-box;
  font-family:'Poppins',sans-serif;
}
.delivery-option-content textarea {
  min-height:70px;
  resize:vertical;
}
.order-mop-pill {
  display:inline-flex;
  align-items:center;
  padding:8px 16px;
  border-radius:999px;
  background:#C8A2C8;
  color:#fff;
  font-size:.85rem;
  font-weight:500;
  margin-bottom:8px;
}
.btn.primary.is-loading {
  opacity:.6;
  cursor:wait;
  pointer-events:none;
}

#photoTopLabel {
  font-size:.85rem;
}
#photoTopPreview {
  border-radius:8px;
}

.keychain-swatch-row {
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:4px;
}
.color-swatch {
  width:26px;
  height:26px;
  border-radius:999px;
  background:var(--swatch-color,#eee);
  box-shadow:0 2px 6px rgba(0,0,0,0.12);
  border:2px solid transparent;
  cursor:pointer;
  transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.color-swatch:hover {
  transform:translateY(-1px) scale(1.05);
  box-shadow:0 4px 10px rgba(0,0,0,0.18);
}
.color-swatch.active {
  border-color:#C8A2C8;
  box-shadow:0 4px 12px rgba(200,162,200,0.55);
  transform:translateY(-1px) scale(1.06);
}
.keychain-color-hint {
  font-size:11px;
  color:#777;
  margin-top:8px;
}

@media (max-width:600px){
  .order-card {
    padding:18px 14px 20px;
    border-radius:18px;
    max-height:calc(100vh - 36px);
  }
  .order-grid-2 {
    grid-template-columns:1fr;
  }
}
</style>
</head>
<body>

<div class="order-shell">
  <div class="order-card">
    <h2><?= $success ? "Order Placed! ✨" : "Place an Order" ?></h2>

    <?php if ($success): ?>
      <div class="order-success">
        <div class="sparkle">✨</div>
        <h3>Yay, thank you!</h3>
        <p>We've received your order and we'll message you soon for the final details.</p>
        <p>Can't wait to make something cute for you!</p>
        <a href="<?= htmlspecialchars($placeAnotherHref) ?>" class="btn-place-another">
          Place another order 
        </a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <p class="order-error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" id="orderForm">

        <?php if ($defaultProductId && $defaultProduct): ?>
          <input type="hidden" name="product_id" value="<?= (int)$defaultProduct['product_id'] ?>">
          <div class="order-field">
            <label>Product</label>
            <div style="font-weight:600;font-size:.9rem;">
              <?= htmlspecialchars($defaultProduct['name']) ?>
            </div>
          </div>
        <?php else: ?>
          <div class="order-field">
            <label for="product_id">Product</label>
            <select name="product_id" id="product_id" onchange="toggleUploadSections()" required>
              <option value="">-- Select Product --</option>
              <?php foreach ($products as $p): ?>
                <?php
                  $isPhotoTopProduct =
                    (stripos($p['name'],'Photocard') !== false) ||
                    (stripos($p['name'],'Photo Card') !== false) ||
                    (stripos($p['name'],'Ref Magnet') !== false);
                  $cleanName = preg_replace('/,\s*\d+\s*photos?/i', '', $p['name']);
                  $label = $cleanName;
                  $suffix = $isPhotoTopProduct
                    ? ''
                    : ' (₱'.number_format((float)$p['price'],2).')';
                  $imgPath = getProductImage($p['name']);
                ?>
                <option
                  value="<?= (int)$p['product_id'] ?>"
                  data-img="<?= $imgPath ? htmlspecialchars($imgPath) : '' ?>"
                  data-price="<?= htmlspecialchars((float)$p['price']) ?>"
                  <?= $defaultProductId===(int)$p['product_id']?'selected':'' ?>>
                  <?= htmlspecialchars($label . $suffix) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php
          $initialPreview = null;
          if ($defaultProduct) {
            $initialPreview = getProductImage($defaultProduct['name']);
          } elseif ($defaultProductId) {
            foreach ($products as $pp) {
              if ((int)$pp['product_id'] === $defaultProductId) {
                $initialPreview = getProductImage($pp['name']);
                break;
              }
            }
          }
        ?>
        <div id="productPreview">
          <img id="productImage"
               src="<?= $initialPreview ? htmlspecialchars($initialPreview) : '' ?>"
               alt="Product preview"
               style="<?= $initialPreview ? '' : 'display:none;' ?>">
        </div>

        <div class="order-field">
          <label for="quantity">Quantity</label>
          <input id="quantity" type="number" name="quantity" min="1" value="1">
          <div class="order-note-small">Note: This will be the same picture for all pieces.</div>
        </div>

        <div id="photoTopSection" class="order-section" style="display:none;">
          <h4>Photo Top Type</h4>
          <div class="order-field">
            <?php if (empty($forcedPhotoTop)): ?>
              <label for="photo_top_type">Type</label>
              <select name="photo_top_type" id="photo_top_type">
                <option value="">-- Select Type --</option>
                <option value="Holo Rainbow" data-pc="12" data-rm="18">Holo Rainbow</option>
                <option value="Leather"      data-pc="8"  data-rm="15">Leather</option>
                <option value="Glossy"       data-pc="6"  data-rm="12">Glossy</option>
                <option value="Glitter"      data-pc="6"  data-rm="15">Glitter</option>
                <option value="Matte"        data-pc="6"  data-rm="12">Matte</option>
              </select>
            <?php else: ?>
              <label>Type</label>
              <input type="hidden" name="photo_top_type" id="photo_top_type"
                     value="<?= htmlspecialchars($forcedPhotoTop, ENT_QUOTES, 'UTF-8') ?>">
              <div style="font-weight:600;font-size:.9rem;">
                <?= htmlspecialchars($forcedPhotoTop) ?>
              </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
              <span id="photoTopLabel" style="font-size:.85rem;font-weight:500;"></span>
              <img id="photoTopPreview"
                   src=""
                   alt=""
                   style="display:none;width:60px;height:60px;object-fit:cover;border-radius:8px;
                          box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            </div>
          </div>
          <div id="photoTopHint" style="font-size:11px;margin-top:6px;color:#666;line-height:1.4;"></div>
        </div>

        <div id="keychainColorSection" class="order-section" style="display:none;">
          <h4>Keychain Color</h4>
          <div class="order-field">
            <input type="hidden" name="keychain_color" id="keychain_color_value">
            <div class="keychain-swatch-row color-swatch-group">
              <div class="color-swatch" data-value="#000000|Black" style="--swatch-color:#000000;"></div>
              <div class="color-swatch" data-value="#FFFFFF|White" style="--swatch-color:#ffffff;"></div>
              <div class="color-swatch" data-value="#FEC7DC|Soft Blush Pink" style="--swatch-color:#FEC7DC;"></div>
              <div class="color-swatch" data-value="#906FAC|Muted Lavender" style="--swatch-color:#906FAC;"></div>
              <div class="color-swatch" data-value="#4C92D4|Blue" style="--swatch-color:#4C92D4;"></div>
            </div>
            <div class="keychain-color-hint">Tap a color to select.</div>
          </div>
        </div>

        <div id="spotifySection" class="order-section" style="display:none;">
          <h4>Spotify Details</h4>
          <div class="order-field">
            <label for="spotify_song_title">Song Title</label>
            <input type="text" name="spotify_song_title" id="spotify_song_title" placeholder="Enter song title">
          </div>
          <div class="order-field">
            <label for="spotify_song_artist">Artist</label>
            <input type="text" name="spotify_song_artist" id="spotify_song_artist" placeholder="Enter artist name">
          </div>
          <p style="font-size:11px;margin-top:6px;color:#666;">Please match the spelling used on Spotify.</p>
        </div>

        <div id="instaxMsgSection" class="order-section" style="display:none;">
          <h4>Short Message (Instax Keychain)</h4>
          <div class="order-field">
            <label for="instax_message">Message</label>
            <textarea name="instax_message" id="instax_message" rows="3" maxlength="200" placeholder="Enter your message here (up to 20 words)"></textarea>
          </div>
          <div id="instaxMsgHint" style="font-size:11px;margin-top:6px;color:#666;">Up to 20 words.</div>
        </div>

        <div id="frontBackSection" class="order-section" style="display:none;">
          <h4>Upload Photos</h4>
          <div class="order-field">
            <label for="front_design">Front</label>
            <input type="file" name="front_design" id="front_design" accept="image/*">
          </div>
          <div class="order-field">
            <label for="back_design">Back (Optional)</label>
            <input type="file" name="back_design" id="back_design" accept="image/*">
          </div>
        </div>

        <div id="photoboothSection" class="order-section" style="display:none;">
          <h4 id="photoboothTitle">Upload Photos</h4>
          <?php for ($i=1;$i<=6;$i++): ?>
            <div class="order-field pb-row pb-row-<?= $i ?>">
              <label for="photo<?= $i ?>">Photo <?= $i ?></label>
              <input type="file" name="photo<?= $i ?>" id="photo<?= $i ?>" accept="image/*">
            </div>
          <?php endfor; ?>
        </div>

        <div id="multiPhotosSection" class="order-section" style="display:none;">
          <h4>Upload Photos</h4>
          <div class="order-field">
            <label for="photosMulti">Photos</label>
            <input type="file" name="photos[]" id="photosMulti" accept="image/*" multiple>
          </div>
          <div id="photosHint" style="font-size:11px;margin-top:6px;color:#666;"></div>
        </div>

        <div class="order-section">
          <h4>Delivery Option</h4>

          <div class="delivery-option-wrap">
            <div class="delivery-option-header" onclick="selectDelivery('meetup')">
              <input type="radio" name="delivery_type" value="meetup" id="dt_meetup" checked>
              <div class="delivery-option-title">Meet up</div>
            </div>
            <div class="delivery-option-content active" id="content_meetup">
              <select name="meetup_place" id="meetup_place">
                <option value="">-- Select Meet-up Place --</option>
                <option value="SMCBI Campus">SMCBI Campus</option>
                <option value="Barayong NHS">Barayong NHS</option>
              </select>
            </div>
          </div>

          <div class="delivery-option-wrap">
            <div class="delivery-option-header" onclick="selectDelivery('pickup')">
              <input type="radio" name="delivery_type" value="pickup" id="dt_pickup">
              <div class="delivery-option-title">Pickup</div>
            </div>
            <div class="delivery-option-content" id="content_pickup">
              <input type="text" value="Capehan Magsaysay Davao del Sur" disabled>
            </div>
          </div>

          <div class="delivery-option-wrap">
            <div class="delivery-option-header" onclick="selectDelivery('delivery')">
              <input type="radio" name="delivery_type" value="delivery" id="dt_delivery">
              <div class="delivery-option-title">Delivery (with shipping fee)</div>
            </div>
            <div class="delivery-option-content" id="content_delivery">
              <textarea name="delivery_address" id="delivery_address" rows="3" placeholder="Enter your complete delivery address"></textarea>
            </div>
          </div>
        </div>

        <div class="order-section">
          <h4>Mode of Payment</h4>
          <div class="order-mop-pill">GCash only (09757944649)</div>
          <p style="font-size:11px;margin-top:8px;color:#666;line-height:1.5;">
            Payments are accepted via GCash only to avoid cancellation.
            If you choose <b>Delivery</b>, please <b>add ₱20</b> to your payment.
          </p>

          <div class="order-field" style="margin-top:12px;">
            <label for="gcash_ref_number">GCash Reference Number (last 5 digits)</label>
            <input type="number" name="gcash_ref_number" id="gcash_ref_number" 
                   placeholder="e.g. 12345" min="0" max="99999" step="1" 
                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5);" required>
            <div class="order-note-small">Enter the last 5 digits of your GCash reference number.</div>
          </div>

          <input type="hidden" name="mop" value="GCash">
        </div>

        <div class="order-total-bar">
          <div class="order-total-label">Estimated total</div>
          <div class="order-total-amount" id="orderTotal">₱0.00</div>
          <div class="order-total-note">
            This includes your items × quantity. If you choose <b>Delivery</b>, ₱20 shipping fee is added.
          </div>
        </div>

        <div class="order-actions">
          <button type="submit" class="btn primary" id="submitBtn">Place order</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
const photoTopImages = {
  "Holo Rainbow": "/product_images/holo_rainbow.png",
  "Leather":      "/product_images/leather.png",
  "Glossy":       "/product_images/glossy.png",
  "Glitter":      "/product_images/glitter.png",
  "Matte":        "/product_images/matte.png"
};

const forcedPhotoTop = "<?= htmlspecialchars($forcedPhotoTop ?? '', ENT_QUOTES, 'UTF-8') ?>";
const selectedProductName = "<?= $defaultProduct ? htmlspecialchars($defaultProduct['name'], ENT_QUOTES, 'UTF-8') : '' ?>";
const selectedProductPrice = <?= $defaultProduct ? json_encode((float)$defaultProduct['price']) : 'null' ?>;

function selectDelivery(type) {
  document.getElementById('dt_meetup').checked   = (type === 'meetup');
  document.getElementById('dt_pickup').checked   = (type === 'pickup');
  document.getElementById('dt_delivery').checked = (type === 'delivery');

  document.getElementById('content_meetup').classList.toggle('active',   type === 'meetup');
  document.getElementById('content_pickup').classList.toggle('active',   type === 'pickup');
  document.getElementById('content_delivery').classList.toggle('active', type === 'delivery');

  updateTotal();
}

function getCurrentProductText() {
  var sel = document.getElementById('product_id');
  if (sel) {
    var opt = sel.options[sel.selectedIndex];
    return opt ? opt.text : '';
  }
  return selectedProductName || '';
}

function updatePhotoTopVisual(type, isRefMagnet, isPhotocard) {
  var img   = document.getElementById('photoTopPreview');
  var label = document.getElementById('photoTopLabel');
  var hint  = document.getElementById('photoTopHint');
  if (!img || !label || !hint) return;

  if (!type) {
    img.style.display = 'none';
    img.src = '';
    label.textContent = '';
    if (!isRefMagnet && !isPhotocard) hint.textContent = '';
    return;
  }

  label.textContent = type;

  var src = photoTopImages[type] || '';
  if (src) {
    img.src = src;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }

  if (isRefMagnet) {
    hint.textContent = 'Prices shown are for Ref Magnet (per piece).';
  } else if (isPhotocard) {
    hint.textContent = 'Prices shown are for Photocard (per piece).';
  } else {
    hint.textContent = '';
  }
}

function getRequiredFromName(txt) {
  if (txt.includes('Keychain') && txt.includes('Spotify')) return 3;
  const m = txt.match(/(\d+)\s*pcs/i);
  return m ? parseInt(m[1], 10) : 0;
}

function updateProductPreview() {
  var sel = document.getElementById('product_id');
  var img = document.getElementById('productImage');
  if (!img) return;
  if (!sel) return;
  var opt = sel.options[sel.selectedIndex];
  if (!opt) return;
  var url = opt.getAttribute('data-img');
  if (url) {
    img.src = url;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
}

function updateTotal() {
  var totalEl = document.getElementById('orderTotal');
  if (!totalEl) return;

  var qtyInput = document.getElementById('quantity');
  var qty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
  if (isNaN(qty) || qty < 1) qty = 1;

  var txt = getCurrentProductText();
  var isPhotoTop  = txt.includes('Photo Card') || txt.includes('Photocard') || txt.includes('Ref Magnet');
  var isPhotocard = txt.includes('Photocard') || txt.includes('Photo Card');
  var isRefMagnet = txt.includes('Ref Magnet');

  var unitPrice = 0;

  var sel = document.getElementById('product_id');
  if (sel) {
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.price) {
      unitPrice = parseFloat(opt.dataset.price) || 0;
    }
  } else if (selectedProductPrice !== null) {
    unitPrice = parseFloat(selectedProductPrice) || 0;
  }

  var pt = document.getElementById('photo_top_type');
  var photoTopType = pt ? pt.value : '';

  if (photoTopType) {
    if (isPhotocard) {
      if (photoTopType === 'Holo Rainbow') unitPrice = 12;
      else if (photoTopType === 'Leather') unitPrice = 8;
      else if (photoTopType === 'Glossy') unitPrice = 6;
      else if (photoTopType === 'Glitter') unitPrice = 6;
      else if (photoTopType === 'Matte') unitPrice = 6;
    } else if (isRefMagnet) {
      if (photoTopType === 'Holo Rainbow') unitPrice = 18;
      else if (photoTopType === 'Leather') unitPrice = 15;
      else if (photoTopType === 'Glossy') unitPrice = 12;
      else if (photoTopType === 'Glitter') unitPrice = 15;
      else if (photoTopType === 'Matte') unitPrice = 12;
    }
  }

  var shippingFee = 0;
  var dt = document.querySelector('input[name="delivery_type"]:checked');
  if (dt && dt.value === 'delivery') {
    shippingFee = 20;
  }

  var total = (unitPrice * qty) + shippingFee;
  totalEl.textContent = '₱' + total.toFixed(2);
}

function toggleUploadSections() {
  if (!document.getElementById('orderForm')) return;

  var txt = getCurrentProductText();

  var isFrontBack = txt.includes('Acrylic Keychain Instax') || txt.includes('Photocard');
  var isPhotoboothBig = txt.includes('Photobooth Keychain') && txt.toLowerCase().includes('big');
  var isPhotoboothSmall = txt.includes('Photobooth Keychain') && txt.toLowerCase().includes('small');
  var isPhotobooth = isPhotoboothBig || isPhotoboothSmall;
  var isInstaxPrint = txt.includes('Instax') && !txt.includes('Keychain');
  var isPhotoTop = txt.includes('Photo Card') || txt.includes('Photocard') || txt.includes('Ref Magnet');
  var isKeychain = txt.includes('Keychain');
  var isTemplate = txt.includes('Photobooth Keychain Big');
  var isKeychainSpotify = txt.includes('Keychain') && txt.includes('Spotify');
  var isKeychainInstax = txt.includes('Keychain') && txt.includes('Instax');

  var fbSec   = document.getElementById('frontBackSection');
  var pbSec   = document.getElementById('photoboothSection');
  var mpSec   = document.getElementById('multiPhotosSection');
  var ptSec   = document.getElementById('photoTopSection');
  var kcSec   = document.getElementById('keychainColorSection');
  var tplSec  = document.getElementById('templateSection');
  var spSec   = document.getElementById('spotifySection');
  var imSec   = document.getElementById('instaxMsgSection');
  var pbTitle = document.getElementById('photoboothTitle');

  if (fbSec) fbSec.style.display = isFrontBack ? 'block' : 'none';
  if (pbSec) pbSec.style.display = isPhotobooth ? 'block' : 'none';
  if (mpSec) mpSec.style.display =
    (!isFrontBack && !isPhotobooth) || isInstaxPrint ? 'block' : 'none';
  if (ptSec) ptSec.style.display = isPhotoTop ? 'block' : 'none';
  if (kcSec) kcSec.style.display = isKeychain ? 'block' : 'none';
  if (tplSec) tplSec.style.display = isTemplate ? 'block' : 'none';
  if (spSec) spSec.style.display = isKeychainSpotify ? 'block' : 'none';
  if (imSec) imSec.style.display = isKeychainInstax ? 'block' : 'none';

  for (var i=1;i<=6;i++) {
    var row = document.querySelector('.pb-row-' + i);
    if (!row) continue;
    if (!isPhotobooth) {
      row.style.display = 'none';
    } else if (isPhotoboothBig) {
      row.style.display = (i <= 4) ? 'block' : 'none';
    } else if (isPhotoboothSmall) {
      row.style.display = 'block';
    }
  }

  if (pbTitle) {
    if (isPhotoboothBig) pbTitle.textContent = 'Upload 4 Photos';
    else if (isPhotoboothSmall) pbTitle.textContent = 'Upload 6 Photos';
    else pbTitle.textContent = 'Upload Photos';
  }

  var req = getRequiredFromName(txt);
  var hint = document.getElementById('photosHint');
  if (hint) {
    if (mpSec && mpSec.style.display === 'block' && req > 0) {
      if (txt.includes('Spotify')) {
        hint.textContent = 'Spotify keychain requires exactly ' + req + ' photos.';
      } else {
        hint.textContent = 'This product requires exactly ' + req + ' photos.';
      }
    } else {
      hint.textContent = '';
    }
  }

  var ptSelect  = document.getElementById('photo_top_type');

  if (ptSec) {
    if (isPhotoTop) {
      ptSec.style.display = 'block';

      var isRefMagnet  = txt.includes('Ref Magnet');
      var isPhotocard  = txt.includes('Photocard') || txt.includes('Photo Card');

      if (ptSelect && ptSelect.tagName === 'SELECT') {
        for (var i = 0; i < ptSelect.options.length; i++) {
          var opt = ptSelect.options[i];
          if (!opt.value) continue;
          if (!opt.dataset.label) {
            opt.dataset.label = opt.textContent.split('–')[0].trim();
          }
          var baseLabel = opt.dataset.label;
          var price = '';
          if (isRefMagnet) {
            price = opt.dataset.rm;
          } else if (isPhotocard) {
            price = opt.dataset.pc;
          }
          if (price) {
            opt.textContent = baseLabel + ' – ₱' + price;
          } else {
            opt.textContent = baseLabel;
          }
        }
      }

      var effectiveType = '';
      if (forcedPhotoTop) {
        effectiveType = forcedPhotoTop;
        if (ptSelect && ptSelect.tagName === 'SELECT') {
          ptSelect.value = forcedPhotoTop;
        }
      } else if (ptSelect) {
        effectiveType = ptSelect.value;
      }

      updatePhotoTopVisual(effectiveType, isRefMagnet, isPhotocard);
    } else {
      ptSec.style.display = 'none';
      updatePhotoTopVisual('', false, false);
      if (ptSelect && ptSelect.tagName === 'SELECT') {
        ptSelect.selectedIndex = 0;
      }
    }
  }

  updateProductPreview();
  updateTotal();
}

function setupKeychainColorSwatches() {
  var container = document.querySelector('.color-swatch-group');
  var hidden = document.getElementById('keychain_color_value');
  if (!container || !hidden) return;

  var swatches = container.querySelectorAll('.color-swatch');
  swatches.forEach(function (swatch) {
    swatch.addEventListener('click', function () {
      swatches.forEach(function (s) { s.classList.remove('active'); });
      swatch.classList.add('active');
      hidden.value = swatch.getAttribute('data-value') || '';
    });
  });
}

document.addEventListener('DOMContentLoaded', function () {
  var ptSelectInit = document.getElementById('photo_top_type');
  if (ptSelectInit) {
    ptSelectInit.addEventListener('change', function () {
      var txt = getCurrentProductText();
      var isRefMagnet  = txt.includes('Ref Magnet');
      var isPhotocard  = txt.includes('Photocard') || txt.includes('Photo Card');
      updatePhotoTopVisual(this.value, isRefMagnet, isPhotocard);
      updateTotal();
    });
  }

  var qtyInput = document.getElementById('quantity');
  if (qtyInput) {
    qtyInput.addEventListener('input', updateTotal);
  }

  var prodSelect = document.getElementById('product_id');
  if (prodSelect) {
    prodSelect.addEventListener('change', function () {
      toggleUploadSections();
    });
  }

  setupKeychainColorSwatches();

  var orderForm = document.getElementById('orderForm');
  if (orderForm) {
    orderForm.addEventListener('submit', function(e) {
      var txt = getCurrentProductText();
      var req = getRequiredFromName(txt);

      var isPhotoboothBig   = txt.includes('Photobooth Keychain') && txt.toLowerCase().includes('big');
      var isPhotoboothSmall = txt.includes('Photobooth Keychain') && txt.toLowerCase().includes('small');
      var isPhotobooth      = isPhotoboothBig || isPhotoboothSmall;
      var isKeychainSpotify = txt.includes('Keychain') && txt.includes('Spotify');

      var multi = document.getElementById('photosMulti');
      var multiSec = document.getElementById('multiPhotosSection');
      if (multi && multiSec && multiSec.style.display === 'block') {
        var count = multi.files ? multi.files.length : 0;
        if (isKeychainSpotify) {
          if (count !== 3) {
            e.preventDefault();
            alert('Spotify keychain requires exactly 3 photos. You uploaded ' + count + '.');
            return false;
          }
        } else {
          if (req > 0 && count !== req) {
            e.preventDefault();
            alert('This product requires exactly ' + req + ' photos. You uploaded ' + count + '.');
            return false;
          }
          if (req === 0 && count < 1) {
            e.preventDefault();
            alert('Please upload at least one photo.');
            return false;
          }
        }
      }

      if (isPhotobooth) {
        var required = isPhotoboothBig ? 4 : 6;
        var missing = [];
        for (var i=1;i<=required;i++){
          var f = document.querySelector('input[name="photo'+i+'"]');
          if (!f || !f.files || f.files.length === 0) missing.push(i);
        }
        if (missing.length) {
          e.preventDefault();
          alert('Missing photos: ' + missing.join(', '));
          return false;
        }
      }

      var photoTopSection = document.getElementById('photoTopSection');
      if (photoTopSection && photoTopSection.style.display === 'block') {
        var pt = document.getElementById('photo_top_type');
        if (pt && !pt.value && !forcedPhotoTop) {
          e.preventDefault();
          alert('Please select a photo top type.');
          return false;
        }
      }

      var keychainColorSection = document.getElementById('keychainColorSection');
      if (keychainColorSection && keychainColorSection.style.display === 'block') {
        var kc = document.getElementById('keychain_color_value');
        if (kc && !kc.value) {
          e.preventDefault();
          alert('Please select a keychain color.');
          return false;
        }
      }

      var spotifySection = document.getElementById('spotifySection');
      if (spotifySection && spotifySection.style.display === 'block') {
        var st = document.getElementById('spotify_song_title');
        var sa = document.getElementById('spotify_song_artist');
        if (!st.value.trim() || !sa.value.trim()) {
          e.preventDefault();
          alert('Please enter both the song title and artist for your Spotify keychain.');
          return false;
        }
      }

      var instaxSection = document.getElementById('instaxMsgSection');
      if (instaxSection && instaxSection.style.display === 'block') {
        var msg = document.getElementById('instax_message').value.trim();
        if (!msg) {
          e.preventDefault();
          alert('Please enter a short message for your Instax keychain.');
          return false;
        }
        var words = msg.split(/\s+/).filter(Boolean);
        if (words.length > 20) {
          e.preventDefault();
          alert('Short message must be 20 words or less. You currently have ' + words.length + ' words.');
          return false;
        }
      }

      var deliveryRadios = document.querySelectorAll('input[name="delivery_type"]');
      var deliveryType = null;
      deliveryRadios.forEach(function(r){ if (r.checked) deliveryType = r.value; });

      if (!deliveryType) {
        e.preventDefault();
        alert('Please choose a delivery option.');
        return false;
      }

      if (deliveryType === 'meetup') {
        var meetup = document.getElementById('meetup_place');
        if (!meetup || !meetup.value) {
          e.preventDefault();
          alert('Please select a meet-up place.');
          return false;
        }
      } else if (deliveryType === 'delivery') {
        var addr = document.getElementById('delivery_address');
        if (!addr || !addr.value.trim()) {
          e.preventDefault();
          alert('Please enter a delivery address.');
          return false;
        }
      }

      var gcashRef = document.getElementById('gcash_ref_number');
      if (!gcashRef || !gcashRef.value.trim()) {
        e.preventDefault();
        alert('Please enter the last 5 digits of your GCash reference number.');
        return false;
      }
      if (!/^\d{5}$/.test(gcashRef.value.trim())) {
        e.preventDefault();
        alert('GCash reference number must be exactly 5 digits.');
        return false;
      }

      var btn = document.getElementById('submitBtn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Placing order...';
        btn.classList.add('is-loading');
      }
    });
  }

  toggleUploadSections();
  updateTotal();
});
</script>

</body>
</html>