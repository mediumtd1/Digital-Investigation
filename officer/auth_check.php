<?php
// officer/auth_check.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Verify session role
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'Officer' && $_SESSION['user_role'] !== 'Admin')) {
    header("Location: " . BASE_URL . "/officer/login.php");
    exit;
}

// Get the requested file path from query param
$requested_path = $_GET['path'] ?? '';

// Sanitize path (prevent directory traversal)
$requested_path = str_replace(['..', '\\'], '', $requested_path);

// Ensure it points only to the allowed folders
if (strpos($requested_path, 'officer/police_station/') !== 0 && strpos($requested_path, 'officer/cyber_notice_portal/') !== 0) {
    header("HTTP/1.1 403 Forbidden");
    echo "Access Denied.";
    exit;
}

$file_path = __DIR__ . '/../' . $requested_path;

// Resolve directory index files if it's a directory
if (is_dir($file_path)) {
    $file_path = rtrim($file_path, '/');
    if (file_exists($file_path . '/index.php')) {
        $file_path = $file_path . '/index.php';
    } elseif (file_exists($file_path . '/index.html')) {
        $file_path = $file_path . '/index.html';
    }
}

if (file_exists($file_path) && is_file($file_path)) {
    // Set the execution working directory to the target file's directory so relative includes work properly
    chdir(dirname($file_path));
    
    // Include the actual target file to execute it
    include $file_path;
    exit;
} else {
    header("HTTP/1.1 404 Not Found");
    echo "Resource Not Found.";
    exit;
}
