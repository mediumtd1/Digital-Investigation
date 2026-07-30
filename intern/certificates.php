<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Intern', 'Admin'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['user_role'] === 'Admin');
$intern_id = $_SESSION['user_id'];
$page_title = 'My Certificates | Cyber Academy';

$total_modules = 0;
try {
    $total_modules = (int)$pdo->query("SELECT COUNT(*) FROM internship_modules WHERE is_visible = 1")->fetchColumn();
} catch(Exception $e) {}

$completed_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM module_tracking WHERE intern_id = ?");
    $stmt->execute([$intern_id]);
    $completed_count = (int)$stmt->fetchColumn();
} catch(Exception $e) {}

$is_eligible = ($completed_count >= $total_modules && $total_modules > 0);

$cert = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE intern_id = ? ORDER BY CASE approval_status WHEN 'approved' THEN 1 WHEN 'pending' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END ASC, id DESC LIMIT 1");
    $stmt->execute([$intern_id]);
    $cert = $stmt->fetch();

    if (!$cert && $is_eligible) {
        $hash = bin2hex(random_bytes(32));
        $qr_token = bin2hex(random_bytes(32));
        $ins = $pdo->prepare("INSERT INTO certificates (intern_id, certificate_hash, qr_token, approval_status) VALUES (?, ?, ?, 'pending')");
        $ins->execute([$intern_id, $hash, $qr_token]);
        
        
        $stmt->execute([$intern_id]);
        $cert = $stmt->fetch();
    }
} catch(Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Futuristic Cyber UI System */
.cert-page {
    padding: 40px 20px;
    max-width: 1000px;
    margin: 0 auto;
    font-family: 'Rajdhani', sans-serif;
}
.cyber-title {
    font-size: 2.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 3px;
    text-align: center;
    color: #fff;
    text-shadow: 0 0 15px rgba(0, 243, 255, 0.5);
    margin-bottom: 5px;
}
.cyber-subtitle {
    color: #64748b;
    text-align: center;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.95rem;
    letter-spacing: 1px;
    margin-bottom: 40px;
}
.cert-card {
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(0, 243, 255, 0.15);
    border-radius: 24px;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 40px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(16px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.cert-card:hover {
    border-color: rgba(0, 243, 255, 0.4);
    box-shadow: 0 25px 60px rgba(0, 243, 255, 0.1);
}
.cert-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 243, 255, 0.05) 0%, transparent 60%);
    pointer-events: none;
}
.cert-icon-wrapper {
    position: relative;
    width: 140px;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    border: 2px solid rgba(0, 243, 255, 0.3);
    background: rgba(0, 243, 255, 0.05);
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.1);
}
.cert-icon {
    font-size: 4.5rem;
    background: linear-gradient(135deg, #00f3ff, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.cert-details {
    flex-grow: 1;
}
.cert-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 15px;
    font-family: 'Share Tech Mono', monospace;
    border: 1px solid;
}
.badge-verified {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.3);
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
}
.badge-pending {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border-color: rgba(245, 158, 11, 0.3);
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.1);
}
.badge-rejected {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.1);
}
.badge-locked {
    background: rgba(100, 116, 139, 0.1);
    color: #94a3b8;
    border-color: rgba(100, 116, 139, 0.3);
}

.cert-title {
    font-size: 2.2rem;
    color: #fff;
    margin: 0 0 10px 0;
    letter-spacing: 1px;
}
.cert-desc {
    color: #94a3b8;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0 0 25px 0;
}
.btn-cyber-primary {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #00f3ff, #a855f7);
    color: #020617;
    text-decoration: none;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    letter-spacing: 1px;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(0, 243, 255, 0.25);
}
.btn-cyber-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(0, 243, 255, 0.45);
    color: #fff;
}
.btn-cyber-secondary {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.03);
    color: #94a3b8;
    text-decoration: none;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    letter-spacing: 1px;
    transition: all 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.btn-cyber-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(0, 243, 255, 0.2);
    color: #fff;
}

