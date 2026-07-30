<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$portal = 'officer';
$page_title = 'Officer Registration | ' . SITE_NAME;

$locations = require __DIR__ . '/../assets/data/locations.php';

$errors = [];
$success = false;

$ranks = [
    'Constable','Head Constable','ASI (Assistant Sub-Inspector)',
    'SI (Sub-Inspector)','Inspector','DSP (Deputy Superintendent)',
    'SP (Superintendent)','ASP (Additional Superintendent)',
    'DIG (Deputy Inspector General)','IG (Inspector General)',
    'ADG (Additional Director General)','DGP (Director General)',
    'Cyber Analyst','Technical Officer','Forensic Expert'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    $name = trim($_POST['name'] ?? '');
    $badge_number = trim($_POST['badge_number'] ?? '');
    $rank = trim($_POST['rank'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $joining_date = trim($_POST['joining_date'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    
    if (empty($name) || empty($badge_number) || empty($rank) || empty($designation) || empty($district) || empty($mobile) || empty($email) || empty($joining_date)) {
        $errors[] = "Please fill in all required fields.";
    }

    $consent = isset($_POST['consent']) ? true : false;
    if (!$consent) {
        $errors[] = "You must agree to the Official LEA Authorization & Terms of Service.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = "Password must be between 8 and 72 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    
    $stmt = $pdo->prepare("SELECT id FROM officers WHERE mobile = ? OR email = ? OR badge_number = ?");
    $stmt->execute([$mobile, $email, $badge_number]);
    if ($stmt->fetch()) {
        $errors[] = "Badge number, mobile, or email already registered.";
    }

    
    $photo_path = '';
    if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Photo exceeds 2MB limit.";
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors[] = "Only JPG/PNG permitted.";
        } else {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_name = uniqid('officer_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_name)) {
                $photo_path = 'uploads/profiles/' . $new_name;
            } else {
                $errors[] = "Failed to upload photo.";
            }
        }
    }

    
    if (empty($errors)) {
        try {
            
            $cols_to_add = [
                "otp VARCHAR(10) DEFAULT NULL",
                "otp_expiry DATETIME DEFAULT NULL",
                "is_verified TINYINT(1) DEFAULT 0",
                "state VARCHAR(100) DEFAULT NULL AFTER designation",
                "referral_id VARCHAR(50) DEFAULT NULL"
            ];
            foreach ($cols_to_add as $col_def) {
                try {
                    $pdo->exec("ALTER TABLE officers ADD COLUMN $col_def");
                } catch (PDOException $e) {
                    
                }
            }

            $referral_id = trim($_POST['referral_id'] ?? '');
            if (!empty($referral_id)) {
                $ref_check = $pdo->prepare("SELECT officer_id FROM officers WHERE officer_id = ? LIMIT 1");
                $ref_check->execute([strtoupper($referral_id)]);
                $valid_ref = $ref_check->fetchColumn();
                if ($valid_ref) {
                    $referral_id = $valid_ref;
                } else {
                    $ref_check_intern = $pdo->prepare("SELECT intern_id FROM interns WHERE intern_id = ? LIMIT 1");
                    $ref_check_intern->execute([strtoupper($referral_id)]);
                    $valid_ref_intern = $ref_check_intern->fetchColumn();
                    if ($valid_ref_intern) {
                        $referral_id = $valid_ref_intern;
                    } else {
                        $referral_id = null;
                    }
                }
            } else {
                $referral_id = null;
            }

            $officer_id = 'OFR-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            
            require_once __DIR__ . '/../includes/functions.php';
            $otp = generate_otp(6);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $pdo->prepare("INSERT INTO officers (officer_id, name, badge_number, `rank`, designation, state, district, mobile, email, password_hash, profile_photo, joining_date, status, otp, otp_expiry, is_verified, referral_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, 0, ?)");
            $stmt->execute([$officer_id, $name, $badge_number, $rank, $designation, $state, $district, $mobile, $email, $password_hash, $photo_path, $joining_date, $otp, $otp_expiry, $referral_id]);

            
            if (send_otp_email($email, $otp, "Your OTP for Officer Registration")) {
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_type'] = 'officer';
                header("Location: otp_verify.php");
                exit;
            } else {
                $errors[] = "Account created, but failed to send OTP email. Please try to login to resend.";
            }

        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.signup-container {
    max-width: 850px;
    margin: 0 auto;
    padding: 2rem;
}
.signup-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.signup-section:last-child { border-bottom: none; }
.section-title {
    font-size: 1.1rem;
    color: #ef4444;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pending-card {
    text-align: center;
    padding: 40px 20px;
}
.pending-card i {
    font-size: 4rem;
    color: #f59e0b;
    margin-bottom: 20px;
}
</style>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="signup-container" style="width: 100%;">
        <div class="glass-panel" style="width: 100%; border-top: 4px solid #ef4444;">

            <?php if ($success): ?>
                
                <div class="pending-card">
                    <i class="fa-solid fa-clock"></i>
                    <h2 style="color: var(--text-primary); margin-bottom: 10px;"><?= __('reg_success') ?></h2>
                    <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 500px; margin: 0 auto 25px;">
                        <?= __('pending_approval_msg') ?>
                    </p>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px dashed #f59e0b; padding: 15px; border-radius: 8px; max-width: 400px; margin: 0 auto 25px;">
                        <p style="color: var(--text-muted); font-size: 0.9rem;"><i class="fa-solid fa-shield-halved" style="color: #f59e0b;"></i> Please contact your superior or HQ Admin for expedited verification.</p>
                    </div>
                    <a href="login.php" class="btn-primary" style="padding: 12px 30px;"><i class="fa-solid fa-arrow-left"></i> <?= __('login_here') ?></a>
                </div>
            <?php else: ?>
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <img src="../assets/images/logo.png" alt="Logo" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px;">
                    <h2 style="margin-top: 10px; color: var(--text-primary);"><?= __('signup_title') ?></h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Digital Investigation — Officer Onboarding</p>
                    <p style="color: #f59e0b; font-size: 0.85rem; margin-top: 8px;"><i class="fa-solid fa-triangle-exclamation"></i> <?= __('pending_approval_msg') ?></p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div style="background: rgba(220, 38, 38, 0.15); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <ul style="color: #ef4444; list-style-type: none; padding: 0; margin: 0;">
                            <?php foreach ($errors as $error) echo "<li style='margin-bottom:5px;'><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>

                    
                    <div class="signup-section">
                        <div class="section-title"><i class="fa-solid fa-id-card"></i> <?= __('identity_section') ?></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label"><?= __('full_name') ?> *</label>
                                <input type="text" name="name" class="form-control" required value="<?= esc($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('badge_no') ?> *</label>
                                <input type="text" name="badge_number" class="form-control" required value="<?= esc($_POST['badge_number'] ?? '') ?>" placeholder="e.g. MP/CC/2024/001">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('rank') ?> *</label>
                                <select name="rank" class="form-control" required>
                                    <option value=""><?= __('view') ?></option>
                                    <?php foreach($ranks as $r): ?>
                                        <option value="<?= esc($r) ?>" <?= (($_POST['rank']??'') == $r) ? 'selected' : '' ?>><?= esc($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('unit') ?> *</label>
                                <input type="text" name="designation" class="form-control" required value="<?= esc($_POST['designation'] ?? '') ?>" placeholder="e.g. Corporate Forensics Unit">
                            </div>
                        </div>
                    </div>

                    
                    <div class="signup-section">
                        <div class="section-title"><i class="fa-solid fa-map-location-dot"></i> <?= __('posting_section') ?></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label"><?= __('state') ?> *</label>
                                <select name="state" id="stateSelect" class="form-control" required>
                                    <option value=""><?= __('state') ?></option>
                                    <?php foreach(array_keys($locations) as $s): ?>
                                        <option value="<?= esc($s) ?>" <?= (($_POST['state']??'') == $s) ? 'selected' : '' ?>><?= esc($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('district') ?> *</label>
                                <select name="district" id="districtSelect" class="form-control" required>
                                    <option value=""><?= __('district') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    
                    <div class="signup-section">
                        <div class="section-title"><i class="fa-solid fa-address-book"></i> <?= __('contact_section') ?></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label"><?= __('official_mobile') ?> *</label>
                                <input type="text" name="mobile" class="form-control" required maxlength="10" value="<?= esc($_POST['mobile'] ?? '') ?>" placeholder="10 Digits">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('official_email') ?> *</label>
                                <input type="email" name="email" class="form-control" required value="<?= esc($_POST['email'] ?? '') ?>" placeholder="e.g. name@digitalinvestigation.in">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('joining_date') ?> *</label>
                                <input type="date" name="joining_date" class="form-control" required value="<?= esc($_POST['joining_date'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Profile Photo (JPG/PNG, Max 2MB)</label>
                                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png" style="padding:10px;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Referral ID (Optional)</label>
                                <input type="text" name="referral_id" class="form-control" value="<?= esc($_POST['referral_id'] ?? $_GET['ref'] ?? '') ?>" placeholder="Enter Referral ID of who invited you">
                                <?php if (!empty($_GET['ref'])): ?>
                                <small style="color:#ef4444; font-size:0.75rem;"><i class="fa-solid fa-user-plus"></i> Referral code pre-filled from your invite link!</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div class="signup-section">
                        <div class="section-title"><i class="fa-solid fa-lock"></i> <?= __('set_password') ?></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label"><?= __('password') ?> *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= __('confirm_password') ?> *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Consent Checkbox -->
                    <div class="signup-section" style="border-bottom: none; margin-bottom: 10px; padding-bottom: 0;">
                        <div style="display: flex; align-items: flex-start; gap: 12px; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px;">
                            <input type="checkbox" name="consent" id="consentCheckbox" required style="width: 20px; height: 20px; cursor: pointer; margin-top: 3px;" <?= isset($_POST['consent']) ? 'checked' : '' ?>>
                            <label for="consentCheckbox" style="color: var(--text-primary); cursor: pointer; font-size: 0.95rem; line-height: 1.4;">
                                <strong>Official Legal Attestation & Service Agreement</strong><br>
                                I certify that I am filing this registration inside an authentic, authorized law enforcement capacity. I bind my agency and myself to use the forensic tool matrix hosted on digitalinvestigation.in strictly for validated investigative procedures conforming entirely to standard cyber laws and evidence processing protocols. 
                                <a href="javascript:void(0)" onclick="openConsentModal()" style="color: #ef4444; text-decoration: underline; font-weight: 700; transition: color 0.2s;" onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#ef4444'">
                                    [Read Full LEA Consent Form]
                                </a>.
                            </label>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 10px;">
                        <button type="submit" class="btn-primary" style="width: 260px; padding: 12px; background: #ef4444;">
                            <i class="fa-solid fa-paper-plane"></i> <?= __('submit_approval') ?>
                        </button>
                    </div>
                    <div style="margin-top: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                        <?= __('already_have_account') ?> <a href="login.php"><?= __('login_here') ?></a>
                    </div>
                    
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center;">
                        <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">
                            <i class="fa-solid fa-arrow-left-long"></i> Back to Home
                        </a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- LEA Consent Form Modal -->
            <div id="consentModal" class="cyber-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
                <div class="modal-content" style="max-width: 850px; width: 90%; background: #ffffff; padding: 0; overflow: hidden; border-radius: 12px; border: 2px solid #ef4444; box-shadow: 0 0 35px rgba(239, 68, 68, 0.4); display: flex; flex-direction: column; height: 85vh;">
                    <div style="background: #111827; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ef4444;">
                        <h3 style="color: #ffffff; margin: 0; font-family: 'Rajdhani', sans-serif; font-size: 1.3rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Official LEA Authorization & Terms</h3>
                        <span class="close" style="color: #ffffff; font-size: 2rem; cursor: pointer; line-height: 1; transition: color 0.2s;" onclick="closeConsentModal()" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#ffffff'">&times;</span>
                    </div>
                    <div style="flex-grow: 1; overflow: hidden; background: #f4f6f9;">
                        <iframe src="lea_consent_form.html" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                    <div style="background: #111827; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1);">
                        <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Please review terms carefully before proceeding.</span>
                        <button type="button" class="btn-primary" style="background: #ef4444; color: #ffffff; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'" onclick="acceptConsent()">I Agree</button>
                    </div>
                </div>
            </div>

            <script>
            function openConsentModal() {
                document.getElementById('consentModal').style.display = 'flex';
            }
            function closeConsentModal() {
                document.getElementById('consentModal').style.display = 'none';
            }
            function acceptConsent() {
                document.getElementById('consentCheckbox').checked = true;
                closeConsentModal();
            }
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('consentModal');
                if (event.target === modal) {
                    closeConsentModal();
                }
            });
            </script>

        </div>
    </div>
</div>

<script>
const locationData = <?= json_encode($locations) ?>;

const stateEl = document.getElementById('stateSelect');
const districtEl = document.getElementById('districtSelect');

if (stateEl) {
    stateEl.addEventListener('change', function() {
        const state = this.value;
        districtEl.innerHTML = '<option value="">Select District</option>';
        if (state && locationData[state]) {
            locationData[state].forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                districtEl.appendChild(opt);
            });
        }
        const saved = "<?= esc($_POST['district'] ?? '') ?>";
        if (saved) districtEl.value = saved;
    });
    if (stateEl.value) stateEl.dispatchEvent(new Event('change'));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
