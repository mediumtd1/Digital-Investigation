<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Intern', 'Admin'])) {
    die("Access Denied. Unauthorized Session.");
}

$is_admin = ($_SESSION['user_role'] === 'Admin');
$session_intern_id = $_SESSION['user_id'];
$cert_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cert = null;

if ($cert_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE id = ?");
    $stmt->execute([$cert_id]);
    $cert = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE intern_id = ? AND approval_status = 'approved' ORDER BY approved_at DESC LIMIT 1");
    $stmt->execute([$session_intern_id]);
    $cert = $stmt->fetch();
}

if (!$cert)
    die("No active certificate found. Complete all training units and await HQ clearance.");
if (!$is_admin && $cert['intern_id'] != $session_intern_id)
    die("Access Denied.");
if ($cert['approval_status'] !== 'approved' && !$is_admin)
    die("Certificate not yet approved.");
if ($cert['is_enabled'] == 0 && !$is_admin)
    die("Certificate suspended by administrator.");

$stmt = $pdo->prepare("SELECT name, intern_id as uid FROM interns WHERE id = ?");
$stmt->execute([$cert['intern_id']]);
$intern = $stmt->fetch();
if (!$intern)
    die("Intern profile not found.");

$display_name = strtoupper($intern['name']);
$issue_date = $cert['issue_date_custom'] ?? date('Y-m-d', strtotime($cert['approved_at']));
$issue_date_f = date('d / m / Y', strtotime($issue_date));
$cert_hash = $cert['certificate_hash'];

$seed = crc32($cert_hash . $intern['uid']);
srand($seed);
$part1 = sprintf('%04X', rand(0, 65535));
$part2 = sprintf('%04X', rand(0, 65535));
$part3 = sprintf('%04X', rand(0, 65535));
$cert_id_label = "DI-CSCF-" . $part1 . "-" . $part2 . "-" . $part3;
srand(); 

$logo_path = __DIR__ . '/../assets/images/logo.png';
$logo_uri = '';
if (file_exists($logo_path)) {
    $logo_uri = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}

