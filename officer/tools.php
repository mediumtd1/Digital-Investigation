<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}

$portal = 'officer';
$page_title = __('tools') . ' | ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><?= __('tools') ?></h2>
            </div>
        </header>

        <div class="dashboard-content" style="display: flex; align-items: center; justify-content: center; min-height: 70vh;">
            <div class="glass-panel" style="text-align: center; max-width: 600px; padding: 50px;">
                <i class="fa-solid fa-person-digging" style="font-size: 5rem; color: var(--accent-highlight); margin-bottom: 25px;"></i>
                <h1 style="color: var(--text-primary); margin-bottom: 15px;"><?= __('under_construction') ?></h1>
                <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.6;">
                    We are currently integrating specialized investigative databases and forensic tools into this module. 
                    Targeted database expansion and API connectivity are in progress.
                </p>
                <div style="margin-top: 30px; border: 1px dashed var(--border-color); padding: 15px; border-radius: 8px;">
                    <p style="font-family: monospace; color: var(--accent-blue);">STATUS: SYNCING DATABASE_STRUCTURE.SQL ... 45%</p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
