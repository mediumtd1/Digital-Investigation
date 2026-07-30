<?php
// Database setup script
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('config/schema.sql');
    $pdo->exec($sql);
    
    // Add additional seed cases
    $pdo->exec("USE cyber_notice_db;");
    $pdo->exec("INSERT IGNORE INTO cases (fir_number, sections, complainant_name, investigating_officer_id, status) VALUES 
        ('FIR-101/2026', '66D IT Act', 'Amit Sharma', 1, 'Open'),
        ('FIR-105/2026', '420 IPC, 66C IT Act', 'Priya Singh', 1, 'Pending');");
    
    echo "Database setup successfully!";
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
