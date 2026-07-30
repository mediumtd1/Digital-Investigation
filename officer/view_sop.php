<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!function_exists('check_auth')) {
    require_once __DIR__ . '/../includes/functions.php';
}
check_auth('Officer');   

if (!isset($_GET['id'])) {
    http_response_code(400);
    die("Invalid request.");
}

$id   = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM sops WHERE id = ?");
$stmt->execute([$id]);
$sop  = $stmt->fetch();

if (!$sop) {
    http_response_code(404);
    die("SOP not found.");
}

if (empty($sop['file_path'])) {
    http_response_code(404);
    die("No file has been uploaded for this SOP yet.");
}

$filepath = __DIR__ . '/../' . ltrim($sop['file_path'], '/');

if (!file_exists($filepath)) {
    http_response_code(404);
    die("File no longer exists on the server. Please contact HQ.");
}

$ext     = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$wm_text = esc($_SESSION['user_name']) . " | " . esc($_SESSION['user_mapped_id']);

if (in_array($ext, ['html', 'htm'])) {
    $content = file_get_contents($filepath);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= esc($sop['title']) ?> | Secure SOP</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0; padding: 0;
                background: #0f172a; color: #e2e8f0;
                font-family: 'Inter', sans-serif;
                -webkit-user-select: none; user-select: none;
            }
            @media print { html, body { display: none !important; } }

            #reading-progress-bar {
                position: fixed; top: 0; left: 0; width: 0; height: 4px;
                background: linear-gradient(90deg, #0891b2, #00f3ff, #a855f7);
                z-index: 99999; transition: width 0.1s linear;
                box-shadow: 0 0 10px rgba(0,243,255,0.6);
            }
            #reading-progress-label {
                position: fixed; top: 8px; right: 15px;
                font-family: 'Share Tech Mono', monospace; font-size: 11px;
                color: #00f3ff; background: rgba(0,0,0,0.6);
                backdrop-filter: blur(8px); padding: 3px 10px;
                border-radius: 20px; border: 1px solid rgba(0,243,255,0.3);
                z-index: 99999; letter-spacing: 1px;
            }
            .secure-container {
                width: 100%; max-width: 1200px; margin: 0 auto;
                padding: 60px 20px 40px; box-sizing: border-box;
            }
            .watermark {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                pointer-events: none; z-index: 9998; opacity: 0.04;
                display: flex; flex-wrap: wrap;
                justify-content: center; align-content: center; overflow: hidden;
            }
            .watermark span {
                font-size: 22px; font-weight: bold; padding: 45px;
                transform: rotate(-30deg); white-space: nowrap; color: #fff;
            }
        </style>
        <script>
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('dragstart',   e => e.preventDefault());
            document.addEventListener('copy',        e => { e.preventDefault(); return false; });
            document.addEventListener('cut',         e => { e.preventDefault(); return false; });
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    if (['s','p','c','x','u','a'].includes(e.key.toLowerCase())) e.preventDefault();
                }
                if (e.key === 'F12') e.preventDefault();
            });
        </script>
    </head>
    <body oncontextmenu="return false;" oncopy="return false;" oncut="return false;">
        <div id="reading-progress-bar"></div>
        <div id="reading-progress-label">0% READ</div>

        <div class="watermark">
            <?php for ($i = 0; $i < 50; $i++) echo "<span>$wm_text</span>"; ?>
        </div>

        <div class="secure-container">
            <?= $content ?>
        </div>

        <script>
        (function() {
            const bar   = document.getElementById('reading-progress-bar');
            const label = document.getElementById('reading-progress-label');
            function update() {
                const st  = window.scrollY || document.documentElement.scrollTop;
                const dh  = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const pct = dh > 0 ? Math.min(100, Math.round((st / dh) * 100)) : 0;
                bar.style.width   = pct + '%';
                label.textContent = pct + '% READ';
                if (pct >= 90) {
                    label.style.color = '#10b981';
                    label.style.borderColor = 'rgba(16,185,129,0.4)';
                    bar.style.background = 'linear-gradient(90deg,#059669,#10b981)';
                }
            }
            window.addEventListener('scroll', update, { passive: true });
            update();
        })();
        </script>
    </body>
    </html>
    <?php

} else {
    $mime = mime_content_type($filepath) ?: 'application/octet-stream';

    
    header('Content-Type: ' . $mime);
    
    header('Content-Disposition: inline; filename="secure_sop.' . $ext . '"');
    
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    header('Content-Length: ' . filesize($filepath));

    
    readfile($filepath);
}
?>
