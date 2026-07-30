<?php
if (php_sapi_name() !== 'cli') {
    die("CLI access only");
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$to             = $argv[1] ?? '';
$subject        = $argv[2] ?? '';
$body           = $argv[3] ?? '';
$attachment     = $argv[4] ?? '';   // Optional: absolute path to file

if ($to && $subject && $body) {
    if (!empty($attachment) && file_exists($attachment)) {
        send_custom_email_with_attachment_sync($to, $subject, $body, $attachment);
    } else {
        send_custom_email_sync($to, $subject, $body);
    }
}
