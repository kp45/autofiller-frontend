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

// Ensure admins table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Ensure default admin user exists (email and password provided by you)
$defaultEmail = 'kp452000@gmail.com';
$check = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
$check->execute([$defaultEmail]);
$admin = $check->fetch();

if (!$admin) {
    // Hash for password: Lenovo@13697
    $hash = password_hash('Lenovo@13697', PASSWORD_BCRYPT);
    $ins  = $pdo->prepare('INSERT INTO admins (email, password) VALUES (?, ?)');
    $ins->execute([$defaultEmail, $hash]);
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
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

