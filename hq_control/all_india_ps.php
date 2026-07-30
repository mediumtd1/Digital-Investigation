<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin'); 
$portal = 'admin';

$page_title = 'All India PS List | ' . SITE_NAME;
$external_url = 'https://digitalinvestigation.in/officer/police_station/search.php';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .dashboard-wrapper {
        background: transparent !important;
    }
    .main-content {
        background: transparent !important;
    }
    
    .cyber-iframe-container {
        flex-grow: 1;
        position: relative;
        background: rgba(0, 0, 0, 0.4); 
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin: 15px;
        border: 1px solid var(--border-color);
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }
    
    .cyber-iframe {
        width: 100%;
        height: 100%;
        border: none;
        filter: none;
        background: transparent;
    }

    .iframe-overlay-hint {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 243, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 8px 15px;
        border-radius: 4px;
        border: 1px solid var(--accent-blue);
        color: var(--accent-blue);
        font-size: 0.7rem;
        pointer-events: none;
        z-index: 10;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: bold;
    }
    
    .topbar {
        background: rgba(0,0,0,0.85) !important;
        backdrop-filter: blur(15px);
        border-bottom: 1px solid var(--border-color);
    }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content" style="padding: 0; overflow: hidden; height: 100vh; display: flex; flex-direction: column;">
        <header class="topbar" style="padding: 12px 25px; flex-shrink: 0;">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png" style="height: 40px; width: 40px; object-fit: contain; filter: drop-shadow(0 0 10px var(--accent-glow));">
                <div style="display:flex; flex-direction:column;">
                    <h2 style="margin: 0; font-size: 1.2rem; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">ALL INDIA POLICE STATIONS</h2>
                    <small style="color: var(--accent-blue); font-size: 0.65rem; font-weight: bold;">SYSTEM ADMINISTRATOR ACCESS</small>
                </div>
            </div>
        </header>
        
        <div class="cyber-iframe-container">
            <iframe src="<?= esc($external_url) ?>" 
                    class="cyber-iframe"
                    allow="geolocation; microphone; camera; encrypted-media;"
                    sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts allow-downloads">
            </iframe>
            <div class="iframe-overlay-hint">
                <i class="fa-solid fa-shield-halved"></i> SECURE VIEW ACTIVE
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>