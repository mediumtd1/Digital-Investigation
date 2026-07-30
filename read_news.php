<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = null;

if ($news_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM cyber_news WHERE id = ?");
    $stmt->execute([$news_id]);
    $news = $stmt->fetch();
}

if (!$news) {
    header("Location: index.php");
    exit;
}

$page_title = esc($news['title']) . " | " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        /* Anti-Copy CSS */
        body, .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        @media print { body { display: none !important; } }

        .article-container {
            max-width: 800px;
            margin: 60px auto;
            padding: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent-blue);
            text-decoration: none;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 25px;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #fff;
            transform: translateX(-5px);
        }

        .article-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .article-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 15px;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
        }

        .article-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .article-meta-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .article-body {
            line-height: 1.8;
            color: #cbd5e1;
            font-size: 1.1rem;
            white-space: pre-wrap;
            font-family: 'Inter', sans-serif;
            margin-bottom: 40px;
        }

        .article-attachment-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-share {
            background: rgba(0, 243, 255, 0.1);
            border: 1px solid rgba(0, 243, 255, 0.3);
            color: #00f3ff;
            padding: 8px 16px;
            border-radius: 6px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-share:hover {
            background: #00f3ff;
            color: #000;
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
        }

        /* Dynamic toast overrides will be handled by container */
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableInspector(event);">
    <script>
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
        document.addEventListener('copy', (e) => { e.preventDefault(); return false; });
    </script>

    <div class="article-container">
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left-long"></i> Back to Hub
        </a>

        <article class="glass-panel" style="padding: 40px;">
            <div class="article-header">
                <h1 class="article-title"><?= esc($news['title']) ?></h1>
                
                <div class="article-meta">
                    <div class="article-meta-left">
                        <div class="meta-item">
                            <i class="fa-regular fa-calendar-check" style="color: var(--accent-blue);"></i>
                            <span><?= esc($news['news_date'] ?: date('d M Y', strtotime($news['created_at']))) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fa-solid fa-rss" style="color: #ffaa00;"></i>
                            <span>Cyber Security News</span>
                        </div>
                    </div>
                    <button class="btn-share" onclick="shareContent()">
                        <i class="fa-solid fa-share-nodes"></i> Share News
                    </button>
                </div>
            </div>

            <div class="article-body"><?= esc($news['description']) ?></div>

            <?php if (!empty($news['url']) || !empty($news['attachment'])): ?>
                <div class="article-attachment-card">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fa-solid fa-folder-open" style="color: var(--accent-blue); font-size: 1.5rem;"></i>
                        <div>
                            <div style="color: #fff; font-weight: bold; font-size: 0.95rem; font-family: 'Rajdhani', sans-serif;">ATTACHMENTS & RESOURCES</div>
                            <div style="color: #64748b; font-size: 0.8rem;">Access verified external resources or download documentation.</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <?php if (!empty($news['url'])): ?>
                            <a href="<?= esc($news['url']) ?>" target="_blank" class="btn-share" style="background: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3); color: #a855f7;">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Link
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($news['attachment'])): ?>
                            <a href="<?= BASE_URL . '/' . esc($news['attachment']) ?>" target="_blank" class="btn-share" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #10b981;">
                                <i class="fa-solid fa-download"></i> View Attachment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </article>
    </div>

    
    <div id="toast-container" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

    <script>
        function shareContent() {
            const url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    triggerToast("Link copied to clipboard! Ready to share.", "success");
                }).catch(err => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(url) {
            const tempInput = document.createElement("textarea");
            tempInput.value = url;
            tempInput.style.position = 'fixed';
            tempInput.style.top = '0';
            tempInput.style.left = '0';
            tempInput.style.opacity = '0';
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    triggerToast("Link copied to clipboard! Ready to share.", "success");
                } else {
                    triggerToast("Could not copy link automatically.", "error");
                }
            } catch (err) {
                triggerToast("Could not copy link automatically.", "error");
            }
            document.body.removeChild(tempInput);
        }

        function triggerToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast     = document.createElement('div');
            const iconMap   = { success:'fa-circle-check', error:'fa-circle-xmark' };
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
    </script>
</body>
</html>
