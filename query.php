<?php require_once "config/db.php"; $q = $pdo->query("DESCRIBE investigation_tools"); while($r = $q->fetch()) print_r($r);
