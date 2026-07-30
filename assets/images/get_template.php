<?php
require_once __DIR__ . '/../../config/config.php';
// Simple wrapper to serve the certificate template image securely
$file = __DIR__ . '/certificate_template.jpg';
if (file_exists($file)) {
    $mime = mime_content_type($file);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    header('X-Debug-Path: ' . $file); // For debugging if it fails
    readfile($file);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "Template File Not Found at " . $file;
}
