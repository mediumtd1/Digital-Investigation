<?php
require_once __DIR__ . '/../../config/db.php';

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

    case 'export':
        // Override content-type — we return HTML for this action
        header('Content-Type: text/html; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data) && isset($_GET['mac'])) {
            // Fetch stats from DB if we only have the MAC address via GET
            $stmt_hist = $db->prepare("SELECT * FROM mac_search_history WHERE mac_address = ? ORDER BY search_timestamp DESC LIMIT 1");
            $stmt_hist->execute([$_GET['mac']]);
            $hist = $stmt_hist->fetch(PDO::FETCH_ASSOC);
            
            $stmt_wl = $db->prepare("SELECT list_type FROM mac_watchlist WHERE mac_address = ?");
            $stmt_wl->execute([$_GET['mac']]);
            $wl = $stmt_wl->fetchColumn();

            if ($hist) {
                $data = [
                    'mac_address' => $hist['mac_address'],
                    'vendor' => $hist['vendor'],
                    'country' => $hist['country'],
                    'address' => $hist['address'],
                    'mac_prefix' => $hist['mac_prefix'],
                    'block_type' => $hist['block_type'],
                    'is_spoofed' => (int)$hist['is_spoofed'],
                    'is_private' => (int)$hist['is_private'],
                    'watchlist_status' => $wl ?: 'NONE'
                ];
            } else {
                $data = [
                    'mac_address' => $_GET['mac'],
                    'watchlist_status' => $wl ?: 'NONE'
                ];
            }
        }
        $mac     = htmlspecialchars($data['mac_address'] ?? 'N/A');
        $vendor  = htmlspecialchars($data['vendor'] ?? 'Unknown');
        $country = htmlspecialchars($data['country'] ?? 'N/A');
        $address = htmlspecialchars($data['address'] ?? 'N/A');
        $prefix  = htmlspecialchars($data['mac_prefix'] ?? 'N/A');
        $block   = htmlspecialchars($data['block_type'] ?? 'N/A');
        $spoofed = !empty($data['is_spoofed']) ? 'YES ⚠' : 'NO ✔';
        $private = !empty($data['is_private']) ? 'YES' : 'NO';
        $watchlist = htmlspecialchars($data['watchlist_status'] ?? 'NONE');
        $spoofed_class = ($spoofed === 'YES ⚠') ? 'status-warn' : 'status-ok';
        $watchlist_class = ($watchlist !== 'NONE') ? 'status-warn' : 'status-ok';
        $ts = date('d M Y, H:i:s');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MAC Lookup Report — {$mac}</title>
<style>
    body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fff; margin: 0; padding: 40px; }
    h1 { text-align:center; font-size:22px; text-transform:uppercase; letter-spacing:1px; border-bottom:3px double #000; padding-bottom:12px; margin-bottom:25px; }
    table { width:100%; border-collapse:collapse; margin-bottom:30px; }
    th, td { border:1px solid #000; padding:10px 14px; font-size:14px; text-align:left; }
    th { background:#f0f0f0; width:35%; font-weight:bold; }
    .status-ok { color:green; font-weight:bold; }
    .status-warn { color:red; font-weight:bold; }
    .footer { margin-top:50px; text-align:right; }
    .sig-line { border-top:1px solid #000; display:inline-block; width:260px; text-align:center; padding-top:5px; }
    @media print { body { padding:0; } button { display:none; } }
</style>
</head>
<body onload="window.print()">
<button onclick="window.print()" style="position:fixed;top:10px;right:10px;padding:10px 20px;background:#f59e0b;border:none;font-weight:bold;border-radius:4px;cursor:pointer;">🖨 Print / Save PDF</button>
<h1>MAC Address Investigation Report</h1>
<p style="text-align:center;font-size:13px;margin-bottom:25px;">Generated: {$ts}</p>
<table>
    <tr><th>MAC Address</th><td><strong style="font-family:monospace;font-size:16px;">{$mac}</strong></td></tr>
    <tr><th>Vendor / Manufacturer</th><td>{$vendor}</td></tr>
    <tr><th>Country of Origin</th><td>{$country}</td></tr>
    <tr><th>Registered Address</th><td>{$address}</td></tr>
    <tr><th>MAC Prefix (OUI)</th><td style="font-family:monospace;">{$prefix}</td></tr>
    <tr><th>Block Type</th><td>{$block}</td></tr>
    <tr><th>MAC Spoofing Detected</th><td class="{$spoofed_class}">{$spoofed}</td></tr>
    <tr><th>Private / Locally Administered</th><td>{$private}</td></tr>
    <tr><th>Watchlist Status</th><td class="{$watchlist_class}">{$watchlist}</td></tr>
</table>
<div class="footer">
    <div class="sig-line">Authorized Signature<br><small>Investigating Officer</small></div>
</div>
</body>
</html>
HTML;
        exit;

    default:
        echo json_encode(["error" => "Invalid action"]);
        break;
}
