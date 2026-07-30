<?php
require_once __DIR__ . '/i18n.php';
$page_title = $page_title ?? SITE_NAME;

$_allowed_langs = ['en', 'hi'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $_allowed_langs, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Prevent browser back button cache (bfcache)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && (window.performance.navigation.type === 2 || (window.performance.getEntriesByType && window.performance.getEntriesByType("navigation")[0] && window.performance.getEntriesByType("navigation")[0].type === "back_forward")))) {
                window.location.reload();
            }
        });
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Rajdhani:wght@400;500;600;700&family=Share+Tech+Mono&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Rajdhani:wght@400;500;600;700&family=Share+Tech+Mono&display=swap"></noscript>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (!empty($need_docx)): ?>
    <script src="https://unpkg.com/jszip/dist/jszip.min.js" defer></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.js" defer></script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    
    <link rel="stylesheet" href="<?= (strpos($_SERVER['PHP_SELF'], '/officer/') !== false || strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false || strpos($_SERVER['PHP_SELF'], '/intern/') !== false) ? '../' : '' ?>assets/css/style.css?v=3.1">
    
    <?php 
    $prefix = (strpos($_SERVER['PHP_SELF'], '/officer/') !== false || strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false || strpos($_SERVER['PHP_SELF'], '/intern/') !== false) ? '../' : '';
    if(isset($portal) && file_exists(__DIR__ . '/../assets/css/' . $portal . '.css')): ?>
        <link rel="stylesheet" href="<?= $prefix ?>assets/css/<?= $portal ?>.css">
    <?php endif; ?>
    
    <script src="<?= $prefix ?>assets/js/hacker-fx.js" defer></script>
    <style>
        /* Anti-Copy CSS */
        body, .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        @media print {
            .sidebar, .sidebar-control-bar, .topbar, .tool-topbar, .no-print, #theme-toggle-btn, .scanning-line, .logout-btn, .mobile-toggle {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
            }
            body {
                background: #fff !important;
                color: #000 !important;
                -webkit-user-select: auto !important;
                -moz-user-select: auto !important;
                -ms-user-select: auto !important;
                user-select: auto !important;
            }
            .glass-panel, .glass-card, .info-box, .info-chip, .result-box {
                background: #fff !important;
                color: #000 !important;
                border: 1px solid #000 !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }
            .info-label, .result-label {
                color: #555 !important;
            }
            .info-value, .result-value {
                color: #000 !important;
            }
            #map, #osintMap {
                border: 1px solid #000 !important;
                height: 400px !important;
            }
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableInspector(event);">
    <script>
        // Anti-Copy & Anti-Inspection JavaScript
        function disableInspector(e) {
            const isModifier = e.ctrlKey || e.metaKey;
            const key = e.key ? e.key.toLowerCase() : String.fromCharCode(e.keyCode).toLowerCase();

            // Disable F12
            if (e.keyCode === 123 || e.key === 'F12') {
                e.preventDefault();
                return false;
            }

            if (isModifier) {
                // Block view source (U), save page (S), print (P), copy (C), cut (X), select all (A)
                if (['u', 's', 'p', 'c', 'x', 'a'].includes(key)) {
                    e.preventDefault();
                    return false;
                }
                // Block inspect element (I), developer tool console (J)
                if (key === 'i' || key === 'j') {
                    e.preventDefault();
                    return false;
                }
            }
        }
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            return false;
        });
    </script>
    <div class="scanning-line"></div>
    

    
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <?php if (isset($_GET['iframe']) || isset($_GET['embed'])): ?>
    <style>
        .topbar, .sidebar, .scanning-line { display: none !important; }
        .main-content { margin: 0 !important; width: 100% !important; padding: 0 !important; }
        body { background: #0b1120 !important; }
    </style>
    <?php endif; ?>

    <script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast     = document.createElement('div');
        const iconMap   = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-shield-halved', warn:'fa-triangle-exclamation' };
        const icon      = iconMap[type] || 'fa-circle-check';

        toast.className = `cyber-toast toast-${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${icon} toast-icon"></i>
            <span>${message}</span>
            <span class="toast-close" onclick="this.parentElement.remove()">&times;</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 350);
        }, 3500);
    }

    // Global Anti-Theft Lockdown
    document.addEventListener('DOMContentLoaded', () => {
        const sensitiveElements = document.querySelectorAll('.main-content, .glass-panel, .dataTable, .dir-card, article');
        sensitiveElements.forEach(el => el.classList.add('no-select'));
    });

    function toggleTheme() {
        const current = localStorage.getItem('theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateToggleBtn();
    }

    function updateToggleBtn() {
        const btn = document.getElementById('theme-toggle-btn');
        if (!btn) return;
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        btn.innerHTML = isLight 
            ? '<i class="fa-solid fa-moon" style="font-size:1.2rem;"></i>' 
            : '<i class="fa-solid fa-sun" style="font-size:1.2rem;"></i>';
        btn.style.background = isLight ? '#0f172a' : '#f8fafc';
        btn.style.color = isLight ? '#f8fafc' : '#0f172a';
        btn.title = isLight ? 'Switch to Dark Mode' : 'Switch to Light Mode';
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.createElement('div');
        btn.id = 'theme-toggle-btn';
        btn.style.cssText = 'position:fixed;top:20px;right:20px;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,0.3);border:2px solid rgba(148,163,184,0.4);transition:all 0.3s ease;';
        btn.onmouseenter = () => { btn.style.transform = 'scale(1.1)'; };
        btn.onmouseleave = () => { btn.style.transform = 'scale(1)'; };
        btn.onclick = toggleTheme;
        document.body.appendChild(btn);
        updateToggleBtn();

        // 3D Tilt Effect on cards with .tilt-card class
        document.querySelectorAll('.glass-panel, .programme-card, .sop-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect   = this.getBoundingClientRect();
                const x      = e.clientX - rect.left;
                const y      = e.clientY - rect.top;
                const cx     = rect.width  / 2;
                const cy     = rect.height / 2;
                const tiltX  = ((y - cy) / cy) * 6;  // max 6deg
                const tiltY  = ((cx - x) / cx) * 6;
                this.style.transform = `perspective(800px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateY(-4px)`;
                this.style.transition = 'transform 0.1s ease';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.transition = 'transform 0.4s ease';
            });
        });
    });
    </script>
