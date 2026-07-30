<?php
// Suppress PHP notices/warnings so they don't corrupt JSON output
error_reporting(0);
ini_set('display_errors', '0');
ob_start(); // buffer any stray output

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_end_clean(); exit(0); }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../services/http_helper.php';
require_once __DIR__ . '/../services/groq_chat.php';

try {
    $input   = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if (empty($message)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['detail' => 'Message is required']);
        exit;
    }

    $response = groq_chat($message) ?? rule_based_answer($message);
    ob_end_clean();
    echo json_encode(['response' => $response, 'timestamp' => date('c')]);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['detail' => 'Server error: ' . $e->getMessage()]);
}
