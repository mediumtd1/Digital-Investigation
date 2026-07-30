<?php
require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $statement = $pdo->prepare('DELETE FROM police_stations WHERE id = ?');
    $statement->execute([$id]);
}

header('Location: index.php');
exit;
