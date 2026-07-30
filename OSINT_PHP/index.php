<?php
// Simple front controller — redirect / to /pages/search.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

if ($uri === '' || $uri === '/index.php') {
    header('Location: pages/search.php');
    exit;
}
