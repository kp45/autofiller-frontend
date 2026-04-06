<?php

$configPaths = [
    '/home/u730879231/domains/gatijobs.in/config.php',
    __DIR__ . '/config.php',
];

$loadedConfig = false;
foreach ($configPaths as $configPath) {
    if (is_readable($configPath)) {
        include_once $configPath;
        $loadedConfig = true;
        break;
    }
}

if (!$loadedConfig) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file missing']);
    exit;
}

$dbHost = defined('DB_HOST') ? DB_HOST : (isset($DB_HOST) ? $DB_HOST : (isset($host) ? $host : ''));
$dbName = defined('DB_NAME') ? DB_NAME : (isset($DB_NAME) ? $DB_NAME : (isset($db) ? $db : ''));
$dbUser = defined('DB_USER') ? DB_USER : (isset($DB_USER) ? $DB_USER : (isset($user) ? $user : ''));
$dbPass = defined('DB_PASS') ? DB_PASS : (isset($DB_PASS) ? $DB_PASS : (isset($pass) ? $pass : ''));
$dbPort = defined('DB_PORT') ? DB_PORT : (isset($DB_PORT) ? $DB_PORT : (isset($port) ? $port : ''));

$pricePerImage = defined('PRICE_PER_IMAGE') ? PRICE_PER_IMAGE : (isset($PRICE_PER_IMAGE) ? $PRICE_PER_IMAGE : '');
$startingBalance = defined('STARTING_BALANCE') ? STARTING_BALANCE : (isset($STARTING_BALANCE) ? $STARTING_BALANCE : '');
$minTopupAmount = defined('MIN_TOPUP_AMOUNT') ? MIN_TOPUP_AMOUNT : (isset($MIN_TOPUP_AMOUNT) ? $MIN_TOPUP_AMOUNT : '');
$fastApiEndpoint = trim((string)(defined('FASTAPI_ENDPOINT') ? FASTAPI_ENDPOINT : (isset($FASTAPI_ENDPOINT) ? $FASTAPI_ENDPOINT : '')));

if ($fastApiEndpoint === '' && is_readable(__DIR__ . '/.env')) {
    $parsedEnv = @parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    if (is_array($parsedEnv) && !empty($parsedEnv['FASTAPI_ENDPOINT'])) {
        $fastApiEndpoint = trim((string)$parsedEnv['FASTAPI_ENDPOINT']);
    }
}

$missingConfig = [];
if ($dbHost === '') $missingConfig[] = 'DB_HOST';
if ($dbName === '') $missingConfig[] = 'DB_NAME';
if ($dbUser === '') $missingConfig[] = 'DB_USER';
if ($dbPass === '') $missingConfig[] = 'DB_PASS';
if ($dbPort === '') $missingConfig[] = 'DB_PORT';
if ($fastApiEndpoint === '') $missingConfig[] = 'FASTAPI_ENDPOINT';

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

$fallbackPricePerImage = is_numeric($pricePerImage) ? (float)$pricePerImage : 2.0;
$fallbackStartingBalance = is_numeric($startingBalance) ? (float)$startingBalance : 20.0;
$fallbackMinTopupAmount = is_numeric($minTopupAmount) ? (float)$minTopupAmount : 100.0;

$appConfig = [
    'price_per_image' => $fallbackPricePerImage,
    'starting_balance' => $fallbackStartingBalance,
    'min_topup_amount' => $fallbackMinTopupAmount,
    'fastapi_endpoint' => $fastApiEndpoint,
];

try {
    $rows = $pdo->query('SELECT `key`, `value` FROM app_config')->fetchAll();
    if ($rows) {
        $configMap = [];
        foreach ($rows as $row) {
            $configMap[(string)$row['key']] = (string)$row['value'];
        }

        $resolveConfigValue = function (array $aliases) use ($configMap) {
            foreach ($aliases as $alias) {
                if (!array_key_exists($alias, $configMap)) {
                    continue;
                }
                $value = trim((string)$configMap[$alias]);
                if ($value !== '') {
                    return $value;
                }
            }
            return null;
        };

        $tablePricePerImage = $resolveConfigValue(['PRICE_PER_IMAGE', 'price_per_image', 'pricePerImage']);
        if ($tablePricePerImage !== null && is_numeric($tablePricePerImage)) {
            $appConfig['price_per_image'] = (float)$tablePricePerImage;
        }

        $tableStartingBalance = $resolveConfigValue(['STARTING_BALANCE', 'starting_balance', 'initialBalance']);
        if ($tableStartingBalance !== null && is_numeric($tableStartingBalance)) {
            $appConfig['starting_balance'] = (float)$tableStartingBalance;
        }

        $tableMinTopupAmount = $resolveConfigValue(['MIN_TOPUP_AMOUNT', 'min_topup_amount', 'minTopupAmount']);
        if ($tableMinTopupAmount !== null && is_numeric($tableMinTopupAmount)) {
            $appConfig['min_topup_amount'] = (float)$tableMinTopupAmount;
        }

        $tableFastApiEndpoint = $resolveConfigValue(['FASTAPI_ENDPOINT', 'fastapi_endpoint']);
        if ($tableFastApiEndpoint !== null) {
            $appConfig['fastapi_endpoint'] = $tableFastApiEndpoint;
        }
    }
} catch (Throwable $e) {
    error_log('app_config read skipped: ' . $e->getMessage());
}
