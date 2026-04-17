<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('GET, OPTIONS');

require __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin not logged in']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, email FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin session invalid']);
    exit;
}

echo json_encode([
    'success' => true,
    'admin' => [
        'id' => (int)$admin['id'],
        'email' => $admin['email'],
    ],
    'pricing' => $appConfig,
]);
