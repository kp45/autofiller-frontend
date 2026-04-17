<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('POST, OPTIONS');

require __DIR__ . '/_auth.php';
require __DIR__ . '/../db.php';

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

try {
    // Ensure default admin user exists.
    $defaultEmail = 'kp452000@gmail.com';
    $check = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
    $check->execute([$defaultEmail]);
    $defaultAdmin = $check->fetch();

    if (!$defaultAdmin) {
        $hash = password_hash('Lenovo@13697', PASSWORD_BCRYPT);
        $ins  = $pdo->prepare('INSERT INTO admins (email, password) VALUES (?, ?)');
        $ins->execute([$defaultEmail, $hash]);
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    $passwordOk = $admin && verify_password_compat($pdo, 'admins', (int)$admin['id'], $password, (string)$admin['password']);

    if (!$passwordOk) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid admin email or password.']);
        exit;
    }

    $_SESSION['admin_id'] = $admin['id'];

    echo json_encode([
        'success' => true,
        'admin'   => [
            'id'    => $admin['id'],
            'email' => $admin['email'],
        ],
    ]);
} catch (PDOException $e) {
    error_log('admin_login.php database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Admin login backend error. Check admins table and DB permissions.',
    ]);
}
