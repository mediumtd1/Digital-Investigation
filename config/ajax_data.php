<?php
// AJAX endpoint to fetch districts by state, or specializations by course
header('Content-Type: application/json');

// Auth check: require an active session
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';
$key = $_GET['key'] ?? '';

if ($type === 'districts' && !empty($key)) {
    $locations = require __DIR__ . '/../assets/data/locations.php';
    echo json_encode($locations[$key] ?? []);
} elseif ($type === 'specializations' && !empty($key)) {
    $courses = require __DIR__ . '/../assets/data/courses.php';
    echo json_encode($courses[$key] ?? []);
} else {
    echo json_encode([]);
}
