<?php
$pdo = new PDO("mysql:host=localhost;dbname=cyber_portal", 'root', '');
$stmt = $pdo->query("SHOW COLUMNS FROM interns");
echo "INTERNS COLUMNS:\n";
foreach($stmt->fetchAll() as $row) echo $row['Field'] . "\n";

$stmt = $pdo->query("SHOW COLUMNS FROM officers");
echo "\nOFFICERS COLUMNS:\n";
foreach($stmt->fetchAll() as $row) echo $row['Field'] . "\n";
