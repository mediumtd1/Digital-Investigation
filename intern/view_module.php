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
$stmt = $pdo->prepare("SELECT file_path, week, day, is_visible FROM internship_modules WHERE id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch();

if (!$module) {
    die("Module not found.");
}

if (!$is_admin && !$module['is_visible']) {
    die("This module is currently hidden by HQ.");
}

if (empty($module['file_path'])) {
    die("No file has been uploaded for this module yet.");
}

$mod_week = (int)($module['week'] ?? 1);
$mod_day  = (int)($module['day'] ?? 1);
$calendar_day = (($mod_week - 1) * 7) + $mod_day;
$current_day_of_month = (int)date('j');

$has_override = false;
try {
    $ov_stmt = $pdo->prepare("SELECT id FROM module_access_overrides WHERE intern_id = ? AND module_id = ?");
    $ov_stmt->execute([$_SESSION['user_id'], $id]);
    if ($ov_stmt->fetch()) $has_override = true;
} catch(Exception $e) {}

if ($calendar_day > $current_day_of_month && !$is_admin && !$has_override) {
    $msg = ($current_day_of_month > $calendar_day) ? "Access Expired. This module was only available on Day $calendar_day." : "Access Denied. This module will unlock on Day $calendar_day.";
    die("<div style='background:#0f172a; color:#ef4444; padding:40px; text-align:center; font-family:\"Rajdhani\",sans-serif; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
        <div style='background:rgba(239,68,68,0.1); border:1px solid #ef4444; padding:30px; border-radius:15px; max-width:500px;'>
            <i class='fa-solid fa-clock' style='font-size:3rem; margin-bottom:20px;'></i>
            <h2 style='margin:0 0 10px 0; color:#fff;'>OUT OF SCHEDULE</h2>
            <p style='color:#94a3b8; line-height:1.6;'>$msg</p>
            <p style='color:#64748b; font-size:0.8rem; margin-top:10px;'>If you missed this module, request HQ for a manual access override.</p>
            <button onclick='window.close()' style='margin-top:20px; background:#ef4444; color:#fff; border:none; padding:12px 30px; border-radius:8px; cursor:pointer; font-weight:bold; letter-spacing:1px;'>CLOSE WINDOW</button>
        </div>
    </div>");
}

$intern_id = $_SESSION['user_id'];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS module_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        module_id INT NOT NULL,
        opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_open (intern_id, module_id)
    )");
} catch(Exception $e) {}

$check = $pdo->prepare("SELECT id FROM module_tracking WHERE intern_id = ? AND module_id = ?");
$check->execute([$intern_id, $id]);

if ($check->fetch() && !$is_admin) {
    
    
}

if (!$is_admin) {
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO module_tracking (intern_id, module_id) VALUES (?, ?)");
        $ins->execute([$intern_id, $id]);
    } catch(Exception $e) {}
}

$filepath = __DIR__ . '/../uploads/modules/' . basename($module['file_path']);

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
        <title>Secure Module Viewer</title>
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
            
            // 4. Block Keyboard Shortcuts (Ctrl+S, Ctrl+P, Ctrl+U, Ctrl+C, F12)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    if (['s', 'p', 'c', 'x', 'u', 'a'].includes(e.key.toLowerCase())) {
                        e.preventDefault();
                    }
                }
                // Block F12 (DevTools)
                if (e.key === "F12") {
                    e.preventDefault();
                }
            });
        </script>
    </head>
    <body oncontextmenu="return false;" oncopy="return false;" oncut="return false;" onpaste="return false;">
        
        <div id="reading-progress-bar"></div>
        <div id="reading-progress-label">0% READ</div>
        
        <div class="watermark">
            <?php 
                $wm_text = esc($_SESSION['user_name']) . " | " . esc($_SESSION['user_mapped_id']);
                for($i=0; $i<50; $i++) echo "<span>$wm_text</span>"; 
            ?>
        </div>

        <div class="secure-container">
            
            <?= $content ?>
            
            <div style="margin-top: 50px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px;">
                <h3 style="color: #00f3ff;">Module Completion & Attendance</h3>
                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px;">Once you have read the material and completed the quiz above, click the button below to record your performance and mark your attendance for today.</p>
                <button id="markCompleteBtn" style="background: #10b981; color: #fff; border: none; padding: 12px 24px; font-size: 1rem; border-radius: 6px; cursor: pointer; font-family: 'Rajdhani', sans-serif; font-weight: bold; letter-spacing: 1px; transition: 0.3s;" onclick="markComplete(<?= $id ?>)">
                    <i class="fa-solid fa-check-circle"></i> MARK AS COMPLETED
                </button>
                <div id="completeMsg" style="display: none; color: #10b981; margin-top: 15px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> Attendance marked as Present! Performance recorded.
                </div>
            </div>
        </div>
        
        <script>
        function markComplete(moduleId) {
            let btn = document.getElementById('markCompleteBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> RECORDING...';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            
            fetch('ajax_mark_module.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'module_id=' + moduleId
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    btn.style.display = 'none';
                    document.getElementById('completeMsg').style.display = 'block';
                } else {
                    btn.innerHTML = 'ERROR: ' + (data.error || 'TRY AGAIN');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            })
            .catch(e => {
                btn.innerHTML = 'ERR: ' + e.message;
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
        </script>

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
