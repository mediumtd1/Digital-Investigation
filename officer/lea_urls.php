<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Important LEA URLs | ' . SITE_NAME;

$categories = [
    [
        'title' => 'Cybercrime Portals',
        'icon'  => 'fa-shield-halved',
        'color' => '#00f3ff',
        'links' => [
            ['name'=>'Cybercrime.gov.in', 'desc'=>'National Cybercrime Reporting Portal — Report cybercrime, CSAM, financial fraud & online abuse.', 'url'=>'https://cybercrime.gov.in', 'tag'=>'I4C / MHA'],
            ['name'=>'Cyber Dost', 'desc'=>'MHA initiative for cybercrime awareness. Follow on social media for daily advisories.', 'url'=>'https://www.mha.gov.in/en/cyberdost', 'tag'=>'MHA'],
            ['name'=>'NCRB CrVS', 'desc'=>'Crime and Criminal Tracking Network & Systems — National Crime Records Bureau.', 'url'=>'https://ncrb.gov.in', 'tag'=>'NCRB'],
            ['name'=>'CCTNS Portal', 'desc'=>'Crime and Criminal Tracking Network & Systems. State-level case management system.', 'url'=>'https://cctns.ncrb.gov.in', 'tag'=>'CCTNS'],
        ],
    ],
    [
        'title' => 'Telecom & SIM Verification',
        'icon'  => 'fa-mobile-screen-button',
        'color' => '#a855f7',
        'links' => [
            ['name'=>'Sanchar Saathi', 'desc'=>'DoT portal to trace SIM connections on an Aadhaar, report lost/stolen phones, block SIMs & check IMEI status.', 'url'=>'https://sancharsaathi.gov.in', 'tag'=>'DoT'],
            ['name'=>'TAFCOP Portal', 'desc'=>'Telecom Analytics for Fraud Management & Consumer Protection — Check active connections on your ID.', 'url'=>'https://tafcop.sancharsaathi.gov.in', 'tag'=>'TAFCOP'],
            ['name'=>'CEIR — IMEI Registry', 'desc'=>'Central Equipment Identity Register — Block/Unblock stolen mobile devices by IMEI number.', 'url'=>'https://ceir.gov.in', 'tag'=>'CEIR / DoT'],
            ['name'=>'Sanchar Mitra', 'desc'=>'Official channel for telecom grievances and network quality complaints.', 'url'=>'https://sancharmitra.gov.in', 'tag'=>'DoT'],
        ],
    ],
    [
        'title' => 'Financial Fraud & Banking',
        'icon'  => 'fa-landmark',
        'color' => '#10b981',
        'links' => [
            ['name'=>'Citizen Financial Cyber Fraud Helpline', 'desc'=>'Call 1930 — Immediate cyber fraud reporting. Portal to freeze fraud accounts and trace transactions.', 'url'=>'https://cybercrime.gov.in/fraud', 'tag'=>'1930 Helpline'],
            ['name'=>'RBI SACHET Portal', 'desc'=>'Reserve Bank of India portal to report unauthorised deposit schemes and financial frauds.', 'url'=>'https://sachet.rbi.org.in', 'tag'=>'RBI'],
            ['name'=>'NPCI — UPI Dispute', 'desc'=>'National Payments Corporation of India. UPI transaction dispute & chargeback mechanism.', 'url'=>'https://www.npci.org.in', 'tag'=>'NPCI'],
            ['name'=>'SEBI SCORES', 'desc'=>'SEBI Complaints Redress System — Securities market related fraud reporting.', 'url'=>'https://scores.sebi.gov.in', 'tag'=>'SEBI'],
        ],
    ],
    [
        'title' => 'OSINT & Investigation Tools',
        'icon'  => 'fa-magnifying-glass-chart',
        'color' => '#f59e0b',
        'links' => [
            ['name'=>'Whois Lookup', 'desc'=>'IANA / ICANN WHOIS lookup to identify domain registrant information for website investigation.', 'url'=>'https://lookup.icann.org', 'tag'=>'ICANN'],
            ['name'=>'Google Transparency Report', 'desc'=>'Legal process request data from Google. Also shows unsafe website flags.', 'url'=>'https://transparencyreport.google.com', 'tag'=>'Google'],
            ['name'=>'Have I Been Pwned', 'desc'=>'Check if an email address has been compromised in a known data breach.', 'url'=>'https://haveibeenpwned.com', 'tag'=>'HIBP'],
            ['name'=>'VirusTotal', 'desc'=>'Analyse suspicious files, domains, IPs and URLs to detect malware and other breaches.', 'url'=>'https://www.virustotal.com', 'tag'=>'VT'],
            ['name'=>'Shodan', 'desc'=>'Search engine for internet-connected devices. Useful for infrastructure investigation.', 'url'=>'https://www.shodan.io', 'tag'=>'OSINT'],
            ['name'=>'Archive.org / Wayback Machine', 'desc'=>'View cached/archived versions of deleted websites or social media profiles.', 'url'=>'https://web.archive.org', 'tag'=>'Archive'],
        ],
    ],
    [
        'title' => 'Legal References & Government',
        'icon'  => 'fa-scale-balanced',
        'color' => '#6366f1',
        'links' => [
            ['name'=>'India Code — IT Act 2000', 'desc'=>'Official text of the Information Technology Act, 2000 with all amendments.', 'url'=>'https://www.indiacode.nic.in/handle/123456789/1999', 'tag'=>'Govt'],
            ['name'=>'BNSS 2023 (New CrPC)', 'desc'=>'Bharatiya Nagarik Suraksha Sanhita — replacement for the Code of Criminal Procedure 1973.', 'url'=>'https://www.indiacode.nic.in', 'tag'=>'MHA'],
            ['name'=>'MHA Cyber Wing', 'desc'=>'Ministry of Home Affairs — Indian Cyber Crime Coordination Centre (I4C).', 'url'=>'https://www.mha.gov.in/en/divisionofmha/cyber-and-information-security-division', 'tag'=>'I4C'],
            ['name'=>'CERT-In', 'desc'=>'Indian Computer Emergency Response Team — Report incidents, advisories, and vulnerabilities.', 'url'=>'https://www.cert-in.org.in', 'tag'=>'CERT-In'],
        ],
    ],
];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">

