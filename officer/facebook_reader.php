<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Officer');

$page_title = 'Facebook Report Reader | ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar" style="background: rgba(0,0,0,0.9); border-bottom: 2px solid #1877f2; padding: 15px 30px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <i class="fa-brands fa-facebook" style="color:#1877f2; font-size: 1.8rem;"></i>
                <div>
                    <h2 style="margin:0; color:#fff; font-family:'Rajdhani'; letter-spacing: 1px;">FACEBOOK REPORT READER</h2>
                    <small style="color: #1877f2; font-weight: bold; font-size: 0.7rem; letter-spacing: 1px;">// SECURE FORENSIC ANALYSIS</small>
                </div>
            </div>
        </header>

        <div class="cyber-tool-viewport" style="flex-grow:1; padding:20px; display:flex; flex-direction:column; background:#020406; height: calc(100vh - 80px);">
            <div class="iframe-wrapper" style="flex-grow:1; position:relative; background:#000; border:2px solid #1877f2; border-radius:8px; overflow:hidden; box-shadow: 0 0 30px rgba(24, 119, 242, 0.15);">
                <iframe src="https://digitalinvestigation.in/officer/FacebookandInsta_report_reading/facebook-instagram-report-reader.html" 
                        style="width:100%; height:100%; border:none; background:transparent;"
                        allow="geolocation; microphone; camera; encrypted-media;"
                        sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts allow-downloads">
                </iframe>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>