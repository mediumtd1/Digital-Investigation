<?php

function get_cyber_news() {
    $cache_file = __DIR__ . '/../assets/data/news_cache.json';
    $cache_time = 12 * 3600; 

    
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        $news = json_decode(file_get_contents($cache_file), true);
        if (!empty($news)) return $news;
    }

    
    
    $news_sources = [
        [
            'source' => 'CERT-In Advisory',
            'title' => 'Vulnerability in Google Chrome for Desktop',
            'desc' => 'Multiple vulnerabilities have been reported in Google Chrome which could allow a remote attacker to execute arbitrary code.',
            'tag' => 'CRITICAL',
            'time' => '4 Hours Ago'
        ],
        [
            'source' => 'MHA Cyber Dost',
            'title' => 'Rise in Part-time Job Scams',
            'desc' => 'Home Ministry issues advisory against fraudulent part-time job offers via WhatsApp/Telegram promising high returns.',
            'tag' => 'ADVISORY',
            'time' => '10 Hours Ago'
        ],
        [
            'source' => 'RBI Security',
            'title' => 'New Guidelines on Digital Lending',
            'desc' => 'RBI mandates that all digital loans must be disbursed and repaid through regulated entities only.',
            'tag' => 'REGULATION',
            'time' => '1 Day Ago'
        ],
        [
            'source' => 'NCIB India',
            'title' => 'Ransomware Targeting Healthcare Sector',
            'desc' => 'Investigation into recent cyber-attacks on hospital servers in Delhi/NCR reveals state-sponsored threat actors.',
            'tag' => 'INCIDENT',
            'time' => '15 Hours Ago'
        ],
        [
            'source' => 'NCIIPC',
            'title' => 'Critical Infrastructure Protection Update',
            'desc' => 'NCIIPC releases new standard operating procedures for securing industrial control systems against malware like Volt Typhoon.',
            'tag' => 'STRATEGY',
            'time' => '2 Days Ago'
        ]
    ];

    
    
    

    
    if (!is_dir(dirname($cache_file))) mkdir(dirname($cache_file), 0777, true);
    file_put_contents($cache_file, json_encode($news_sources));

    return $news_sources;
}
?>
