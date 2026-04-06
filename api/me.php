<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8001');
header('Access-Control-Allow-Credentials: true');

require __DIR__ . '/../db.php';

$userId = null;

// Check active session
if (!empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}
// Check remember_me cookie
elseif (!empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt  = $pdo->prepare('SELECT id FROM users WHERE remember_token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        $userId = $row['id'];
        $_SESSION['user_id'] = $userId;
    }
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, balance FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode([
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'balance' => (float)$user['balance'],
    ],
    'pricing' => $appConfig,
]);
