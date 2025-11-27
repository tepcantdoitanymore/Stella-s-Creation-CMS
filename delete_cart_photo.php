<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$pid      = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$filename = $_POST['filename'] ?? '';

if (!$pid || $filename === '') {
    echo json_encode(['success' => false, 'error' => 'Missing data.']);
    exit;
}

if (empty($_SESSION['cart_photos'][$pid]) || !is_array($_SESSION['cart_photos'][$pid])) {
    echo json_encode(['success' => false, 'error' => 'No photos found for this item.']);
    exit;
}

$photos = $_SESSION['cart_photos'][$pid];
$index  = array_search($filename, $photos, true);

if ($index === false) {
    echo json_encode(['success' => false, 'error' => 'Photo not found.']);
    exit;
}

unset($photos[$index]);
$_SESSION['cart_photos'][$pid] = array_values($photos);

$fsPath = __DIR__ . '/uploads/cart_photos/' . $filename;
if (is_file($fsPath)) {
    @unlink($fsPath);
}

echo json_encode([
    'success'   => true,
    'remaining' => count($_SESSION['cart_photos'][$pid]),
]);
