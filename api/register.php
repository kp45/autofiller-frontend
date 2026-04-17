<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/_cors.php';
send_cors_headers('POST, OPTIONS');

require __DIR__ . '/../db.php';

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters long.']);
    exit;
}

// Check if user already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    http_response_code(409);
    echo json_encode([
        'error' => 'User already exists. Please log in with this email.',
        'code'  => 'USER_EXISTS',
    ]);
    exit;
}

// Create user with hashed password
$hash = password_hash($password, PASSWORD_BCRYPT);

// Simple default name from email prefix
$name = explode('@', $email)[0] ?: 'User';
$startingBalance = (float)($appConfig['starting_balance'] ?? 100);

$stmt = $pdo->prepare('INSERT INTO users (email, password, name, balance) VALUES (?, ?, ?, ?)');
$stmt->execute([$email, $hash, $name, $startingBalance]);

echo json_encode([
    'success' => true,
    'message' => 'Account created successfully. Please log in with your email and password.',
    'pricing' => $appConfig,
]);
