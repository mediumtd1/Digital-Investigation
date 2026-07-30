<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['module_id'] = 1;
$_SESSION['user_id'] = '1';
$_SESSION['role'] = 'Intern';
require_once __DIR__ . '/intern/ajax_mark_module.php';
