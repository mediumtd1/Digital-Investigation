<?php
// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

define('VIRUSTOTAL_API_KEY', $_ENV['VIRUSTOTAL_API_KEY'] ?? '');
define('ABUSEIPDB_API_KEY',  $_ENV['ABUSEIPDB_API_KEY']  ?? '');
define('IPINFO_TOKEN',       $_ENV['IPINFO_TOKEN']        ?? '');
define('PHISHTANK_APP_KEY',  $_ENV['PHISHTANK_APP_KEY']   ?? '');
define('HIBP_API_KEY',       $_ENV['HIBP_API_KEY']        ?? '');
define('GROQ_API_KEY',       $_ENV['GROQ_API_KEY']        ?? '');
define('GROQ_MODEL',         $_ENV['GROQ_MODEL']          ?? 'llama-3.1-8b-instant');
