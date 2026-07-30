<?php
require_once __DIR__ . '/http_helper.php';

function phishtank_check(string $url): array {
    if (!PHISHTANK_APP_KEY) return ['mock' => true, 'data' => ['url_info' => ['in_database' => false]]];
    $ch = curl_init('https://checkurl.phishtank.com/checkurl/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['url' => base64_encode($url), 'format' => 'json', 'app_key' => PHISHTANK_APP_KEY]),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return ['mock' => true, 'data' => ['url_info' => ['in_database' => false]]];
    $data = json_decode($response, true);
    return ['mock' => false, 'data' => $data ?? ['url_info' => ['in_database' => false]]];
}
