<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'My Profile | Officer Portal';
$officer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $name = trim($_POST['name']);
        $badge_number = trim($_POST['badge_number']);
        $designation = trim($_POST['designation']);
        $district = trim($_POST['district']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        
        $upd = $pdo->prepare("UPDATE officers SET name=?, badge_number=?, designation=?, district=?, email=?, mobile=? WHERE id=?");
        $upd->execute([$name, $badge_number, $designation, $district, $email, $mobile, $officer_id]);
        
        $_SESSION['toast_msg'] = "Profile updated successfully!";
        $_SESSION['toast_type'] = "success";
        header("Location: profile.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if ($_FILES['photo']['size'] <= 2 * 1024 * 1024 && in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_name = uniqid('officer_', true) . '.' . $ext;
            
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            
            if (in_array($mime, ['image/jpeg', 'image/png'])) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_name)) {
                    $photo_path = 'uploads/profiles/' . $new_name;
                    $upd = $pdo->prepare("UPDATE officers SET profile_photo = ? WHERE id = ?");
                    $upd->execute([$photo_path, $officer_id]);
                    $_SESSION['toast_msg'] = "Profile photo updated successfully!";
                    $_SESSION['toast_type'] = "success";
                    header("Location: profile.php");
                    exit;
                }
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM officers WHERE id = ?");
$stmt->execute([$officer_id]);
$officer = $stmt->fetch();

// Referral System Logic
$my_referral_code = $officer['officer_id'] ?? '';
$referral_link = BASE_URL . '/officer/signup.php?ref=' . urlencode($my_referral_code);

$referred_officers = [];
$referred_interns = [];

if (!empty($my_referral_code)) {
    $ref_stmt = $pdo->prepare("SELECT name, badge_number, rank, designation, district, created_at FROM officers WHERE referral_id = ? ORDER BY created_at DESC");
    $ref_stmt->execute([$my_referral_code]);
    $referred_officers = $ref_stmt->fetchAll();

    $ref_stmt2 = $pdo->prepare("SELECT name, college, course, batch, created_at FROM interns WHERE referral_id = ? ORDER BY created_at DESC");
    $ref_stmt2->execute([$my_referral_code]);
    $referred_interns = $ref_stmt2->fetchAll();
}

$my_referral_points = 0;
try {
    $pdo->exec("ALTER TABLE officers ADD COLUMN referral_points DECIMAL(4,1) DEFAULT 0");
} catch (PDOException $e) {}

