<?php
$host = 'localhost';      // use TCP to avoid socket issues
$db   = 'u730879231_autofiller_db';
$user = 'u730879231_idea_2';        // dedicated MySQL user created by setup.sql
$pass = 'Mysql@1369724680'; 



try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
