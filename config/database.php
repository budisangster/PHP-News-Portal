<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/cache.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$cache = new Cache(__DIR__ . '/../cache', 3600); // Cache for 1 hour
