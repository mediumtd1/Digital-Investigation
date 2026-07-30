<?php
header('Content-Type: application/json');

/**
 * Blockstream.info API Proxy
 * Handles requests for Bitcoin addresses and transaction details,
 * formatting them to match the legacy Blockchain.info response structure.
 */

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

require_once __DIR__ . '/../config/db.php';

if (empty($action) || empty($id)) {
    echo json_encode(['error' => 'Missing action or identifier']);
    exit;
}

// Log to Central Database
if ($pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO bitcoin_search_history (address_or_txid, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$id, $action, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {}
}

// Sanitize input to prevent injection
$id = preg_replace('/[^a-zA-Z0-9]/', '', $id);

function make_request($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpcode >= 400) {
        return false;
    }
    return $response;
}

if ($action === 'address') {
    // 1. Get address summary stats
    $stats_url = "https://blockstream.info/api/address/" . $id;
    $stats_response = make_request($stats_url);

    if ($stats_response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch address details. Address might be invalid or api offline.']);
        exit;
    }

    $stats_data = json_decode($stats_response, true);
    if (!$stats_data) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid data format received from upstream API.']);
        exit;
    }

    $total_received = ($stats_data['chain_stats']['funded_txo_sum'] ?? 0) + ($stats_data['mempool_stats']['funded_txo_sum'] ?? 0);
    $total_sent = ($stats_data['chain_stats']['spent_txo_sum'] ?? 0) + ($stats_data['mempool_stats']['spent_txo_sum'] ?? 0);
    $final_balance = $total_received - $total_sent;
    $n_tx = ($stats_data['chain_stats']['tx_count'] ?? 0) + ($stats_data['mempool_stats']['tx_count'] ?? 0);

    // 2. Get address transactions
    $txs_url = "https://blockstream.info/api/address/" . $id . "/txs";
    $txs_response = make_request($txs_url);
    $txs_data = [];
    if ($txs_response !== false) {
        $txs_data = json_decode($txs_response, true) ?: [];
    }

    // 3. Format transactions to Blockchain.com style
    $formatted_txs = [];
    foreach ($txs_data as $tx) {
        $inputs = [];
        if (isset($tx['vin']) && is_array($tx['vin'])) {
            foreach ($tx['vin'] as $vin) {
                $prev_out = null;
                if (isset($vin['prevout'])) {
                    $prev_out = [
                        'addr' => $vin['prevout']['scriptpubkey_address'] ?? 'Unknown',
                        'value' => (int)($vin['prevout']['value'] ?? 0)
                    ];
                }
                $inputs[] = ['prev_out' => $prev_out];
            }
        }

        $outputs = [];
        if (isset($tx['vout']) && is_array($tx['vout'])) {
            foreach ($tx['vout'] as $vout) {
                $outputs[] = [
                    'addr' => $vout['scriptpubkey_address'] ?? 'Unknown',
                    'value' => (int)($vout['value'] ?? 0)
                ];
            }
        }

        $formatted_txs[] = [
            'hash' => $tx['txid'] ?? '',
            'time' => $tx['status']['block_time'] ?? time(),
            'inputs' => $inputs,
            'out' => $outputs
        ];
    }

    // Output final structure
    echo json_encode([
        'address' => $id,
        'total_received' => $total_received,
        'total_sent' => $total_sent,
        'final_balance' => $final_balance,
        'n_tx' => $n_tx,
        'txs' => $formatted_txs
    ]);
    exit;

} elseif ($action === 'transaction') {
    // Get single transaction details
    $tx_url = "https://blockstream.info/api/tx/" . $id;
    $tx_response = make_request($tx_url);

    if ($tx_response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch transaction details. TxID might be invalid or api offline.']);
        exit;
    }

    $tx = json_decode($tx_response, true);
    if (!$tx) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid transaction data format received from upstream.']);
        exit;
    }

    $inputs = [];
    if (isset($tx['vin']) && is_array($tx['vin'])) {
        foreach ($tx['vin'] as $vin) {
            $prev_out = null;
            if (isset($vin['prevout'])) {
                $prev_out = [
                    'addr' => $vin['prevout']['scriptpubkey_address'] ?? 'Unknown',
                    'value' => (int)($vin['prevout']['value'] ?? 0)
                ];
            }
            $inputs[] = ['prev_out' => $prev_out];
        }
    }

    $outputs = [];
    if (isset($tx['vout']) && is_array($tx['vout'])) {
        foreach ($tx['vout'] as $vout) {
            $outputs[] = [
                'addr' => $vout['scriptpubkey_address'] ?? 'Unknown',
                'value' => (int)($vout['value'] ?? 0)
            ];
        }
    }

    // Output formatted transaction
    echo json_encode([
        'hash' => $tx['txid'] ?? '',
        'time' => $tx['status']['block_time'] ?? time(),
        'inputs' => $inputs,
        'out' => $outputs
    ]);
    exit;

} else {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}
