<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$token = isset($_GET['t']) ? trim($_GET['t']) : '';
$featured = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM featured_interns WHERE share_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $featured = $stmt->fetch();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Intern Profile | Digital Investigation Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #020617;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Abstract Cyber Background */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 15% 25%, rgba(0, 243, 255, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 85% 75%, rgba(168, 85, 247, 0.06) 0%, transparent 50%);
            z-index: 1;
            pointer-events: none;
        }

        .showcase-wrapper {
            position: relative;
            z-index: 2;
            width: 550px;
            max-width: 90%;
            padding: 30px 20px;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            text-align: center;
            transition: all 0.5s;
        }
        
        .success-glow {
            border-color: rgba(0, 243, 255, 0.25);
            box-shadow: 0 0 45px rgba(0, 243, 255, 0.12);
        }

        .error-glow {
            border-color: rgba(239, 68, 68, 0.25);
            box-shadow: 0 0 45px rgba(239, 68, 68, 0.12);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 24px;
            border-radius: 30px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 25px;
            border: 1px solid;
        }

        .badge-featured {
            background: rgba(0, 243, 255, 0.1);
            color: #00f3ff;
            border-color: rgba(0, 243, 255, 0.3);
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.3);
        }

        .badge-error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .media-container {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: #020617;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .media-item {
            width: 100%;
            display: block;
            max-height: 280px;
            object-fit: cover;
        }

        .media-video {
            width: 100%;
            display: block;
            max-height: 280px;
            object-fit: contain;
        }

        .college-info {
            font-size: 0.95rem;
            color: #94a3b8;
            margin-top: 5px;
            font-weight: 500;
        }

        .accomplishments {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            font-size: 0.92rem;
            color: #e2e8f0;
            line-height: 1.6;
            text-align: left;
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
        }

        .academy-logo {
            height: 50px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="showcase-wrapper">
    <?php if ($featured): ?>
        <!-- Valid Showcase Card -->
        <div class="glass-card success-glow">
            <img src="assets/images/logo.png" class="academy-logo" alt="Academy Logo" onerror="this.style.display='none';">
            
            <div>
                <span class="status-badge badge-featured">
                    <i class="fa-solid fa-award"></i> Featured Best Intern
                </span>
            </div>

            <?php if ($featured['media_url']): ?>
                <div class="media-container">
                    <?php if ($featured['media_type'] === 'video'): ?>
                        <video class="media-video" src="<?= esc($featured['media_url']) ?>" controls autoplay muted></video>
                    <?php else: ?>
                        <img class="media-item" src="<?= esc($featured['media_url']) ?>" alt="Featured Photo">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 700; letter-spacing: 0.5px; color:#fff;">
                <?= esc($featured['name']) ?>
            </h1>
            
            <?php if ($featured['college']): ?>
                <div class="college-info">
                    <i class="fa-solid fa-graduation-cap" style="color:#00f3ff; margin-right:5px;"></i> <?= esc($featured['college']) ?>
                </div>
            <?php endif; ?>

            <div style="font-family: 'Share Tech Mono', monospace; font-size: 0.85rem; color: #00f3ff; letter-spacing: 1px; margin-top: 15px; text-transform: uppercase;">
                Featured Month: <?= esc($featured['featured_month']) ?>
            </div>

            <div class="accomplishments">
                <h3 style="margin-top:0; font-size:1rem; color:#fff; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:8px; margin-bottom:10px;">
                    <i class="fa-solid fa-star" style="color:#fbbf24; margin-right:5px;"></i> Key Achievements & Performance
                </h3>
                <?= nl2br(esc($featured['description'])) ?>
            </div>

            <div style="margin-top: 30px; font-size:0.8rem; color:#475569;">
                Verified Digital Certificate and Badge &copy; <?= date('Y') ?> Digital Investigation Portal.
            </div>
        </div>
    <?php else: ?>
        <!-- Invalid/Not Found Card -->
        <div class="glass-card error-glow">
            <span class="status-badge badge-error">
                <i class="fa-solid fa-triangle-exclamation"></i> Verification Failed
            </span>
            <h2 style="color: #ef4444; margin-bottom: 10px;">Showcase Profile Not Found</h2>
            <p style="color: #64748b; font-size: 0.95rem; line-height: 1.5;">
                The share token is invalid, expired, or the featured profile has been removed by the academy administration.
            </p>
            <div style="margin-top: 25px;">
                <a href="/" style="display:inline-block; padding:10px 24px; border-radius:6px; background:#1e293b; color:#94a3b8; text-decoration:none; font-weight:700; font-size:0.85rem; transition:0.2s;">
                    Back to Academy
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
