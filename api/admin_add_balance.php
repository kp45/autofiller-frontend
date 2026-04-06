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
$amountRaw = $data['amount'] ?? null;

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid user email.']);
    exit;
}

if (!is_numeric($amountRaw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount must be numeric.']);
    exit;
}

$amount = round((float)$amountRaw, 2);
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount must be greater than 0.']);
    exit;
}

$adminId = (int)$_SESSION['admin_id'];

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare('SELECT id, balance FROM users WHERE email = ? FOR UPDATE');
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'User not found.']);
        exit;
    }

    $oldBalance = (float)$user['balance'];
    $newBalance = round($oldBalance + $amount, 2);

    $updStmt = $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?');
    $updStmt->execute([$newBalance, $user['id']]);

    $desc = sprintf('Admin balance credit by admin #%d', $adminId);
    $txnStmt = $pdo->prepare('INSERT INTO transactions (user_id, amount, type, description, status, payment_proof) VALUES (?, ?, ?, ?, ?, ?)');
    $txnStmt->execute([
        $user['id'],
        $amount,
        'credit',
        $desc,
        'approved',
        null,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('admin_add_balance.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not update user balance.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Balance updated successfully.',
    'old_balance' => $oldBalance,
    'new_balance' => $newBalance,
    'added_amount' => $amount,
]);
