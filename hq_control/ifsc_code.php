<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer');
$portal = $is_admin ? 'admin' : 'officer';

try {
    $stmt = $pdo->prepare("SELECT * FROM investigation_tools WHERE filename = 'ifsc_code.php'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO investigation_tools (tool_name, filename, icon, url, is_active) VALUES (?, ?, ?, ?, 1)");
        $insert->execute([
            'IFSC Code Search',
            'ifsc_code.php',
            'fa-building-columns',
            '../officer/ifscsearch/ifscsearch.html'
        ]);
    }
} catch (Exception $e) {
    
}

$page_title = 'IFSC Code Search | ' . SITE_NAME;
$external_url = '../officer/ifscsearch/ifscsearch.html';
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
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin: 15px;
        border: 2px solid var(--accent-blue);
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
    }

    .cyber-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: #fff;
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
        background: rgba(0, 0, 0, 0.85) !important;
        backdrop-filter: blur(15px);
        border-bottom: 1px solid var(--border-color);
    }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content"
        style="padding: 0; overflow: hidden; height: 100vh; display: flex; flex-direction: column;">
        <header class="topbar" style="padding: 12px 25px; flex-shrink: 0;">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png"
                    style="height: 40px; width: 40px; object-fit: contain; filter: drop-shadow(0 0 10px var(--accent-glow));">
                <div style="display:flex; flex-direction:column;">
                    <h2 style="margin: 0; font-size: 1.2rem; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">
                        IFSC Code Search</h2>
                    <?php if ($is_admin): ?><small
                            style="color: var(--accent-blue); font-size: 0.65rem; font-weight: bold;">SYSTEM ADMINISTRATOR
                            ACCESS</small><?php endif; ?>
                </div>
            </div>
            <div class="topbar-right" style="display:flex; gap:12px;">
                <?php if ($external_url): ?>
                    <a href="<?= esc($external_url) ?>" target="_blank" class="btn-primary"
                        style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%);">
                        <i class="fa-solid fa-expand"></i> POP-OUT
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="cyber-iframe-container">
            <?php if ($external_url): ?>
                <iframe src="<?= esc($external_url) ?>" class="cyber-iframe"
                    allow="geolocation; microphone; camera; encrypted-media;"
                    sandbox="allow-forms allow-modals allow-popups allow-same-origin allow-scripts allow-downloads">
                </iframe>
                <div class="iframe-overlay-hint">
                    <i class="fa-solid fa-shield-halved"></i> SECURE VIEW ACTIVE
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-link-slash"
                        style="font-size: 4rem; margin-bottom: 25px; display: block; color: #ef4444; filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.4));"></i>
                    <h3 style="font-family: 'Rajdhani', sans-serif; font-size: 1.5rem; color: #fff;">Tool Configuration
                        Required</h3>
                    <p>The external source URL has not been established for this investigative module.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
