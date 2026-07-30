<?php
require_once 'config/db.php';
try {
    $q = $pdo->query("SELECT * FROM articles LIMIT 1");
    if ($q) {
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "Article found: " . $row['title'] . "\n";
        } else {
            echo "Articles table is empty.\n";
        }
    } else {
        echo "Articles table not found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
