<?php

$hostingerConfig = '/home/u730879231/config.php';
$localConfig = __DIR__ . '/config.php';

if (is_file($hostingerConfig)) {
    require_once '/home/u730879231/config.php';
} elseif (is_file($localConfig)) {
    require_once $localConfig;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Config file missing']);
    exit;
}

$dbHost = defined('DB_HOST') ? DB_HOST : (isset($DB_HOST) ? $DB_HOST : '');
$dbName = defined('DB_NAME') ? DB_NAME : (isset($DB_NAME) ? $DB_NAME : '');
$dbUser = defined('DB_USER') ? DB_USER : (isset($DB_USER) ? $DB_USER : '');
$dbPass = defined('DB_PASS') ? DB_PASS : (isset($DB_PASS) ? $DB_PASS : '');
$dbPort = defined('DB_PORT') ? DB_PORT : (isset($DB_PORT) ? $DB_PORT : '');

$pricePerImage = defined('PRICE_PER_IMAGE') ? PRICE_PER_IMAGE : (isset($PRICE_PER_IMAGE) ? $PRICE_PER_IMAGE : '');
$startingBalance = defined('STARTING_BALANCE') ? STARTING_BALANCE : (isset($STARTING_BALANCE) ? $STARTING_BALANCE : '');
$minTopupAmount = defined('MIN_TOPUP_AMOUNT') ? MIN_TOPUP_AMOUNT : (isset($MIN_TOPUP_AMOUNT) ? $MIN_TOPUP_AMOUNT : '');

$missingConfig = [];
if ($dbHost === '') $missingConfig[] = 'DB_HOST';
if ($dbName === '') $missingConfig[] = 'DB_NAME';
if ($dbUser === '') $missingConfig[] = 'DB_USER';
if ($dbPass === '') $missingConfig[] = 'DB_PASS';
if ($dbPort === '') $missingConfig[] = 'DB_PORT';
if ($pricePerImage === '') $missingConfig[] = 'PRICE_PER_IMAGE';
if ($startingBalance === '') $missingConfig[] = 'STARTING_BALANCE';
if ($minTopupAmount === '') $missingConfig[] = 'MIN_TOPUP_AMOUNT';

if (!empty($missingConfig)) {
    error_log('Missing config keys: ' . implode(', ', $missingConfig));
    http_response_code(500);
    echo json_encode(['error' => 'Configuration missing']);
    exit;
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        $options
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$appConfig = [
    'price_per_image' => (float)$pricePerImage,
    'starting_balance' => (float)$startingBalance,
    'min_topup_amount' => (float)$minTopupAmount,
];
