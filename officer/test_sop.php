<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM sops");
    echo "SOP Count: " . $stmt->fetchColumn();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
