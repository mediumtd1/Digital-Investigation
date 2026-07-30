<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$portal = 'intern';
$page_title = 'Intern Registration | ' . SITE_NAME;

$locations = require __DIR__ . '/../assets/data/locations.php';
$courses = require __DIR__ . '/../assets/data/courses.php';

$current_time = time();
$allowed_batches = [];
for ($i = 1; $i <= 3; $i++) {
    $timestamp = strtotime("+$i month", $current_time);
    $allowed_batches[] = date('F Y', $timestamp) . " Batch";
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        header("HTTP/1.1 403 Forbidden");
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    
    $name = trim($_POST['name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $father_occupation = trim($_POST['father_occupation'] ?? '');

    
    $edu_state = trim($_POST['edu_state'] ?? '');
    $university = ''; 
    $college = trim($_POST['college'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    if ($specialization === 'Other') {
        $specialization = trim($_POST['other_specialization'] ?? 'Other');
    }
    $semester = (int)($_POST['semester'] ?? 0);
    $high_school_percentage = trim($_POST['high_school_percentage'] ?? '');
    $higher_secondary_percentage = trim($_POST['higher_secondary_percentage'] ?? '');

    
    $country_type = trim($_POST['country_type'] ?? 'India');
    if ($country_type === 'India') {
        $country = 'India';
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['district'] ?? '');
    } else {
        $country = trim($_POST['country_custom'] ?? 'Other');
        $state = trim($_POST['state_custom'] ?? '');
        $city = trim($_POST['district_custom'] ?? '');
    }
    $postal_address = trim($_POST['postal_address'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $referral_id = trim($_POST['referral_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $batch = trim($_POST['batch'] ?? '');

    
    if (empty($name) || empty($dob) || empty($gender) || empty($father_name) || empty($email) || empty($mobile) || empty($batch)) {
        $errors[] = "Please fill in all required personal and contact fields.";
    }

    $consent = isset($_POST['consent']) ? true : false;
    if (!$consent) {
        $errors[] = "You must read and agree to the Internship Consent & Non-Disclosure Agreement (NDA).";
    }

    if ($country_type === 'India') {
        if (empty($state) || empty($city)) {
            $errors[] = "Please select Home State and Home District.";
        }
    } else {
        if (empty($country) || $country === 'Other') {
            $errors[] = "Please specify your Country Name.";
        }
    }

    if (empty($edu_state) || empty($college) || empty($course) || empty($specialization) || empty($semester)) {
        $errors[] = "Please fill in all required educational fields.";
    }

    if (!empty($batch) && !in_array($batch, $allowed_batches)) {
        $errors[] = "Registration for the selected batch is closed.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    if (strlen($password) < 8 || strlen($password) > 72 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
        $errors[] = "Password must be 8–72 characters, include 1 uppercase, 1 number, and 1 special character.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    
    $stmt = $pdo->prepare("SELECT id FROM interns WHERE mobile = ? OR email = ?");
    $stmt->execute([$mobile, $email]);
    if ($stmt->fetch()) {
        $errors[] = "Mobile number or email already registered.";
    }

    
    if (!empty($referral_id)) {
        $ref_check = $pdo->prepare("SELECT intern_id FROM interns WHERE intern_id = ? LIMIT 1");
        $ref_check->execute([strtoupper($referral_id)]);
        $valid_ref = $ref_check->fetchColumn();
        if ($valid_ref) {
            $referral_id = $valid_ref; 
        } else {
            $referral_id = ''; 
        }
    }

    
    $photo_path = '';
    if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['photo']['tmp_name'];
        $file_size = $_FILES['photo']['size'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if ($file_size > 300 * 1024) {
            $errors[] = "Profile photo exceeds the 300KB size limit.";
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors[] = "Only JPG and PNG images are permitted for profile photo.";
        } else {
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                $errors[] = "Security Violation: Profile photo file is not a valid image.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_name = uniqid('profile_', true) . '.' . $ext;
                $destination = $upload_dir . $new_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $photo_path = 'uploads/profiles/' . $new_name;
                } else {
                    $errors[] = "Failed to upload profile photo.";
                }
            }
        }
    } else if (empty($errors)) {
        $errors[] = "Profile photo is required.";
    }

    
    if (empty($errors)) {
        $otp = generate_otp(6);
        $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        
        $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE mobile = ? AND used = 0")->execute([$mobile]);

        
        $stmt = $pdo->prepare("INSERT INTO otp_verifications (mobile, otp_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$mobile, $hashed_otp, $expires]);

        
        if (MOCK_OTP) {
            $_SESSION['toast_msg'] = "DEV MODE — OTP is: " . $otp;
            $_SESSION['toast_type'] = "info";
            $otp_sent = true;
        } else {
            $otp_sent = send_otp_email($email, $otp);
        }

        if (!$otp_sent) {
            
            $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE mobile = ? AND used = 0")->execute([$mobile]);
            $errors[] = "Failed to send OTP to $email. Please check your email address and try again.";
        } else {
            
            $_SESSION['pending_signup'] = [
                'name'                        => $name,
                'dob'                         => $dob,
                'gender'                      => $gender,
                'father_name'                 => $father_name,
                'father_occupation'           => $father_occupation,
                'edu_state'                   => $edu_state,
                'university'                  => $university,
                'college'                     => $college,
                'course'                      => $course,
                'specialization'              => $specialization,
                'semester'                    => $semester,
                'high_school_percentage'      => $high_school_percentage,
                'higher_secondary_percentage' => $higher_secondary_percentage,
                'country'                     => $country,
                'state'                       => $state,
                'city'                        => $city,
                'postal_address'              => $postal_address,
                'pincode'                     => $pincode,
                'mobile'                      => $mobile,
                'email'                       => $email,
                'referral_id'                 => $referral_id,
                'password_hash'               => password_hash($password, PASSWORD_BCRYPT),
                'photo_path'                  => $photo_path,
                'batch'                       => $batch
            ];
            $_SESSION['otp_context'] = 'signup';
            $_SESSION['otp_mobile']  = $mobile;

            header("Location: otp_verify.php");
            exit;
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .signup-container {
        max-width: 950px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .signup-section {
        margin-bottom: 30px;
        padding-bottom: 25px;
        border-bottom: 1px solid var(--border-color);
    }

    .signup-section:last-of-type {
        border-bottom: none;
        margin-bottom: 15px;
        padding-bottom: 10px;
    }

    .section-title {
        font-size: 1.15rem;
        color: var(--accent-blue);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .password-wrapper {
        position: relative;
    }

    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 1.1rem;
        padding: 4px;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }

    .password-toggle-btn:hover {
        color: var(--accent-blue);
    }

    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
        height: 42px;
    }

    .radio-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-primary);
        cursor: pointer;
        font-size: 0.95rem;
    }

    .radio-label input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent-blue);
        cursor: pointer;
    }
</style>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem 0;">
    <div class="signup-container" style="width: 100%;">
        <div class="glass-panel" style="width: 100%; padding: 2.5rem;">
            <div style="text-align: center; margin-bottom: 35px;">
                <img src="../assets/images/logo.png" alt="Logo"
                    style="width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px;">
                <h2 style="margin-top: 10px; color: var(--text-primary);"><?= __('signup_title') ?></h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Digital Investigation — Internship Onboarding</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="background: rgba(220, 38, 38, 0.15); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <ul style="color: #ef4444; list-style-type: none; padding: 0; margin: 0;">
                        <?php foreach ($errors as $error)
                            echo "<li style='margin-bottom:5px;'><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="signupForm">
                <?php csrf_field(); ?>

                
                <div class="signup-section">
                    <div class="section-title"><i class="fa-solid fa-user"></i> Personal Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><?= __('full_name') ?> *</label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= esc($_POST['name'] ?? '') ?>" placeholder="As per Aadhaar/ID Card">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('dob') ?> *</label>
                            <input type="date" name="dob" class="form-control" required
                                value="<?= esc($_POST['dob'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('gender') ?> *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?= (($_POST['gender'] ?? '') == 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (($_POST['gender'] ?? '') == 'Female') ? 'selected' : '' ?>>Female</option>
                                <option value="Transgender" <?= (($_POST['gender'] ?? '') == 'Transgender') ? 'selected' : '' ?>>Transgender</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('father_name') ?> *</label>
                            <input type="text" name="father_name" class="form-control" required
                                value="<?= esc($_POST['father_name'] ?? '') ?>" placeholder="Father's Full Name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Father's Occupation *</label>
                            <input type="text" name="father_occupation" class="form-control" required
                                value="<?= esc($_POST['father_occupation'] ?? '') ?>" placeholder="Father's Occupation">
                        </div>
                    </div>
                </div>

                
                <div class="signup-section">
                    <div class="section-title"><i class="fa-solid fa-graduation-cap"></i> Education Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">College / University State *</label>
                            <select name="edu_state" id="eduStateSelect" class="form-control" required>
                                <option value="">Select College / University State</option>
                                <?php foreach (array_keys($locations) as $s): ?>
                                    <option value="<?= esc($s) ?>" <?= (($_POST['edu_state'] ?? '') == $s) ? 'selected' : '' ?>><?= esc($s) ?></option>
                                <?php endforeach; ?>
                                <option value="Other" <?= (($_POST['edu_state'] ?? '') == 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">College / University Name *</label>
                            <input type="text" name="college" class="form-control" required
                                value="<?= esc($_POST['college'] ?? '') ?>" placeholder="e.g. LNCT, SIRT, IIT Delhi, etc.">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Degree / PG / Diploma *</label>
                            <select name="course" id="courseSelect" class="form-control" required>
                                <option value="">Select Course Type</option>
                                <?php foreach (array_keys($courses) as $c): ?>
                                    <option value="<?= esc($c) ?>" <?= (($_POST['course'] ?? '') == $c) ? 'selected' : '' ?>><?= esc($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stream / Specialization *</label>
                            <select name="specialization" id="specSelect" class="form-control" required>
                                <option value="">Select Stream</option>
                            </select>
                        </div>
                        <div class="form-group" id="otherSpecGroup" style="grid-column: 1 / -1; display: none;">
                            <label class="form-label">Specify Specialization *</label>
                            <input type="text" name="other_specialization" id="otherSpecInput" class="form-control"
                                value="<?= esc($_POST['other_specialization'] ?? '') ?>" placeholder="e.g. Artificial Intelligence, Power Systems...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Semester *</label>
                            <select name="semester" class="form-control" required>
                                <option value="">Select Semester</option>
                                <?php for ($sem = 1; $sem <= 8; $sem++): ?>
                                    <option value="<?= $sem ?>" <?= (($_POST['semester'] ?? '') == $sem) ? 'selected' : '' ?>><?= $sem ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">High School Percentage (10th) *</label>
                            <input type="text" name="high_school_percentage" class="form-control" required
                                value="<?= esc($_POST['high_school_percentage'] ?? '') ?>" placeholder="e.g. 85% or 8.5 CGPA">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Higher Secondary Percentage (12th/Diploma) *</label>
                            <input type="text" name="higher_secondary_percentage" class="form-control" required
                                value="<?= esc($_POST['higher_secondary_percentage'] ?? '') ?>" placeholder="e.g. 78% or 7.8 CGPA">
                        </div>
                    </div>
                </div>

                
                <div class="signup-section">
                    <div class="section-title"><i class="fa-solid fa-house-user"></i> Contact Details</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Country *</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="country_type" value="India" <?= ($_POST['country_type'] ?? 'India') === 'India' ? 'checked' : '' ?> onclick="toggleCountry(this.value)">
                                    India
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="country_type" value="Other" <?= ($_POST['country_type'] ?? '') === 'Other' ? 'checked' : '' ?> onclick="toggleCountry(this.value)">
                                    International / Out of India
                                </label>
                            </div>
                        </div>

                        
                        <div class="form-group" id="customCountryGroup" style="grid-column: 1 / -1; display: none;">
                            <label class="form-label">Country Name *</label>
                            <input type="text" name="country_custom" id="countryCustomInput" class="form-control"
                                value="<?= esc($_POST['country_custom'] ?? '') ?>" placeholder="Enter Country Name">
                        </div>

                        
                        <div class="form-group" id="stateDomesticGroup">
                            <label class="form-label">Home State *</label>
                            <select name="state" id="stateSelect" class="form-control">
                                <option value="">Select Home State</option>
                                <?php foreach (array_keys($locations) as $s): ?>
                                    <option value="<?= esc($s) ?>" <?= (($_POST['state'] ?? '') == $s) ? 'selected' : '' ?>><?= esc($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        
                        <div class="form-group" id="districtDomesticGroup">
                            <label class="form-label">Home District *</label>
                            <select name="district" id="districtSelect" class="form-control">
                                <option value="">Select District First</option>
                            </select>
                        </div>

                        
                        <div class="form-group" id="stateInternationalGroup" style="display: none;">
                            <label class="form-label">State / Province *</label>
                            <input type="text" name="state_custom" id="stateCustomInput" class="form-control"
                                value="<?= esc($_POST['state_custom'] ?? '') ?>" placeholder="Enter State / Province">
                        </div>

                        
                        <div class="form-group" id="districtInternationalGroup" style="display: none;">
                            <label class="form-label">City / District *</label>
                            <input type="text" name="district_custom" id="districtCustomInput" class="form-control"
                                value="<?= esc($_POST['district_custom'] ?? '') ?>" placeholder="Enter City / District">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Postal Address *</label>
                            <textarea name="postal_address" class="form-control" rows="3" required placeholder="Complete Residential Address"><?= esc($_POST['postal_address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pin code / Zip code *</label>
                            <input type="text" name="pincode" class="form-control" required
                                value="<?= esc($_POST['pincode'] ?? '') ?>" placeholder="6-digit Pin Code or International Zip Code">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('mobile') ?> *</label>
                            <input type="text" name="mobile" class="form-control" placeholder="10 Digits" required
                                maxlength="10" value="<?= esc($_POST['mobile'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('official_email') ?> *</label>
                            <input type="email" name="email" class="form-control" required
                                value="<?= esc($_POST['email'] ?? '') ?>" placeholder="e.g. student@domain.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Training Batch *</label>
                            <select name="batch" class="form-control" required>
                                <option value="">Select Batch</option>
                                <?php foreach ($allowed_batches as $b): ?>
                                    <option value="<?= esc($b) ?>" <?= (($_POST['batch'] ?? '') == $b) ? 'selected' : '' ?>><?= esc($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                
                <div class="signup-section">
                    <div class="section-title"><i class="fa-solid fa-shield-halved"></i> Uploads & Security</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Profile Photo (Max 300KB, JPG/PNG) *</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png" required style="padding: 10px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Referral ID (Optional)</label>
                            <input type="text" name="referral_id" class="form-control"
                                value="<?= esc($_POST['referral_id'] ?? $_GET['ref'] ?? '') ?>"
                                placeholder="Enter Referral ID of who invited you (if any)">
                            <?php if (!empty($_GET['ref'])): ?>
                            <small style="color:#a855f7; font-size:0.75rem;"><i class="fa-solid fa-user-plus"></i> Referral code pre-filled from your invite link!</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('password') ?> *</label>
                            <div class="password-wrapper">
                                <input type="password" name="password" id="passwordInput" class="form-control" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('passwordInput', 'passwordIcon')">
                                    <i class="fa-solid fa-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('confirm_password') ?> *</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirmPasswordInput', 'confirmPasswordIcon')">
                                    <i class="fa-solid fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consent Checkbox -->
                <div class="signup-section" style="border-bottom: none; margin-bottom: 10px; padding-bottom: 0;">
                    <div style="display: flex; align-items: flex-start; gap: 12px; background: rgba(0, 243, 255, 0.05); border: 1px solid rgba(0, 243, 255, 0.2); padding: 15px; border-radius: 8px;">
                        <input type="checkbox" name="consent" id="consentCheckbox" required style="width: 20px; height: 20px; cursor: pointer; margin-top: 3px;" <?= isset($_POST['consent']) ? 'checked' : '' ?>>
                        <label for="consentCheckbox" style="color: var(--text-primary); cursor: pointer; font-size: 0.95rem; line-height: 1.4;">
                            <strong>Declaration & Electronic Acknowledgment</strong><br>
                            I hereby solemnly declare that I have thoroughly examined, recognized, and voluntarily accepted every legal mandate and operational constraint established within this Internship Consent and NDA. I understand that any breach of these terms instantly invalidates my position and initiates direct legal and financial liabilities. 
                            <a href="javascript:void(0)" onclick="openConsentModal()" style="color: #00f3ff; text-decoration: underline; font-weight: 700; transition: color 0.2s;" onmouseover="this.style.color='#67e8f9'" onmouseout="this.style.color='#00f3ff'">
                                [Read Full Internship Consent & NDA Form]
                            </a>.
                        </label>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="width: 250px; padding: 12px; font-weight: 700;">
                        <i class="fa-solid fa-paper-plane"></i> Verify Email & Register
                    </button>
                </div>
                <div style="margin-top: 20px; text-align: center; color: var(--text-muted); font-size: 0.95rem;">
                    <?= __('already_have_account') ?> <a href="login.php" style="color: var(--accent-blue); text-decoration: none; font-weight: 600;"><?= __('login_here') ?></a>
                </div>
                
                <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center;">
                    <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='#00f3ff'" onmouseout="this.style.color='var(--text-muted)'">
                        <i class="fa-solid fa-arrow-left-long"></i> Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Internship Consent Form Modal -->
<div id="consentModal" class="cyber-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div class="modal-content" style="max-width: 850px; width: 90%; background: #ffffff; padding: 0; overflow: hidden; border-radius: 12px; border: 2px solid #00f3ff; box-shadow: 0 0 35px rgba(0, 243, 255, 0.4); display: flex; flex-direction: column; height: 85vh;">
        <div style="background: #0f172a; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #00f3ff;">
            <h3 style="color: #ffffff; margin: 0; font-family: 'Rajdhani', sans-serif; font-size: 1.3rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Internship Consent & NDA</h3>
            <span class="close" style="color: #ffffff; font-size: 2rem; cursor: pointer; line-height: 1; transition: color 0.2s;" onclick="closeConsentModal()" onmouseover="this.style.color='#00f3ff'" onmouseout="this.style.color='#ffffff'">&times;</span>
        </div>
        <div style="flex-grow: 1; overflow: hidden; background: #f8fafc;">
            <iframe src="internship_consent_form.html" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
        <div style="background: #0f172a; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1);">
            <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Please review NDA terms carefully before proceeding.</span>
            <button type="button" class="btn-primary" style="background: #3b82f6; color: #ffffff; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" onclick="acceptConsent()">I Agree</button>
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

<script>
    const locationData = <?= json_encode($locations) ?>;
    const courseData = <?= json_encode($courses) ?>;

    const courseEl = document.getElementById('courseSelect');
    const specEl = document.getElementById('specSelect');

    const stateEl = document.getElementById('stateSelect');
    const districtEl = document.getElementById('districtSelect');

    function populateSelect(selectEl, items, placeholder) {
        selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            selectEl.appendChild(opt);
        });
    }

    // Course & Specialization mapping
    courseEl.addEventListener('change', function () {
        const course = this.value;
        if (course && courseData[course]) {
            const specializations = [...courseData[course], 'Other'];
            populateSelect(specEl, specializations, 'Select Specialization');
        } else {
            specEl.innerHTML = '<option value="">Select Course First</option>';
        }
        const savedSpec = "<?= esc($_POST['specialization'] ?? '') ?>";
        if (savedSpec) {
            specEl.value = savedSpec;
            specEl.dispatchEvent(new Event('change'));
        }
    });

    specEl.addEventListener('change', function () {
        const otherGroup = document.getElementById('otherSpecGroup');
        const otherInput = document.getElementById('otherSpecInput');
        if (this.value === 'Other') {
            otherGroup.style.display = 'block';
            otherInput.required = true;
        } else {
            otherGroup.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    });

    // Home State & Home District (Domestic India)
    stateEl.addEventListener('change', function () {
        const state = this.value;
        if (state && locationData[state]) {
            populateSelect(districtEl, locationData[state], 'Select District');
        } else {
            districtEl.innerHTML = '<option value="">Select State First</option>';
        }
        const savedDistrict = "<?= esc($_POST['district'] ?? '') ?>";
        if (savedDistrict) {
            districtEl.value = savedDistrict;
        }
    });

    // Country Toggle (India vs International)
    function toggleCountry(countryType) {
        const customCountryGroup = document.getElementById('customCountryGroup');
        const countryCustomInput = document.getElementById('countryCustomInput');

        const stateDomesticGroup = document.getElementById('stateDomesticGroup');
        const districtDomesticGroup = document.getElementById('districtDomesticGroup');
        const stateSelect = document.getElementById('stateSelect');
        const districtSelect = document.getElementById('districtSelect');

        const stateInternationalGroup = document.getElementById('stateInternationalGroup');
        const districtInternationalGroup = document.getElementById('districtInternationalGroup');
        const stateCustomInput = document.getElementById('stateCustomInput');
        const districtCustomInput = document.getElementById('districtCustomInput');

        if (countryType === 'India') {
            customCountryGroup.style.display = 'none';
            countryCustomInput.required = false;

            stateDomesticGroup.style.display = 'block';
            districtDomesticGroup.style.display = 'block';
            stateSelect.required = true;
            districtSelect.required = true;

            stateInternationalGroup.style.display = 'none';
            districtInternationalGroup.style.display = 'none';
            stateCustomInput.required = false;
            districtCustomInput.required = false;
        } else {
            customCountryGroup.style.display = 'block';
            countryCustomInput.required = true;

            stateDomesticGroup.style.display = 'none';
            districtDomesticGroup.style.display = 'none';
            stateSelect.required = false;
            districtSelect.required = false;

            stateInternationalGroup.style.display = 'block';
            districtInternationalGroup.style.display = 'block';
            stateCustomInput.required = true;
            districtCustomInput.required = true;
        }
    }

    // Toggle Password Visibility
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Trigger on page load for initial state/restoration
    document.addEventListener('DOMContentLoaded', function() {
        if (courseEl.value) courseEl.dispatchEvent(new Event('change'));
        if (stateEl.value) stateEl.dispatchEvent(new Event('change'));
        
        const initialCountryType = document.querySelector('input[name="country_type"]:checked').value;
        toggleCountry(initialCountryType);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>