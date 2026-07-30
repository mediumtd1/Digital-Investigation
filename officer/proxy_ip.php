<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

 
$portal = 'officer';

?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content">
        <div class="container-fluid" style="padding: 2rem;">
            <div class="glass-panel" style="min-height: 70vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                <div style="background: rgba(0, 243, 255, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fa-solid fa-microchip" style="font-size: 2.5rem; color: #00f3ff;"></i>
                </div>
                <h2 style="color: #fff; margin-bottom: 10px;">Native Module Integration</h2>
                <p style="color: var(--text-muted); max-width: 500px;">
                    The iframe has been removed. We are currently migrating this section to a native database-driven interface.
                </p>
                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button class="btn-primary" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); cursor: default;">
                        <i class="fa-solid fa-database"></i> Local DB Connected
                    </button>
                    <button class="btn-primary" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); cursor: default;">
                        <i class="fa-solid fa-shield-halved"></i> Secure Access
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>