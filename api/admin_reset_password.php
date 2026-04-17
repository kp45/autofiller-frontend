<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('POST, OPTIONS');

require __DIR__ . '/../db.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin not logged in']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$userEmail   = trim($data['email']        ?? '');
$newPassword = trim($data['new_password'] ?? '');

if (!$userEmail || !$newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'User email and new password are required.']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 6 characters long.']);
    exit;
}

try {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $userEmail]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found with that email.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully for user.',
    ]);
} catch (PDOException $e) {
    error_log('admin_reset_password.php database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Admin reset backend error. Verify users table and DB permissions.',
    ]);
}
