<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok'    => false,
        'error' => 'Invalid request method.'
    ]);
    exit;
}

$customerId = $_SESSION['customer_id'] ?? 0;
$customerName = $_SESSION['customer_name'] ?? null;

if (!$customerId) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Not logged in.'
    ]);
    exit;
}

function save_upload_simple($file, $destDir) {
    if (!isset($file['error'])) {
        throw new RuntimeException('Invalid upload.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $ok  = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $ok, true)) {
        throw new RuntimeException('Invalid file type.');
    }
    if ($file['size'] > 8*1024*1024) {
        throw new RuntimeException('File too large.');
    }

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $path = rtrim($destDir,'/') . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Failed to move upload.');
    }

    return $name;
}

try {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = max(1, (int)($_POST['quantity'] ?? 0));

    if (!$productId || $qty < 1) {
        throw new RuntimeException('Missing product or quantity.');
    }

    // fetch product
    $stmt = $pdo->prepare("SELECT name, price FROM products_tbl WHERE product_id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    $pname = $product['name'];
    $price = (float)$product['price'];

    // Delivery & MOP
    $deliveryType    = $_POST['delivery_type'] ?? 'meetup';
    $meetupPlace     = trim($_POST['meetup_place'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $mop             = 'GCash'; // locked to GCash

    $notes = [];

    if ($deliveryType === 'meetup') {
        if ($meetupPlace === '') {
            throw new RuntimeException('Please select a meet-up place.');
        }
        $notes[] = "Delivery: Meet up at {$meetupPlace}";
    } elseif ($deliveryType === 'pickup') {
        $notes[] = "Delivery: Pickup at Capehan Magsaysay Davao del Sur";
    } elseif ($deliveryType === 'delivery') {
        if ($deliveryAddress === '') {
            throw new RuntimeException('Please enter a delivery address.');
        }
        $notes[] = "Delivery: To address - {$deliveryAddress}";
    }

    $notes[] = "Mode of payment: GCash only (09757944649)";
    $notesStr = $notes ? implode('; ', $notes) : null;

    // GCash proof required
    if (empty($_FILES['gcash_proof']) || $_FILES['gcash_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please upload your GCash payment screenshot.');
    }

    $uploadDir = __DIR__ . '/uploads/orders';
    $gcashFile = save_upload_simple($_FILES['gcash_proof'], $uploadDir);

    // Insert order (simple version)
    $stmt = $pdo->prepare("
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
        )
        VALUES (?,?,?,?,?,?,?,?,?, 'Pending')
    ");

    $stmt->execute([
        $customerId,
        $customerName,
        null,          // contact_number
        null,          // email
        $productId,
        $qty,
        null,          // front_design
        null,          // back_design
        $notesStr
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // save gcash file reference
    $up = $pdo->prepare("INSERT INTO uploads_tbl (order_id, role, filename) VALUES (?,?,?)");
    $up->execute([$orderId, 'gcash', $gcashFile]);

    echo json_encode([
        'ok'      => true,
        'order_id'=> $orderId,
        'message' => "Thank you! Your order #{$orderId} has been received."
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
