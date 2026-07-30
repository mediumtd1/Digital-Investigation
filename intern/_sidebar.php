<?php
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_GET['iframe']) || isset($_GET['embed'])) return;

$_notif_unread = 0;
$_notif_list   = [];
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $__ns = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE intern_id = ? AND is_read = 0");
        $__ns->execute([$_SESSION['user_id']]);
        $_notif_unread = (int)$__ns->fetchColumn();

        $__nl = $pdo->prepare("SELECT * FROM notifications WHERE intern_id = ? ORDER BY created_at DESC LIMIT 15");
        $__nl->execute([$_SESSION['user_id']]);
        $_notif_list = $__nl->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>

<div class="sidebar-control-bar">
    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Navigation">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="portal-branding-icon">
        <img src="../assets/images/logo.png" alt="Logo" class="glowing-logo">
    </div>
</div>

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header" style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <a href="dashboard.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; width: 100%;">
            <img src="../assets/images/logo.png" alt="Logo" class="glowing-logo" style="width: 55px; height: 55px; object-fit: contain;">
            <div class="sidebar-title" style="font-size: 0.9rem; font-family: 'Rajdhani', sans-serif; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; background: linear-gradient(90deg, #00f3ff, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                ACADEMY PORTAL
            </div>
        </a>
        <button class="mobile-close" onclick="toggleSidebar()">&times;</button>
    </div>

    <div class="sidebar-nav">
        <div class="sidebar-group">
            <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house" style="color: #38bdf8;"></i> Dashboard
            </a>
            <a href="profile.php" class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-circle" style="color: #fb7185;"></i> Professional Profile
            </a>
        </div>
 
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">ACADEMY <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="modules.php" class="nav-item <?= $current_page == 'modules.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open" style="color: #34d399;"></i> Daily Modules
                </a>
                <a href="daily_challenge.php" class="nav-item <?= $current_page == 'daily_challenge.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gamepad" style="color: #a855f7;"></i> Daily Challenge
                </a>
                <a href="tasks.php" class="nav-item <?= $current_page == 'tasks.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check" style="color: #fbbf24;"></i> My Tasks
                </a>
                <a href="attendance.php" class="nav-item <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check" style="color: #60a5fa;"></i> Attendance Record
                </a>
                <a href="performance.php" class="nav-item <?= $current_page == 'performance.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line" style="color: #f87171;"></i> Performance Matrix
                </a>
            </div>
        </div>
 
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">RESOURCES <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="materials.php" class="nav-item <?= $current_page == 'materials.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-folder-open" style="color: #4ade80;"></i> Study Material
                </a>
                <a href="certificates.php" class="nav-item <?= $current_page == 'certificates.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-certificate" style="color: #22d3ee;"></i> My Certificates
                </a>
                <a href="announcements.php" class="nav-item <?= $current_page == 'announcements.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullhorn" style="color: #f43f5e;"></i> Official Updates
                </a>
            </div>
        </div>
 
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">INVESTIGATION LAB <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="osint.php" class="nav-item <?= $current_page == 'osint.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-compass" style="color: #00d4ff;"></i> OSINT Threat Search
                </a>
                <a href="bitcoin_finder.php" class="nav-item <?= $current_page == 'bitcoin_finder.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-coins" style="color: #f59e0b;"></i> Bitcoin Finder
                </a>
                <a href="email_header_analysis.php" class="nav-item <?= $current_page == 'email_header_analysis.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-open-text" style="color: #ec4899;"></i> Email Header Analysis
                </a>
                <a href="hash_calculator.php" class="nav-item <?= $current_page == 'hash_calculator.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calculator" style="color: #84cc16;"></i> Hash Value Calculator
                </a>
                <a href="exif_data.php" class="nav-item <?= $current_page == 'exif_data.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-images" style="color: #14b8a6;"></i> Image Exif Data
                </a>
                <a href="imei_checker.php" class="nav-item <?= $current_page == 'imei_checker.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-mobile-screen-button" style="color: #e11d48;"></i> IMEI Check Digit
                </a>
                <a href="ip_lookup.php" class="nav-item <?= $current_page == 'ip_lookup.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-globe" style="color: #06b6d4;"></i> IP Lookup
                </a>
                <a href="mac_lookup.php" class="nav-item <?= $current_page == 'mac_lookup.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-network-wired" style="color: #6366f1;"></i> MAC Lookup
                </a>
                <a href="voice_analysis.php" class="nav-item <?= $current_page == 'voice_analysis.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-microphone-lines" style="color: #10b981;"></i> Voice Analysis
                </a>
            </div>
        </div>
 
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">COMMUNITY <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="leaderboard.php" class="nav-item <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-trophy" style="color: #fbbf24;"></i> Elite Leaderboard
                </a>
                <a href="support.php" class="nav-item <?= $current_page == 'support.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-headset" style="color: #f97316;"></i> Support Desk
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="system-ip-card" style="margin: 10px 15px; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,243,255,0.1); border-radius: 8px; font-size: 0.75rem; text-align: center; color: #94a3b8; margin-bottom: 15px;">
            <i class="fa-solid fa-network-wired" style="color: #00f3ff; margin-right: 5px;"></i> System IP: <br>
            <span style="font-family: monospace; color: #fff;"><?= esc($_SERVER['REMOTE_ADDR']) ?></span>
        </div>
        <a href="dashboard.php?action=logout&csrf_token=<?= get_csrf_token() ?>" class="logout-btn" id="terminate-btn">
            <i class="fa-solid fa-power-off"></i>
            <span>TERMINATE SESSION</span>
        </a>
    </div>
