<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('POST, OPTIONS');

require __DIR__ . '/../db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$images = (int)($data['images'] ?? 0);

if ($images <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image count']);
    exit;
}

$pricePerImage = (float)($appConfig['price_per_image'] ?? 2);
$cost = round($images * $pricePerImage, 2);

// Fetch current balance
$stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ((float)$user['balance'] < $cost) {
    http_response_code(402);
    echo json_encode([
        'error'    => 'Insufficient balance',
        'balance'  => (float)$user['balance'],
        'required' => $cost,
    ]);
    exit;
}

// Deduct balance
$pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
    ->execute([$cost, $_SESSION['user_id']]);

// Log usage
$pdo->prepare('INSERT INTO usage_log (user_id, images, cost) VALUES (?, ?, ?)')
    ->execute([$_SESSION['user_id'], $images, $cost]);

// Return updated balance
$stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$updated = $stmt->fetch();

echo json_encode([
    'success'     => true,
    'deducted'    => $cost,
    'new_balance' => (float)$updated['balance'],
    'pricing'     => $appConfig,
]);
