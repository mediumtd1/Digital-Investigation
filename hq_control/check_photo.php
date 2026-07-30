<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$stmt = $pdo->query("SELECT * FROM officers LIMIT 1");
$officer = $stmt->fetch();
echo "Officer columns: " . implode(', ', array_keys($officer)) . "<br>";
if (isset($officer['photo_path'])) echo "Officer photo_path: " . $officer['photo_path'] . "<br>";
if (isset($officer['photo'])) echo "Officer photo: " . $officer['photo'] . "<br>";

$stmt = $pdo->query("SELECT * FROM interns LIMIT 1");
$intern = $stmt->fetch();
echo "Intern columns: " . implode(', ', array_keys($intern)) . "<br>";
if (isset($intern['photo_path'])) echo "Intern photo_path: " . $intern['photo_path'] . "<br>";
if (isset($intern['photo'])) echo "Intern photo: " . $intern['photo'] . "<br>";
