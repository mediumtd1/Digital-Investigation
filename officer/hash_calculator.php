<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$stmt = $pdo->prepare("SELECT * FROM investigation_tools WHERE filename = ?");
$stmt->execute([basename($_SERVER['PHP_SELF'])]);
$tool = $stmt->fetch();

if (!$tool) { die("Tool not found."); }

$page_title = $tool['tool_name'] . ' | ' . SITE_NAME;
$external_url = $tool['url'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .main-content {
        background: var(--bg-primary) !important;
        height: 100vh;
        overflow: hidden;
    }
    .cyber-tool-viewport {
        flex-grow: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
        background: #020406;
    }
    .iframe-wrapper {
        flex-grow: 1;
        position: relative;
        background: #000;
        border: 2px solid var(--accent-blue);
        box-shadow: 0 0 50px rgba(0, 242, 255, 0.15);
        border-radius: 4px;
        overflow: hidden;
    }
    .cyber-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: #fff; /* Tool default */
        transition: filter 0.3s ease;
    }
    
    /* INVERSION FILTER REMOVED - FIXES ORANGE UI ISSUE */
    .cyber-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: transparent;
    }

    .scanline-effect {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 242, 255, 0.05) 50%);
        background-size: 100% 4px;
        pointer-events: none;
        z-index: 11;
        opacity: 0.5;
    }
    
    .corner { position: absolute; width: 30px; height: 30px; border: 3px solid var(--accent-blue); z-index: 12; pointer-events: none; }
    .c-tl { top: 0; left: 0; border-right: none; border-bottom: none; }
    .c-br { bottom: 0; right: 0; border-left: none; border-top: none; }
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar" style="background: rgba(0,0,0,0.9); border-bottom: 2px solid var(--accent-blue);">
            <div class="topbar-left">
                <h2 style="font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 2px; color: var(--accent-blue);"><?= esc($tool['tool_name']) ?></h2>
                <small style="color: #fff; opacity: 0.7; font-weight: bold; letter-spacing: 1px;">// THEME-AWARE FORENSIC INTERFACE</small>
            </div>
            <div class="topbar-right">
                <a href="<?= esc($external_url) ?>" target="_blank" class="btn-primary">
                    <i class="fa-solid fa-expand"></i> POP-OUT
                </a>
            </div>
        </header>

        <div class="cyber-tool-viewport">
            <div class="iframe-wrapper">
                <div class="corner c-tl"></div>
                <div class="corner c-br"></div>
                <div class="inverter-overlay"></div>
                <div class="scanline-effect"></div>
                <?php if ($external_url): ?>
                    <iframe src="<?= esc($external_url) ?>" 
                            class="cyber-iframe"
                            allow="geolocation; microphone; camera; encrypted-media;"
                            sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts allow-downloads">
                    </iframe>
                <?php else: ?>
                    <div style="display:flex; align-items:center; justify-content:center; height:100%; color:var(--accent-blue);">
                        <h3 style="font-family:'Rajdhani', sans-serif;">SOURCE LINK NOT CONFIGURED</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>