function loadSigBase64($path)
{
    if (file_exists($path)) {
        if (extension_loaded('gd')) {
            try {
                $info = @getimagesize($path);
                $mime = $info['mime'] ?? '';
                $img = null;
                if (strpos($mime, 'png') !== false) {
                    $img = @imagecreatefrompng($path);
                } elseif (strpos($mime, 'jpeg') !== false || strpos($mime, 'jpg') !== false) {
                    $img = @imagecreatefromjpeg($path);
                }

                if ($img) {
                    $w = imagesx($img);
                    $h = imagesy($img);

                    
                    $has_transparency = false;
                    if (strpos($mime, 'png') !== false) {
                        for ($x = 0; $x < $w; $x += 10) {
                            for ($y = 0; $y < $h; $y += 10) {
                                $rgba = imagecolorat($img, $x, $y);
                                $alpha = ($rgba & 0x7F000000) >> 24;
                                if ($alpha > 0) {
                                    $has_transparency = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    
                    if (!$has_transparency) {
                        $tc = imagecreatetruecolor($w, $h);
                        $black = imagecolorallocate($tc, 0, 0, 0);
                        imagefill($tc, 0, 0, $black);
                        imagecopy($tc, $img, 0, 0, 0, 0, $w, $h);
                        imagedestroy($img);
                        $img = $tc;

                        
                        imagefilter($img, IMG_FILTER_NEGATE);

                        
                        imagecolortransparent($img, $black);
                        imagesavealpha($img, true);
                    }

                    ob_start();
                    imagepng($img);
                    $data = ob_get_clean();
                    imagedestroy($img);

                    return 'data:image/png;base64,' . base64_encode($data);
                }
            } catch (Exception $e) {
                
            }
        }
        $type = mime_content_type($path);
        return 'data:' . $type . ';base64,' . base64_encode(file_get_contents($path));
    }
    return '';
}
$sig_mentor = loadSigBase64(__DIR__ . '/../assets/images/Mentor.png') ?: (loadSigBase64(__DIR__ . '/../assets/images/mentor.png') ?: loadSigBase64(__DIR__ . '/../assets/images/sig_mentor.png'));
$sig_director = loadSigBase64(__DIR__ . '/../assets/images/director.jpeg') ?: (loadSigBase64(__DIR__ . '/../assets/images/director.jpeg') ?: loadSigBase64(__DIR__ . '/../assets/images/sig_director.jpeg'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate – <?= esc($display_name) ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Rajdhani:wght@400;500;600;700&family=Share+Tech+Mono&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #040817;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Rajdhani', sans-serif;
            padding: 28px 15px;
            gap: 22px;
        }

        /* ── Responsive wrapper ── */
        .cert-scale-wrapper {
            width: 100%;
            max-width: 1000px;
            display: flex;
            justify-content: center;
        }

        /* ─────────────────────────────────────────────────
   CERTIFICATE: 1000 × 707 px  —  strict fixed size
───────────────────────────────────────────────── */
        .cert {
            position: relative;
            width: 1000px;
            height: 707px;
            background: linear-gradient(160deg, #060c22 0%, #050917 50%, #070d24 100%);
            transform-origin: top center;
            overflow: hidden;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Grid background (rendered as flat background to prevent html2canvas linear-gradient createPattern errors) ── */
        .cert-grid-bg {
            position: absolute;
            inset: 0;
            background: #050917;
            z-index: 0;
        }

        .cert-grid-lines {
            position: absolute;
            inset: 0;
            border: 1px solid rgba(0, 180, 255, 0.08);
            z-index: 1;
        }

        /* ── Scanline overlay ── */
        .cert-scanlines {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 2;
            pointer-events: none;
        }

        /* ── Radial vignette ── */
        .cert-vignette {
            position: absolute;
            inset: 0;
            background: rgba(4, 8, 23, .5);
            z-index: 2;
            pointer-events: none;
        }

        /* ═══════════════════════════════════════════════
   CYBERPUNK HACKER BORDER SYSTEM
═══════════════════════════════════════════════ */

        /* Outer dashed neon border */
        .cb-outer {
            position: absolute;
            inset: 10px;
            border: 1.5px dashed rgba(0, 220, 255, .22);
            z-index: 3;
            box-shadow:
                inset 0 0 25px rgba(0, 200, 255, .04),
                0 0 15px rgba(0, 180, 255, .06);
        }

        /* Inner solid border */
        .cb-inner {
            position: absolute;
            inset: 17px;
            border: .5px solid rgba(0, 180, 255, .12);
            z-index: 3;
        }

        /* Glowing mid-line accent (top & bottom) */
        .cb-top-line {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 2px;
            background: rgba(0, 220, 255, .7);
            z-index: 4;
            box-shadow: 0 0 8px rgba(0, 220, 255, .6);
        }

        .cb-bottom-line {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 2px;
            background: rgba(0, 220, 255, .7);
            z-index: 4;
            box-shadow: 0 0 8px rgba(0, 220, 255, .6);
        }

        /* ── Corner brackets ── */
        .cb-corner {
            position: absolute;
            width: 40px;
            height: 40px;
            z-index: 5;
        }

        .cb-corner::before,
        .cb-corner::after {
            content: '';
            position: absolute;
            background: rgba(0, 220, 255, .85);
            box-shadow: 0 0 6px rgba(0, 220, 255, .9);
        }

        .cb-tl {
            top: 10px;
            left: 10px;
        }

        .cb-tl::before {
            width: 40px;
            height: 2px;
            top: 0;
            left: 0;
        }

        .cb-tl::after {
            width: 2px;
            height: 40px;
            top: 0;
            left: 0;
        }

        .cb-tr {
            top: 10px;
            right: 10px;
        }

        .cb-tr::before {
            width: 40px;
            height: 2px;
            top: 0;
            right: 0;
            left: 0;
        }

        .cb-tr::after {
            width: 2px;
            height: 40px;
            top: 0;
            right: 0;
        }

        .cb-bl {
            bottom: 10px;
            left: 10px;
        }

        .cb-bl::before {
            width: 40px;
            height: 2px;
            bottom: 0;
            left: 0;
        }

        .cb-bl::after {
            width: 2px;
            height: 40px;
            bottom: 0;
            left: 0;
            top: 0;
        }

        .cb-br {
            bottom: 10px;
            right: 10px;
        }

        .cb-br::before {
            width: 40px;
            height: 2px;
            bottom: 0;
            right: 0;
            left: 0;
        }

        .cb-br::after {
            width: 2px;
            height: 40px;
            bottom: 0;
            right: 0;
            top: 0;
        }

        /* ── Hex corner labels ── */
        .cb-hex {
            position: absolute;
            font-family: 'Share Tech Mono', monospace;
            font-size: 7.5px;
            color: rgba(0, 220, 255, .28);
            letter-spacing: 1px;
            z-index: 6;
        }

        .cb-hex-tl {
            top: 14px;
            left: 56px;
        }

        .cb-hex-tr {
            top: 14px;
            right: 56px;
        }

        .cb-hex-bl {
            bottom: 14px;
            left: 56px;
        }

        .cb-hex-br {
            bottom: 14px;
            right: 56px;
        }

        /* ── Side tick marks ── */
        .cb-tick {
            position: absolute;
            background: rgba(0, 200, 255, .35);
            z-index: 4;
        }

        .cb-tick-left {
            width: 2px;
            height: 18px;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .cb-tick-right {
            width: 2px;
            height: 18px;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .cb-tick-top {
            width: 18px;
            height: 2px;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .cb-tick-bottom {
            width: 18px;
            height: 2px;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* ═══════════════════════════════════════════════
   CONTENT LAYER
═══════════════════════════════════════════════ */
        .cert-body {
            position: absolute;
            inset: 0;
            z-index: 7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 24px 65px 22px;
        }

        /* ── TOP BLOCK ── */
        .cert-top {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            width: 100%;
        }

        /* Logo ── enlarged */
        .cert-logo {
            width: 185px;
            height: 115px;
            object-fit: contain;
            filter: drop-shadow(0 0 16px rgba(0, 200, 255, .6)) drop-shadow(0 0 4px rgba(0, 200, 255, .4));
            margin-bottom: 3px;
        }

        .cert-domain {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 3.5px;
            color: rgba(180, 220, 255, .45);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .cert-domain::before,
        .cert-domain::after {
            content: '';
            width: 38px;
            height: .5px;
            background: rgba(0, 200, 255, .25);
        }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 11px;
            color: #cce6ff;
            text-transform: uppercase;
            text-shadow: 0 0 30px rgba(0, 160, 255, .3), 0 2px 4px rgba(0, 0, 0, .6);
            margin-bottom: 5px;
        }

        .cert-subtitle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            margin-bottom: 5px;
        }

        .cert-sub-line {
            flex: 1;
            height: 1px;
            background: rgba(0, 200, 255, .3);
        }

        .cert-subtitle {
            font-family: 'Share Tech Mono', monospace;
            font-size: 9.5px;
            letter-spacing: 4px;
            color: rgba(140, 200, 255, .65);
            white-space: nowrap;
        }

        /* ── MIDDLE BLOCK ── */
        .cert-mid {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            width: 100%;
        }

        .cert-certify {
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px;
            color: rgba(180, 210, 250, .5);
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }

        .cert-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 7px;
            color: #b8e8ff;
            text-shadow: 0 0 22px rgba(0, 200, 255, .5), 0 2px 8px rgba(0, 0, 0, .7);
            text-align: center;
            margin-bottom: 9px;
        }

        .cert-para {
            font-family: 'Rajdhani', sans-serif;
            font-size: 12.5px;
            color: rgba(175, 205, 240, .7);
            text-align: center;
            line-height: 1.6;
            max-width: 660px;
            margin-bottom: 5px;
        }

        .cert-para .hl {
            color: rgba(60, 185, 255, .9);
            font-weight: 600;
        }

        /* ── TABLE ── */
        .cert-table {
            width: 520px;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .cert-table tr {
            border-bottom: .5px solid rgba(0, 140, 200, .18);
        }

        .cert-table tr:first-child {
            border-top: .5px solid rgba(0, 140, 200, .18);
        }

        .cert-table td {
            padding: 7px 14px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 11px;
        }

        .td-label {
            color: rgba(120, 180, 230, .65);
            letter-spacing: 2px;
            text-transform: uppercase;
            width: 180px;
            border-right: .5px solid rgba(0, 140, 200, .18);
        }

        .td-sep {
            color: rgba(120, 180, 230, .45);
            width: 22px;
            text-align: center;
        }

        .td-val {
            color: #cde0f8;
            letter-spacing: 1px;
        }

        .td-val.accent {
            color: #3dbbff;
            font-weight: 700;
            letter-spacing: 3px;
        }

        /* ── BOTTOM BLOCK — Signatures ── */
        .cert-bottom {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .cert-divider {
            width: 100%;
            height: 1px;
            background: rgba(0, 170, 220, .3);
            margin-bottom: 10px;
        }

        .cert-sigs {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .cert-sig {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 180px;
        }

        .sig-img {
            width: 130px;
            height: 48px;
            object-fit: contain;
            object-position: center bottom;
            margin-bottom: 6px;
            opacity: .85;
        }

        .sig-svg {
            width: 130px;
            height: 46px;
            margin-bottom: 6px;
            opacity: .7;
        }

        .sig-line {
            width: 130px;
            height: .5px;
            background: rgba(100, 170, 220, .45);
            margin-bottom: 5px;
        }

        .sig-name {
            font-family: 'Share Tech Mono', monospace;
            font-size: 9px;
            letter-spacing: 3px;
            color: rgba(150, 200, 240, .65);
            text-transform: uppercase;
        }

        .sig-sub {
            font-family: 'Rajdhani', sans-serif;
            font-size: 10px;
            color: rgba(100, 155, 200, .45);
            margin-top: 2px;
            letter-spacing: 1px;
        }

        /* ── Cert ID — centered at bottom between signatures ── */
        .cert-id {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-family: 'Share Tech Mono', monospace;
            font-size: 8px;
            color: rgba(0, 200, 255, .4);
            letter-spacing: 1.5px;
            z-index: 8;
            text-align: center;
        }

        .cert-classified {
            position: absolute;
            top: 20px;
            right: 60px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 7px;
            color: rgba(0, 220, 255, .18);
            letter-spacing: 2px;
            z-index: 8;
        }

        /* ═══════════════════════════════════════════════
   PAGE CONTROLS
═══════════════════════════════════════════════ */
        .control-panel {
            display: flex;
            gap: 14px;
        }

        .btn-action {
            background: linear-gradient(135deg, #00c8ff, #6d28d9);
            color: #fff;
            border: none;
            padding: 13px 28px;
            border-radius: 50px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 800;
            font-size: .9rem;
            cursor: pointer;
            letter-spacing: 1.5px;
            box-shadow: 0 8px 24px rgba(0, 200, 255, .22);
            transition: all .3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 35px rgba(0, 200, 255, .4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .1);
            box-shadow: none;
            color: #94a3b8;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, .09);
            border-color: rgba(0, 200, 255, .3);
        }

        .toast-notify {
            position: fixed;
            bottom: 26px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: rgba(8, 16, 38, .95);
            border: 1px solid #00c8ff;
            color: #fff;
            padding: 10px 26px;
            border-radius: 50px;
            font-family: 'Share Tech Mono', monospace;
            font-size: .78rem;
            box-shadow: 0 8px 24px rgba(0, 200, 255, .2);
            transition: transform .4s cubic-bezier(.175, .885, .32, 1.275);
            z-index: 9999;
            pointer-events: none;
        }

        .toast-notify.show {
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>

<body>

    <div class="cert-scale-wrapper" id="scaleWrap">
        <div class="cert" id="cert">

            
            <div class="cert-grid-bg"></div>
            <div class="cert-grid-lines"></div>
            <div class="cert-scanlines"></div>
            <div class="cert-vignette"></div>

            
            <div class="cb-outer"></div>
            <div class="cb-inner"></div>

            
            <div class="cb-top-line"></div>
            <div class="cb-bottom-line"></div>

            
            <div class="cb-corner cb-tl"></div>
            <div class="cb-corner cb-tr"></div>
            <div class="cb-corner cb-bl"></div>
            <div class="cb-corner cb-br"></div>

            
            <div class="cb-hex cb-hex-tl">[0xA4C1::CERT]</div>
            <div class="cb-hex cb-hex-tr">[SYS::VERIFIED]</div>
            <div class="cb-hex cb-hex-bl">[REF::DI-2026]</div>
            <div class="cb-hex cb-hex-br">[STATUS::ACTIVE]</div>

            
            <div class="cb-tick cb-tick-left"></div>
            <div class="cb-tick cb-tick-right"></div>
            <div class="cb-tick cb-tick-top"></div>
            <div class="cb-tick cb-tick-bottom"></div>

            
            <div class="cert-classified">■ OFFICIAL RECORD ■</div>

            
            <div class="cert-id">ID: <?= esc($cert_id_label) ?></div>

            
            <div class="cert-body">

                
                <div class="cert-top">
                    <?php if ($logo_uri): ?>
                        <img class="cert-logo" src="<?= $logo_uri ?>" alt="Logo">
                    <?php else: ?>
                        <div style="width:160px;height:100px;display:flex;align-items:center;justify-content:center;
                font-family:'Cinzel',serif;font-size:28px;color:#3dbbff;font-weight:700;
                border:1px solid rgba(0,200,255,.3);border-radius:8px;margin-bottom:5px;">DI</div>
                    <?php endif; ?>

                    <div class="cert-domain">investigation.in</div>
                    <div class="cert-title">Digital Investigation</div>
                    <div class="cert-subtitle-row">
                        <div class="cert-sub-line"></div>
                        <div class="cert-subtitle">— &nbsp; Certificate of Internship Completion &nbsp; —</div>
                        <div class="cert-sub-line"></div>
                    </div>
                </div>

                
                <div class="cert-mid">
                    <div class="cert-certify">This is to certify that</div>
                    <div class="cert-name"><?= esc($display_name) ?></div>

                    <p class="cert-para">
                        has successfully completed the Internship Program in
                        <span class="hl">Cyber Security and Cyber Forensics</span>
                        and actively participated in cyber analysis, digital evidence handling,
                        investigative research, technical operations, and analytical activities
                        throughout the internship tenure with dedication, professionalism, and integrity.
                    </p>
                    <p class="cert-para">
                        During the internship period, the candidate demonstrated technical curiosity,
                        problem-solving ability, teamwork, discipline, and commitment towards
                        cyber investigation-related responsibilities.
                    </p>

                    <table class="cert-table">
                        <tr>
                            <td class="td-label">Internship Domain</td>
                            <td class="td-sep">:</td>
                            <td class="td-val">Cyber Security and Cyber Forensics</td>
                        </tr>
                        <tr>
                            <td class="td-label">Duration</td>
                            <td class="td-sep">:</td>
                            <td class="td-val">1 Month</td>
                        </tr>
                        <tr>
                            <td class="td-label">Organization</td>
                            <td class="td-sep">:</td>
                            <td class="td-val">Digital Investigation</td>
                        </tr>
                        <tr>
                            <td class="td-label">Issue Date</td>
                            <td class="td-sep">:</td>
                            <td class="td-val accent"><?= esc($issue_date_f) ?></td>
                        </tr>
                    </table>
                </div>

                
                <div class="cert-bottom">
                    <div class="cert-divider"></div>
                    <div class="cert-sigs">

                        
                        <div class="cert-sig">
                            <?php if (!empty($sig_mentor)): ?>
                                <img src="<?= $sig_mentor ?>" class="sig-img" alt="Mentor Signature">
                            <?php else: ?>
                                
                                <svg class="sig-svg" viewBox="0 0 130 46" fill="none">
                                    <path d="M8 34 Q20 10 36 26 Q46 38 58 18 Q65 6 78 24 Q88 36 105 20 Q114 12 122 20"
                                        stroke="#00f3ff" stroke-width="1.8" stroke-linecap="round" fill="none" />
                                    <path d="M16 38 Q28 42 40 38" stroke="rgba(0,243,255,0.4)" stroke-width="1.2"
                                        stroke-linecap="round" fill="none" />
                                </svg>
                            <?php endif; ?>
                            <div class="sig-line"></div>
                            <div class="sig-name">Mentor</div>
                            <div class="sig-sub">Authorized Signature</div>
                        </div>

                        
                        <div class="cert-sig">
                            <?php if (!empty($sig_director)): ?>
                                <img src="<?= $sig_director ?>" class="sig-img" alt="Director Signature">
                            <?php else: ?>
                                
                                <svg class="sig-svg" viewBox="0 0 130 46" fill="none">
                                    <path d="M6 30 Q16 12 30 28 Q42 40 55 20 Q63 8 76 26 Q86 38 100 22 Q110 12 122 22"
                                        stroke="#a855f7" stroke-width="1.8" stroke-linecap="round" fill="none" />
                                    <path d="M84 34 Q96 40 110 34" stroke="rgba(168,85,247,0.4)" stroke-width="1.2"
                                        stroke-linecap="round" fill="none" />
                                </svg>
                            <?php endif; ?>
                            <div class="sig-line"></div>
                            <div class="sig-name">Director</div>
                            <div class="sig-sub">Authorized Signature</div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    
    <div class="control-panel">
        <button class="btn-action" onclick="downloadPDF()">
            <i class="fa-solid fa-file-pdf"></i> Download Official PDF
        </button>
        <button class="btn-action btn-secondary" onclick="closeTerminal()">
            <i class="fa-solid fa-xmark"></i> Close Terminal
        </button>
    </div>

    <div class="toast-notify" id="toast">INITIALIZING SECURE PDF EXPORT...</div>

    <script>
        /* ── Close Terminal Fallback ── */
        function closeTerminal() {
            // If opened in new tab, try to close it
            window.close();
            // Force redirect instantly so the user doesn't get stuck
            window.location.replace('certificates.php');
        }

        /* ── Responsive scale ── */
        function applyScale() {
            const wrap = document.getElementById('scaleWrap');
            const cert = document.getElementById('cert');
            const avail = wrap.offsetWidth;
            const scale = Math.min(1, avail / 1000);
            cert.style.transform = `scale(${scale})`;
            wrap.style.height = (707 * scale) + 'px';
        }
        window.addEventListener('resize', applyScale);

        document.addEventListener('DOMContentLoaded', () => {
            applyScale();
            // Auto download if query param is set
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('download') === '1') {
                downloadPDF();
            }
        });

        /* ── High-Quality Bulletproof PDF Export ── */
        function downloadPDF() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');

            const cert = document.getElementById('cert');
            const wrap = document.getElementById('scaleWrap');

            // Reset scales for 1:1 crisp capture
            const originalTransform = cert.style.transform;
            const originalHeight = wrap.style.height;

            cert.style.transform = 'none';
            wrap.style.height = '707px';

            const opt = {
                margin: 0,
                filename: 'DigitalInvestigation_Certificate_<?= str_replace(' ', '_', esc($display_name)) ?>.pdf',
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: {
                    scale: 2,
                    useCORS: false, // Turn off CORS restriction checks for local XAMPP environment to avoid security errors
                    allowTaint: true, // Allow tainted canvas so local files do not throw security errors
                    backgroundColor: '#050917',
                    width: 1000,
                    height: 707
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(cert).save().then(() => {
                toast.innerText = 'PDF DOWNLOADED SUCCESSFULLY.';
                cert.style.transform = originalTransform;
                wrap.style.height = originalHeight;
                applyScale();
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => { toast.innerText = 'INITIALIZING SECURE PDF EXPORT...'; }, 400);
                }, 2000);
            }).catch(err => {
                console.error("PDF Export Error: ", err);
                toast.innerText = 'EXPORT ERROR. CHECK CONSOLE.';
                cert.style.transform = originalTransform;
                wrap.style.height = originalHeight;
                applyScale();
                setTimeout(() => toast.classList.remove('show'), 3000);
            });
        }
    </script>
</body>

</html>