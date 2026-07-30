<?php

function detect_input_type(string $q): string {
    $q = trim($q);
    if (preg_match('/^https?:\/\//i', $q)) return 'url';
    if (preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $q)) return 'ip';
    if (filter_var($q, FILTER_VALIDATE_EMAIL)) return 'email';
    if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z]{2,})+$/', $q)) return 'domain';
    return 'unknown';
}

function score_to_level(int $score): string {
    if ($score >= 80) return 'CRITICAL';
    if ($score >= 60) return 'HIGH';
    if ($score >= 40) return 'MEDIUM';
    if ($score >= 20) return 'LOW';
    return 'SAFE';
}

function score_url(string $url): array {
    $score = 0;
    $indicators = [];
    $url_lower = strtolower($url);

    $sus_keywords = ['login', 'signin', 'verify', 'update', 'secure', 'account', 'banking', 'free', 'click', 'prize', 'winner', 'password', 'credential'];
    foreach ($sus_keywords as $kw) {
        if (strpos($url_lower, $kw) !== false) { $score += 10; $indicators[] = "Suspicious keyword: '$kw'"; }
    }

    if (strlen($url) > 100) { $score += 10; $indicators[] = 'Unusually long URL'; }
    if (substr_count($url, '-') > 3) { $score += 10; $indicators[] = 'Multiple hyphens in URL'; }
    if (substr_count($url, '.') > 4) { $score += 10; $indicators[] = 'Excessive subdomains'; }

    $sus_tlds = ['.tk', '.ml', '.ga', '.cf', '.gq', '.xyz', '.top', '.club', '.online'];
    foreach ($sus_tlds as $tld) {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if (substr($host, -strlen($tld)) === $tld) {
            $score += 20; $indicators[] = "Suspicious TLD: $tld";
        }
    }
    if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $url)) {
        $score += 20; $indicators[] = 'IP address used as host';
    }
    if (!preg_match('/^https:\/\//i', $url)) { $score += 5; $indicators[] = 'Non-HTTPS connection'; }

    $score = min($score, 100);
    $level = score_to_level($score);

    return [
        'risk_score'  => $score,
        'threat_level'=> $level,
        'indicators'  => $indicators,
        'summary'     => generate_url_summary($score, $level, $indicators),
        'recommendations' => url_recommendations($score),
        'phishing_probability' => round(min($score / 100, 1.0), 2),
    ];
}

function score_ip(string $ip, int $abuse_score, int $report_count): array {
    $score = 0;
    $indicators = [];

    if ($abuse_score > 80) { $score += 50; $indicators[] = "High abuse confidence: $abuse_score%"; }
    elseif ($abuse_score > 50) { $score += 30; $indicators[] = "Moderate abuse score: $abuse_score%"; }
    elseif ($abuse_score > 20) { $score += 15; $indicators[] = "Low abuse score: $abuse_score%"; }

    if ($report_count > 100) { $score += 20; $indicators[] = "High report count: $report_count"; }
    elseif ($report_count > 10) { $score += 10; $indicators[] = "Multiple reports: $report_count"; }

    $score = min($score, 100);
    $level = score_to_level($score);

    return [
        'risk_score'   => $score,
        'threat_level' => $level,
        'indicators'   => $indicators,
        'summary'      => "IP $ip scored $score/100. Threat level: $level.",
        'recommendations' => $score > 60 ? ['Block this IP', 'Report to ISP', 'Monitor logs'] : ['Monitor traffic from this IP', 'Keep logs updated'],
    ];
}

function score_email(string $email, int $breach_count): array {
    $score = 0;
    $indicators = [];

    if ($breach_count > 5) { $score = 85; $indicators[] = "Found in $breach_count data breaches"; }
    elseif ($breach_count > 2) { $score = 60; $indicators[] = "Found in $breach_count breaches"; }
    elseif ($breach_count > 0) { $score = 35; $indicators[] = "Found in $breach_count breach"; }
    else { $indicators[] = 'No known data breaches found'; }

    $level = score_to_level($score);

    return [
        'risk_score'   => $score,
        'threat_level' => $level,
        'indicators'   => $indicators,
        'summary'      => $breach_count > 0 ? "Email found in $breach_count breach(es). Change passwords immediately." : "No breaches detected for this email.",
        'recommendations' => $breach_count > 0 ? ['Change all passwords immediately', 'Enable 2FA', 'Monitor for phishing emails'] : ['Enable 2FA as precaution', 'Use a password manager'],
    ];
}

function generate_url_summary(int $score, string $level, array $indicators): string {
    $count = count($indicators);
    if ($score >= 80) return "CRITICAL threat detected. $count risk indicators found. Do not visit this URL.";
    if ($score >= 60) return "HIGH risk URL. $count suspicious indicators identified. Exercise extreme caution.";
    if ($score >= 40) return "MEDIUM risk. $count concerns noted. Verify the source before proceeding.";
    if ($score >= 20) return "LOW risk URL with $count minor concerns. Proceed with caution.";
    return "URL appears SAFE with no significant threat indicators detected.";
}

function url_recommendations(int $score): array {
    if ($score >= 80) return ['Do NOT visit this URL', 'Report to cybercrime authorities', 'Block at firewall level', 'Warn others'];
    if ($score >= 60) return ['Avoid visiting this URL', 'Verify through official channels', 'Check with IT security team'];
    if ($score >= 40) return ['Verify the URL source', 'Ensure HTTPS connection', 'Check domain reputation'];
    if ($score >= 20) return ['Proceed with caution', 'Verify the site is legitimate'];
    return ['URL appears safe', 'Always verify HTTPS before entering sensitive data'];
}
