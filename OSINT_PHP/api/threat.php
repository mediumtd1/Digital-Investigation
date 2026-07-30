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
require_once __DIR__ . '/../services/ai_analyzer.php';
require_once __DIR__ . '/../services/virustotal.php';
require_once __DIR__ . '/../services/abuseipdb.php';
require_once __DIR__ . '/../services/ipinfo.php';
require_once __DIR__ . '/../services/phishtank.php';
require_once __DIR__ . '/../services/hibp.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = trim($input['query'] ?? '');
    $query_type = $input['query_type'] ?? null;

    if (empty($query)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['detail' => 'Query is required']);
        exit;
    }

    $qtype = $query_type ?? detect_input_type($query);

    ob_end_clean(); // discard any stray output before writing JSON
    switch ($qtype) {
        case 'url':    echo json_encode(analyze_url($query));    break;
        case 'ip':     echo json_encode(analyze_ip($query));     break;
        case 'email':  echo json_encode(analyze_email($query));  break;
        case 'domain': echo json_encode(analyze_domain($query)); break;
        default:
            http_response_code(400);
            echo json_encode(['detail' => 'Invalid input. Enter a single IP, URL, email, or domain.']);
    }
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['detail' => 'Server error: ' . $e->getMessage()]);
}

function analyze_url(string $url): array {
    $vt_data   = virustotal_analyze_url($url);
    $phish_data = phishtank_check($url);
    $ai = score_url($url);

    $positives = $vt_data['data']['positives'] ?? 0;
    if ($positives > 5) $ai['risk_score'] = min($ai['risk_score'] + 20, 100);
    elseif ($positives > 0) $ai['risk_score'] = min($ai['risk_score'] + 10, 100);

    if ($phish_data['data']['url_info']['in_database'] ?? false) {
        $ai['risk_score'] = min($ai['risk_score'] + 25, 100);
        $ai['indicators'][] = 'Listed in PhishTank database';
    }
    $ai['threat_level']          = score_to_level($ai['risk_score']);
    $ai['summary']               = generate_url_summary($ai['risk_score'], $ai['threat_level'], $ai['indicators']);
    $ai['recommendations']       = url_recommendations($ai['risk_score']);
    $ai['phishing_probability']  = round(min($ai['risk_score'] / 100, 1.0), 2);

    return ['query' => $url, 'query_type' => 'url', 'virustotal' => $vt_data, 'phishtank' => $phish_data, 'ai_analysis' => $ai, 'timestamp' => date('c')];
}

function analyze_ip(string $ip): array {
    $abuse_data = abuseipdb_analyze($ip);
    $geo_data   = ipinfo_get_geo($ip);
    $ai = score_ip($ip, $abuse_data['data']['abuseConfidenceScore'] ?? 0, $abuse_data['data']['totalReports'] ?? 0);
    return ['query' => $ip, 'query_type' => 'ip', 'abuseipdb' => $abuse_data, 'geolocation' => $geo_data, 'ai_analysis' => $ai, 'timestamp' => date('c')];
}

function analyze_email(string $email): array {
    $breach_data = hibp_check($email);
    $ai = score_email($email, $breach_data['breach_count'] ?? 0);
    return ['query' => $email, 'query_type' => 'email', 'breaches' => $breach_data, 'ai_analysis' => $ai, 'timestamp' => date('c')];
}

function analyze_domain(string $domain): array {
    $vt_data = virustotal_analyze_domain($domain);
    $ai = score_url("http://$domain");
    return ['query' => $domain, 'query_type' => 'domain', 'virustotal' => $vt_data, 'ai_analysis' => $ai, 'timestamp' => date('c')];
}
