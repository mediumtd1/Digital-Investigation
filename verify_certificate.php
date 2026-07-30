<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$cert = null;
$intern = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM certificates WHERE qr_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $cert = $stmt->fetch();

        if ($cert) {
            $istmt = $pdo->prepare("SELECT name, intern_id FROM interns WHERE id = ?");
            $istmt->execute([$cert['intern_id']]);
            $intern = $istmt->fetch();
        }
    } catch (Exception $e) {}
}

$is_verified = ($cert && $cert['approval_status'] === 'approved' && $cert['is_enabled'] == 1 && $intern);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Verification | Digital Investigation Academy</title>
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
                radial-gradient(circle at 20% 30%, rgba(0, 243, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
            z-index: 1;
            pointer-events: none;
        }

        .verification-wrapper {
            position: relative;
            z-index: 2;
            width: 480px;
            max-width: 90%;
            padding: 20px;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            text-align: center;
            transition: all 0.5s;
        }
        
        .verified-glow {
            border-color: rgba(16, 185, 129, 0.25);
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.1);
        }
        .invalid-glow {
            border-color: rgba(239, 68, 68, 0.25);
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 30px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 25px;
            border: 1px solid;
        }
        .badge-ok {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        .badge-err {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }

        .shield-icon {
            font-size: 4.5rem;
            margin-bottom: 20px;
        }
        .shield-ok {
            background: linear-gradient(135deg, #10b981, #00f3ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .shield-err {
            background: linear-gradient(135deg, #ef4444, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cert-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .details-grid {
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: 25px;
            padding-top: 20px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-label {
            color: #64748b;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .detail-value {
            color: #fff;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .footer-branding {
            margin-top: 35px;
            font-size: 0.8rem;
            color: #475569;
            font-family: 'Share Tech Mono', monospace;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="verification-wrapper">
        <div class="glass-card <?= $is_verified ? 'verified-glow' : 'invalid-glow' ?>">
            
            <?php if ($is_verified): ?>
                <span class="status-badge badge-ok">
                    <i class="fa-solid fa-square-check"></i> Genuine Endorsement
                </span>
                <i class="fa-solid fa-shield-halved shield-icon shield-ok"></i>
                <h1 class="cert-title">Verified</h1>
                <p style="color:#94a3b8; font-size:0.95rem; margin: 0 0 20px 0;">This cryptographic certificate matches the official registries of the Digital Investigation and Cyber Intelligence Academy.</p>
                
                <div class="details-grid">
                    <div class="detail-row">
                        <span class="detail-label">Intern Name</span>
                        <span class="detail-value" style="color: #00f3ff; font-size:1.05rem;"><?= esc($intern['name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Intern UID</span>
                        <span class="detail-value" style="font-family:'Share Tech Mono', monospace;"><?= esc($intern['intern_id']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Issue Date</span>
                        <span class="detail-value"><?= date('d M Y', strtotime($cert['issue_date_custom'] ?? $cert['approved_at'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Certificate Hash</span>
                        <span class="detail-value" style="font-family:'Share Tech Mono', monospace; font-size: 0.8rem; color:#a855f7;"><?= substr($cert['certificate_hash'], 0, 16) ?>...</span>
                    </div>
                </div>

            <?php else: ?>
                <span class="status-badge badge-err">
                    <i class="fa-solid fa-triangle-exclamation"></i> Verification Failed
                </span>
                <i class="fa-solid fa-ban shield-icon shield-err"></i>
                <h1 class="cert-title" style="color:#ef4444;">Invalid</h1>
                <p style="color:#94a3b8; font-size:0.95rem; margin: 0 0 20px 0;">The security system could not find any active, approved, or authentic certification matching the provided credential token.</p>
                
                <div class="details-grid" style="border-color: rgba(239, 68, 68, 0.15);">
                    <div class="detail-row">
                        <span class="detail-label">Error Reason</span>
                        <span class="detail-value" style="color: #ef4444;">SUSPICIOUS / NON-EXISTENT CREDENTIAL</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Action Required</span>
                        <span class="detail-value" style="font-size:0.85rem;">Check for link tampering or contact administration.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="footer-branding">
                DigitalInvestigation.in &bull; Cyber Labs
            </div>

        </div>
    </div>

</body>
</html>
