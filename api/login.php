<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require __DIR__ . '/../db.php';

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$remember = (bool)($data['remember'] ?? true);

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

// Set session
$_SESSION['user_id'] = $user['id'];

// Remember me — 30 days cookie
if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, [
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Store token in DB for verification
    $pdo->prepare('UPDATE users SET remember_token = ? WHERE id = ?')
        ->execute([$token, $user['id']]);
}

echo json_encode([
    'success' => true,
    'user' => [
        'id'      => $user['id'],
        'name'    => $user['name'],
        'email'   => $user['email'],
        'balance' => (float)$user['balance'],
    ],
    'pricing' => $appConfig,
]);