<style>
.lurl-page { padding: 30px; }
.lurl-hero { margin-bottom:35px; }
.lurl-hero h2 { font-family:'Rajdhani',sans-serif; font-size:2.2rem; color:#fff; letter-spacing:2px; margin:0; }

/* Search */
.lurl-search-wrap { margin-bottom:30px; position:relative; }
.lurl-search {
    width:100%; padding:14px 20px 14px 50px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:12px;
    color:#fff;
    font-size:0.95rem;
    box-sizing:border-box;
    transition:0.3s;
}
.lurl-search:focus { outline:none; border-color:rgba(0,243,255,0.4); box-shadow:0 0 0 3px rgba(0,243,255,0.08); }
.lurl-search::placeholder { color:#475569; }
.lurl-search-icon { position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#475569; }

/* Category Section */
.lurl-section { margin-bottom:40px; }
.lurl-section-header {
    display:flex; align-items:center; gap:14px;
    margin-bottom:18px;
    padding-bottom:12px;
    border-bottom:1px solid rgba(255,255,255,0.05);
}
.lurl-section-icon {
    width:44px; height:44px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; flex-shrink:0;
}
.lurl-section-title { font-family:'Rajdhani',sans-serif; font-size:1.3rem; font-weight:700; color:#fff; }

/* Link Cards */
.lurl-cards { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:16px; }
.lurl-card {
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(255,255,255,0.05);
    border-radius:12px;
    padding:18px;
    transition:0.3s;
    text-decoration:none;
    display:block;
    position:relative;
    overflow:hidden;
}
.lurl-card:hover {
    transform:translateY(-4px);
    box-shadow:0 15px 40px rgba(0,0,0,0.3);
    border-color:rgba(255,255,255,0.12);
    text-decoration:none;
}
.lurl-card::after {
    content:'\f35d';
    font-family:'Font Awesome 6 Free';
    font-weight:900;
    position:absolute;
    top:18px; right:18px;
    font-size:0.8rem;
    color:#475569;
    transition:0.3s;
}
.lurl-card:hover::after { color:#fff; }

.lurl-card-name {
    font-weight:700; color:#fff;
    font-size:0.95rem;
    margin-bottom:5px;
    padding-right:25px;
}
.lurl-card-desc {
    font-size:0.8rem;
    color:#64748b;
    line-height:1.5;
    margin-bottom:12px;
}
.lurl-card-tag {
    display:inline-block;
    padding:2px 9px;
    border-radius:20px;
    font-size:0.65rem;
    font-weight:800;
    letter-spacing:1px;
    text-transform:uppercase;
}
.lurl-card-url {
    font-family:'Share Tech Mono',monospace;
    font-size:0.7rem;
    color:#475569;
    margin-top:6px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-link" style="color:#00f3ff;"></i> IMPORTANT LEA URLs
                </h2>
            </div>
            <div class="topbar-right">
                <span style="background:rgba(16,185,129,0.1); color:#10b981; padding:6px 15px; border-radius:50px; font-size:0.75rem; font-weight:bold; border:1px solid rgba(16,185,129,0.2);">
                    <i class="fa-solid fa-circle" style="font-size:0.5rem; vertical-align:middle; animation:pulse 2s infinite;"></i> LIVE DIRECTORY
                </span>
            </div>
        </header>

        <div class="dashboard-content lurl-page">
            <div class="lurl-hero">
                <h2><span style="color:#00f3ff;">//</span> Quick-Access Portal Directory</h2>
                <p style="color:#64748b;">Official portals, investigation tools, and legal references for Law Enforcement use.</p>
            </div>

            
            <div class="lurl-search-wrap">
                <i class="fa-solid fa-search lurl-search-icon"></i>
                <input type="text" class="lurl-search" id="urlSearch" placeholder="Search portals by name, agency, or keyword..." oninput="filterURLs(this.value)">
            </div>

            
            <?php foreach($categories as $cat): ?>
            <div class="lurl-section" data-cat="<?= esc($cat['title']) ?>">
                <div class="lurl-section-header">
                    <div class="lurl-section-icon" style="background:<?= $cat['color'] ?>15; color:<?= $cat['color'] ?>; border:1px solid <?= $cat['color'] ?>33;">
                        <i class="fa-solid <?= $cat['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="lurl-section-title"><?= esc($cat['title']) ?></div>
                        <div style="font-size:0.75rem; color:#475569;"><?= count($cat['links']) ?> portals</div>
                    </div>
                </div>
                <div class="lurl-cards">
                    <?php foreach($cat['links'] as $link): ?>
                    <a href="<?= esc($link['url']) ?>" target="_blank" rel="noopener" class="lurl-card"
                       data-name="<?= strtolower(esc($link['name'])) ?>" data-desc="<?= strtolower(esc($link['desc'])) ?>" data-tag="<?= strtolower(esc($link['tag'])) ?>">
                        <div class="lurl-card-name"><?= esc($link['name']) ?></div>
                        <div class="lurl-card-tag" style="background:<?= $cat['color'] ?>15; color:<?= $cat['color'] ?>; border:1px solid <?= $cat['color'] ?>33; margin-bottom:8px;"><?= esc($link['tag']) ?></div>
                        <div class="lurl-card-desc"><?= esc($link['desc']) ?></div>
                        <div class="lurl-card-url"><?= esc(parse_url($link['url'], PHP_URL_HOST) ?: $link['url']) ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </main>
</div>

<script>
function filterURLs(query) {
    query = query.toLowerCase().trim();
    document.querySelectorAll('.lurl-card').forEach(card => {
        const text = (card.dataset.name || '') + ' ' + (card.dataset.desc || '') + ' ' + (card.dataset.tag || '');
        card.style.display = (!query || text.includes(query)) ? 'block' : 'none';
    });
    // Hide empty sections
    document.querySelectorAll('.lurl-section').forEach(section => {
        const visible = [...section.querySelectorAll('.lurl-card')].some(c => c.style.display !== 'none');
        section.style.display = visible ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>