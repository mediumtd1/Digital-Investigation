<?php
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_GET['iframe']) || isset($_GET['embed'])) return; 
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
    <div class="sidebar-header" style="padding: 20px; border-bottom: 1px solid rgba(0, 243, 255, 0.1);">
        <a href="dashboard.php" class="sidebar-logo-link" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
            <img src="../assets/images/logo.png" alt="Logo" class="glowing-logo" style="width: 55px; height: 55px; object-fit: contain;">
            <div class="sidebar-title" style="font-size: 1.1rem; font-family: 'Rajdhani', sans-serif; font-weight: 800; color: #fff; letter-spacing: 1px;">OFFICER PORTAL</div>
        </a>
        <button class="mobile-close" onclick="toggleSidebar()">&times;</button>
    </div>
    
    <div class="sidebar-nav" style="padding-top: 10px;">
        <div class="sidebar-group">
            <a href="<?= BASE_URL ?>/officer/dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house-chimney" style="color: #38bdf8;"></i> <span class="nav-text"><?= __('dashboard') ?></span>
            </a>
            <a href="<?= BASE_URL ?>/officer/profile.php" class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-address-card" style="color: #fb7185;"></i> <span class="nav-text"><?= __('my_profile') ?></span>
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">
                <span class="label-text">RESOURCES</span> <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="sidebar-group-content">
                <a href="<?= BASE_URL ?>/officer/intermediaries.php" class="nav-item <?= $current_page == 'intermediaries.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-address-book" style="color: #34d399;"></i> <span class="nav-text">Intermediaries</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/letter_formats.php" class="nav-item <?= $current_page == 'letter_formats.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-contract" style="color: #60a5fa;"></i> <span class="nav-text">Letter Format</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/sops.php" class="nav-item <?= $current_page == 'sops.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-pdf" style="color: #f87171;"></i> <span class="nav-text">SOPs</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/lea_urls.php" class="nav-item <?= $current_page == 'lea_urls.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-link" style="color: #a78bfa;"></i> <span class="nav-text">LEA URLs</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/all_india_ps.php" class="nav-item <?= $current_page == 'all_india_ps.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-building-user" style="color: #fbbf24;"></i> <span class="nav-text">All India PS List</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/toll_plazas.php" class="nav-item <?= $current_page == 'toll_plazas.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-road" style="color: #818cf8;"></i> <span class="nav-text">All India Toll Plaza</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/training.php" class="nav-item <?= $current_page == 'training.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap" style="color: #4ade80;"></i> <span class="nav-text">Training Material</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/materials.php" class="nav-item <?= $current_page == 'materials.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open-reader" style="color: #00f3ff;"></i> <span class="nav-text">Study Materials</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/awareness.php" class="nav-item <?= $current_page == 'awareness.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-lightbulb" style="color: #facc15;"></i> <span class="nav-text">Awareness Material</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">
                <span class="label-text">INVESTIGATION LAB</span> <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="sidebar-group-content">
                <?php
                
                $tool_colors = ['#00d4ff','#f59e0b','#ec4899','#84cc16','#14b8a6','#e11d48','#06b6d4','#6366f1','#10b981','#f97316','#a855f7','#38bdf8'];
                $tool_color_i = 0;
                if (isset($pdo)) {
                    $stmt_sidebar = $pdo->query("SELECT * FROM investigation_tools WHERE is_active = 1 ORDER BY tool_name ASC");
                    while ($tool_item = $stmt_sidebar->fetch()) {
                        $isActive = ($current_page == $tool_item['filename']) ? 'active' : '';
                        $tc = $tool_colors[$tool_color_i % count($tool_colors)];
                        $tool_color_i++;
                        echo "<a href='" . BASE_URL . "/officer/{$tool_item['filename']}' class='nav-item {$isActive}'>";
                        echo "<i class='fa-solid {$tool_item['icon']}' style='color:{$tc};'></i> <span class='nav-text'>" . esc($tool_item['tool_name']) . "</span>";
                        echo "</a>";
                    }
                }
                ?>
                <a href="<?= BASE_URL ?>/officer/osint.php" class="nav-item <?= $current_page == 'osint.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-compass" style="color: #ef4444;"></i> <span class="nav-text">OSINT</span>
                </a>
            </div>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">
                <span class="label-text">SUPPORT & UPDATES</span> <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="sidebar-group-content">
                <a href="<?= BASE_URL ?>/officer/announcements.php" class="nav-item <?= $current_page == 'announcements.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullhorn" style="color: #f43f5e;"></i> <span class="nav-text">Official Circulars</span>
                </a>
                <a href="<?= BASE_URL ?>/officer/support.php" class="nav-item <?= $current_page == 'support.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-headset" style="color: #f97316;"></i> <span class="nav-text">Support Tickets</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="system-ip-card" style="margin: 10px 15px; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,243,255,0.1); border-radius: 8px; font-size: 0.75rem; text-align: center; color: #94a3b8; margin-bottom: 15px;">
            <i class="fa-solid fa-network-wired" style="color: #00f3ff; margin-right: 5px;"></i> System IP: <br>
            <span style="font-family: monospace; color: #fff;"><?= esc($_SERVER['REMOTE_ADDR']) ?></span>
        </div>
        <a href="<?= BASE_URL ?>/officer/dashboard.php?action=logout&csrf_token=<?= get_csrf_token() ?>" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> <span class="nav-text"><?= __('logout') ?></span>
        </a>
    </div>
