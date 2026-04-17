<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('GET, OPTIONS');

require __DIR__ . '/../db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$uid = $_SESSION['user_id'];

// Total images processed and total spent
$stmt = $pdo->prepare('SELECT COALESCE(SUM(images),0) as total_images, COALESCE(SUM(cost),0) as total_spent FROM usage_log WHERE user_id = ?');
$stmt->execute([$uid]);
$usage = $stmt->fetch();

// Current balance
$stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

echo json_encode([
    'images'  => (int)$usage['total_images'],
    'spent'   => (float)$usage['total_spent'],
    'balance' => (float)$user['balance'],
    'pricing' => $appConfig,
]);
