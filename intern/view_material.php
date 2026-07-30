<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Intern', 'Admin'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['user_role'] === 'Admin');

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT file_path, title, description, is_visible FROM study_materials WHERE id = ?");
$stmt->execute([$id]);
$material = $stmt->fetch();

if (!$material) {
    die("Material not found.");
}

if (!$is_admin && !$material['is_visible']) {
    die("This study material is currently hidden by HQ.");
}

if (empty($material['file_path'])) {
    die("No file has been uploaded for this study material yet.");
}

$filepath = __DIR__ . '/../' . $material['file_path'];

if (!file_exists($filepath)) {
    die("File no longer exists on the server.");
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

if (in_array($ext, ['html', 'htm'])) {
    $content = file_get_contents($filepath);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Secure Material Viewer | <?= esc($material['title']) ?></title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                padding: 0;
                background: #0f172a;
                color: #e2e8f0;
                font-family: 'Inter', sans-serif;
                user-select: none;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
            }
            @media print {
                html, body { display: none !important; opacity: 0 !important; visibility: hidden !important; }
                * { display: none !important; }
            }

            /* ── Reading Progress Bar ── */
            #reading-progress-bar {
                position: fixed;
                top: 0; left: 0;
                width: 0%;
                height: 4px;
                background: linear-gradient(90deg, #0891b2, #00f3ff, #a855f7);
                z-index: 99999;
                transition: width 0.1s linear;
                box-shadow: 0 0 10px rgba(0,243,255,0.6);
            }
            #reading-progress-label {
                position: fixed;
                top: 8px; right: 15px;
                font-family: 'Share Tech Mono', monospace;
                font-size: 11px;
                color: #00f3ff;
                background: rgba(0,0,0,0.6);
                backdrop-filter: blur(8px);
                padding: 3px 10px;
                border-radius: 20px;
                border: 1px solid rgba(0,243,255,0.3);
                z-index: 99999;
                letter-spacing: 1px;
            }

            .secure-container {
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
                padding: 60px 20px 40px;
                box-sizing: border-box;
            }
            .watermark {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                pointer-events: none;
                z-index: 9998;
                opacity: 0.04;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                align-content: center;
                overflow: hidden;
            }
            .watermark span {
                font-size: 24px;
                font-weight: bold;
                padding: 50px;
                transform: rotate(-30deg);
                white-space: nowrap;
            }
        </style>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">
        
        <script>
            // 1. Block Right-Click Context Menu
            document.addEventListener('contextmenu', event => event.preventDefault());
            
            // 2. Block Dragging
            document.addEventListener('dragstart', event => event.preventDefault());
            
            // 3. Block Copy, Cut, Paste
            document.addEventListener('copy', event => { event.preventDefault(); return false; });
            document.addEventListener('cut', event => { event.preventDefault(); return false; });
            document.addEventListener('paste', event => { event.preventDefault(); return false; });
            
            // 4. Block Keyboard Shortcuts (Ctrl+S, Ctrl+P, Ctrl+U, Ctrl+C, F12, Cmd+Opt+U, etc.)
            document.addEventListener('keydown', function(e) {
                const isModifier = e.ctrlKey || e.metaKey;
                const key = e.key ? e.key.toLowerCase() : String.fromCharCode(e.keyCode).toLowerCase();
                
                if (isModifier) {
                    if (['s', 'p', 'c', 'x', 'u', 'a'].includes(key)) {
                        e.preventDefault();
                        return false;
                    }
                    if (key === 'i' || key === 'j') {
                        e.preventDefault();
                        return false;
                    }
                }
                if (e.keyCode === 123 || e.key === "F12") {
                    e.preventDefault();
                    return false;
                }
            });
        </script>
    </head>
    <body oncontextmenu="return false;" oncopy="return false;" oncut="return false;" onpaste="return false;">
        
        <div id="reading-progress-bar"></div>
        <div id="reading-progress-label">0% READ</div>
        
        <div class="watermark">
            <?php 
                $wm_text = esc($_SESSION['user_name']) . " | " . esc($_SESSION['user_mapped_id'] ?? 'Admin');
                for($i=0; $i<50; $i++) echo "<span>$wm_text</span>"; 
            ?>
        </div>

        <div class="secure-container">
            
            <?= $content ?>
        </div>
        
        <script>
        // ── Reading Progress Tracker ──
        (function() {
            const bar   = document.getElementById('reading-progress-bar');
            const label = document.getElementById('reading-progress-label');
            function updateProgress() {
                const scrollTop    = window.scrollY || document.documentElement.scrollTop;
                const docHeight    = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const pct          = docHeight > 0 ? Math.min(100, Math.round((scrollTop / docHeight) * 100)) : 0;
                bar.style.width    = pct + '%';
                label.textContent  = pct + '% READ';
                if (pct >= 90) {
                    label.style.color  = '#10b981';
                    label.style.borderColor = 'rgba(16,185,129,0.4)';
                    bar.style.background = 'linear-gradient(90deg,#059669,#10b981)';
                }
            }
            window.addEventListener('scroll', updateProgress, { passive: true });
            updateProgress();
        })();
        </script>
    </body>
    </html>
    <?php
} else {
    
    header('Content-Type: ' . mime_content_type($filepath));
    header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
    readfile($filepath);
}
?>
