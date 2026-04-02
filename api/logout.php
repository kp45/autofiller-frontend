<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require __DIR__ . '/../db.php';

// Clear remember token from DB
if (!empty($_SESSION['user_id'])) {
    $pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')
        ->execute([$_SESSION['user_id']]);
}

// Destroy session
session_destroy();

// Clear cookie
setcookie('remember_token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

echo json_encode(['success' => true]);
