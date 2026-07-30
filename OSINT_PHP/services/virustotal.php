<?php
require_once __DIR__ . '/http_helper.php';

function virustotal_analyze_url(string $url): array {
    if (!VIRUSTOTAL_API_KEY) return _vt_mock_url($url);
    $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    $data = http_get(
        "https://www.virustotal.com/api/v3/urls/$encoded",
        ["x-apikey: " . VIRUSTOTAL_API_KEY]
    );
    if (!$data) return _vt_mock_url($url);
    $stats = $data['data']['attributes']['last_analysis_stats'] ?? [];
    return [
        'mock' => false,
        'data' => [
            'url'       => $url,
            'positives' => ($stats['malicious'] ?? 0) + ($stats['suspicious'] ?? 0),
            'total'     => array_sum($stats),
            'stats'     => $stats,
            'permalink' => "https://www.virustotal.com/gui/url/$encoded",
        ],
    ];
}

function virustotal_analyze_domain(string $domain): array {
    if (!VIRUSTOTAL_API_KEY) return _vt_mock_domain($domain);
    $data = http_get(
        "https://www.virustotal.com/api/v3/domains/$domain",
        ["x-apikey: " . VIRUSTOTAL_API_KEY]
    );
    if (!$data) return _vt_mock_domain($domain);
    $stats = $data['data']['attributes']['last_analysis_stats'] ?? [];
    return [
        'mock' => false,
        'data' => [
            'domain'    => $domain,
            'positives' => ($stats['malicious'] ?? 0) + ($stats['suspicious'] ?? 0),
            'total'     => array_sum($stats),
            'stats'     => $stats,
            'permalink' => "https://www.virustotal.com/gui/domain/$domain",
        ],
    ];
}

function _vt_mock_url(string $url): array {
    $sus = stripos($url, 'login') !== false || stripos($url, 'free') !== false;
    return ['mock' => true, 'data' => ['url' => $url, 'positives' => $sus ? 3 : 0, 'total' => 70, 'stats' => ['malicious' => $sus ? 3 : 0, 'harmless' => 60]]];
}

function _vt_mock_domain(string $domain): array {
    return ['mock' => true, 'data' => ['domain' => $domain, 'positives' => 0, 'total' => 70, 'stats' => ['malicious' => 0, 'harmless' => 65]]];
}
