<?php
require_once __DIR__ . '/../config.php';

/**
 * Lightweight cURL helper — returns decoded JSON or null on failure.
 */
function http_get(string $url, array $headers = []): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'OSINT-Threat-Intel/1.0',
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $code >= 500) return null;
    return json_decode($response, true);
}

function http_post(string $url, array $payload, array $headers = []): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'OSINT-Threat-Intel/1.0',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response === false) return null;
    return json_decode($response, true);
}
