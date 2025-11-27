<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json');

// ---------- BASIC CHECKS ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid request method.'
    ]);
    exit;
}

if (empty($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'Your session expired. Please log in again.'
    ]);
    exit;
}

$pid = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if (!$pid) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid product.'
    ]);
    exit;
}

if (empty($_SESSION['cart'][$pid])) {
    echo json_encode([
        'success' => false,
        'error'   => 'This item is no longer in your cart.'
    ]);
    exit;
}

// ---------- HELPERS ----------
require_once __DIR__ . '/db.php';

function save_upload_cart(array $file, string $destDir): string {
    if (!isset($file['error'])) {
        throw new RuntimeException('Invalid upload data.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error (code '.$file['error'].')');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $ok  = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $ok, true)) {
        throw new RuntimeException('Invalid file type. Allowed: JPG, PNG, WEBP.');
    }
    if ($file['size'] > 8*1024*1024) { // 8MB
        throw new RuntimeException('File too large (max 8MB per photo).');
    }

    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new RuntimeException('Cannot create upload folder.');
        }
    }

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $path = rtrim($destDir, '/') . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return $name;
}

function countUploadedFiles(array $files): int {
    if (!isset($files['name'])) return 0;

    if (is_array($files['name'])) {
        $n   = count($files['name']);
        $cnt = 0;
        for ($i=0; $i<$n; $i++) {
            $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err !== UPLOAD_ERR_NO_FILE) $cnt++;
        }
        return $cnt;
    }

    return ($files['error'] === UPLOAD_ERR_NO_FILE) ? 0 : 1;
}

function requiredPhotoCountByName(string $pname): int {
    if (preg_match('/\b(\d+)\s*pcs\b/i', $pname, $m)) {
        return (int)$m[1];
    }
    return 0;
}

// ---------- LOAD PRODUCT (to know requirements) ----------
$prodStmt = $pdo->prepare("SELECT product_id, name, price FROM products_tbl WHERE product_id = ? LIMIT 1");
$prodStmt->execute([$pid]);
$product = $prodStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode([
        'success' => false,
        'error'   => 'Product not found in database.'
    ]);
    exit;
}

$pname = $product['name'];
$lower = strtolower($pname);

// ---------- DETERMINE REQUIREMENTS (same logic as order_form.php) ----------
$isPhotoboothBig   = (stripos($pname, 'Photobooth Keychain') !== false && stripos($pname, 'Big')   !== false);
$isPhotoboothSmall = (stripos($pname, 'Photobooth Keychain') !== false && stripos($pname, 'Small') !== false);
$isSpotifyKeychain = (stripos($pname, 'Keychain') !== false && stripos($pname, 'Spotify') !== false);
$isInstaxKeychain  = (stripos($pname, 'Keychain') !== false && stripos($pname, 'Instax')  !== false);
$isInstaxMulti     = (stripos($pname, 'Instax') !== false && stripos($pname, 'Keychain') === false);

// FILES FROM JS
$files = $_FILES['photos'] ?? null;
if (!$files) {
    echo json_encode([
        'success' => false,
        'error'   => 'No photos received. Please select your photos again.'
    ]);
    exit;
}

$uploadedCount = countUploadedFiles($files);
if ($uploadedCount === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Please upload at least one photo.'
    ]);
    exit;
}

// apply per–product rules
$required = 0;
$errorMsg = '';

if ($isPhotoboothBig) {
    $required = 4;
    if ($uploadedCount !== $required) {
        $errorMsg = "Photobooth Keychain Big requires exactly {$required} photos. You uploaded {$uploadedCount}.";
    }
} elseif ($isPhotoboothSmall) {
    $required = 6;
    if ($uploadedCount !== $required) {
        $errorMsg = "Photobooth Keychain Small requires exactly {$required} photos. You uploaded {$uploadedCount}.";
    }
} elseif ($isSpotifyKeychain) {
    $required = 3;
    if ($uploadedCount !== $required) {
        $errorMsg = "Spotify keychain requires exactly 3 photos. You uploaded {$uploadedCount}.";
    }
} elseif ($isInstaxMulti) {
    $required = requiredPhotoCountByName($pname);
    if ($required > 0 && $uploadedCount !== $required) {
        $errorMsg = "This Instax product requires exactly {$required} photos. You uploaded {$uploadedCount}.";
    } elseif ($required === 0 && $uploadedCount < 1) {
        $errorMsg = "Please upload at least one photo for this Instax product.";
    }
} else {
    // generic products: at least 1
    if ($uploadedCount < 1) {
        $errorMsg = "Please upload at least one photo.";
    }
}

if ($errorMsg !== '') {
    echo json_encode([
        'success' => false,
        'error'   => $errorMsg
    ]);
    exit;
}

// ---------- SAVE FILES TO /uploads/cart_photos ----------
$cartUploadDir = __DIR__ . '/uploads/cart_photos';

try {
    // optionally delete old cart photos for this product from session + folder
    if (!empty($_SESSION['cart_photos'][$pid]) && is_array($_SESSION['cart_photos'][$pid])) {
        foreach ($_SESSION['cart_photos'][$pid] as $old) {
            $oldPath = rtrim($cartUploadDir,'/') . '/' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    }

    $_SESSION['cart_photos'][$pid] = [];
    $saved = [];

    // multiple upload (standard HTML multiple)
    if (is_array($files['name'])) {
        $n = count($files['name']);
        for ($i=0; $i<$n; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $tmp = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $fn = save_upload_cart($tmp, $cartUploadDir);
            $saved[] = $fn;
        }
    } else { // single file fallback
        $fn = save_upload_cart($files, $cartUploadDir);
        $saved[] = $fn;
    }

    if (!$saved) {
        throw new RuntimeException('No files saved.');
    }

    $_SESSION['cart_photos'][$pid] = $saved;

    echo json_encode([
        'success' => true,
        'files'   => $saved
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
    exit;
}
