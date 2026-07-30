<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'FIR / Cases | ' . SITE_NAME;
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>FIR / Cases</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <img src="../<?php echo esc($_SESSION['user_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-pic">
                    <div class="user-info">
                        <span class="user-name"><?php echo esc($_SESSION['user_name']) ?></span>
                        <span class="user-role">Admin • <?php echo esc($_SESSION['user_mapped_id'] ?? 'Admin') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="text-align: center; padding: 50px 20px;">
                <i class="fa-solid fa-person-digging" style="font-size: 4rem; color: var(--accent-highlight); margin-bottom: 20px;"></i>
                <h3>Under Construction</h3>
                <p style="color: var(--text-muted); margin-top: 10px;">The <strong>FIR / Cases</strong> page is currently being developed. Please check back later.</p>
                <a href="dashboard.php" class="btn-primary" style="margin-top: 20px; display: inline-block;"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
