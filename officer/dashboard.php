<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Officer');

$portal = 'officer';
$page_title = 'Officer Dashboard | ' . SITE_NAME;

$officer_id = $_SESSION['user_id'];
$officer = ['name' => 'Officer', 'officer_id' => 'N/A', 'rank' => 'Member', 'designation' => 'Personnel', 'district' => 'Digital', 'badge_number' => '000'];

try {
    $stmt = $pdo->prepare("SELECT * FROM officers WHERE id = ?");
    $stmt->execute([$officer_id]);
    $db_officer = $stmt->fetch();
    if ($db_officer) $officer = $db_officer;
} catch (\Exception $e) {}

$cases_total = 0;
$cases_solved = 0;
$firs_filed_month = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM firs WHERE assigned_officer_id = ?");
    $stmt->execute([$officer_id]);
    $cases_total = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM firs WHERE assigned_officer_id = ? AND status = 'Closed'");
    $stmt->execute([$officer_id]);
    $cases_solved = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM firs WHERE filed_by = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $stmt->execute([$officer_id]);
    $firs_filed_month = $stmt->fetch()['total'] ?? 0;
} catch (\Exception $e) {}

$cases_pending = $cases_total - $cases_solved;

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (!validate_csrf($_GET['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Security Error: Invalid CSRF token.");
    }
    if (isset($_SESSION['user_mapped_id'])) {
        log_audit($pdo, $_SESSION['user_mapped_id'], 'Officer', 'Logged Out');
    }
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    header("Location: login.php");
    exit;
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        
        <header class="topbar">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png" style="height: 45px; width: 45px; object-fit: contain;">
                <h2 style="margin: 0;">Officer Console</h2>
            </div>
            <div class="topbar-right">
                <div class="notification-bell" onclick="toggleNotifications()" style="position: relative; cursor: pointer; padding: 10px; border-radius: 50%; background: rgba(255,255,255,0.05); transition: 0.3s;">
                    <i class="fa-solid fa-bell" style="color: #00f3ff;"></i>
                    <div style="position: absolute; top: 5px; right: 5px; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px #ef4444;"></div>
                </div>
                <div class="user-profile">
                    <img src="../<?= esc($officer['profile_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-pic">
                    <div class="user-info">
                        <span class="user-name"><?= esc($officer['name']) ?> (<?= esc($officer['badge_number']) ?>)</span>
                        <span class="user-role" style="color: #60a5fa;"><?= esc($officer['rank']) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            <div class="glass-panel profile-summary-card" style="border-left: 4px solid #3b82f6;">
                <img src="../<?= esc($officer['profile_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-summary-pic" style="border-radius: 4px; border: 2px solid #64748b;">
                <div class="profile-details">
                    <h3><?= esc($officer['name']) ?> <span class="badge badge-success"><i class="fa-solid fa-signal"></i> On Duty</span></h3>
                    <div style="font-family: monospace; color: #94a3b8; margin-top: 5px; font-size: 1.1rem; letter-spacing: 1px;">
                        <i class="fa-solid fa-fingerprint"></i> <?= esc($officer['officer_id']) ?>
                    </div>
                    <p style="color: var(--text-muted); margin-top: 10px; font-size: 0.9rem;">
                        <strong>Rank:</strong> <?= esc($officer['rank']) ?> | <strong>Designation:</strong> <?= esc($officer['designation']) ?><br>
                        <strong>Posting:</strong> <?= esc($officer['district']) ?> District<br>
                        <strong>Active System IP:</strong> <span id="dash-ip-address" style="font-family: monospace; color: #00f3ff;">Analysing...</span>
                        <script>
                            fetch('https://api.ipify.org?format=json')
                                .then(r => r.json())
                                .then(d => { document.getElementById('dash-ip-address').innerText = d.ip; });
                        </script>
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
                
                <div class="glass-panel" style="padding: 0; overflow: hidden; position: relative; border-top: 2px solid #00f3ff;">
                    <div style="background: rgba(0, 243, 255, 0.05); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 20px;">
                        <h3 style="margin: 0; color: var(--text-primary); font-size: 1rem;"><i class="fa-solid fa-bullhorn" style="color: #00f3ff; margin-right: 8px;"></i> Official Announcement</h3>
                    </div>
                    <div style="padding: 15px;">
                        <?php
                        try {
                            $ad_stmt = $pdo->query("SELECT * FROM advertisements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
                            $ad = $ad_stmt->fetch();
                        } catch (\Exception $e) { $ad = false; }
                        
                        if ($ad): 
                            $width = (int)($ad['display_width'] ?? 400);
                            $isLarge = ($width > 300);
                        ?>
                            <div style="display: flex; gap: 25px; align-items: <?= $isLarge ? 'flex-start' : 'center' ?>; flex-direction: <?= $isLarge ? 'column' : 'row' ?>;">
                                <div style="width: <?= $width ?>px; max-width: 100%; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); background: #0f172a; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                                    <img src="../<?= esc($ad['image_url']) ?>" alt="Feature" style="width: 100%; height: auto; display: block; object-fit: contain;">
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="color: #00f3ff; margin: 0 0 10px 0; font-size: 1.3rem; font-family: 'Rajdhani', sans-serif;"><?= esc($ad['title']) ?></h3>
                                    <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 15px; line-height: 1.6;">
                                        <?= esc($ad['description'] ?? 'Exclusive system intelligence update for authorized personnel.') ?>
                                    </p>
                                    <a href="<?= esc($ad['link_url'] ?: '#') ?>" class="btn-primary" style="padding: 8px 20px; font-size: 0.9rem; border-radius: 4px; display: inline-block; background: var(--accent-blue);">
                                        Access Intel <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 15px; color: var(--text-muted); opacity: 0.7;">
                                <i class="fa-solid fa-rectangle-ad" style="font-size: 2rem;"></i>
                                <p style="margin: 0; font-size: 0.9rem;">HQ News Space: Stay tuned for upcoming system updates.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                
                <div class="glass-panel">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; color: #e2e8f0;">Session Logs</h3>
                    <div class="activity-feed">
                        <?php
                        $logs = [];
                        try {
                            $stmt = $pdo->prepare("SELECT action, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->execute([$_SESSION['user_mapped_id']]);
                            $logs = $stmt->fetchAll();
                        } catch (\Exception $e) {}

                        if (count($logs) > 0) {
                            foreach($logs as $log) {
                                $icon = (strpos($log['action'], 'Out') !== false) ? 'fa-right-from-bracket' : 'fa-right-to-bracket';
                                $color = (strpos($log['action'], 'Out') !== false) ? 'var(--danger)' : 'var(--success)';
                                echo '<div class="activity-item" style="margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start;">';
                                echo '<div class="activity-icon" style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 50%; color: '.$color.';"><i class="fa-solid '.$icon.'"></i></div>';
                                echo '<div class="activity-content">';
                                echo '<p style="margin: 0; font-size: 0.95rem; color: #e2e8f0;"><strong>' . esc($log['action']) . '</strong></p>';
                                echo '<span class="activity-time" style="color: var(--text-muted); font-size: 0.8rem;">' . date('d M, Y h:i A', strtotime($log['created_at'])) . '</span>';
                                echo '</div></div>';
                            }
                        } else {
                            echo '<p style="color: var(--text-muted);">No recent logs found.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
</div>
        </div>
    </main>
</div>

<div id="notificationDropdown" class="glass-panel" style="display: none; position: fixed; top: 80px; right: 20px; width: 350px; z-index: 1000; padding: 0; overflow: hidden; border: 1px solid #00f3ff; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
    <div style="background: rgba(0,243,255,0.1); padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
        <h4 style="margin: 0; color: #fff; font-family: 'Rajdhani', sans-serif;"><i class="fa-solid fa-bullhorn"></i> OFFICIAL ALERTS</h4>
        <span style="font-size: 0.7rem; color: #00f3ff; cursor: pointer;" onclick="toggleNotifications()">CLOSE</span>
    </div>
    <div style="max-height: 400px; overflow-y: auto; padding: 10px;">
        <?php
        try {
            $updates = $pdo->query("SELECT * FROM announcements WHERE target_role IN ('All', 'Officers_Only') ORDER BY created_at DESC LIMIT 5")->fetchAll();
            if (empty($updates)): ?>
                <p style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No new alerts.</p>
            <?php else: ?>
                <?php foreach($updates as $upd): ?>
                    <div style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.03); transition: 0.3s; cursor: pointer;" onclick="window.location.href='announcements.php'" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <h5 style="margin: 0 0 3px 0; color: #00f3ff; font-size: 0.85rem;"><i class="fa-solid fa-circle-info"></i> <?= esc($upd['title']) ?></h5>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);"><?= date('d M, Y', strtotime($upd['created_at'])) ?> — Click to review</p>
                    </div>
                <?php endforeach; ?>
            <?php endif;
        } catch(Exception $e) {}
        ?>
    </div>
    <a href="announcements.php" style="display: block; text-align: center; padding: 10px; background: rgba(255,255,255,0.03); color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">VIEW ALL ANNOUNCEMENTS</a>
</div>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = (dropdown.style.display === 'none') ? 'block' : 'none';
}

window.onclick = function(event) {
    if (!event.target.closest('.notification-bell') && !event.target.closest('#notificationDropdown')) {
        document.getElementById('notificationDropdown').style.display = 'none';
    }
}
</script><?php require_once __DIR__ . '/../includes/footer.php'; ?>
