<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Intern Detail | ' . SITE_NAME;

try {
    $pdo->exec("ALTER TABLE interns ADD COLUMN batch VARCHAR(50) DEFAULT NULL");
} catch(PDOException $e) {}

$intern_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
$stmt->execute([$intern_id]);
$intern = $stmt->fetch();

if (!$intern) {
    $_SESSION['toast_msg'] = "Intern not found.";
    $_SESSION['toast_type'] = "error";
    header("Location: interns.php");
    exit;
}

$referred_interns = [];
$my_ref_code = $intern['intern_id'] ?? '';
if (!empty($my_ref_code)) {
    try {
        $ref_stmt = $pdo->prepare("SELECT id, intern_id, name, college, course, batch, status, created_at FROM interns WHERE referral_id = ? ORDER BY created_at DESC");
        $ref_stmt->execute([$my_ref_code]);
        $referred_interns = $ref_stmt->fetchAll();
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }

    if ($_POST['action'] === 'update') {
        $allowed_statuses = ['Active', 'Suspended', 'Pending'];
        $new_status = in_array($_POST['status'] ?? '', $allowed_statuses) ? $_POST['status'] : $intern['status'];
        $new_batch = !empty($_POST['batch']) ? $_POST['batch'] : null;

        $name = trim($_POST['name'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender = trim($_POST['gender'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $college = trim($_POST['college'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        
        $father_occupation = trim($_POST['father_occupation'] ?? '');
        $edu_state = trim($_POST['edu_state'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $semester = (int)($_POST['semester'] ?? 0);
        $high_school_percentage = trim($_POST['high_school_percentage'] ?? '');
        $higher_secondary_percentage = trim($_POST['higher_secondary_percentage'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $postal_address = trim($_POST['postal_address'] ?? '');

        $upd = $pdo->prepare("UPDATE interns SET status = ?, batch = ?, name = ?, father_name = ?, dob = ?, gender = ?, email = ?, mobile = ?, state = ?, city = ?, college = ?, course = ?, specialization = ?, father_occupation = ?, edu_state = ?, university = ?, semester = ?, high_school_percentage = ?, higher_secondary_percentage = ?, country = ?, pincode = ?, postal_address = ? WHERE id = ?");
        $upd->execute([
            $new_status, $new_batch, $name, $father_name, $dob, $gender, $email, $mobile, $state, $city, $college, $course, $specialization,
            $father_occupation, $edu_state, $university, $semester, $high_school_percentage, $higher_secondary_percentage, $country, $pincode, $postal_address,
            $intern_id
        ]);

        $_SESSION['toast_msg'] = "Intern profile updated successfully.";
        $_SESSION['toast_type'] = "success";
        header("Location: intern_detail.php?id=" . intval($intern_id));
        exit;
    }

    if ($_POST['action'] === 'send_email') {
        $email_subject = trim($_POST['email_subject'] ?? '');
        $email_body    = trim($_POST['email_body'] ?? '');

        if (empty($email_subject) || empty($email_body)) {
            $_SESSION['toast_msg'] = "Email subject and message cannot be empty.";
            $_SESSION['toast_type'] = "error";
        } else {
            $sent = send_custom_email($intern['email'], $email_subject, $email_body);
            if ($sent) {
                log_audit($pdo, $_SESSION['user_id'] ?? 0, 'Admin', 'Manual email sent to intern #' . $intern_id . ' — Subject: ' . $email_subject);
                $_SESSION['toast_msg'] = "✅ Email sent successfully to " . $intern['email'] . ".";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "⚠️ Failed to send email. Check SMTP configuration.";
                $_SESSION['toast_type'] = "error";
            }
        }
        header("Location: intern_detail.php?id=" . intval($intern_id));
        exit;
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Intern Profile</h2>
            </div>
            <div class="topbar-right">
                <a href="interns.php" class="btn-primary" style="background-color: #475569; text-decoration: none;">&larr; Back</a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 25px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <i class="fa-solid fa-user-graduate" style="color: var(--accent-highlight);"></i> <?= esc($intern['name']) ?> (Intern ID: <?= esc($intern['id']) ?>)
                </h3>
                
                <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update">
                    
                    <div style="text-align: center; margin-bottom: 30px;">
                        <?php
                        $u_photo = $intern['profile_photo'] ?? '';
                        $u_name = $intern['name'] ?? 'Intern';
                        if (!empty($u_photo) && file_exists(__DIR__ . '/../' . $u_photo)) {
                            $photo_url = '../' . $u_photo;
                        } else {
                            $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($u_name) . "&background=00f3ff&color=000&bold=true";
                        }
                        ?>
                        <img src="<?= $photo_url ?>" alt="Intern Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #00f3ff; padding: 3px; background: #0f172a;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Registration Date</label>
                            <input type="text" class="form-control" value="<?= date('d M Y - h:i A', strtotime($intern['created_at'])) ?>" disabled style="color: #10b981; font-weight: bold; background: rgba(16,185,129,0.05); border-color: rgba(16,185,129,0.2);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= esc($intern['name']) ?>" required style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?= esc($intern['father_name'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Father's Occupation</label>
                            <input type="text" name="father_occupation" class="form-control" value="<?= esc($intern['father_occupation'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?= esc($intern['dob'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff; line-height: 1.5;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control" style="background: rgba(255,255,255,0.05); color: #fff;">
                                <option value="" style="background: #0f172a; color: #fff;">Select Gender</option>
                                <option value="Male" <?= ($intern['gender'] ?? '') == 'Male' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Male</option>
                                <option value="Female" <?= ($intern['gender'] ?? '') == 'Female' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Female</option>
                                <option value="Transgender" <?= ($intern['gender'] ?? '') == 'Transgender' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Transgender</option>
                                <option value="Other" <?= ($intern['gender'] ?? '') == 'Other' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= esc($intern['email']) ?>" required style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control" value="<?= esc($intern['mobile']) ?>" required style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        
                        
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= esc($intern['country'] ?? 'India') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State / Province</label>
                            <input type="text" name="state" class="form-control" value="<?= esc($intern['state'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">District / City</label>
                            <input type="text" name="city" class="form-control" value="<?= esc($intern['city'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pin code / Zip code</label>
                            <input type="text" name="pincode" class="form-control" value="<?= esc($intern['pincode'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Postal Address</label>
                            <textarea name="postal_address" class="form-control" rows="2" style="background: rgba(255,255,255,0.05); color: #fff;"><?= esc($intern['postal_address'] ?? '') ?></textarea>
                        </div>

                        
                        <div class="form-group">
                            <label class="form-label">University State</label>
                            <input type="text" name="edu_state" class="form-control" value="<?= esc($intern['edu_state'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">University Name</label>
                            <input type="text" name="university" class="form-control" value="<?= esc($intern['university'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">College Name *</label>
                            <input type="text" name="college" class="form-control" value="<?= esc($intern['college']) ?>" required style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Course / Degree</label>
                            <input type="text" name="course" class="form-control" value="<?= esc($intern['course'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" value="<?= esc($intern['specialization'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-control" style="background: rgba(255,255,255,0.05); color: #fff;">
                                <option value="" style="background: #0f172a; color: #fff;">Select Semester</option>
                                <?php for($sem=1; $sem<=8; $sem++): ?>
                                    <option value="<?= $sem ?>" <?= ($intern['semester'] ?? 0) == $sem ? 'selected' : '' ?> style="background: #0f172a; color: #fff;"><?= $sem ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">High School % (10th)</label>
                            <input type="text" name="high_school_percentage" class="form-control" value="<?= esc($intern['high_school_percentage'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Higher Secondary % (12th)</label>
                            <input type="text" name="higher_secondary_percentage" class="form-control" value="<?= esc($intern['higher_secondary_percentage'] ?? '') ?>" style="background: rgba(255,255,255,0.05); color: #fff;">
                        </div>
                        
                        <?php if (!empty($intern['resume_path'])): ?>
                            <div class="form-group" style="grid-column: 1 / -1; align-self: center; margin-top: 10px;">
                                <label class="form-label">Resume / CV Document</label>
                                <a href="../<?= esc($intern['resume_path']) ?>" target="_blank" class="btn-primary" style="display: inline-block; background-color: #10b981; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600;">
                                    <i class="fa-solid fa-file-pdf"></i> Download/View Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <div class="form-group">
                            <label class="form-label">Account Status</label>
                            <select name="status" class="form-control" required style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-color); border-radius: 8px;">
                                <option value="Active" <?= $intern['status'] == 'Active' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Active</option>
                                <option value="Suspended" <?= $intern['status'] == 'Suspended' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Suspended</option>
                                <option value="Pending" <?= $intern['status'] == 'Pending' ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">Pending Review</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assigned Batch</label>
                            <select name="batch" class="form-control" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-color); border-radius: 8px;">
                                <option value="" <?= empty($intern['batch']) ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">-- Not Assigned / None --</option>
                                <?php 
                                $admin_batches = [];
                                $ref_time = time();
                                for ($i = -6; $i <= 18; $i++) {
                                    $timestamp = strtotime("$i month", $ref_time);
                                    $admin_batches[] = date('F Y', $timestamp) . " Batch";
                                }
                                $current_batch = $intern['batch'] ?? '';
                                if (!empty($current_batch) && !in_array($current_batch, $admin_batches)) {
                                    $admin_batches[] = $current_batch;
                                }
                                foreach ($admin_batches as $m): 
                                ?>
                                    <option value="<?= esc($m) ?>" <?= ($intern['batch'] ?? '') == $m ? 'selected' : '' ?> style="background: #0f172a; color: #fff;"><?= esc($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 20px; text-align: right;">
                        <button type="submit" class="btn-primary" style="background-color: #6366f1;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            
            <div class="glass-panel" style="max-width: 800px; margin: 28px auto 0; border: 1px solid rgba(99,102,241,0.35); background: rgba(99,102,241,0.04);">
                <h3 style="margin-bottom: 6px; color: var(--text-primary); border-bottom: 1px solid rgba(99,102,241,0.25); padding-bottom: 14px; display:flex; align-items:center; gap:10px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background:rgba(99,102,241,0.18); box-shadow:0 0 12px rgba(99,102,241,0.45);">
                        <i class="fa-solid fa-envelope-open-text" style="color:#818cf8; font-size:15px;"></i>
                    </span>
                    <span>Send Manual Email</span>
                    <span style="margin-left:auto; font-size:12px; font-weight:400; color:#94a3b8; background:rgba(99,102,241,0.12); border:1px solid rgba(99,102,241,0.25); padding:3px 10px; border-radius:20px;">
                        To: <?= esc($intern['email']) ?>
                    </span>
                </h3>
                <p style="font-size:13px; color:#94a3b8; margin-bottom:22px;">
                    Compose a direct message to this intern. The email will be sent from the portal's official address.
                </p>

                <form method="POST" id="emailComposeForm">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="send_email">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="email_subject" style="display:flex; align-items:center; gap:7px;">
                            <i class="fa-solid fa-tag" style="color:#818cf8;"></i> Email Subject
                        </label>
                        <input
                            type="text"
                            id="email_subject"
                            name="email_subject"
                            class="form-control"
                            placeholder="e.g. Important Update Regarding Your Internship"
                            maxlength="200"
                            required
                            style="border-color: rgba(99,102,241,0.4); background: rgba(99,102,241,0.05);"
                        >
                    </div>

                    <div class="form-group" style="margin-bottom: 22px;">
                        <label class="form-label" for="email_body" style="display:flex; align-items:center; gap:7px;">
                            <i class="fa-solid fa-align-left" style="color:#818cf8;"></i> Message Body
                        </label>
                        <textarea
                            id="email_body"
                            name="email_body"
                            class="form-control"
                            rows="7"
                            placeholder="Write your message here..."
                            required
                            style="border-color: rgba(99,102,241,0.4); background: rgba(99,102,241,0.05); resize: vertical; font-family: inherit; line-height: 1.6;"
                        ></textarea>
                        <div style="text-align:right; font-size:12px; color:#64748b; margin-top:5px;">
                            <span id="charCount">0</span> characters
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; border-top: 1px solid rgba(99,102,241,0.2); padding-top: 18px;">
                        <div style="font-size:12px; color:#64748b;">
                            <i class="fa-solid fa-shield-halved" style="color:#818cf8;"></i>
                            Sent via secure portal SMTP
                        </div>
                        <button type="submit" id="sendEmailBtn" class="btn-primary" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 0 18px rgba(99,102,241,0.4); display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-paper-plane"></i> Send Email
                        </button>
                    </div>
                </form>
            </div>

            
            <div class="glass-panel" style="max-width: 800px; margin: 28px auto 0; border: 1px solid rgba(168,85,247,0.35); background: rgba(168,85,247,0.02);">
                <h3 style="margin-bottom: 6px; color: var(--text-primary); border-bottom: 1px solid rgba(168,85,247,0.25); padding-bottom: 14px; display:flex; align-items:center; gap:10px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background:rgba(168,85,247,0.15); box-shadow:0 0 12px rgba(168,85,247,0.3);">
                        <i class="fa-solid fa-users-line" style="color:#a855f7; font-size:15px;"></i>
                    </span>
                    <span>Referral History</span>
                    <span style="margin-left:auto; font-size:12px; font-weight:700; color:#a855f7; background:rgba(168,85,247,0.12); border:1px solid rgba(168,85,247,0.25); padding:3px 12px; border-radius:20px;">
                        <?= count($referred_interns) ?> Referral<?= count($referred_interns) !== 1 ? 's' : '' ?>
                    </span>
                </h3>
                <p style="font-size:13px; color:#94a3b8; margin-bottom:20px;">
                    List of registered interns who joined using this intern's referral code <code style="background:rgba(168,85,247,0.1); color:#a855f7; padding:2px 6px; border-radius:4px; font-family:monospace;"><?= esc($my_ref_code) ?></code>:
                </p>

                <?php if (empty($referred_interns)): ?>
                    <div style="text-align:center; padding:30px; color:#64748b; font-size:0.85rem;">
                        <i class="fa-solid fa-user-plus" style="font-size:2rem; margin-bottom:10px; display:block; opacity:0.35;"></i>
                        No referrals recorded for this intern yet.
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.06); text-align:left;">
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem;">Intern Name / ID</th>
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem;">College</th>
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem;">Batch</th>
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem;">Date Joined</th>
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem;">Status</th>
                                    <th style="padding:10px; color:#475569; font-weight:700; text-transform:uppercase; font-size:0.7rem; text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referred_interns as $ref): ?>
                                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                                        <td style="padding:12px 10px;">
                                            <div style="font-weight:bold; color:#fff;"><?= esc($ref['name']) ?></div>
                                            <div style="font-size:0.72rem; color:#00f3ff; font-family:monospace;"><?= esc($ref['intern_id']) ?></div>
                                        </td>
                                        <td style="padding:12px 10px; color:#94a3b8;"><?= esc($ref['college'] ?? '—') ?></td>
                                        <td style="padding:12px 10px;">
                                            <span style="background:rgba(0,243,255,0.05); color:#00f3ff; border:1px solid rgba(0,243,255,0.15); padding:2px 6px; border-radius:4px; font-size:0.72rem;">
                                                <?= esc($ref['batch'] ?: 'Unassigned') ?>
                                            </span>
                                        </td>
                                        <td style="padding:12px 10px; color:#94a3b8;"><?= date('d M Y', strtotime($ref['created_at'])) ?></td>
                                        <td style="padding:12px 10px;">
                                            <?php
                                            $st = $ref['status'] ?? 'Active';
                                            $color = ($st === 'Active') ? '#10b981' : (($st === 'Suspended') ? '#ef4444' : '#eab308');
                                            ?>
                                            <span style="color:<?= $color ?>; font-weight:bold; font-size:0.75rem;"><?= $st ?></span>
                                        </td>
                                        <td style="padding:12px 10px; text-align:right;">
                                            <a href="intern_detail.php?id=<?= $ref['id'] ?>" class="btn-primary" style="padding:4px 8px; font-size:0.7rem; background:#475569; border:none; border-radius:4px; text-decoration:none; color:#fff;">
                                                <i class="fa-solid fa-eye"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script>
(function() {
    const bodyEl   = document.getElementById('email_body');
    const countEl  = document.getElementById('charCount');
    const sendBtn  = document.getElementById('sendEmailBtn');
    const form     = document.getElementById('emailComposeForm');

    if (bodyEl && countEl) {
        bodyEl.addEventListener('input', function() {
            countEl.textContent = this.value.length;
        });
    }

    if (form && sendBtn) {
        form.addEventListener('submit', function() {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        });
    }
}());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