</aside>

<style>
.notif-bell-fab {
    position: fixed;
    top: 18px;
    right: 22px;
    z-index: 10002;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(0,243,255,0.15), rgba(168,85,247,0.15));
    border: 1px solid rgba(0,243,255,0.25);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
.notif-bell-fab:hover {
    background: linear-gradient(135deg, rgba(0,243,255,0.25), rgba(168,85,247,0.25));
    box-shadow: 0 0 20px rgba(0,243,255,0.35);
    transform: scale(1.05);
}
.notif-bell-fab .fa-bell {
    font-size: 1.15rem;
    color: #00f3ff;
    transition: 0.3s;
}
.notif-bell-fab:hover .fa-bell { color: #fff; }
.notif-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 800;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    box-shadow: 0 0 8px rgba(239,68,68,0.8);
    animation: badge-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    font-family: 'Inter', sans-serif;
}
@keyframes badge-pop {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.notif-bell-ring {
    animation: bell-ring 1.2s ease-in-out infinite;
    transform-origin: top center;
}
@keyframes bell-ring {
    0%, 60%, 100% { transform: rotate(0deg); }
    10%, 30% { transform: rotate(-18deg); }
    20%, 40% { transform: rotate(18deg); }
    50% { transform: rotate(-10deg); }
}

/* Notification Dropdown */
#globalNotifDropdown {
    position: fixed;
    top: 72px;
    right: 22px;
    width: 360px;
    max-height: 500px;
    z-index: 10001;
    background: linear-gradient(145deg, rgba(15,23,42,0.98), rgba(2,6,23,0.98));
    border: 1px solid rgba(0,243,255,0.3);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,0.6), 0 0 30px rgba(0,243,255,0.08);
    backdrop-filter: blur(20px);
    display: none;
    animation: notif-slide-in 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes notif-slide-in {
    from { opacity: 0; transform: translateY(-12px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.notif-drop-header {
    padding: 14px 18px;
    background: rgba(0,243,255,0.07);
    border-bottom: 1px solid rgba(0,243,255,0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.notif-drop-header h4 {
    margin: 0;
    color: #fff;
    font-family: 'Rajdhani', sans-serif;
    font-size: 1rem;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.notif-drop-header .notif-close-btn {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: #94a3b8;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.72rem;
    cursor: pointer;
    letter-spacing: 1px;
    transition: 0.2s;
}
.notif-drop-header .notif-close-btn:hover { background: rgba(239,68,68,0.15); color: #f87171; }
.notif-drop-body { max-height: 390px; overflow-y: auto; }
.notif-drop-body::-webkit-scrollbar { width: 3px; }
.notif-drop-body::-webkit-scrollbar-thumb { background: rgba(0,243,255,0.2); border-radius: 3px; }
.notif-item {
    padding: 13px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.notif-item:hover { background: rgba(0,243,255,0.05); }
.notif-item.unread { border-left: 3px solid #00f3ff; background: rgba(0,243,255,0.04); }
.notif-item.unread:hover { background: rgba(0,243,255,0.08); }
.notif-icon-wrap {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.85rem;
}
.notif-icon-wrap.unread { background: rgba(0,243,255,0.15); color: #00f3ff; }
.notif-icon-wrap.read   { background: rgba(255,255,255,0.05); color: #475569; }
.notif-text { flex: 1; min-width: 0; }
.notif-text p { margin: 0 0 3px 0; font-size: 0.83rem; color: #cbd5e1; line-height: 1.4; }
.notif-item.unread .notif-text p { color: #e2e8f0; font-weight: 600; }
.notif-text span { font-size: 0.72rem; color: #475569; }
.notif-item.unread .notif-text span { color: #00f3ff; }
.notif-drop-footer {
    padding: 10px;
    background: rgba(255,255,255,0.02);
    text-align: center;
    font-size: 0.75rem;
    color: #475569;
    border-top: 1px solid rgba(255,255,255,0.04);
    letter-spacing: 1px;
}
.notif-empty {
    padding: 40px 20px;
    text-align: center;
    color: #475569;
    font-size: 0.85rem;
}
.notif-empty i { font-size: 2rem; display: block; margin-bottom: 10px; color: #1e293b; }
</style>

<div class="notif-bell-fab" id="globalNotifBell" onclick="toggleGlobalNotif()" title="Notifications" style="position:relative;">
    <i class="fa-solid fa-bell <?= ($_notif_unread > 0) ? 'notif-bell-ring' : '' ?>"></i>
    <?php if ($_notif_unread > 0): ?>
        <span class="notif-badge"><?= min($_notif_unread, 99) ?></span>
    <?php endif; ?>
</div>

<div id="globalNotifDropdown">
    <div class="notif-drop-header">
        <h4>
            <i class="fa-solid fa-bell" style="color:#00f3ff;"></i>
            NOTIFICATIONS
            <?php if ($_notif_unread > 0): ?>
                <span style="background:rgba(239,68,68,0.2); color:#f87171; font-size:0.7rem; padding:2px 8px; border-radius:10px; font-family:'Inter',sans-serif; font-weight:800; letter-spacing:0;"><?= $_notif_unread ?> new</span>
            <?php endif; ?>
        </h4>
        <span class="notif-close-btn" onclick="toggleGlobalNotif()">✕ CLOSE</span>
    </div>
    <div class="notif-drop-body">
        <?php if (empty($_notif_list)): ?>
            <div class="notif-empty">
                <i class="fa-regular fa-bell-slash"></i>
                No notifications yet
            </div>
        <?php else: ?>
            <?php foreach ($_notif_list as $_n): ?>
                <?php $_is_unread = !$_n['is_read']; ?>
                <div class="notif-item <?= $_is_unread ? 'unread' : '' ?>"
                     onclick="window.location.href='read_notification.php?id=<?= $_n['id'] ?>'">
                    <div class="notif-icon-wrap <?= $_is_unread ? 'unread' : 'read' ?>">
                        <i class="fa-solid fa-<?= $_is_unread ? 'check-circle' : 'check' ?>"></i>
                    </div>
                    <div class="notif-text">
                        <p><?= esc($_n['message']) ?></p>
                        <span>
                            <i class="fa-regular fa-clock"></i>
                            <?= date('d M, h:i A', strtotime($_n['created_at'])) ?>
                            <?php if ($_is_unread): ?>
                                &nbsp;·&nbsp;<strong style="color:#00f3ff;">Unread</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="notif-drop-footer">SYSTEM NOTIFICATIONS — REVIEW &amp; SUBMISSIONS</div>
</div>

<script>
function toggleGlobalNotif() {
    const panel = document.getElementById('globalNotifDropdown');
    const isOpen = panel.style.display === 'block';
    panel.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        // Re-trigger animation
        panel.style.animation = 'none';
        panel.offsetHeight; // force reflow
        panel.style.animation = 'notif-slide-in 0.25s cubic-bezier(0.34, 1.56, 0.64, 1)';
    }
}
document.addEventListener('click', function(e) {
    const bell   = document.getElementById('globalNotifBell');
    const panel  = document.getElementById('globalNotifDropdown');
    if (panel && bell && !bell.contains(e.target) && !panel.contains(e.target)) {
        panel.style.display = 'none';
    }
}, true);

// Reposition bell next to user profile if topbar exists
document.addEventListener("DOMContentLoaded", function() {
    let topbarRight = document.querySelector('.topbar-right');
    const topbar = document.querySelector('.topbar');
    const bellFab = document.getElementById('globalNotifBell');
    
    if (bellFab) {
        if (!topbarRight && topbar) {
            // Create a topbar-right element dynamically to host the bell
            topbarRight = document.createElement('div');
            topbarRight.className = 'topbar-right';
            topbarRight.style.display = 'flex';
            topbarRight.style.alignItems = 'center';
            topbarRight.style.marginLeft = 'auto';
            topbar.appendChild(topbarRight);
        }
        
        if (topbarRight) {
            bellFab.style.position = 'relative';
            bellFab.style.top = 'auto';
            bellFab.style.right = 'auto';
            bellFab.style.margin = '0';
            bellFab.style.zIndex = 'auto';
            topbarRight.insertBefore(bellFab, topbarRight.firstChild);
        }
    }
});
</script>

<style>
/* Responsive Sidebar System */
:root {
    --sidebar-width: 270px;
}

.sidebar { 
    width: var(--sidebar-width); 
    height: 100vh; 
    position: fixed; 
    left: 0; 
    top: 0; 
    background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
    border-right: 1px solid rgba(0, 243, 255, 0.15); 
    z-index: 9999; 
    overflow-y: auto; 
    display: flex; 
    flex-direction: column; 
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), width 0.4s ease;
}

.main-content { 
    margin-left: var(--sidebar-width) !important; 
    min-height: 100vh; 
    background: #020617; 
    width: calc(100% - var(--sidebar-width)) !important;
    transition: margin-left 0.4s ease, width 0.4s ease;
}

/* Universal Control Bar */
.sidebar-control-bar {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 10001;
    display: flex;
    align-items: center;
    gap: 15px;
    pointer-events: none;
}

.sidebar-toggle {
    pointer-events: auto;
    background: linear-gradient(135deg, #00f3ff, #a855f7);
    color: #fff;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
}

.portal-branding-icon {
    display: none;
}

.glowing-logo {
    height: 50px;
    width: auto;
    filter: drop-shadow(0 0 10px rgba(0, 243, 255, 0.8));
    animation: pulse-glow 2s infinite alternate;
}

@keyframes pulse-glow {
    from { filter: drop-shadow(0 0 5px rgba(0, 243, 255, 0.4)); transform: scale(1); }
    to { filter: drop-shadow(0 0 15px rgba(0, 243, 255, 1)); transform: scale(1.05); }
}

/* Visibility States */
body.sidebar-collapsed .portal-branding-icon { display: block; }
@media (max-width: 1024px) {
    .portal-branding-icon { display: block; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
    .main-content { margin-left: 0 !important; width: 100% !important; }
    .mobile-close { display: block; }
    body.sidebar-open .portal-branding-icon { display: none; }
    body.sidebar-open { overflow: hidden; }
}

/* Desktop Collapse State */
body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
body.sidebar-collapsed .main-content { margin-left: 0 !important; width: 100% !important; }

/* FIX DOUBLE LOGO: Hide page-specific logos */
.topbar img:not(.glowing-logo), 
header img:not(.glowing-logo), 
.portal-header img, 
.tool-topbar img { 
    display: none !important; 
}

/* FIX OVERLAP */
.topbar, header.tool-topbar, .portal-header, .main-header-portal { 
    padding-left: 130px !important; 
    transition: 0.3s ease;
}

@media (max-width: 1024px) {
    .topbar, header.tool-topbar, .portal-header, .main-header-portal { padding-left: 130px !important; }
}

.nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 25px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; transition: 0.3s; border-left: 3px solid transparent; position: relative; }
.nav-item:hover, .nav-item.active { background: rgba(0, 243, 255, 0.05); color: #00f3ff; border-left-color: #00f3ff; font-weight: bold; }
.sidebar-group-label { padding: 15px 25px; font-size: 0.75rem; font-weight: 800; color: #475569; letter-spacing: 2px; display: flex; justify-content: space-between; cursor: pointer; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.02); }
.sidebar-group-content { overflow: hidden; transition: 0.4s ease-in-out; }

/* Scrollbar */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(0, 243, 255, 0.1); border-radius: 10px; }
</style>

<script>
function toggleSidebar() {
    if (window.innerWidth > 1024) {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', document.body.classList.contains('sidebar-collapsed'));
    } else {
        const sidebar = document.getElementById('mainSidebar');
        sidebar.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }
}
function toggleSidebarGroup(el) {
    const content = el.nextElementSibling;
    if (content.style.maxHeight && content.style.maxHeight !== "0px") { 
        content.style.maxHeight = "0px"; 
        el.querySelector('i').style.transform = 'rotate(0deg)';
    } else { 
        content.style.maxHeight = content.scrollHeight + "px"; 
        el.querySelector('i').style.transform = 'rotate(180deg)';
    }
}
document.addEventListener("DOMContentLoaded", function() {
    if (window.innerWidth > 1024 && localStorage.getItem('sidebar_collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }
    document.querySelectorAll('.nav-item.active').forEach(activeItem => {
        const group = activeItem.closest('.sidebar-group-content');
        if(group) {
            group.style.maxHeight = group.scrollHeight + "px";
            const label = group.previousElementSibling;
            if(label) label.querySelector('i').style.transform = 'rotate(180deg)';
        }
    });
});
</script>