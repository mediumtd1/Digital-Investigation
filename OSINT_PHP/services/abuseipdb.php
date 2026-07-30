<?php
require_once __DIR__ . '/http_helper.php';

function abuseipdb_analyze(string $ip): array {
    if (!ABUSEIPDB_API_KEY) return _abuse_mock($ip);
    $data = http_get(
        "https://api.abuseipdb.com/api/v2/check?ipAddress=" . urlencode($ip) . "&maxAgeInDays=90&verbose",
        ["Key: " . ABUSEIPDB_API_KEY, "Accept: application/json"]
    );
    if (!$data || !isset($data['data'])) return _abuse_mock($ip);
    return ['mock' => false, 'data' => $data['data']];
}

function _abuse_mock(string $ip): array {
    $is_private = preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $ip);
    return [
        'mock' => true,
        'data' => [
            'ipAddress'            => $ip,
            'abuseConfidenceScore' => $is_private ? 0 : rand(0, 25),
            'countryCode'          => 'IN',
            'isp'                  => 'Demo ISP',
            'domain'               => 'demo.example.com',
            'totalReports'         => $is_private ? 0 : rand(0, 5),
            'isPublic'             => !$is_private,
            'usageType'            => 'Data Center/Web Hosting/Transit',
        ],
    ];
}
