<?php
require_once __DIR__ . '/http_helper.php';

function ipinfo_get_geo(string $ip): array {
    $token = IPINFO_TOKEN ? "?token=" . IPINFO_TOKEN : '';
    $data = http_get("https://ipinfo.io/$ip/json$token");
    if (!$data) return _ipinfo_mock($ip);
    return ['mock' => false, 'data' => $data];
}

function _ipinfo_mock(string $ip): array {
    return [
        'mock' => true,
        'data' => [
            'ip'       => $ip,
            'city'     => 'Mumbai',
            'region'   => 'Maharashtra',
            'country'  => 'IN',
            'org'      => 'AS13335 Demo ISP',
            'timezone' => 'Asia/Kolkata',
            'loc'      => '19.0760,72.8777',
        ],
    ];
}
