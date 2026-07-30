<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'toll_directory_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Professional Database Connection for Production/cPanel
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Set error mode to exception to see actual SQL errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // In production, you might want to log this instead of using die()
    die("Database Connection Error: " . $e->getMessage());
}
?>