/* Glass Overlay Panels */
.overlay-panel {
    position: absolute;
    inset: 0;
    background: rgba(2, 6, 23, 0.85);
    backdrop-filter: blur(12px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 24px;
    border: 1px dashed rgba(0, 243, 255, 0.25);
    text-align: center;
    padding: 20px;
}
.overlay-icon-container {
    position: relative;
    margin-bottom: 20px;
}
.overlay-icon-outer {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90px;
    height: 90px;
    border: 2px solid #00f3ff;
    border-radius: 50%;
    animation: cyberPulse 2s infinite;
}
.overlay-icon {
    font-size: 3.5rem;
    position: relative;
    z-index: 2;
}

@keyframes cyberPulse {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; box-shadow: 0 0 10px rgba(0, 243, 255, 0.2); }
    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.4; box-shadow: 0 0 30px rgba(0, 243, 255, 0.6); }
    100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.crazy-unlock {
    animation: neonExplosion 1.2s cubic-bezier(0.23, 1, 0.32, 1) forwards;
}
@keyframes neonExplosion {
    0% { transform: scale(0.95); box-shadow: 0 0 0px transparent; }
    50% { transform: scale(1.02); box-shadow: 0 0 50px rgba(0, 243, 255, 0.4); }
    100% { transform: scale(1); }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2 style="font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-award"></i> Achievement Portal</h2>
        </header>

        <div class="dashboard-content cert-page">
            
            <h1 class="cyber-title">Academic Credentials</h1>
            <p class="cyber-subtitle">STATUS REPORT & CERTIFICATE MANAGEMENT GATEWAY</p>

            <div class="cert-card" id="certCard">
                
                <?php 
                
                $state = 'locked';
                if ($cert) {
                    if ($cert['approval_status'] === 'pending') {
                        $state = 'pending';
                    } elseif ($cert['approval_status'] === 'rejected') {
                        $state = 'rejected';
                    } elseif ($cert['approval_status'] === 'approved') {
                        $state = ($cert['is_enabled'] == 1) ? 'approved' : 'disabled';
                    }
                }

                
                if ($state === 'locked'): 
                ?>
                    <div class="overlay-panel">
                        <div class="overlay-icon-container">
                            <div class="overlay-icon-outer"></div>
                            <i class="fa-solid fa-lock overlay-icon" style="color: #64748b;"></i>
                        </div>
                        <h3 style="color:#fff; margin:0 0 10px 0; font-size:1.6rem; letter-spacing:1px;">CERTIFICATE SYSTEM SECURED</h3>
                        <p style="color:#64748b; font-family:'Share Tech Mono', monospace; font-size: 0.85rem; margin-bottom:15px;">MODULE PROGRESS: <?= $completed_count ?> / <?= $total_modules ?></p>
                        
                        <div style="width: 250px; height: 8px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; margin: 0 auto; border: 1px solid rgba(0, 243, 255, 0.2);">
                            <div style="width: <?= ($total_modules > 0) ? ($completed_count/$total_modules)*100 : 0 ?>%; height: 100%; background: linear-gradient(90deg, #00f3ff, #a855f7); box-shadow: 0 0 10px #00f3ff;"></div>
                        </div>
                        <p style="color:#475569; font-size:0.8rem; margin-top:15px; font-family:'Share Tech Mono', monospace;">COMPLETE ALL TRAINING UNITS TO REQUEST HQ APPROVAL</p>
                    </div>

                <?php elseif ($state === 'pending'): ?>
                    <div class="overlay-panel" style="border-color: rgba(245, 158, 11, 0.4);">
                        <div class="overlay-icon-container">
                            <div class="overlay-icon-outer" style="border-color: #f59e0b; animation-duration: 2.5s;"></div>
                            <i class="fa-solid fa-hourglass-half overlay-icon" style="color: #f59e0b; animation: spin 4s linear infinite;"></i>
                        </div>
                        <h3 style="color:#fff; margin:0 0 10px 0; font-size:1.6rem; letter-spacing:1px; text-shadow: 0 0 10px rgba(245, 158, 11, 0.2);">AWAITING HQ CLEARANCE</h3>
                        <p style="color:#94a3b8; max-width: 400px; font-size: 0.9rem; margin-bottom:5px;">Your curriculum is fully completed! Your certificate request has been auto-submitted and is currently in the administration review queue.</p>
                        <p style="color:#f59e0b; font-family:'Share Tech Mono', monospace; font-size: 0.8rem;">STATUS CODE: PENDING_OFFICIAL_ENDORSEMENT</p>
                        <style>
                            @keyframes spin { 100% { transform: rotate(360deg); } }
                        </style>
                    </div>

                <?php elseif ($state === 'rejected'): ?>
                    <div class="overlay-panel" style="border-color: rgba(239, 68, 68, 0.4);">
                        <div class="overlay-icon-container">
                            <div class="overlay-icon-outer" style="border-color: #ef4444;"></div>
                            <i class="fa-solid fa-triangle-exclamation overlay-icon" style="color: #ef4444;"></i>
                        </div>
                        <h3 style="color:#fff; margin:0 0 10px 0; font-size:1.6rem; letter-spacing:1px;">ENDORSEMENT REJECTED</h3>
                        <p style="color:#ef4444; font-family:'Share Tech Mono', monospace; font-size: 0.9rem; margin-bottom:10px;">REASON: <?= esc($cert['rejection_reason'] ?: 'No explanation provided.') ?></p>
                        <p style="color:#94a3b8; max-width: 400px; font-size: 0.85rem;">Please review the feedback above or contact the internship coordinator for guidance.</p>
                    </div>

                <?php elseif ($state === 'disabled'): ?>
                    <div class="overlay-panel" style="border-color: rgba(100, 116, 139, 0.4);">
                        <div class="overlay-icon-container">
                            <div class="overlay-icon-outer" style="border-color: #94a3b8;"></div>
                            <i class="fa-solid fa-ban overlay-icon" style="color: #94a3b8;"></i>
                        </div>
                        <h3 style="color:#fff; margin:0 0 10px 0; font-size:1.6rem; letter-spacing:1px;">CREDENTIAL INACTIVE</h3>
                        <p style="color:#94a3b8; max-width: 400px; font-size: 0.9rem;">This credential has been temporarily suspended by the administrator. Please contact your coordinator.</p>
                    </div>

                <?php elseif ($state === 'approved'): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            if (!sessionStorage.getItem('cert_crazy_unlocked_<?= $intern_id ?>')) {
                                triggerCyberUnlock();
                                sessionStorage.setItem('cert_crazy_unlocked_<?= $intern_id ?>', 'true');
                            }
                        });
                        function triggerCyberUnlock() {
                            confetti({
                                particleCount: 200,
                                spread: 80,
                                origin: { y: 0.6 },
                                colors: ['#00f3ff', '#a855f7', '#ffffff']
                            });
                            let card = document.getElementById('certCard');
                            card.classList.add('crazy-unlock');
                        }
                    </script>
                <?php endif; ?>

                <div class="cert-icon-wrapper">
                    <i class="fa-solid fa-shield-halved cert-icon"></i>
                </div>
                <div class="cert-details">
                    <span class="cert-badge badge-verified">
                        <i class="fa-solid fa-certificate"></i> Verified Credential
                    </span>
                    <h2 class="cert-title">Cyber Forensic Investigator Endorsement</h2>
                    <p class="cert-desc">Awarded for the rigorous application, analysis, and validation of curriculum units covering OSINT, Network Analysis, Metadata Mining, and Advanced Forensics Operations.</p>
                    <div style="display:flex; gap:15px; flex-wrap: wrap;">
                        <a href="view_certificate.php" class="btn-cyber-primary" target="_blank">
                            <i class="fa-solid fa-circle-play"></i> LAUNCH CERTIFICATE
                        </a>
                        <a href="view_certificate.php?download=1" class="btn-cyber-secondary">
                            <i class="fa-solid fa-download"></i> EXPORT TO PDF
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
