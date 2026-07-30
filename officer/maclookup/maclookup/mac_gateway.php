<?php
require_once __DIR__ . '/../../../config/db.php';

// Action handler for MAC operations
$action = $_REQUEST['action'] ?? '';

// Load Analyzer
require_once __DIR__ . '/utils/mac_analyzer.php';

if (empty($action)) {
    echo json_encode(["error" => "No action specified"]);
    exit;
}

header('Content-Type: application/json');

// Get the main PDO connection
$db = $pdo;

if (!$db) {
    echo json_encode(["error" => "Database connection offline"]);
    exit;
}

switch ($action) {
    case 'lookup':
        $mac = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $mac = $data['mac_address'] ?? $_POST['mac_address'] ?? '';
        } else {
            $mac = $_GET['mac_address'] ?? '';
        }
        
        $mac = trim($mac);
        if (!$mac) {
            echo json_encode(["error" => "MAC address required"]);
            exit;
        }

        $result = mac_analyze($mac);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result);
            exit;
        }

        // Check Watchlist in MySQL
        $stmt = $db->prepare("SELECT list_type FROM mac_watchlist WHERE mac_address = ?");
        $stmt->execute([$result['mac_address']]);
        $watchlist_entry = $stmt->fetch();
        $result['watchlist_status'] = $watchlist_entry ? $watchlist_entry['list_type'] : "NONE";

        // Save to History in MySQL
        try {
            $stmt = $db->prepare("INSERT INTO mac_search_history 
                (mac_address, vendor, is_spoofed, country, address, mac_prefix, block_type, is_private, last_api_update) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $result['mac_address'],
                $result['vendor'],
                $result['is_spoofed'] ? 1 : 0,
                $result['country'] ?? 'Unknown',
                $result['address'] ?? 'Unknown',
                $result['mac_prefix'] ?? null,
                $result['block_type'] ?? null,
                $result['is_private'] ? 1 : 0,
                $result['last_api_update'] ?? 'Unknown'
            ]);
        } catch (Exception $e) {}

        echo json_encode($result);
        break;

    case 'stats':
        $total = $db->query("SELECT COUNT(*) FROM mac_search_history")->fetchColumn();
        $spoofed = $db->query("SELECT COUNT(*) FROM mac_search_history WHERE is_spoofed = 1")->fetchColumn();
        $watchlist = $db->query("SELECT COUNT(*) FROM mac_watchlist")->fetchColumn();
        $recent = $db->query("SELECT id, mac_address, search_timestamp FROM mac_search_history ORDER BY search_timestamp DESC LIMIT 10")->fetchAll();
        
        echo json_encode([
            "total_searches" => (int)$total,
            "spoofed_count" => (int)$spoofed,
            "watchlist_hits" => (int)$watchlist,
            "recent_searches" => $recent
        ]);
        break;

    case 'watchlist':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $mac = strtoupper(trim($data['mac_address'] ?? ''));
            $list_type = $data['list_type'] ?? 'BLACKLIST';
            
            $stmt = $db->prepare("INSERT INTO mac_watchlist (mac_address, list_type) VALUES (?, ?) ON DUPLICATE KEY UPDATE list_type = VALUES(list_type)");
            $stmt->execute([$mac, $list_type]);
            echo json_encode(["success" => true]);
        }
        break;

    default:
        echo json_encode(["error" => "Invalid action"]);
        break;
}
