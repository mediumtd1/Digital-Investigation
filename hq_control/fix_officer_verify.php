<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin'); // Security: Admin authentication required
$stmt = $pdo->prepare("UPDATE officers SET is_verified = 1 WHERE status = 'Active'");
$stmt->execute();
echo "<h3>Updated " . $stmt->rowCount() . " Active officers to be is_verified = 1.</h3>";

// Specifically fix atulshrivastava290@gmail.com
$stmt2 = $pdo->prepare("UPDATE officers SET is_verified = 1, status = 'Active', otp = NULL, otp_expiry = NULL WHERE email = 'atulshrivastava290@gmail.com'");
$stmt2->execute();
echo "<h3>Updated atul specifically to is_verified = 1 and Active.</h3>";

echo "<hr><h3><a href='../officer/login.php'>Go back to Officer Login</a></h3>";
