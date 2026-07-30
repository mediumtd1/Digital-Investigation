<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("HTTP/1.1 404 Not Found");
    exit;
}
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Generate CSRF Token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function validate_csrf($token) {
    return hash_equals(get_csrf_token(), $token);
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . get_csrf_token() . '">';
}
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://kit.fontawesome.com https://ka-f.fontawesome.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://ka-f.fontawesome.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; connect-src 'self' https: blob:; worker-src 'self' blob: https://cdnjs.cloudflare.com; child-src 'self' blob: https://cdnjs.cloudflare.com;");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
date_default_timezone_set('Asia/Kolkata');
define('SITE_NAME', 'Digital Investigation — Management System');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$script_name = $_SERVER['SCRIPT_NAME'];
$dir = str_replace('\\', '/', dirname($script_name));
if ($dir !== '/') {
    if (strpos($dir, '/hq_control') !== false)
        $dir = str_replace('/hq_control', '', $dir);
    if (strpos($dir, '/officer') !== false)
        $dir = str_replace('/officer', '', $dir);
}
$base_dir = rtrim($dir, '/');
define('BASE_URL', $protocol . $host . $base_dir);
define('MOCK_OTP', false);

// DirectAdmin SMTP config — credentials loaded from secrets file
$_secrets_file = __DIR__ . '/secrets.php';
if (file_exists($_secrets_file)) {
    require_once $_secrets_file;
} else {
    // Fallback defaults (override in secrets.php on server)
    define('SMTP_HOST', 'mail.digitalinvestigation.in');
    define('SMTP_PORT', 465);
    define('SMTP_USER', 'support@digitalinvestigation.in');
    define('SMTP_PASS', '');
}
require_once __DIR__ . '/../includes/functions.php';
