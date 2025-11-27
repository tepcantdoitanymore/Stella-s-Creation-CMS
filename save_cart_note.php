<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$pid  = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if (!$pid) {
    echo json_encode(['success' => false, 'error' => 'Missing product ID.']);
    exit;
}

// enforce 100-word limit server-side too
$words = preg_split('/\s+/', $note);
$words = array_filter($words, fn($w) => $w !== '');
if (count($words) > 100) {
    echo json_encode(['success' => false, 'error' => 'Note can be up to 100 words only.']);
    exit;
}

if (!isset($_SESSION['cart_notes'])) {
    $_SESSION['cart_notes'] = [];
}

$_SESSION['cart_notes'][$pid] = $note;

echo json_encode(['success' => true]);
