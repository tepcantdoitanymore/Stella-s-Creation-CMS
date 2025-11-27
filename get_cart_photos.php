<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$pid) {
    echo json_encode(['success' => false, 'error' => 'Missing product id.']);
    exit;
}

$cartPhotos = $_SESSION['cart_photos'][$pid] ?? [];
$photosOut  = [];

$webBase = '/uploads/cart_photos/'; // public URL base

foreach ($cartPhotos as $fname) {
    $fname = trim($fname);
    if ($fname === '') continue;
    $photosOut[] = [
        'filename' => $fname,
        'url'      => $webBase . $fname,
    ];
}

echo json_encode([
    'success' => true,
    'photos'  => $photosOut,
]);
