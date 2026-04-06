<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string)($data['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid user email.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, balance, created_at FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

$usageStmt = $pdo->prepare('SELECT COALESCE(SUM(images),0) AS total_images, COALESCE(SUM(cost),0) AS total_spent FROM usage_log WHERE user_id = ?');
$usageStmt->execute([$user['id']]);
$usage = $usageStmt->fetch();

echo json_encode([
    'success' => true,
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'balance' => (float)$user['balance'],
        'created_at' => $user['created_at'],
        'total_images' => (int)$usage['total_images'],
        'total_spent' => (float)$usage['total_spent'],
    ],
]);
