<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE notices 
        ADD COLUMN IF NOT EXISTS office_name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS office_address TEXT,
        ADD COLUMN IF NOT EXISTS office_email VARCHAR(100),
        ADD COLUMN IF NOT EXISTS office_phone VARCHAR(50),
        ADD COLUMN IF NOT EXISTS reference_no VARCHAR(100),
        MODIFY COLUMN user_id INT NULL;");
    echo "Database updated successfully!";
} catch (PDOException $e) {
    echo "Update failed: " . $e->getMessage();
}
?>
