<?php
$host = 'srv1750.hstgr.io';
$db = 'u730879231_autofiller_db';
$user = 'u730879231_idea_2';
$pass = 'Mysql@1369724680';
$port = 3306;

try {
 $pdo = new PDO(
 "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
 $user,
 $pass,
 [
 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 ]
 );
} catch (PDOException $e) {
 http_response_code(500);
 echo json_encode(['error' => 'Database connection failed']);
 exit;
}