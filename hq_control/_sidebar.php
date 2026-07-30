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
    <div class="sidebar-header" style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <a href="dashboard.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; width: 100%;">
            <img src="../assets/images/logo.png" alt="Logo" class="glowing-logo" style="width: 55px; height: 55px; object-fit: contain;">
            <div class="sidebar-title" style="font-size: 0.9rem; font-family: 'Rajdhani', sans-serif; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; background: linear-gradient(90deg, #00f3ff, #ff0055); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                HQ CONTROL PANEL
            </div>
        </a>
        <button class="mobile-close" onclick="toggleSidebar()">&times;</button>
    </div>

    <div class="sidebar-nav">
        
        <div class="sidebar-group">
            <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high" style="color: #38bdf8;"></i> Dashboard Overview
            </a>
            <a href="profile.php" class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-tie" style="color: #fb7185;"></i> My Admin Profile
            </a>
        </div>

        
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">OFFICER FORCE <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="officers.php" class="nav-item <?= $current_page == 'officers.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-shield" style="color: #60a5fa;"></i> Active Force
                </a>
                <a href="pending_officers.php" class="nav-item <?= $current_page == 'pending_officers.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-id-badge" style="color: #fbbf24;"></i> Pending Approvals
                </a>
                <a href="manage_officer_training.php" class="nav-item <?= $current_page == 'manage_officer_training.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chalkboard-user" style="color: #4ade80;"></i> Officer Training
                </a>
            </div>
        </div>

        
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">INTERN ACADEMY <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="interns.php" class="nav-item <?= $current_page == 'interns.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-viewfinder" style="color: #34d399;"></i> Intern Directory
                </a>
                <a href="manage_modules.php" class="nav-item <?= $current_page == 'manage_modules.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open" style="color: #818cf8;"></i> Daily Modules
                </a>
                <a href="manage_study_materials.php" class="nav-item <?= $current_page == 'manage_study_materials.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap" style="color: #a78bfa;"></i> Study Materials
                </a>
                <a href="module_access_override.php" class="nav-item <?= $current_page == 'module_access_override.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-key" style="color: #facc15;"></i> Module Overrides
                </a>
                <a href="internship_mgmt.php" class="nav-item <?= $current_page == 'internship_mgmt.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap" style="color: #a78bfa;"></i> Academy Programmes
                </a>
                <a href="performance_reports.php" class="nav-item <?= $current_page == 'performance_reports.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line" style="color: #f87171;"></i> Performance Reports
                </a>
                <a href="leaderboard.php" class="nav-item <?= $current_page == 'leaderboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-trophy" style="color: #fbbf24;"></i> Academy Leaderboard
                </a>
                <a href="manage_featured.php" class="nav-item <?= $current_page == 'manage_featured.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-star" style="color: var(--accent-highlight);"></i> Featured Interns
                </a>
                <a href="tasks.php" class="nav-item <?= $current_page == 'tasks.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check" style="color: #22d3ee;"></i> Intern Task Flow
                </a>
                <a href="attendance.php" class="nav-item <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check" style="color: #60a5fa;"></i> Attendance Management
                </a>
                <a href="pending_certificates.php" class="nav-item <?= $current_page == 'pending_certificates.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-award" style="color: #f59e0b;"></i> Pending Certificates
                </a>
                <a href="certificates.php" class="nav-item <?= $current_page == 'certificates.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-certificate" style="color: #10b981;"></i> Certificates Management
                </a>
                <a href="bulk_email.php" class="nav-item <?= $current_page == 'bulk_email.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelopes-bulk" style="color: #06b6d4;"></i> Mass Email (Interns)
                </a>
                <a href="ctf_management.php" class="nav-item <?= $current_page == 'ctf_management.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-flag" style="color: #f59e0b;"></i> CTF Management
                </a>
                <a href="daily_challenges.php" class="nav-item <?= $current_page == 'daily_challenges.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gamepad" style="color: #a855f7;"></i> Daily Challenges
                </a>
            </div>
        </div>

        
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">RESOURCES &amp; LAB <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="manage_tools.php" class="nav-item <?= $current_page == 'manage_tools.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-toolbox" style="color: #f97316;"></i> Manage Tools
                </a>
                <a href="directories.php" class="nav-item <?= $current_page == 'directories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-address-book" style="color: #34d399;"></i> LEA Directories
                </a>
                <a href="manage_directories.php" class="nav-item <?= $current_page == 'manage_directories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-pen-to-square" style="color: #22d3ee;"></i> Manage Directories
                </a>
                <a href="sops.php" class="nav-item <?= $current_page == 'sops.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-shield" style="color: #ef4444;"></i> View SOPs
                </a>
                <a href="manage_sops.php" class="nav-item <?= $current_page == 'manage_sops.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-circle-plus" style="color: #f87171;"></i> Manage SOPs
                </a>
                <a href="letter_formats.php" class="nav-item <?= $current_page == 'letter_formats.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-open-text" style="color: #a78bfa;"></i> Letter Formats
                </a>
                <a href="toll_plazas.php" class="nav-item <?= $current_page == 'toll_plazas.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-road" style="color: #818cf8;"></i> All India Toll Plaza
                </a>
                <a href="all_india_ps.php" class="nav-item <?= $current_page == 'all_india_ps.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-building-shield" style="color: #60a5fa;"></i> All India PS
                </a>
            </div>
        </div>

        
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">FORENSIC TOOLS <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="investigation_tools.php" class="nav-item <?= $current_page == 'investigation_tools.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-flask" style="color: #f97316;"></i> All Tools Hub
                </a>
                <a href="ip_lookup.php" class="nav-item <?= $current_page == 'ip_lookup.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-globe" style="color: #38bdf8;"></i> IP Lookup
                </a>
                <a href="mac_lookup.php" class="nav-item <?= $current_page == 'mac_lookup.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-network-wired" style="color: #34d399;"></i> MAC Lookup
                </a>
                <a href="imei_checker.php" class="nav-item <?= $current_page == 'imei_checker.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-mobile-screen" style="color: #a78bfa;"></i> IMEI Checker
                </a>
                <a href="imei_lookup.php" class="nav-item <?= $current_page == 'imei_lookup.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-magnifying-glass" style="color: #c084fc;"></i> IMEI Lookup
                </a>
                <a href="email_header_analysis.php" class="nav-item <?= $current_page == 'email_header_analysis.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-circle-check" style="color: #60a5fa;"></i> Email Header
                </a>
                <a href="exif_data.php" class="nav-item <?= $current_page == 'exif_data.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-image" style="color: #f472b6;"></i> EXIF Data
                </a>
                <a href="cdr_analysis.php" class="nav-item <?= $current_page == 'cdr_analysis.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-bar" style="color: #fb923c;"></i> CDR Analysis
                </a>
                <a href="hash_calculator.php" class="nav-item <?= $current_page == 'hash_calculator.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-hashtag" style="color: #facc15;"></i> Hash Calculator
                </a>
                <a href="ifsc_code.php" class="nav-item <?= $current_page == 'ifsc_code.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-building-columns" style="color: #10b981;"></i> IFSC Code
                </a>
                <a href="mnc_mcc.php" class="nav-item <?= $current_page == 'mnc_mcc.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-signal" style="color: #22d3ee;"></i> MNC/MCC Lookup
                </a>
                <a href="bitcoin_finder.php" class="nav-item <?= $current_page == 'bitcoin_finder.php' ? 'active' : '' ?>">
                    <i class="fa-brands fa-bitcoin" style="color: #f59e0b;"></i> Bitcoin Finder
                </a>
                <a href="whatsapp_reader.php" class="nav-item <?= $current_page == 'whatsapp_reader.php' ? 'active' : '' ?>">
                    <i class="fa-brands fa-whatsapp" style="color: #4ade80;"></i> WhatsApp Reader
                </a>
                <a href="facebook_reader.php" class="nav-item <?= $current_page == 'facebook_reader.php' ? 'active' : '' ?>">
                    <i class="fa-brands fa-facebook" style="color: #60a5fa;"></i> Facebook Reader
                </a>
                <a href="instagram_reader.php" class="nav-item <?= $current_page == 'instagram_reader.php' ? 'active' : '' ?>">
                    <i class="fa-brands fa-instagram" style="color: #f472b6;"></i> Instagram Reader
                </a>
                <a href="twitter_reader.php" class="nav-item <?= $current_page == 'twitter_reader.php' ? 'active' : '' ?>">
                    <i class="fa-brands fa-x-twitter" style="color: #94a3b8;"></i> X/Twitter Reader
                </a>
                <a href="voice_analysis.php" class="nav-item <?= $current_page == 'voice_analysis.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-microphone-lines" style="color: #e879f9;"></i> Voice Analysis
                </a>
                <a href="port_numbers.php" class="nav-item <?= $current_page == 'port_numbers.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-plug" style="color: #38bdf8;"></i> Port Numbers
                </a>
                <a href="proxy_ip.php" class="nav-item <?= $current_page == 'proxy_ip.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-mask" style="color: #f87171;"></i> Proxy IP Check
                </a>
                <a href="lea_urls.php" class="nav-item <?= $current_page == 'lea_urls.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-link" style="color: #a78bfa;"></i> LEA URLs
                </a>
                <a href="fir_overview.php" class="nav-item <?= $current_page == 'fir_overview.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-contract" style="color: #34d399;"></i> FIR Overview
                </a>
            </div>
        </div>

        
        <div class="sidebar-group">
            <div class="sidebar-group-label" onclick="toggleSidebarGroup(this)">CONTENT MGMT <i class="fa-solid fa-chevron-down"></i></div>
            <div class="sidebar-group-content">
                <a href="manage_news.php" class="nav-item <?= $current_page == 'manage_news.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-rss" style="color: #fb923c;"></i> Cyber News Feed
                </a>
                <a href="manage_articles.php" class="nav-item <?= $current_page == 'manage_articles.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-newspaper" style="color: #38bdf8;"></i> Knowledge Articles
                </a>
                <a href="manage_awareness.php" class="nav-item <?= $current_page == 'manage_awareness.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-eye" style="color: #facc15;"></i> Awareness Materials
                </a>
                <a href="announcements.php" class="nav-item <?= $current_page == 'announcements.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullhorn" style="color: #f43f5e;"></i> Official Circulars
                </a>
                <a href="batch_announcements.php" class="nav-item <?= $current_page == 'batch_announcements.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group" style="color: #a855f7;"></i> Batch Announcements
                </a>
                <a href="messages.php" class="nav-item <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-open-text" style="color: #ec4899;"></i> Portal Messages
                </a>
                <a href="support.php" class="nav-item <?= $current_page == 'support.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-headset" style="color: #f97316;"></i> Support Tickets
                </a>
            </div>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="system-ip-card" style="margin: 10px 15px; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,243,255,0.1); border-radius: 8px; font-size: 0.75rem; text-align: center; color: #94a3b8;">
            <i class="fa-solid fa-network-wired" style="color: #00f3ff;"></i> System IP: <br>
            <span style="font-family: monospace; color: #fff;"><?= esc($_SERVER['REMOTE_ADDR']) ?></span>
        </div>
        <a href="dashboard.php?action=logout&csrf_token=<?= get_csrf_token() ?>" class="logout-btn">
            <i class="fa-solid fa-power-off"></i> SECURE LOGOUT
        </a>
    </div>
