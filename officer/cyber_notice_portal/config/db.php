<?php
// config/db.php - RESTORED STABILITY
$host = 'localhost';
$db   = 'surbhi_digitalinvestigation';
$user = 'surbhi_digitalinvestigation';
$pass = 'BUAWfc7xVRyxCrnVVSA2';

if ((isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1')) || (PHP_SAPI === 'cli')) {
    $host = '127.0.0.1'; $db = 'cyber_portal'; $user = 'root'; $pass = '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) { die("DATABASE_CONNECTION_ERROR"); }

if (!function_exists('esc')) { function esc($t) { return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8'); } }

// AUTO-CREATE CORE TABLES
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_mapped_id VARCHAR(50),
        user_role VARCHAR(20),
        action TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