$s = $pdo->prepare("SELECT COALESCE(referral_points, 0) FROM officers WHERE id=?");
$s->execute([$officer_id]);
$my_referral_points = (float)$s->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h2>Officer Identity Management</h2>
        </header>

        <div class="dashboard-content">
            <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
                
                <div class="glass-panel" style="text-align: center; border-top: 4px solid var(--danger);">
                    <?php
                    $name_enc = urlencode($officer['name'] ?? 'Officer');
                    $avatar_url = "https://ui-avatars.com/api/?name={$name_enc}&background=059669&color=fff&size=150";
                    $photo_src = !empty($officer['profile_photo']) ? '../' . ltrim($officer['profile_photo'], '/') : $avatar_url;
                    ?>
                    <img src="<?= $photo_src ?>" style="width: 150px; height: 150px; border-radius: 8px; object-fit: cover; border: 2px solid var(--border-color); margin-bottom: 20px;">
                    <h3 style="margin-bottom: 5px;"><?= esc($officer['name']) ?></h3>
                    <p style="color: var(--accent-highlight); font-weight: 700; margin-bottom: 15px;"><?= esc($officer['rank']) ?></p>
                    
                    <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px;">
                        <i class="fa-solid fa-circle-check"></i> ACTIVE SERVICE
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" style="border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px; text-align: left;">
                        <?php csrf_field(); ?>
                        <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 5px; text-transform: uppercase;">Update Profile Photo</label>
                        <input type="file" name="photo" accept=".png,.jpg,.jpeg" required style="width: 100%; font-size: 0.8rem; margin-bottom: 10px; color: #94a3b8;">
                        <button type="submit" class="btn-primary" style="width: 100%; padding: 6px; font-size: 0.8rem; background: #3b82f6;">
                            <i class="fa-solid fa-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>

                
                <div class="glass-panel" id="profileViewPanel">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 class="section-heading" style="margin: 0; border: none; padding: 0;">Service Credentials</h3>
                        <button onclick="document.getElementById('profileViewPanel').style.display='none'; document.getElementById('profileEditPanel').style.display='block';" class="btn-primary" style="background: #3b82f6; padding: 8px 16px; font-size: 0.85rem;"><i class="fa-solid fa-pen-to-square"></i> Edit Profile</button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Unique ID</label>
                            <div style="font-family: monospace; font-weight: bold; font-size: 1.1rem; color: var(--text-primary);"><?= esc($officer['officer_id']) ?></div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Officer Name</label>
                            <div style="font-weight: bold; font-size: 1.1rem; color: var(--text-primary);"><?= esc($officer['name']) ?></div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Badge Number</label>
                            <div style="font-weight: bold; color: var(--text-primary);"><?= esc($officer['badge_number']) ?></div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Designation</label>
                            <div style="font-weight: bold; color: var(--text-primary);"><?= esc($officer['designation']) ?></div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Posting District</label>
                            <div style="font-weight: bold; color: var(--text-primary);"><?= esc($officer['district']) ?></div>
                        </div>
                    </div>

                    <h3 class="section-heading" style="margin-top: 30px;">Contact Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Official Email</label>
                            <div style="font-weight: bold; color: var(--text-primary);"><?= esc($officer['email']) ?></div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Mobile Line</label>
                            <div style="font-weight: bold; color: var(--text-primary);"><?= esc($officer['mobile']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="glass-panel" id="profileEditPanel" style="display: none; border-left: 4px solid #3b82f6;">
                    <form method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="section-heading" style="margin: 0; border: none; padding: 0;">Edit Service Credentials</h3>
                            <div>
                                <button type="button" onclick="document.getElementById('profileEditPanel').style.display='none'; document.getElementById('profileViewPanel').style.display='block';" class="btn-primary" style="background: rgba(255,255,255,0.1); padding: 8px 16px; font-size: 0.85rem; margin-right: 10px;">Cancel</button>
                                <button type="submit" class="btn-primary" style="background: var(--success); padding: 8px 16px; font-size: 0.85rem;"><i class="fa-solid fa-floppy-disk"></i> Save Details</button>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Unique ID (Readonly)</label>
                                <input type="text" class="form-control" value="<?= esc($officer['officer_id']) ?>" readonly style="background: rgba(0,0,0,0.2); color: #94a3b8; font-family: monospace;">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Officer Name</label>
                                <input type="text" name="name" class="form-control" value="<?= esc($officer['name']) ?>" required>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Badge Number</label>
                                <input type="text" name="badge_number" class="form-control" value="<?= esc($officer['badge_number']) ?>" required>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Designation</label>
                                <input type="text" name="designation" class="form-control" value="<?= esc($officer['designation']) ?>" required>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Posting District</label>
                                <input type="text" name="district" class="form-control" value="<?= esc($officer['district']) ?>" required>
                            </div>
                        </div>

                        <h3 class="section-heading" style="margin-top: 30px;">Contact Information</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Official Email</label>
                                <input type="email" name="email" class="form-control" value="<?= esc($officer['email']) ?>" required>
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; color: var(--text-muted);">Mobile Line</label>
                                <input type="text" name="mobile" class="form-control" value="<?= esc($officer['mobile']) ?>" required>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ====== REFERRAL SYSTEM CARD ====== -->
            <style>
                .ref-card { background: rgba(10,10,20,0.6); border: 1px solid rgba(99,102,241,0.25); border-radius: 12px; padding: 28px; margin-top: 30px; }
                .ref-code-box { display: flex; align-items: center; gap: 12px; background: rgba(0,0,0,0.4); border: 1px dashed rgba(99,102,241,0.4); border-radius: 8px; padding: 14px 18px; margin: 12px 0; }
                .ref-code-text { font-family: 'Share Tech Mono', monospace; font-size: 1.1rem; font-weight: 700; color: #6366f1; letter-spacing: 3px; flex: 1; }
                .ref-link-box { display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; }
                .ref-link-text { font-size: 0.8rem; color: #94a3b8; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                .ref-copy-btn { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #818cf8; padding: 6px 14px; border-radius: 6px; font-size: 0.78rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
                .ref-copy-btn:hover { background: rgba(99,102,241,0.35); color: #fff; }
                .ref-stat-badge { background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 8px; padding: 10px 16px; text-align: center; min-width: 90px; }
                .ref-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; margin-top: 12px; }
                .ref-table th { color: #475569; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,0.06); text-align: left; font-weight: 600; }
                .ref-table td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.04); color: #94a3b8; vertical-align: middle; }
                .ref-table tr:last-child td { border-bottom: none; }
                .ref-table tr:hover td { background: rgba(99,102,241,0.04); }
                .ref-tab-btn { background: transparent; border: none; color: #64748b; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 16px; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
                .ref-tab-btn.active { color: #6366f1; border-bottom-color: #6366f1; }
            </style>

            <div class="ref-card">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                    <i class="fa-solid fa-share-nodes" style="font-size:1.4rem; color:#6366f1;"></i>
                    <div>
                        <div style="font-family:'Rajdhani',sans-serif; font-size:1rem; font-weight:800; color:#6366f1; letter-spacing:1px; text-transform:uppercase;">My Referral Code</div>
                        <div style="font-size:0.75rem; color:#475569;">Share to recruit officers & interns — earn referral points on every verified registration</div>
                    </div>
                    <?php if ($my_referral_points > 0): ?>
                    <div class="ref-stat-badge" style="margin-left:auto;">
                        <div style="font-family:'Share Tech Mono',monospace; color:#10b981; font-weight:bold; font-size:1.1rem;"><?= number_format($my_referral_points, 1) ?> pts</div>
                        <div style="font-size:0.65rem; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">Referral Points</div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($officer['referral_id'])): ?>
                    <div class="ref-stat-badge">
                        <div style="font-size:0.7rem; color:#00f3ff; font-weight:700;">Joined via Referral</div>
                        <div style="font-family:'Share Tech Mono',monospace; font-size:0.65rem; color:#475569;"><?= esc($officer['referral_id']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Code + Link -->
                <div class="ref-code-box">
                    <span class="ref-code-text" id="offRefCodeText"><?= esc($my_referral_code) ?></span>
                    <button class="ref-copy-btn" onclick="copyRefCode()"><i class="fa-solid fa-copy"></i> Copy Code</button>
                </div>
                <div style="font-size:0.7rem; color:#475569; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Referral Link (Officers Signup)</div>
                <div class="ref-link-box">
                    <span class="ref-link-text" id="offRefLinkText"><?= esc($referral_link) ?></span>
                    <button class="ref-copy-btn" onclick="copyRefLink()"><i class="fa-solid fa-link"></i> Copy Link</button>
                </div>

                <!-- Referral History Tabs -->
                <?php $total_referred = count($referred_officers) + count($referred_interns); ?>
                <?php if ($total_referred > 0): ?>
                <div style="margin-top:24px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px; border-bottom:1px solid rgba(255,255,255,0.06);">
                        <i class="fa-solid fa-users-line" style="color:#6366f1;"></i>
                        <span style="color:#6366f1; font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Referral History</span>
                        <span style="margin-left:auto; font-size:0.75rem; color:#475569;"><?= $total_referred ?> Total Referral<?= $total_referred !== 1 ? 's' : '' ?></span>
                    </div>

                    <!-- Tab switcher -->
                    <div style="margin-bottom:12px;">
                        <button class="ref-tab-btn active" id="tabOfficers" onclick="showTab('officers')">
                            <i class="fa-solid fa-shield-halved"></i> Officers (<?= count($referred_officers) ?>)
                        </button>
                        <button class="ref-tab-btn" id="tabInterns" onclick="showTab('interns')">
                            <i class="fa-solid fa-user-graduate"></i> Interns (<?= count($referred_interns) ?>)
                        </button>
                    </div>

                    <!-- Officers tab -->
                    <div id="refTabOfficers">
                        <?php if (empty($referred_officers)): ?>
                            <div style="font-size:0.85rem; color:#475569; padding:10px 0;">No officers referred yet.</div>
                        <?php else: ?>
                        <table class="ref-table">
                            <thead><tr>
                                <th>#</th><th>Name</th><th>Badge No.</th><th>Rank / Designation</th><th>District</th><th>Joined</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($referred_officers as $i => $ro): ?>
                            <tr>
                                <td style="color:#475569;"><?= $i + 1 ?></td>
                                <td style="color:#e2e8f0; font-weight:600;"><?= esc($ro['name']) ?></td>
                                <td style="font-family:monospace; color:#6366f1;"><?= esc($ro['badge_number']) ?></td>
                                <td><?= esc($ro['rank']) ?><?= $ro['designation'] ? ' · ' . esc($ro['designation']) : '' ?></td>
                                <td><?= esc($ro['district']) ?></td>
                                <td style="color:#10b981; font-size:0.78rem;"><?= date('d M Y', strtotime($ro['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Interns tab -->
                    <div id="refTabInterns" style="display:none;">
                        <?php if (empty($referred_interns)): ?>
                            <div style="font-size:0.85rem; color:#475569; padding:10px 0;">No interns referred yet.</div>
                        <?php else: ?>
                        <table class="ref-table">
                            <thead><tr>
                                <th>#</th><th>Name</th><th>College</th><th>Course</th><th>Batch</th><th>Joined</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($referred_interns as $i => $ri): ?>
                            <tr>
                                <td style="color:#475569;"><?= $i + 1 ?></td>
                                <td style="color:#e2e8f0; font-weight:600;"><?= esc($ri['name']) ?></td>
                                <td><?= esc($ri['college']) ?></td>
                                <td><?= esc($ri['course']) ?></td>
                                <td style="color:#a855f7;"><?= esc($ri['batch']) ?></td>
                                <td style="color:#10b981; font-size:0.78rem;"><?= date('d M Y', strtotime($ri['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div style="margin-top:18px; padding:14px; background:rgba(0,0,0,0.2); border-radius:8px; font-size:0.85rem; color:#475569; text-align:center;">
                    <i class="fa-solid fa-user-plus" style="font-size:1.4rem; color:#6366f1; display:block; margin-bottom:8px;"></i>
                    No one has used your referral code yet.<br>
                    <span style="font-size:0.75rem;">Share your code or link to start earning referral points!</span>
                </div>
                <?php endif; ?>
            </div>
            <!-- ====== /REFERRAL SYSTEM CARD ====== -->

        </div>

    </main>
</div>

<script>
function copyRefCode() {
    const t = document.getElementById('offRefCodeText').innerText;
    navigator.clipboard.writeText(t).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.color = '#10b981';
        setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy Code'; btn.style.color = ''; }, 2000);
    });
}
function copyRefLink() {
    const t = document.getElementById('offRefLinkText').innerText;
    navigator.clipboard.writeText(t).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.color = '#10b981';
        setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-link"></i> Copy Link'; btn.style.color = ''; }, 2000);
    });
}
function showTab(tab) {
    document.getElementById('refTabOfficers').style.display = tab === 'officers' ? '' : 'none';
    document.getElementById('refTabInterns').style.display  = tab === 'interns'  ? '' : 'none';
    document.getElementById('tabOfficers').classList.toggle('active', tab === 'officers');
    document.getElementById('tabInterns').classList.toggle('active',  tab === 'interns');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
