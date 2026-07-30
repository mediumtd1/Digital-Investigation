<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer'); 
$portal = $is_admin ? 'admin' : 'officer';

$stmt = $pdo->prepare("SELECT * FROM investigation_tools WHERE filename = ?");
$stmt->execute([basename($_SERVER['PHP_SELF'])]);
$tool = $stmt->fetch();

if (!$tool) {
    die("Tool not found.");
}

$page_title = $tool['tool_name'] . ' | ' . SITE_NAME;
$external_url = $tool['url'];
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
        /* No solid background - let global grid show through */
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
        /* Original UI Restored */ filter: none;
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
                    <h2 style="margin: 0; font-size: 1.2rem; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;"><?= esc($tool['tool_name']) ?></h2>
                    <?php if ($is_admin): ?><small style="color: var(--accent-blue); font-size: 0.65rem; font-weight: bold;">SYSTEM ADMINISTRATOR ACCESS</small><?php endif; ?>
                </div>
            </div>
            <div class="topbar-right" style="display:flex; gap:12px;">
                <?php if ($is_admin): ?>
                <a href="manage_tools.php" class="btn-primary" style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%); background: rgba(0,243,255,0.1); border: 1px solid var(--accent-blue);">
                    <i class="fa-solid fa-sliders"></i> SETTINGS
                </a>
                <?php endif; ?>
                <?php if ($external_url): ?>
                <a href="<?= esc($external_url) ?>" target="_blank" class="btn-primary" style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%);">
                    <i class="fa-solid fa-expand"></i> POP-OUT
                </a>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="cyber-iframe-container" style="background: #020406;">
            <?php 
            
            $old_tool_url = 'https://digitalinvestigation.in/officer/cdr_include/cdranalysis.html'; 
            ?>
            <div id="iframeLoader" style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background: #020406; z-index:1;">
                <i class="fa-solid fa-microchip fa-spin" style="font-size: 3rem; color: var(--accent-blue); margin-bottom: 20px;"></i>
                <h3 style="font-family: 'Rajdhani', sans-serif; color: #fff;">LINKING TO ORIGINAL ENGINE...</h3>
            </div>
            <iframe src="<?= $old_tool_url ?>" 
                    class="cyber-iframe"
                    style="opacity: 0; transition: opacity 1s;"
                    onload="this.style.opacity='1'; document.getElementById('iframeLoader').style.display='none';"
                    allow="geolocation; microphone; camera; encrypted-media;"
                    sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts allow-downloads">
            </iframe>
            <div class="iframe-overlay-hint">
                <i class="fa-solid fa-shield-halved"></i> SECURE FORENSIC VIEW
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>