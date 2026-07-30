<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Intern');

$portal = 'intern';
$page_title = 'My Profile | ' . SITE_NAME;
$intern_id = $_SESSION['user_id'];

foreach (['linkedin_url VARCHAR(255)', 'github_url VARCHAR(255)', 'bio TEXT', 'skills TEXT'] as $col) {
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN $col"); } catch(Exception $e) {}
}

function validate_safe_url($url) {
    if (empty($url)) return true;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    $parsed = parse_url($url);
    return in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https']);
}

$success_msg = '';
$error_msg   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    $bio      = trim($_POST['bio']      ?? '');
    $skills   = trim($_POST['skills']   ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github   = trim($_POST['github']   ?? '');
    $college  = trim($_POST['college']  ?? '');
    $course   = trim($_POST['course']   ?? '');
    
    $father_occupation = trim($_POST['father_occupation'] ?? '');
    $edu_state = trim($_POST['edu_state'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $semester = (int)($_POST['semester'] ?? 0);
    $high_school_percentage = trim($_POST['high_school_percentage'] ?? '');
    $higher_secondary_percentage = trim($_POST['higher_secondary_percentage'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $postal_address = trim($_POST['postal_address'] ?? '');

    
    if ($linkedin && !validate_safe_url($linkedin)) { $error_msg = 'Invalid LinkedIn URL. Must start with https://'; }
    if ($github   && !validate_safe_url($github))   { $error_msg = 'Invalid GitHub URL. Must start with https://'; }

    
    $photo_path = null;
    if (!$error_msg && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_size = $_FILES['photo']['size'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if ($file_size > 300 * 1024) {
            $error_msg = "Profile photo exceeds 300KB size limit.";
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $error_msg = "Only JPG and PNG are permitted.";
        } else {
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                $error_msg = 'Security Violation: File is not a valid image.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $new_name = uniqid('profile_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_name)) {
                    $photo_path = 'uploads/profiles/' . $new_name;
                    
                    $stmt_old = $pdo->prepare("SELECT profile_photo FROM interns WHERE id = ?");
                    $stmt_old->execute([$intern_id]);
                    $old_photo = $stmt_old->fetchColumn();
                    if ($old_photo && file_exists(__DIR__ . '/../' . $old_photo)) {
                        @unlink(__DIR__ . '/../' . $old_photo);
                    }
                }
            }
        }
    }

    
    $resume_upload_path = null;
    if (!$error_msg && isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file_size = $_FILES['resume']['size'];
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($file_size > 2 * 1024 * 1024) {
            $error_msg = "Resume exceeds 2MB size limit.";
        } elseif (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $error_msg = "Only PDF, DOC, and DOCX are permitted for resume.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['resume']['tmp_name']);
            finfo_close($finfo);
            $allowed_mimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            if (!in_array($mime, $allowed_mimes)) {
                $error_msg = 'Security Violation: Invalid resume file type.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/resumes/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $new_name = uniqid('resume_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $new_name)) {
                    $resume_upload_path = 'uploads/resumes/' . $new_name;
                }
            }
        }
    }

    if (!$error_msg) {
        try {
            $sql = "UPDATE interns SET bio=?, skills=?, linkedin_url=?, github_url=?, college=?, course=?, father_occupation=?, edu_state=?, university=?, semester=?, high_school_percentage=?, higher_secondary_percentage=?, pincode=?, postal_address=?";
            $params = [
                $bio, $skills, $linkedin, $github, $college, $course,
                $father_occupation, $edu_state, $university, $semester,
                $high_school_percentage, $higher_secondary_percentage, $pincode, $postal_address
            ];
            if ($photo_path) { $sql .= ', profile_photo=?'; $params[] = $photo_path; }
            if ($resume_upload_path) { $sql .= ', resume_path=?'; $params[] = $resume_upload_path; }
            $sql .= ' WHERE id=?';
            $params[] = $intern_id;
            $pdo->prepare($sql)->execute($params);
            log_audit($pdo, $_SESSION['user_mapped_id'] ?? $intern_id, 'Intern', 'Updated Profile');
            $success_msg = 'Profile updated successfully!';
            if ($photo_path) $_SESSION['user_photo'] = $photo_path;
        } catch(Exception $e) {
            $error_msg = 'Update failed. Please try again.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
$stmt->execute([$intern_id]);
$intern = $stmt->fetch();

$my_referral_code = $intern['intern_id'] ?? '';
$referral_link = 'https://digitalinvestigation.in/intern/signup.php?ref=' . urlencode($my_referral_code);
$referred_interns = [];
if (!empty($my_referral_code)) {
    try {
        $ref_stmt = $pdo->prepare("SELECT intern_id, name, college, course, batch, state, country, created_at FROM interns WHERE referral_id = ? ORDER BY created_at DESC");
        $ref_stmt->execute([$my_referral_code]);
        $referred_interns = $ref_stmt->fetchAll();
    } catch(Exception $e) {}
}

$attendance_present = 0; $tasks_completed = 0; $avg_score = 0; $cert_total = 0;
$my_referral_points = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM intern_attendance WHERE intern_id=? AND status='Present'"); $s->execute([$intern_id]); $attendance_present = $s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE intern_id=? AND status='Completed'"); $s->execute([$intern_id]); $tasks_completed = $s->fetchColumn();
    $s = $pdo->prepare("SELECT AVG(score) FROM performance_scores WHERE intern_id=?"); $s->execute([$intern_id]); $avg_score = round((float)$s->fetchColumn(), 1);
    $s = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE intern_id=?"); $s->execute([$intern_id]); $cert_total = $s->fetchColumn();
    $s = $pdo->prepare("SELECT COALESCE(referral_points, 0) FROM interns WHERE id=?"); $s->execute([$intern_id]); $my_referral_points = (float)$s->fetchColumn();
} catch(Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">
<style>
.profile-grid { display:grid; grid-template-columns:320px 1fr; gap:28px; padding:28px; }

/* ID Card */
.id-card {
    background: linear-gradient(145deg, rgba(15,23,42,0.9), rgba(30,27,75,0.7));
    border: 1px solid rgba(0,243,255,0.25);
    border-radius: 18px;
    padding: 30px 24px;
    text-align: center;
    position: relative;
    overflow: hidden;
    height: fit-content;
}
.id-card::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:120px; height:120px; border-radius:50%;
    background: radial-gradient(circle, rgba(0,243,255,0.08) 0%, transparent 70%);
}
.id-card-photo-wrap { position:relative; display:inline-block; margin-bottom:18px; }
.id-card-photo {
    width:110px; height:110px; border-radius:50%; object-fit:cover;
    border:3px solid #00f3ff;
    box-shadow: 0 0 20px rgba(0,243,255,0.4), 0 0 40px rgba(0,243,255,0.15);
}
.id-online-dot {
    position:absolute; bottom:6px; right:6px;
    width:18px; height:18px; border-radius:50%;
    background:#10b981; border:3px solid #0f172a;
    box-shadow:0 0 8px rgba(16,185,129,0.6);
}
.id-name { font-family:'Rajdhani',sans-serif; font-size:1.4rem; font-weight:800; color:#fff; margin-bottom:4px; }
.id-uid  { font-family:'Share Tech Mono',monospace; font-size:0.8rem; color:#00f3ff; background:rgba(0,243,255,0.08); padding:4px 12px; border-radius:20px; display:inline-block; margin-bottom:14px; border:1px solid rgba(0,243,255,0.2); user-select:none; }
.id-verified { display:inline-flex; align-items:center; gap:6px; background:rgba(16,185,129,0.1); color:#10b981; padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:700; border:1px solid rgba(16,185,129,0.3); margin-bottom:18px; }
.id-divider { border:0; border-top:1px solid rgba(255,255,255,0.06); margin:18px 0; }
.id-meta-row { display:flex; align-items:flex-start; gap:10px; text-align:left; margin-bottom:12px; font-size:0.82rem; }
.id-meta-icon { color:#00f3ff; width:16px; flex-shrink:0; margin-top:2px; }
.id-meta-val { color:#94a3b8; line-height:1.4; }

/* Social links on card */
.id-social { display:flex; gap:10px; justify-content:center; margin-top:14px; }
.id-social-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:7px 14px; border-radius:8px; font-size:0.75rem; font-weight:700;
    text-decoration:none; transition:0.3s;
    border:1px solid;
}
.id-social-btn:hover { transform:translateY(-2px); }

/* Edit Panel */
.edit-panel .glass-panel { margin-bottom:22px; }
.edit-section-title {
    font-family:'Rajdhani',sans-serif; font-size:1rem; font-weight:800;
    color:#00f3ff; margin-bottom:18px; letter-spacing:1px;
    display:flex; align-items:center; gap:8px;
    border-bottom:1px solid rgba(0,243,255,0.1); padding-bottom:12px;
}
.stat-mini-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.stat-mini { text-align:center; padding:14px 10px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.05); }
.stat-mini-val { font-family:'Rajdhani',sans-serif; font-size:1.5rem; font-weight:900; }
.stat-mini-lbl { font-size:0.65rem; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-top:2px; }

/* Photo upload preview */
.photo-upload-area {
    border:2px dashed rgba(0,243,255,0.2);
    border-radius:12px; padding:20px; text-align:center;
    cursor:pointer; transition:0.3s;
}
.photo-upload-area:hover { border-color:rgba(0,243,255,0.5); background:rgba(0,243,255,0.03); }

/* Skills tags */
.skills-hint { font-size:0.75rem; color:#475569; margin-top:5px; }

/* Referral Section */
.referral-card {
    background: linear-gradient(135deg, rgba(168,85,247,0.08), rgba(0,243,255,0.05));
    border: 1px solid rgba(168,85,247,0.25);
    border-radius: 14px;
    padding: 22px;
    margin-top: 20px;
}
.referral-code-box {
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(168,85,247,0.35);
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 14px 0;
}
.referral-code-text {
    font-family: 'Share Tech Mono', monospace;
    font-size: 1.1rem;
    color: #a855f7;
    letter-spacing: 2px;
    word-break: break-all;
}
.copy-btn {
    background: rgba(168,85,247,0.15);
    border: 1px solid rgba(168,85,247,0.4);
    color: #a855f7;
    padding: 7px 14px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
    transition: 0.25s;
    font-family: inherit;
}
.copy-btn:hover { background: rgba(168,85,247,0.3); transform: scale(1.04); }
.referral-link-box {
    background: rgba(0,0,0,0.25);
    border: 1px solid rgba(0,243,255,0.2);
    border-radius: 10px;
    padding: 11px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}
.referral-link-text {
    font-size: 0.75rem;
    color: #64748b;
    word-break: break-all;
    flex: 1;
}
.referral-table { width:100%; border-collapse:collapse; font-size:0.82rem; margin-top:12px; }
.referral-table th { color:#475569; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; padding:8px 10px; border-bottom:1px solid rgba(255,255,255,0.06); text-align:left; font-weight:600; }
.referral-table td { padding:10px 10px; border-bottom:1px solid rgba(255,255,255,0.04); color:#94a3b8; vertical-align:middle; }
.referral-table tr:last-child td { border-bottom:none; }
.referral-table tr:hover td { background:rgba(168,85,247,0.04); }
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-id-badge" style="color:#00f3ff;"></i> DIGITAL DOSSIER
                </h2>
            </div>
        </header>

        <?php if ($success_msg): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= esc($success_msg) ?>','success'));</script>
        <?php elseif ($error_msg): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast('<?= esc($error_msg) ?>','error'));</script>
        <?php endif; ?>

        <div class="profile-grid">

            
            <div>
                <div class="id-card">
                    <div style="font-size:0.65rem; letter-spacing:3px; color:#475569; text-transform:uppercase; margin-bottom:16px;">CADET IDENTITY CARD</div>
                    <?php
                    $name_enc  = urlencode($intern['name'] ?? 'Intern');
                    $avatar_url= "https://ui-avatars.com/api/?name={$name_enc}&background=0D8ABC&color=fff&size=220";
                    $photo_src = !empty($intern['profile_photo']) ? '../' . ltrim($intern['profile_photo'],'/') : $avatar_url;
                    ?>
                    <div class="id-card-photo-wrap">
                        <img src="<?= esc($photo_src) ?>" class="id-card-photo" id="previewPhoto" alt="Profile">
                        <div class="id-online-dot"></div>
                    </div>
                    <div class="id-name"><?= esc($intern['name']) ?></div>
                    <div class="id-uid"><i class="fa-solid fa-fingerprint" style="margin-right:5px;"></i><?= esc($intern['intern_id'] ?? 'N/A') ?></div>
                    <div class="id-verified"><i class="fa-solid fa-circle-check"></i> Cadet Verified</div>

                    <hr class="id-divider">

                    <div class="id-meta-row"><i class="fa-solid fa-building-columns id-meta-icon"></i><div class="id-meta-val"><?= esc($intern['college'] ?: 'N/A') ?></div></div>
                    <div class="id-meta-row"><i class="fa-solid fa-book id-meta-icon"></i><div class="id-meta-val"><?= esc($intern['course'] ?: 'N/A') ?></div></div>
                    <div class="id-meta-row"><i class="fa-solid fa-map-pin id-meta-icon"></i><div class="id-meta-val"><?= esc($intern['district'] ? $intern['district'].', '.$intern['state'] : 'N/A') ?></div></div>
                    <div class="id-meta-row"><i class="fa-solid fa-calendar id-meta-icon"></i><div class="id-meta-val">Joined <?= !empty($intern['created_at']) ? date('d M Y', strtotime($intern['created_at'])) : 'N/A' ?></div></div>

                    <?php if (!empty($intern['bio'])): ?>
                    <div style="margin-top:14px; padding:10px; background:rgba(255,255,255,0.02); border-radius:8px; font-size:0.78rem; color:#94a3b8; text-align:left; line-height:1.5; border:1px solid rgba(255,255,255,0.05);">
                        "<?= esc($intern['bio']) ?>"
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($intern['skills'])): ?>
                    <div style="margin-top:14px; display:flex; flex-wrap:wrap; gap:6px; justify-content:center;">
                        <?php foreach(array_filter(array_map('trim', explode(',', $intern['skills']))) as $sk): ?>
                        <span style="background:rgba(0,243,255,0.08); color:#00f3ff; font-size:0.65rem; padding:3px 9px; border-radius:20px; border:1px solid rgba(0,243,255,0.2);"><?= esc($sk) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="id-social">
                        <?php if (!empty($intern['linkedin_url'])): ?>
                        <a href="<?= esc($intern['linkedin_url']) ?>" target="_blank" class="id-social-btn" style="background:rgba(10,102,194,0.1); color:#0a66c2; border-color:rgba(10,102,194,0.3);">
                            <i class="fa-brands fa-linkedin"></i> LinkedIn
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($intern['github_url'])): ?>
                        <a href="<?= esc($intern['github_url']) ?>" target="_blank" class="id-social-btn" style="background:rgba(255,255,255,0.06); color:#e2e8f0; border-color:rgba(255,255,255,0.15);">
                            <i class="fa-brands fa-github"></i> GitHub
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="glass-panel" style="margin-top:20px; padding:20px;">
                    <div class="edit-section-title" style="margin-bottom:14px;"><i class="fa-solid fa-chart-pie"></i> PERFORMANCE</div>
                    <div class="stat-mini-grid">
                        <div class="stat-mini"><div class="stat-mini-val" style="color:#10b981;"><?= $attendance_present ?></div><div class="stat-mini-lbl">Present</div></div>
                        <div class="stat-mini"><div class="stat-mini-val" style="color:#f59e0b;"><?= $tasks_completed ?></div><div class="stat-mini-lbl">Tasks</div></div>
                        <div class="stat-mini"><div class="stat-mini-val" style="color:#a855f7;"><?= $avg_score ?>%</div><div class="stat-mini-lbl">Score</div></div>
                        <div class="stat-mini"><div class="stat-mini-val" style="color:#ec4899;"><?= $cert_total ?></div><div class="stat-mini-lbl">Awards</div></div>
                    </div>
                </div>

                
                <div class="referral-card">
                    <div style="display:flex; align-items:center; gap:9px; margin-bottom:6px;">
                        <i class="fa-solid fa-user-plus" style="color:#a855f7; font-size:1rem;"></i>
                        <span style="font-family:'Rajdhani',sans-serif; font-size:0.95rem; font-weight:800; color:#a855f7; letter-spacing:1px; text-transform:uppercase;">My Referral Code</span>
                        <span style="background:rgba(168,85,247,0.15); color:#a855f7; border:1px solid rgba(168,85,247,0.3); border-radius:20px; font-size:0.65rem; padding:2px 9px; font-weight:700;"><?= count($referred_interns) ?> joined</span>
                    </div>

                    
                    <?php if ($my_referral_points > 0 || count($referred_interns) > 0): ?>
                    <div style="display:flex; gap:10px; margin-bottom:10px; flex-wrap:wrap;">
                        <?php if ($my_referral_points > 0): ?>
                        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.25); border-radius:8px; padding:8px 14px; display:flex; align-items:center; gap:7px; flex:1; min-width:130px;">
                            <i class="fa-solid fa-coins" style="color:#10b981;"></i>
                            <div>
                                <div style="font-family:'Share Tech Mono',monospace; color:#10b981; font-weight:bold; font-size:1rem;"><?= number_format($my_referral_points, 1) ?> pts</div>
                                <div style="font-size:0.65rem; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">Referral Points</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($intern['referral_id'])): ?>
                        <div style="background:rgba(0,243,255,0.05); border:1px solid rgba(0,243,255,0.2); border-radius:8px; padding:8px 14px; display:flex; align-items:center; gap:7px; flex:1; min-width:130px;">
                            <i class="fa-solid fa-link" style="color:#00f3ff; font-size:0.85rem;"></i>
                            <div>
                                <div style="font-size:0.7rem; color:#00f3ff; font-weight:700;">Joined via Referral</div>
                                <div style="font-family:'Share Tech Mono',monospace; font-size:0.65rem; color:#475569;"><?= esc($intern['referral_id']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div style="font-size:0.75rem; color:#64748b; margin-bottom:4px;">Share this code or link with others to invite them to join:</div>

                    
                    <div class="referral-code-box">
                        <span class="referral-code-text" id="refCodeText"><?= esc($my_referral_code) ?></span>
                        <button class="copy-btn" onclick="copyToClipboard('refCodeText', this)"><i class="fa-solid fa-copy"></i> Copy Code</button>
                    </div>

                    
                    <div style="font-size:0.7rem; color:#475569; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Referral Link</div>
                    <div class="referral-link-box">
                        <span class="referral-link-text" id="refLinkText"><?= esc($referral_link) ?></span>
                        <button class="copy-btn" onclick="copyToClipboard('refLinkText', this)"><i class="fa-solid fa-link"></i> Copy Link</button>
                    </div>
                </div>
            </div>

            
            <div class="edit-panel">
                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="update_profile" value="1">

                    
                    <div class="glass-panel">
                        <div class="edit-section-title"><i class="fa-solid fa-camera"></i> UPDATE UPLOADS</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label class="form-label">Profile Photo (Max 300KB)</label>
                                <label class="photo-upload-area" for="photoInput" style="padding: 10px;">
                                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.5rem; color:#00f3ff; margin-bottom:4px; display:block;"></i>
                                    <div style="font-size:0.8rem; font-weight:600; color:#fff;">Change Photo</div>
                                    <input type="file" name="photo" id="photoInput" accept=".jpg,.jpeg,.png" style="display:none;" onchange="previewImage(this)">
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Resume / CV (Max 2MB)</label>
                                <label class="photo-upload-area" for="resumeInput" style="padding: 10px;">
                                    <i class="fa-solid fa-file-pdf" style="font-size:1.5rem; color:#00f3ff; margin-bottom:4px; display:block;"></i>
                                    <div style="font-size:0.8rem; font-weight:600; color:#fff;">Update Resume</div>
                                    <input type="file" name="resume" id="resumeInput" accept=".pdf,.doc,.docx" style="display:none;">
                                </label>
                            </div>
                        </div>
                        <?php if (!empty($intern['resume_path'])): ?>
                            <div style="margin-top: 12px; text-align: center;">
                                <a href="../<?= esc($intern['resume_path']) ?>" target="_blank" style="color: var(--accent-blue); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                                    <i class="fa-solid fa-file-lines"></i> View Registered Resume / CV
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    
                    <div class="glass-panel">
                        <div class="edit-section-title"><i class="fa-solid fa-graduation-cap"></i> ACADEMIC BACKGROUND</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label class="form-label">College Name</label>
                                <input type="text" name="college" class="form-control" value="<?= esc($intern['college'] ?? '') ?>" placeholder="e.g. IIT Delhi, RGPV...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Course / Degree</label>
                                <input type="text" name="course" class="form-control" value="<?= esc($intern['course'] ?? '') ?>" placeholder="e.g. B.Tech, BCA...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">University State</label>
                                <input type="text" name="edu_state" class="form-control" value="<?= esc($intern['edu_state'] ?? '') ?>" placeholder="e.g. Madhya Pradesh, Delhi...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">University Name</label>
                                <input type="text" name="university" class="form-control" value="<?= esc($intern['university'] ?? '') ?>" placeholder="e.g. RGPV, Delhi University...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-control">
                                    <option value="">Select Semester</option>
                                    <?php for($s=1; $s<=8; $s++): ?>
                                        <option value="<?= $s ?>" <?= ($intern['semester'] ?? 0) == $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">High School Percentage (10th)</label>
                                <input type="text" name="high_school_percentage" class="form-control" value="<?= esc($intern['high_school_percentage'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Higher Secondary Percentage (12th)</label>
                                <input type="text" name="higher_secondary_percentage" class="form-control" value="<?= esc($intern['higher_secondary_percentage'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    
                    <div class="glass-panel">
                        <div class="edit-section-title"><i class="fa-solid fa-house-user"></i> PERSONAL & CONTACT DETAILS</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label class="form-label">Father's Occupation</label>
                                <input type="text" name="father_occupation" class="form-control" value="<?= esc($intern['father_occupation'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Pin code / Zip code</label>
                                <input type="text" name="pincode" class="form-control" value="<?= esc($intern['pincode'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Postal Address</label>
                                <textarea name="postal_address" class="form-control" rows="3"><?= esc($intern['postal_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    
                    <div class="glass-panel">
                        <div class="edit-section-title"><i class="fa-solid fa-user-pen"></i> BIO & SKILLS</div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label class="form-label">Professional Bio</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Briefly describe your background and interests..."><?= esc($intern['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Skills</label>
                            <input type="text" name="skills" class="form-control" value="<?= esc($intern['skills'] ?? '') ?>" placeholder="e.g. Python, OSINT, Network Forensics, Wireshark">
                            <div class="skills-hint"><i class="fa-solid fa-info-circle"></i> Separate skills with commas. They appear as tags on your ID card.</div>
                        </div>
                    </div>

                    
                    <div class="glass-panel">
                        <div class="edit-section-title"><i class="fa-solid fa-share-nodes"></i> SOCIAL LINKS</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label class="form-label"><i class="fa-brands fa-linkedin" style="color:#0a66c2;"></i> LinkedIn URL</label>
                                <input type="url" name="linkedin" class="form-control" value="<?= esc($intern['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-brands fa-github" style="color:#e2e8f0;"></i> GitHub URL</label>
                                <input type="url" name="github" class="form-control" value="<?= esc($intern['github_url'] ?? '') ?>" placeholder="https://github.com/username">
                            </div>
                        </div>
                    </div>

                    
                    <div class="glass-panel" style="border-left: 3px solid rgba(168,85,247,0.35);">
                        <div class="edit-section-title"><i class="fa-solid fa-users-line" style="color:#a855f7;"></i> <span style="color:#a855f7;">REFERRAL HISTORY</span></div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                            <div style="font-size:0.82rem; color:#64748b;">
                                Interns who joined using your referral code <code style="background:rgba(168,85,247,0.1); color:#a855f7; padding:2px 8px; border-radius:4px; font-size:0.78rem;"><?= esc($my_referral_code) ?></code>
                            </div>
                            <span style="background:rgba(168,85,247,0.12); color:#a855f7; border:1px solid rgba(168,85,247,0.3); border-radius:20px; font-size:0.72rem; padding:3px 12px; font-weight:700;">
                                <?= count($referred_interns) ?> Referral<?= count($referred_interns) !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <?php if (empty($referred_interns)): ?>
                        <div style="text-align:center; padding:30px 20px; color:#475569;">
                            <i class="fa-solid fa-user-group" style="font-size:2rem; margin-bottom:10px; display:block; opacity:0.4;"></i>
                            <div style="font-size:0.85rem;">No one has used your referral code yet.</div>
                            <div style="font-size:0.75rem; margin-top:6px;">Share your link to invite friends!</div>
                        </div>
                        <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="referral-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Intern ID</th>
                                        <th>Name</th>
                                        <th>College</th>
                                        <th>Batch</th>
                                        <th>Location</th>
                                        <th>Joined On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referred_interns as $i => $ref): ?>
                                    <tr>
                                        <td style="color:#475569; font-size:0.75rem;"><?= $i + 1 ?></td>
                                        <td style="font-family:'Share Tech Mono',monospace; color:#00f3ff; font-size:0.78rem;"><?= esc($ref['intern_id']) ?></td>
                                        <td style="font-weight:600; color:#e2e8f0;"><?= esc($ref['name']) ?></td>
                                        <td style="font-size:0.78rem; color:#64748b;"><?= esc($ref['college'] ?? '—') ?></td>
                                        <td>
                                            <?php if (!empty($ref['batch'])): ?>
                                            <span style="background:rgba(0,243,255,0.07); color:#00f3ff; font-size:0.7rem; padding:2px 8px; border-radius:4px; border:1px solid rgba(0,243,255,0.2);"><?= esc($ref['batch']) ?></span>
                                            <?php else: ?><span style="color:#475569;">—</span><?php endif; ?>
                                        </td>
                                        <td style="font-size:0.78rem;"><?= esc(($ref['country'] ?? 'India') !== 'India' ? ($ref['country'] ?? '—') : ($ref['state'] ?? '—')) ?></td>
                                        <td style="font-size:0.75rem; color:#10b981;"><?= !empty($ref['created_at']) ? date('d M Y', strtotime($ref['created_at'])) : '—' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    
                    <div class="glass-panel" style="border-left:3px solid rgba(255,255,255,0.08);">
                        <div class="edit-section-title"><i class="fa-solid fa-lock"></i> IMMUTABLE FIELDS</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:0.85rem;">
                            <div>
                                <div style="color:#475569; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Registered Email</div>
                                <div style="color:#94a3b8;"><?= esc($intern['email'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div style="color:#475569; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Mobile</div>
                                <div style="color:#94a3b8;"><?= esc($intern['mobile'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div style="color:#475569; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Intern UID</div>
                                <div style="font-family:'Share Tech Mono',monospace; color:#00f3ff; font-size:0.85rem;"><?= esc($intern['intern_id'] ?? '—') ?></div>
                            </div>
                            <div>
                                <div style="color:#475569; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Country / State / City</div>
                                <div style="color:#94a3b8;"><?= esc(($intern['country'] ?? 'India') . ' / ' . ($intern['state'] ?? '') . ' / ' . ($intern['city'] ?? $intern['district'] ?? '')) ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="reset" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; padding:12px 24px; border-radius:8px; cursor:pointer; font-family:'Rajdhani',sans-serif; font-weight:700;">
                            <i class="fa-solid fa-rotate-left"></i> RESET
                        </button>
                        <button type="submit" class="btn-primary" style="padding:12px 30px; font-family:'Rajdhani',sans-serif; font-weight:800; letter-spacing:1px;">
                            <i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewPhoto').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function copyToClipboard(elementId, btn) {
    const text = document.getElementById(elementId).innerText.trim();
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.background = 'rgba(16,185,129,0.2)';
        btn.style.borderColor = 'rgba(16,185,129,0.5)';
        btn.style.color = '#10b981';
        setTimeout(() => {
            btn.innerHTML = original;
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2200);
    }).catch(() => {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = original || '<i class="fa-solid fa-copy"></i> Copy'; }, 2200);
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