</aside>

<style>
/* Responsive Admin Sidebar System */
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
    border-right: 1px solid rgba(0, 243, 255, 0.2); 
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

/* Universal Control Bar - COMPACT */
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
    background: linear-gradient(135deg, #00f3ff, #ff0055);
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

/* Visibility Logic */
body.sidebar-collapsed .portal-branding-icon { display: block; }
@media (max-width: 1024px) {
    .portal-branding-icon { display: block; }
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
    .main-content { margin-left: 0 !important; width: 100% !important; }
    .mobile-close { display: block; }
    body.sidebar-open .portal-branding-icon { display: none; }
}

/* Desktop Collapse State */
body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
body.sidebar-collapsed .main-content { margin-left: 0 !important; width: 100% !important; }

/* FIX OVERLAP: Universal Header Spacing */
.topbar, header.tool-topbar, .portal-header, .main-header-portal { 
    padding-left: 130px !important; 
    transition: 0.3s ease;
}

@media (max-width: 1024px) {
    .topbar, header.tool-topbar, .portal-header, .main-header-portal { padding-left: 130px !important; }
}

.nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 25px; color: #94a3b8; text-decoration: none; font-size: 0.85rem; transition: 0.3s; border-left: 3px solid transparent; }
.nav-item:hover, .nav-item.active { background: rgba(0, 243, 255, 0.05); color: #00f3ff; border-left-color: #00f3ff; font-weight: bold; }
.sidebar-group-label { padding: 15px 25px; font-size: 0.7rem; font-weight: 800; color: #475569; letter-spacing: 2px; display: flex; justify-content: space-between; cursor: pointer; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.02); }
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
