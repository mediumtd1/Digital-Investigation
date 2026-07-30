<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Load DB config but don't fail if it's missing (helps with portability)
@include_once __DIR__ . '/../../config/db.php';

if (empty($action) || empty($id)) {
    echo json_encode(['error' => 'Missing action or identifier']);
    exit;
}

// Log Search (Optional)
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO bitcoin_search_history (address_or_txid, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$id, $action, $_SERVER['REMOTE_ADDR'] ?? 'Unknown']);
    } catch (Exception $e) {}
}

// Sanitize input
$id = preg_replace('/[^a-zA-Z0-9]/', '', $id);

/**
 * Fetch data using cURL with professional headers
 */
function fetchBlockchainData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Cache-Control: no-cache'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return $response;
    }
    return false;
}

// PRIMARY SOURCE: Blockchain.info
$url = ($action === 'address') ? "https://blockchain.info/rawaddr/$id?limit=50" : "https://blockchain.info/rawtx/$id";
$response = fetchBlockchainData($url);

// SECONDARY SOURCE: Mempool.space
if (!$response) {
    $mempoolUrl = ($action === 'address') ? "https://mempool.space/api/address/$id" : "https://mempool.space/api/tx/$id";
    $backupResponse = fetchBlockchainData($mempoolUrl);
    if ($backupResponse) {
        $data = json_decode($backupResponse, true);
        // Map Mempool structure to Blockchain.info structure if needed
        if ($action === 'address') {
            $mapped = [
                'address' => $id,
                'total_received' => $data['chain_stats']['funded_txo_sum'] ?? 0,
                'total_sent' => $data['chain_stats']['spent_txo_sum'] ?? 0,
                'final_balance' => ($data['chain_stats']['funded_txo_sum'] ?? 0) - ($data['chain_stats']['spent_txo_sum'] ?? 0),
                'n_tx' => $data['chain_stats']['tx_count'] ?? 0,
                'txs' => [] // Mempool uses a separate endpoint for TX history
            ];
            echo json_encode($mapped);
        } else {
            echo $backupResponse;
        }
        exit;
    }
}

// TERTIARY SOURCE: BlockCypher
if (!$response) {
    $cypherUrl = ($action === 'address') ? "https://api.blockcypher.com/v1/btc/main/addrs/$id" : "https://api.blockcypher.com/v1/btc/main/txs/$id";
    $backup2 = fetchBlockchainData($cypherUrl);
    if ($backup2) {
        echo $backup2;
        exit;
    }
}

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Forensic API Error: Blockchain data could not be retrieved. The address might be invalid or the service is temporarily rate-limited.']);
    exit;
}

echo $response;
