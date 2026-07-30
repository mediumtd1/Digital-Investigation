<?php
require_once __DIR__ . '/http_helper.php';

function groq_chat(string $message): ?string {
    if (!GROQ_API_KEY) return null;
    $payload = [
        'model'       => GROQ_MODEL,
        'temperature' => 0.2,
        'max_tokens'  => 600,
        'messages'    => [
            ['role' => 'system', 'content' => 'You are an OSINT and cybersecurity assistant. Provide accurate, concise, practical answers. Refuse harmful requests.'],
            ['role' => 'user',   'content' => $message],
        ],
    ];
    $data = http_post('https://api.groq.com/openai/v1/chat/completions', $payload, ['Authorization: Bearer ' . GROQ_API_KEY]);
    return $data['choices'][0]['message']['content'] ?? null;
}

function rule_based_answer(string $message): string {
    $msg = strtolower($message);
    $rules = [
        ['phishing', 'What is phishing? Phishing is a cyberattack where attackers impersonate legitimate entities to steal sensitive information. Always verify URLs and sender identities.'],
        ['ransomware', 'Ransomware is malware that encrypts your files and demands payment. Keep backups, patch systems, and avoid suspicious email attachments.'],
        ['osint', 'OSINT (Open Source Intelligence) is the collection of information from publicly available sources. Used in cybersecurity for threat reconnaissance.'],
        ['sql injection', 'SQL Injection is an attack that inserts malicious SQL into queries. Prevent it using parameterized queries and input validation.'],
        ['vpn', 'A VPN encrypts your internet traffic and masks your IP address, protecting privacy on public networks.'],
        ['2fa', 'Two-Factor Authentication adds an extra security layer beyond passwords. Enable it on all critical accounts.'],
        ['firewall', 'A firewall monitors and controls network traffic based on security rules, blocking unauthorized access.'],
        ['malware', 'Malware is malicious software including viruses, trojans, and spyware. Use updated antivirus software and avoid untrusted downloads.'],
        ['ddos', 'A DDoS (Distributed Denial of Service) attack overwhelms a server with traffic. Mitigate using CDNs, rate limiting, and DDoS protection services.'],
        ['dark web', 'The dark web is an encrypted network accessible via Tor, often associated with illegal activity. Legitimate uses include anonymous communication for journalists and activists.'],
        ['brute force', 'Brute force attacks try all possible passwords until they find the correct one. Defend with account lockouts, CAPTCHA, and strong passwords.'],
        ['zero day', 'A zero-day vulnerability is an unknown software flaw exploited before the developer can patch it.'],
    ];
    foreach ($rules as [$keyword, $answer]) {
        if (strpos($msg, $keyword) !== false) return $answer;
    }
    return "I can help with cybersecurity topics like phishing, malware, VPNs, firewalls, OSINT, SQL injection, and more. What would you like to know?";
}
