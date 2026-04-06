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

$priceRaw = $data['price_per_image'] ?? null;
$startingRaw = $data['starting_balance'] ?? null;
$minTopupRaw = $data['min_topup_amount'] ?? null;

if (!is_numeric($priceRaw) || !is_numeric($startingRaw) || !is_numeric($minTopupRaw)) {
    http_response_code(400);
    echo json_encode(['error' => 'All config values must be numeric.']);
    exit;
}

$price = round((float)$priceRaw, 2);
$starting = round((float)$startingRaw, 2);
$minTopup = round((float)$minTopupRaw, 2);

if ($price < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'PRICE_PER_IMAGE cannot be negative.']);
    exit;
}

if ($starting < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'STARTING_BALANCE cannot be negative.']);
    exit;
}

if ($minTopup < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'MIN_TOPUP_AMOUNT must be at least 1.']);
    exit;
}

$adminId = (int)$_SESSION['admin_id'];

$cleanupKeys = [
    'PRICE_PER_IMAGE',
    'STARTING_BALANCE',
    'MIN_TOPUP_AMOUNT',
    'price_per_image',
    'starting_balance',
    'min_topup_amount',
    'pricePerImage',
    'initialBalance',
    'minTopupAmount',
];

$placeholders = implode(',', array_fill(0, count($cleanupKeys), '?'));

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare("DELETE FROM app_config WHERE `key` IN ($placeholders)");
    $del->execute($cleanupKeys);

    $insert = $pdo->prepare(
        'INSERT INTO app_config (`key`, `value`, updated_at, updated_by) VALUES (?, ?, NOW(), ?), (?, ?, NOW(), ?), (?, ?, NOW(), ?)'
    );
    $insert->execute([
        'PRICE_PER_IMAGE',
        (string)$price,
        $adminId,
        'STARTING_BALANCE',
        (string)$starting,
        $adminId,
        'MIN_TOPUP_AMOUNT',
        (string)$minTopup,
        $adminId,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('admin_update_config.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not update app configuration.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Global pricing config updated.',
    'pricing' => [
        'price_per_image' => $price,
        'starting_balance' => $starting,
        'min_topup_amount' => $minTopup,
        'fastapi_endpoint' => $appConfig['fastapi_endpoint'] ?? '',
    ],
]);
