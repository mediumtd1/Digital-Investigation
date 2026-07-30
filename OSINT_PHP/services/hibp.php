<?php
require_once __DIR__ . '/http_helper.php';

function hibp_check(string $email): array {
    if (!HIBP_API_KEY) return _hibp_mock($email);
    $data = http_get(
        "https://haveibeenpwned.com/api/v3/breachedaccount/" . urlencode($email) . "?truncateResponse=false",
        ["hibp-api-key: " . HIBP_API_KEY, "User-Agent: OSINT-Intel"]
    );
    if ($data === null) return ['mock' => false, 'breach_count' => 0, 'breaches' => []];
    return [
        'mock'         => false,
        'breach_count' => count($data),
        'breaches'     => array_map(fn($b) => [
            'name'         => $b['Name'] ?? '',
            'domain'       => $b['Domain'] ?? '',
            'breach_date'  => $b['BreachDate'] ?? '',
            'data_classes' => $b['DataClasses'] ?? [],
        ], $data),
    ];
}

function _hibp_mock(string $email): array {
    $suspicious = strpos($email, 'test') !== false || strpos($email, 'admin') !== false;
    return [
        'mock'         => true,
        'breach_count' => $suspicious ? 2 : 0,
        'breaches'     => $suspicious ? [
            ['name' => 'MockBreach2023', 'domain' => 'example.com', 'breach_date' => '2023-01-01', 'data_classes' => ['Emails', 'Passwords']],
        ] : [],
    ];
}
