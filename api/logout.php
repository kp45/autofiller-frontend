<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('POST, OPTIONS');

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
