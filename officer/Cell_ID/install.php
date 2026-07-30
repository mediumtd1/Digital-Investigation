<?php
require_once __DIR__ . '/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS police_stations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                state VARCHAR(255) NOT NULL,
                district VARCHAR(255) NOT NULL,
                police_station VARCHAR(255) NOT NULL,
                contact VARCHAR(255) NOT NULL,
                location VARCHAR(255) NOT NULL,
                latitude VARCHAR(50) DEFAULT NULL,
                longitude VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS atms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                atm_number VARCHAR(255) NOT NULL,
                bank_name VARCHAR(255) DEFAULT NULL,
                branch VARCHAR(255) DEFAULT NULL,
                city VARCHAR(255) DEFAULT NULL,
                state VARCHAR(255) DEFAULT NULL,
                contact VARCHAR(255) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                latitude VARCHAR(50) DEFAULT NULL,
                longitude VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (atm_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $message = 'Database and table have been created. You can now <a href="index.php">open the app</a>.';
    } catch (PDOException $e) {
        $error = 'Error: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Police Station App</title>
    <style>body{font-family:Arial, sans-serif;margin:24px;max-width:700px;}button{padding:10px 16px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;}button:hover{background:#0056b3;} .message{padding:12px;border-radius:4px;margin-bottom:16px;} .success{background:#e6ffed;color:#1d7a2a;border:1px solid #99d69c;} .error{background:#ffe8e8;color:#a51d1d;border:1px solid #d69c99;}</style>
</head>
<body>
    <h1>Install Police Station App</h1>
    <p>This page creates the MySQL database and the `police_stations` table using the credentials in <code>config.php</code>.</p>

    <?php if ($message): ?>
        <div class="message success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <button type="submit">Create Database and Table</button>
    </form>
    <p><a href="index.php">Back to app</a></p>
</body>
</html>
