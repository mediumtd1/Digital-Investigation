<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("HTTP/1.1 404 Not Found");
    exit;
}
// config/db.php - RESTORED STABILITY
$host = 'localhost';
$db   = 'surbhi_digitalinvestigation';
$user = 'surbhi_digitalinvestigation';
$pass = defined('DB_PASS') ? DB_PASS : '';

if ((isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1')) || (PHP_SAPI === 'cli')) {
    $host = '127.0.0.1'; $db = 'cyber_portal'; $user = 'root'; $pass = '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) { die("DATABASE_CONNECTION_ERROR"); }

if (!function_exists('esc')) { function esc($t) { return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8'); } }

// AUTO-CREATE CORE TABLES
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(15) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_mapped_id VARCHAR(50),
        user_role VARCHAR(20),
        action TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Production-level Auto-Certificate Migration
    // If the table exists but doesn't have the approval_status column, drop it for a fresh structure
    $tableExists = false;
    $hasNewCols = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'certificates'");
        $tableExists = ($stmt->rowCount() > 0);
        if ($tableExists) {
            $cols = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'approval_status'")->fetchAll();
            $hasNewCols = (count($cols) > 0);
        }
    } catch (Exception $e) {}

    if ($tableExists && !$hasNewCols) {
        $pdo->exec("DROP TABLE certificates");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        certificate_hash VARCHAR(64) UNIQUE NOT NULL,
        qr_token VARCHAR(64) UNIQUE NOT NULL,
        approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        issue_date_custom DATE DEFAULT NULL,
        approved_by INT DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        rejection_reason TEXT DEFAULT NULL,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(intern_id)
    )");

    // Auto-migrate new columns for streak tracking in interns table
    try {
        $pdo->exec("ALTER TABLE interns ADD COLUMN streak_count INT DEFAULT 0");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE interns ADD COLUMN last_active_date DATE DEFAULT NULL");
    } catch (Exception $e) {}

    // Auto-migrate new registration fields in interns table
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN father_occupation VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN edu_state VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN university VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN semester INT DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN high_school_percentage VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN higher_secondary_percentage VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN country VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN pincode VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN postal_address TEXT DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN resume_path VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN referral_id VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    // referral_id stores the intern_id of the person who referred this intern (referrer's intern_id code)
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN referral_points DECIMAL(4,1) DEFAULT 0"); } catch (Exception $e) {}
    // referral_points: 1 pt per referral made, 0.5 pt for joining via referral

    // Drop old structures if they exist from the initial migration
    try {
        $check = $pdo->query("SHOW COLUMNS FROM daily_challenges LIKE 'correct_option'")->fetchAll();
        if (!empty($check)) {
            $pdo->exec("DROP TABLE IF EXISTS intern_challenges");
            $pdo->exec("DROP TABLE IF EXISTS daily_challenges");
        }
    } catch (Exception $e) {}

    // Daily Challenges Table (Supports multiple correct options & multiple questions per day)
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_options VARCHAR(50) NOT NULL, -- e.g., 'A,C'
        points INT DEFAULT 2,
        explanation TEXT DEFAULT NULL,
        time_limit INT DEFAULT 10, -- Time limit in minutes
        date_active DATE NOT NULL,
        batch VARCHAR(50) DEFAULT NULL
    )");

    // Intern Quiz Sessions (tracks start and submit times for timer control)
    $pdo->exec("CREATE TABLE IF NOT EXISTS intern_quiz_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        date_active DATE NOT NULL,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        submitted_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY unique_intern_session (intern_id, date_active)
    )");

    // Intern Challenge Submissions Table (Tracks multiple answered options)
    $pdo->exec("CREATE TABLE IF NOT EXISTS intern_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        challenge_id INT NOT NULL,
        selected_options VARCHAR(50) NOT NULL, -- e.g., 'A,C'
        is_correct TINYINT(1) NOT NULL,
        points_earned INT DEFAULT 0,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_intern_challenge (intern_id, challenge_id)
    )");

    // Ensure daily_challenges table has time_limit and batch columns (in case they already exist without them)
    try {
        $pdo->exec("ALTER TABLE daily_challenges ADD COLUMN time_limit INT DEFAULT 10");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE daily_challenges ADD COLUMN batch VARCHAR(50) DEFAULT NULL");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE daily_challenges ALTER COLUMN points SET DEFAULT 2");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE announcements ADD COLUMN target_batch VARCHAR(50) DEFAULT NULL");
    } catch(Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE help_tickets ADD COLUMN attachment VARCHAR(255) DEFAULT NULL");
    } catch(Exception $e) {}

    // Intern Attendance Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS intern_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        status VARCHAR(50) DEFAULT 'Present',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_attendance (intern_id, date)
    )");

    // Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Study Materials Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS study_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Featured Interns Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS featured_interns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NULL,
        name VARCHAR(255) NOT NULL,
        college VARCHAR(255) NULL,
        media_url VARCHAR(255) NULL,
        media_type ENUM('image', 'video') DEFAULT 'image',
        description TEXT NOT NULL,
        featured_month VARCHAR(50) NOT NULL,
        share_token VARCHAR(64) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Ensure default status is 'Active' in interns table
    try {
        $pdo->exec("ALTER TABLE interns MODIFY COLUMN status VARCHAR(20) DEFAULT 'Active'");
    } catch(Exception $e) {}

    // Self-healing batch normalization to fix yearless batches
    try {
        // Appends " Batch" suffix if missing
        $pdo->exec("UPDATE interns SET batch = CONCAT(TRIM(batch), ' Batch') WHERE batch IS NOT NULL AND TRIM(batch) != '' AND LOWER(batch) NOT LIKE '%batch%'");
        $pdo->exec("UPDATE daily_challenges SET batch = CONCAT(TRIM(batch), ' Batch') WHERE batch IS NOT NULL AND TRIM(batch) != '' AND LOWER(batch) NOT LIKE '%batch%'");
        $pdo->exec("UPDATE announcements SET target_batch = CONCAT(TRIM(target_batch), ' Batch') WHERE target_batch IS NOT NULL AND TRIM(target_batch) != '' AND LOWER(target_batch) NOT LIKE '%batch%'");

        // Normalise interns batch
        $stmt = $pdo->query("SELECT id, batch, created_at FROM interns WHERE batch IS NOT NULL AND batch != ''");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch = trim($row['batch']);
            if (!preg_match('/\b\d{4}\b/', $batch)) {
                $parts = explode(' ', $batch);
                $month = $parts[0] ?? '';
                if (!empty($month)) {
                    $created_time = !empty($row['created_at']) ? strtotime($row['created_at']) : time();
                    $created_year = (int)date('Y', $created_time);
                    $created_month = (int)date('n', $created_time);
                    
                    // Parse target month number
                    $target_month_num = (int)date('n', strtotime("$month 1st"));
                    
                    $target_year = $created_year;
                    if ($target_month_num < $created_month && ($created_month - $target_month_num) > 6) {
                        $target_year = $created_year + 1;
                    }
                    
                    $new_batch = "$month $target_year Batch";
                    $pdo->prepare("UPDATE interns SET batch = ? WHERE id = ?")->execute([$new_batch, $row['id']]);
                }
            }
        }

        // Normalise announcements target_batch
        $stmt = $pdo->query("SELECT id, target_batch, created_at FROM announcements WHERE target_batch IS NOT NULL AND target_batch != ''");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch = trim($row['target_batch']);
            if (!preg_match('/\b\d{4}\b/', $batch)) {
                $parts = explode(' ', $batch);
                $month = $parts[0] ?? '';
                if (!empty($month)) {
                    $created_time = !empty($row['created_at']) ? strtotime($row['created_at']) : time();
                    $created_year = (int)date('Y', $created_time);
                    $new_batch = "$month $created_year Batch";
                    $pdo->prepare("UPDATE announcements SET target_batch = ? WHERE id = ?")->execute([$new_batch, $row['id']]);
                }
            }
        }

        // Normalise daily_challenges batch
        $stmt = $pdo->query("SELECT id, batch, date_active FROM daily_challenges WHERE batch IS NOT NULL AND batch != ''");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch = trim($row['batch']);
            if (!preg_match('/\b\d{4}\b/', $batch)) {
                $parts = explode(' ', $batch);
                $month = $parts[0] ?? '';
                if (!empty($month)) {
                    $ref_time = !empty($row['date_active']) ? strtotime($row['date_active']) : time();
                    $ref_year = (int)date('Y', $ref_time);
                    $new_batch = "$month $ref_year Batch";
                    $pdo->prepare("UPDATE daily_challenges SET batch = ? WHERE id = ?")->execute([$new_batch, $row['id']]);
                }
            }
        }
    } catch (Exception $e) {}
} catch (Exception $e) {}

