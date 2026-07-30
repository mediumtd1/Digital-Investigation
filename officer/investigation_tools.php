<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (strpos($_SERVER['PHP_SELF'], '/officer/') !== false) {
     $portal = 'officer';
} else {
    check_auth('Admin'); $portal = 'admin';
}

$filename = basename($_SERVER['PHP_SELF'], '.php');
$titles = [
    'directories' => 'Directories',
    'intermediaries' => 'Intermediaries',
    'letter_formats' => 'Letter Formats',
    'lea_urls' => 'LEA External Portals',
    'all_india_ps' => 'All India Nodal Directory',
    'proxy_ip' => 'Proxy & VPN Database',
    'mnc_mcc' => 'MNC MCC Lookup',
    'port_numbers' => 'Port Vulnerability Research',
    'training' => 'Training Academy',
    'awareness' => 'Awareness Resource Hub',
    'cdr_analysis' => 'CDR & IPDR Tracker',
    'investigation_tools' => 'Investigation Toolkit'
];

$page_title = ($titles[$filename] ?? 'Portal Section') . ' | ' . SITE_NAME;

$external_url = "https://www.bing.com"; 
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content" style="padding: 0; overflow: hidden; height: 100vh; display: flex; flex-direction: column;">
        <header class="topbar" style="padding: 10px 20px; flex-shrink: 0; background: rgba(0,0,0,0.5);">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 12px;">
                <img src="../assets/images/logo.png" style="height: 35px; width: 35px; object-fit: contain;">
                <h2 style="margin: 0; font-size: 1.2rem;"><?= $titles[$filename] ?? 'Section' ?></h2>
            </div>
            <div class="topbar-right">
                <a href="<?= esc($external_url) ?>" target="_blank" class="btn-primary" style="font-size: 0.75rem; padding: 5px 12px; background: rgba(255,255,255,0.05); color:#fff; border: 1px solid var(--border-color);">
                    <i class="fa-solid fa-expand"></i> Launch Task
                </a>
            </div>
        </header>
        <div style="flex-grow: 1; position: relative; background: #000;">
            <iframe src="<?= esc($external_url) ?>" 
                    style="width: 100%; height: 100%; border: none;"
                    allow="geolocation; microphone; camera; encrypted-media;"
                    sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts">
            </iframe>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