</aside>

<style>
/* Responsive Sidebar System */
:root {
    --sidebar-width: 280px;
}

.sidebar { 
    width: var(--sidebar-width); 
    height: 100vh; 
    position: fixed; 
    left: 0; 
    top: 0; 
    background: #0b1120; 
    border-right: 1px solid rgba(0, 243, 255, 0.1); 
    z-index: 9999; 
    overflow-y: auto; 
    display: flex; 
    flex-direction: column; 
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), width 0.4s ease;
}

.main-content { 
    margin-left: var(--sidebar-width); 
    min-height: 100vh; 
    background: #020617; 
    width: calc(100% - var(--sidebar-width));
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
    background: #00f3ff;
    color: #000;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
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

/* Visibility Logic */
body.sidebar-collapsed .portal-branding-icon { display: block; }
@media (max-width: 992px) {
    .portal-branding-icon { display: block; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
    .main-content { margin-left: 0; width: 100%; }
    body.sidebar-open .portal-branding-icon { display: none; }
}

body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
body.sidebar-collapsed .main-content { margin-left: 0; width: 100%; }

.mobile-close { display: none; background: transparent; border: none; color: #ef4444; font-size: 2rem; cursor: pointer; }

/* FIX DOUBLE LOGO: Hide page-specific logos in favor of the universal one */
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

@media (max-width: 992px) {
    .topbar, header.tool-topbar, .portal-header, .main-header-portal { padding-left: 130px !important; }
}

.nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: #94a3b8; text-decoration: none; font-size: 0.95rem; transition: 0.3s; border-left: 3px solid transparent; }
.nav-item:hover, .nav-item.active { background: rgba(0, 243, 255, 0.05); color: #00f3ff; border-left-color: #00f3ff; font-weight: bold; }
.sidebar-group-label { padding: 15px 25px; font-size: 0.75rem; font-weight: 800; color: #475569; letter-spacing: 2px; display: flex; justify-content: space-between; cursor: pointer; text-transform: uppercase; }
.sidebar-group-content { overflow: hidden; transition: 0.4s ease-in-out; }
.logout-btn { display: flex; align-items: center; gap: 10px; padding: 18px 25px; color: #ef4444; text-decoration: none; font-weight: bold; border-top: 1px solid rgba(255,255,255,0.05); transition: 0.3s; margin-top: auto; }

/* Scrollbar Styling */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(0, 243, 255, 0.1); border-radius: 10px; }
</style>

<script>
function toggleSidebar() {
    if (window.innerWidth > 992) {
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
    if (window.innerWidth > 992 && localStorage.getItem('sidebar_collapsed') === 'true') {
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
