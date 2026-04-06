<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require __DIR__ . '/../db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$amount = (float)($_POST['amount'] ?? 0);
$minTopupAmount = (float)($appConfig['min_topup_amount'] ?? 10);

if ($amount < $minTopupAmount) {
    http_response_code(400);
    echo json_encode(['error' => 'Minimum amount is ₹' . number_format($minTopupAmount, 2, '.', '')]);
    exit;
}

$proofPath = '';

// Handle file upload
if (!empty($_FILES['proof']['tmp_name'])) {
    $allowed = ['jpg','jpeg','png','gif','pdf'];
    $ext     = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Use JPG, PNG, or PDF.']);
        exit;
    }

    $uploadDir = '../uploads/payment_proofs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename  = 'proof_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
    $destPath  = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['proof']['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }
    $proofPath = $destPath;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Payment proof screenshot is required']);
    exit;
}

// Insert transaction as pending
$pdo->prepare('INSERT INTO transactions (user_id, amount, type, description, status, payment_proof) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([
        $_SESSION['user_id'],
        $amount,
        'credit',
        'UPI top-up — ₹' . $amount,
        'pending',
        $proofPath,
    ]);

echo json_encode([
    'success' => true,
    'message' => 'Payment request submitted. Your balance will be credited within 2 minutes.',
    'pricing' => $appConfig,
]);
