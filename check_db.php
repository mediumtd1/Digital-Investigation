<?php
require_once 'config/db.php';
try {
    $q = $pdo->query("DESCRIBE intern_attendance");
    if ($q) {
        echo "Table exists:\n";
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
    } else {
        echo "Table does not exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
