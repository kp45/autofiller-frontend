<?php

$db = getenv('DB_NAME') ?: 'u730879231_autofiller_db';
$user = getenv('DB_USER') ?: 'u730879231_idea_2';
$pass = getenv('DB_PASS') ?: 'Mysql@1369724680';
$port = (int)(getenv('DB_PORT') ?: 3306);

$configuredHost = getenv('DB_HOST');
$hosts = $configuredHost
    ? [$configuredHost]
    : ['localhost', 'srv1750.hstgr.io'];

$hosts = array_values(array_unique(array_filter($hosts)));
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT => 5,
];

$lastError = null;

foreach ($hosts as $host) {
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            $options
        );
        return;
    } catch (PDOException $e) {
        $lastError = sprintf('DB connection failed for host "%s": %s', $host, $e->getMessage());
    }
}

if ($lastError) {
    error_log($lastError);
}

http_response_code(500);
echo json_encode(['error' => 'Database connection failed']);
exit;
