<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Intern', 'Admin'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['user_role'] === 'Admin');

$intern_id = $_SESSION['user_id'];
$page_title = 'Daily Modules | Cyber Academy';

$ctf_success_message = '';
$ctf_error_message = '';

try {
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS ctf_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        flag_submitted VARCHAR(255) DEFAULT NULL,
        is_correct TINYINT(1) DEFAULT 0,
        partial_steps VARCHAR(255) DEFAULT NULL,
        partial_notes TEXT DEFAULT NULL,
        points_awarded INT DEFAULT 0,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE
    )");
    
    
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_steps VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_notes TEXT DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN points_awarded INT DEFAULT 0"); } catch(PDOException $e) {}
} catch (PDOException $e) {}

$current_submission = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM ctf_submissions WHERE intern_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$intern_id]);
    $current_submission = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($current_submission && $current_submission['is_correct']) {
        $ctf_completed = true;
    }
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_ctf_flag') {
    $flag = trim($_POST['ctf_flag'] ?? '');
    $correct_flag = 'flag{intern_detective_master}';
    
    if ($flag === $correct_flag) {
        try {
            
            $pdo->prepare("DELETE FROM ctf_submissions WHERE intern_id = ?")->execute([$intern_id]);
            
            $stmt = $pdo->prepare("INSERT INTO ctf_submissions (intern_id, flag_submitted, is_correct, points_awarded) VALUES (?, ?, 1, 100)");
            $stmt->execute([$intern_id, $flag]);
            $ctf_completed = true;
            $ctf_success_message = "🎉 EXCELLENT WORK! Flag Captured Successfully! Cyber Threat Intelligence Clearance Level 3 Granted! (+100 Points added to Leaderboard)";
        } catch (PDOException $e) {
            $ctf_success_message = "🎉 Correct flag! (Submission saved)";
        }
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ctf_submissions (intern_id, flag_submitted, is_correct, points_awarded) VALUES (?, ?, 0, 0)");
            $stmt->execute([$intern_id, $flag]);
        } catch (PDOException $e) {}
        $ctf_error_message = "❌ INVALID FLAG! Intrusion Alert. Double-check your clues and try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_ctf_partial') {
    $steps = $_POST['steps'] ?? [];
    $notes = trim($_POST['partial_notes'] ?? '');
    
    
    $points = count($steps) * 15;
    $steps_str = implode(',', $steps);
    
    try {
        
        $pdo->prepare("DELETE FROM ctf_submissions WHERE intern_id = ?")->execute([$intern_id]);
        
        $stmt = $pdo->prepare("INSERT INTO ctf_submissions (intern_id, is_correct, partial_steps, partial_notes, points_awarded) VALUES (?, 0, ?, ?, ?)");
        $stmt->execute([$intern_id, $steps_str, $notes, $points]);
        
        $ctf_success_message = "💾 Milestone Progress Saved Successfully! Your partial score of +{$points} Points has been updated on the Leaderboard.";
        
        
        $stmt = $pdo->prepare("SELECT * FROM ctf_submissions WHERE intern_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$intern_id]);
        $current_submission = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $ctf_error_message = "❌ Error saving progress: " . $e->getMessage();
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS internship_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        week INT NOT NULL, day INT NOT NULL,
        title VARCHAR(255) NOT NULL, description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        is_visible TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_week_day (week, day)
    )");
    
    try { $pdo->exec("ALTER TABLE internship_modules ADD COLUMN file_path VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}
} catch(PDOException $e) {}

$all_modules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM internship_modules ORDER BY week ASC, day ASC");
    $stmt->execute();
    $all_modules = $stmt->fetchAll();
} catch(PDOException $e) { $all_modules = []; }

$grouped = [];
foreach ($all_modules as $m) {
    $grouped[$m['week']][$m['day']] = $m;
}

$opened_modules = [];
try {
    $stmt = $pdo->prepare("SELECT module_id FROM module_tracking WHERE intern_id = ?");
    $stmt->execute([$intern_id]);
    $opened_modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {}

$hq_overrides = [];
try {
    $stmt = $pdo->prepare("SELECT module_id FROM module_access_overrides WHERE intern_id = ?");
    $stmt->execute([$intern_id]);
    $hq_overrides = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) {}

$intern_status = 'Ongoing';
$intern_batch = null;
if (!$is_admin) {
    try {
        
        try { $pdo->exec("ALTER TABLE interns ADD COLUMN internship_status ENUM('Ongoing', 'Completed') DEFAULT 'Ongoing'"); } catch(PDOException $e) {}
        try { $pdo->exec("ALTER TABLE interns ADD COLUMN batch VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}

        $stmt = $pdo->prepare("SELECT internship_status, batch FROM interns WHERE id = ?");
        $stmt->execute([$intern_id]);
        $intern_row = $stmt->fetch();
        if ($intern_row) {
            $intern_status = $intern_row['internship_status'] ?: 'Ongoing';
            $intern_batch = $intern_row['batch'];
        }
    } catch(PDOException $e) {}

    if ($intern_status === 'Completed') {
        $_SESSION['toast_msg'] = "You have already completed this internship program.";
        $_SESSION['toast_type'] = "warning";
    }
}

$current_day_of_month = (int)date('j');

$static_curriculum = [
    1 => [
        'title' => 'FOUNDATIONS OF CYBER INVESTIGATION',
        'color' => '#00f3ff',
        'icon' => 'fa-shield-halved',
        'days' => [
            1 => ['title' => 'Intro to Cyber Crime',             'desc' => 'Understanding the landscape of modern digital threats and their impact on society.'],
            2 => ['title' => 'Networking for Investigators',      'desc' => 'TCP/IP, Ports, and Protocol analysis fundamentals for cyber investigations.'],
            3 => ['title' => 'Linux for Forensics',              'desc' => 'Mastering the command line for forensic data retrieval and analysis.'],
            4 => ['title' => 'OSINT Tools & Techniques',         'desc' => 'Gathering open-source intelligence from the web for evidence building.'],
            5 => ['title' => 'Web Security 101',                 'desc' => 'How websites work, common vulnerabilities, and exploitation basics.'],
            6 => ['title' => 'Weekly Assignment',                'desc' => 'Practical foundational assessment and submission to HQ.'],
            7 => ['title' => 'Week 1 Live Q&A',                  'desc' => 'Interact live with senior investigators and senior mentors.'],
        ]
    ],
    2 => [
        'title' => 'ADVANCED FORENSICS & HASHING',
        'color' => '#a855f7',
        'icon' => 'fa-magnifying-glass',
        'days' => [
            1 => ['title' => 'Digital Forensics, Chain of Custody & Evidence Handling', 'desc' => 'Core principles of evidence preservation, chain of custody, and forensic workflow management.'],
            2 => ['title' => 'Hash Value Calculation & WordPress Security',              'desc' => 'Understanding data integrity through hashing and securing CMS platforms.'],
            3 => ['title' => 'Week 2 – Day 3 Module',            'desc' => 'Continuing advanced forensic deep-dive training.'],
            4 => ['title' => 'Week 2 – Day 4 Module',            'desc' => 'Continuing advanced forensic deep-dive training.'],
            5 => ['title' => 'Week 2 – Day 5 Module',            'desc' => 'Continuing advanced forensic deep-dive training.'],
            6 => ['title' => 'Week 2 – Day 6 Module',            'desc' => 'Continuing advanced forensic deep-dive training.'],
            7 => ['title' => 'Cloud Forensics Risks & Shared Responsibility Model', 'desc' => 'Investigating crimes in cloud environments: AWS, Azure, and GCP specifics.'],
        ]
    ],
    3 => [
        'title' => 'INVESTIGATIVE TOOLS & LABS',
        'color' => '#10b981',
        'icon' => 'fa-flask',
        'days' => [
            1 => ['title' => 'Week 3 – Day 1 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            2 => ['title' => 'Week 3 – Day 2 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            3 => ['title' => 'Week 3 – Day 3 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            4 => ['title' => 'Week 3 – Day 4 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            5 => ['title' => 'Week 3 – Day 5 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            6 => ['title' => 'Week 3 – Day 6 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
            7 => ['title' => 'Week 3 – Day 7 Lab Session',  'desc' => 'Hands-on practical training with live investigative tools.'],
        ]
    ],
    4 => [
        'title' => 'LEGAL FRAMEWORK & FUTURE TECH',
        'color' => '#f59e0b',
        'icon' => 'fa-scale-balanced',
        'days' => [
            1 => ['title' => 'Advanced Forensic Investigation Summary',  'desc' => 'Finalizing comprehensive investigation techniques and case study reviews.'],
            2 => ['title' => 'Week 4 – Day 2 Continuation',             'desc' => 'Continuing legal and technical wrap-up of case analysis.'],
            3 => ['title' => 'Cyber Laws (IT Act 2000 & Amendments)',   'desc' => 'Understanding the Information Technology Act and its legal relevance.'],
            4 => ['title' => 'DPDP Act & Intermediary Guidelines',      'desc' => 'Digital Personal Data Protection Act and social media platform compliance.'],
            5 => ['title' => 'AI, VR & AR in Cybersecurity & Forensics','desc' => 'The future of digital investigations: Artificial Intelligence and Immersive Technologies.'],
            6 => ['title' => 'Final Capstone Assignment',               'desc' => 'Submit your comprehensive investigative report to HQ Command.'],
            7 => ['title' => 'Capture The Flag (CTF) Challenge',        'desc' => 'The ultimate practical challenge for all graduating interns.'],
        ]
    ],
    5 => [
        'title' => 'FINAL EVALUATION & CERTIFICATION',
        'color' => '#10b981',
        'icon' => 'fa-award',
        'days' => [
            1 => ['title' => 'Project Submission', 'desc' => 'Final Project Submission Day. Upload your complete project files.'],
            2 => ['title' => 'Final Presentation', 'desc' => 'Present your findings and project to the board of investigators.'],
        ]
    ],
];

$week_names = [
    1 => 'Foundations of Cyber Investigation',
    2 => 'Advanced Forensics & Hashing',
    3 => 'Investigative Tools & Labs',
    4 => 'Legal Framework & Future Tech',
    5 => 'Final Evaluation & Certification',
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ===================== MODULES PAGE STYLES ===================== */
.mod-page { padding: 30px; max-width: 1100px; margin: 0 auto; }

/* ---- OVERVIEW HERO ---- */
.overview-hero {
    background: linear-gradient(135deg, rgba(0,243,255,0.08) 0%, rgba(168,85,247,0.06) 100%);
    border: 1px solid rgba(0,243,255,0.15);
    border-radius: 16px;
    padding: 35px 40px;
    margin-bottom: 45px;
    position: relative;
    overflow: hidden;
}
.overview-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2300f3ff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.overview-hero-top {
    display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px;
}
.overview-badge {
    background: linear-gradient(135deg, #00f3ff, #a855f7);
    border-radius: 12px; padding: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.overview-badge i { font-size: 1.8rem; color: #fff; }
.overview-hero h2 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.8rem; color: #fff; margin: 0 0 6px 0; letter-spacing: 1px;
}
.overview-hero p { color: #94a3b8; font-size: 0.9rem; margin: 0; line-height: 1.7; }
.free-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.4);
    color: #10b981; padding: 5px 14px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; margin-top: 12px;
}
.overview-stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 25px;
}
.ov-stat {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 15px 18px; text-align: center;
}
.ov-stat-num { font-size: 1.6rem; font-weight: 800; font-family: 'Rajdhani', sans-serif; color: var(--accent-blue); }
.ov-stat-label { font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

/* ---- WEEK ACCORDION CARDS ---- */
.week-card {
    background: rgba(15,23,42,0.7);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    margin-bottom: 18px;
    overflow: hidden;
    transition: 0.3s;
}
.week-card:hover { border-color: rgba(255,255,255,0.12); }

.week-header {
    display: flex; align-items: center; gap: 16px;
    padding: 20px 25px; cursor: pointer;
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: 0.3s; user-select: none;
}
.week-header:hover { background: rgba(255,255,255,0.04); }
.week-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    font-size: 1rem;
}
.week-header-info { flex: 1; }
.week-header-info .week-num {
    font-size: 0.65rem; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 3px;
}
.week-header-info h3 {
    font-family: 'Rajdhani', sans-serif; font-size: 1.05rem; color: #fff; margin: 0; letter-spacing: 0.5px;
}
.week-header-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.week-progress-pill {
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px; padding: 4px 12px; font-size: 0.72rem; color: #64748b;
}
.week-progress-pill .unlocked { color: #10b981; font-weight: 700; }
.week-chevron { color: #475569; transition: transform 0.3s; }
.week-header.open .week-chevron { transform: rotate(180deg); }

/* ---- DAY LIST ---- */
.day-list { display: none; padding: 8px 0; }
.day-list.open { display: block; }

.day-item {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 16px 25px; transition: 0.2s;
    border-bottom: 1px solid rgba(255,255,255,0.02);
}
.day-item:last-child { border-bottom: none; }
.day-item:hover { background: rgba(255,255,255,0.015); }

.day-num-badge {
    width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 800; font-family: 'Rajdhani', sans-serif;
}
.day-num-badge.live  { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
.day-num-badge.locked{ background: rgba(71,85,105,0.2); color: #475569; border: 1px solid rgba(71,85,105,0.3); }
.day-num-badge.special { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }

.day-info { flex: 1; min-width: 0; }
.day-title {
    font-size: 0.9rem; font-weight: 600; color: #e2e8f0; margin-bottom: 4px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.day-title.locked-title { color: #475569; }
.day-desc { font-size: 0.78rem; color: #64748b; line-height: 1.5; }
.day-desc.locked-desc { color: #334155; }

.status-pill {
    font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;
    letter-spacing: 0.5px; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;
    vertical-align: middle;
}
.status-pill.live   { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
.status-pill.locked { background: rgba(71,85,105,0.1); color: #475569; border: 1px solid rgba(71,85,105,0.2); }
.status-pill.ctf    { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.status-pill.capstone { background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); }

/* ---- FILE ATTACHMENT ---- */
.module-file-link {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(0,243,255,0.05); border: 1px solid rgba(0,243,255,0.2);
    color: #00f3ff; padding: 5px 12px; border-radius: 6px;
    font-size: 0.72rem; text-decoration: none; margin-top: 8px;
    transition: 0.2s;
}
.module-file-link:hover { background: rgba(0,243,255,0.12); }

/* ---- SECTION HEADER ---- */
.section-label {
    font-size: 0.65rem; font-weight: 800; color: #475569; text-transform: uppercase;
    letter-spacing: 2px; margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;
}
.section-label::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.04); }
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:1.5px;">
                    <i class="fa-solid fa-graduation-cap" style="color:#00f3ff;"></i> DAILY TRAINING MODULES
                </h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php
                    $photo = $_SESSION['user_photo'] ?? '';
                    $name_enc = urlencode($_SESSION['user_name'] ?? 'U');
                    $photo_src = !empty($photo)
                        ? (strpos($photo,'http')===0 ? $photo : '../' . ltrim($photo,'/'))
                        : "https://ui-avatars.com/api/?name={$name_enc}&background=0D8ABC&color=fff&size=80";
                    ?>
                    <img src="<?= esc($photo_src) ?>" class="profile-pic" alt="Profile" style="width:38px; height:38px; border-radius:50%; border:2px solid rgba(0,243,255,0.3); object-fit:cover;">
                    <div class="user-info">
                        <span class="user-name" style="font-size:0.9rem; font-weight:700; color:#fff; display:block;"><?= esc($_SESSION['user_name']) ?></span>
                        <span class="user-role" style="font-family:'Share Tech Mono',monospace; font-size:0.65rem; color:#00f3ff; text-transform:uppercase;">Intern</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content mod-page">

            <?php 
            $current_month_name = date('F');
            $has_access = true;
            $restriction_reason = '';

            if (!$is_admin) {
                if ($intern_status === 'Completed') {
                    $has_access = false;
                    $restriction_reason = 'completed';
                } elseif (empty($intern_batch)) {
                    $has_access = false;
                    $restriction_reason = 'no_batch';
                } else {
                    
                    
                    preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)/i', trim($intern_batch), $matches);
                    $batch_month = $matches[1] ?? '';

                    if (empty($batch_month) || strcasecmp($batch_month, $current_month_name) !== 0) {
                        $has_access = false;
                        $restriction_reason = 'wrong_batch';
                    }
                }
            }
            ?>

            
            <?php if (!empty($ctf_success_message)): ?>
                <div class="glass-panel" style="border-left: 5px solid #10b981; background: rgba(16,185,129,0.08); margin-bottom: 25px; padding: 20px;">
                    <p style="color: #10b981; font-weight: bold; margin: 0; font-size: 1rem;"><i class="fa-solid fa-circle-check"></i> <?= $ctf_success_message ?></p>
                </div>
            <?php elseif (!empty($ctf_error_message)): ?>
                <div class="glass-panel" style="border-left: 5px solid #ef4444; background: rgba(239,68,68,0.08); margin-bottom: 25px; padding: 20px;">
                    <p style="color: #fca5a5; font-weight: bold; margin: 0; font-size: 1rem;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $ctf_error_message ?></p>
                </div>
            <?php endif; ?>

            <div class="overview-hero">
                <div class="overview-hero-top">
                    <div class="overview-badge">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h2>INTERNSHIP CURRICULUM OVERVIEW</h2>
                        <p>Welcome to the <strong style="color:#00f3ff;">Digital Investigation Academy</strong>. This is your complete 4-week training roadmap — structured week-by-week, day-by-day. Modules will be unlocked by HQ Command as your internship progresses.</p>
                        <div class="free-badge">
                            <i class="fa-solid fa-circle-check"></i> FREE FOR ALL ENROLLED INTERNS
                        </div>
                    </div>
                </div>
                <div class="overview-stats">
                    <div class="ov-stat">
                        <div class="ov-stat-num">4</div>
                        <div class="ov-stat-label">Total Weeks</div>
                    </div>
                    <div class="ov-stat">
                        <div class="ov-stat-num">28</div>
                        <div class="ov-stat-label">Training Days</div>
                    </div>
                    <div class="ov-stat">
                        <div class="ov-stat-num" id="unlocked-count">
                            <?php 
                            $vis = array_sum(array_column($all_modules, 'is_visible'));
                            echo $vis;
                            ?>
                        </div>
                        <div class="ov-stat-label">Modules Unlocked</div>
                    </div>
                    <div class="ov-stat">
                        <div class="ov-stat-num">1</div>
                        <div class="ov-stat-label">CTF Challenge</div>
                    </div>
                </div>
            </div>

            <?php if (!$has_access): ?>
                
                <div class="glass-panel" style="max-width: 700px; margin: 40px auto; padding: 40px; text-align: center; border-left: 5px solid <?= ($restriction_reason === 'completed') ? '#10b981' : '#f59e0b' ?>;">
                    <?php if ($restriction_reason === 'completed'): ?>
                        <div style="font-size: 4rem; color: #10b981; margin-bottom: 20px;">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2rem; color: #fff; margin-bottom: 15px; letter-spacing: 1px;">
                            INTERNSHIP COMPLETED
                        </h2>
                        <p style="color: #94a3b8; line-height: 1.8; font-size: 1rem; margin-bottom: 25px;">
                            Congratulations, <strong><?= esc($_SESSION['user_name']) ?></strong>! You have successfully completed your Digital Investigation internship program. 
                            As you have already graduated, active daily modules are no longer visible. You can download and view your official credentials in the Certificates tab.
                        </p>
                        <a href="certificates.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; background: linear-gradient(135deg, #10b981, #059669); border: none; color: #020617;">
                            <i class="fa-solid fa-award"></i> View My Certificates
                        </a>
                    <?php elseif ($restriction_reason === 'no_batch'): ?>
                        <div style="font-size: 4rem; color: #38bdf8; margin-bottom: 20px;">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>
                        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2rem; color: #fff; margin-bottom: 15px; letter-spacing: 1px;">
                            BATCH ALLOCATION PENDING
                        </h2>
                        <p style="color: #94a3b8; line-height: 1.8; font-size: 1rem; margin-bottom: 25px;">
                            Welcome to the academy! You have registered successfully. Currently, you have not been allocated to a training batch. 
                            HQ Command will review your application and assign you to an upcoming monthly training batch shortly.
                        </p>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px; font-family: 'Share Tech Mono', monospace; font-size: 0.85rem; color: #38bdf8; display: inline-block;">
                            STATUS: AWAITING BATCH ASSIGNMENT BY ADMIN
                        </div>
                    <?php elseif ($restriction_reason === 'wrong_batch'): ?>
                        <div style="font-size: 4rem; color: #ef4444; margin-bottom: 20px;">
                            <i class="fa-solid fa-lock" style="filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4));"></i>
                        </div>
                        <h2 style="font-family: 'Rajdhani', sans-serif; font-size: 2rem; color: #fff; margin-bottom: 15px; letter-spacing: 1px;">
                            ACCESS RESTRICTED — BATCH OVERLAP
                        </h2>
                        <p style="color: #94a3b8; line-height: 1.8; font-size: 1rem; margin-bottom: 25px;">
                            Your assigned training batch is the <strong><?= strripos($intern_batch, 'batch') !== false ? esc($intern_batch) : esc($intern_batch) . ' Batch' ?></strong>.
                            Under safety protocols, you are permitted to attend training sessions, view daily modules, and download materials <strong>only</strong> during the month of <strong><?= strripos($intern_batch, 'batch') !== false ? esc($intern_batch) : esc($intern_batch) . ' Batch' ?></strong>.
                        </p>
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">
                            <h4 style="color: #ef4444; margin-top: 0; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Security Guideline
                            </h4>
                            <p style="color: #fca5a5; font-size: 0.85rem; margin: 0; line-height: 1.5;">
                                Access is scheduled strictly from the 1st of <?= strripos($intern_batch, 'batch') !== false ? esc($intern_batch) : esc($intern_batch) . ' Batch' ?> to the end of <?= strripos($intern_batch, 'batch') !== false ? esc($intern_batch) : esc($intern_batch) . ' Batch' ?>. Other batches (like <?= esc($current_month_name) ?> batch) are not allowed to access these secure modules.
                            </p>
                        </div>
                        <a href="dashboard.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; background: #3b82f6; border: none; color: #fff;">
                            <i class="fa-solid fa-house"></i> Return to Console
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>

            
            <div class="section-label">INTERNSHIP PROGRAMME MODULES</div>

            <?php foreach ($static_curriculum as $week_num => $week_data): 
                $week_color = $week_data['color'];
                $unlocked_in_week = 0;
                foreach ($week_data['days'] as $d => $day_static) {
                    if (!empty($grouped[$week_num][$d]) && $grouped[$week_num][$d]['is_visible']) $unlocked_in_week++;
                }
                $total_days = count($week_data['days']);
            ?>
            <div class="week-card" id="week-card-<?= $week_num ?>">
                <div class="week-header" onclick="toggleWeek(<?= $week_num ?>)" id="week-hdr-<?= $week_num ?>">
                    <div class="week-icon" style="background:<?= $week_color ?>18; border:1px solid <?= $week_color ?>30;">
                        <i class="fa-solid <?= $week_data['icon'] ?>" style="color:<?= $week_color ?>;"></i>
                    </div>
                    <div class="week-header-info">
                        <div class="week-num" style="color:<?= $week_color ?>;">WEEK <?= str_pad($week_num, 2, '0', STR_PAD_LEFT) ?></div>
                        <h3><?= htmlspecialchars($week_data['title']) ?></h3>
                    </div>
                    <div class="week-header-right">
                        <div class="week-progress-pill">
                            <span class="unlocked"><?= $unlocked_in_week ?></span><span style="color:#334155;">/<?= $total_days ?></span> Live
                        </div>
                        <i class="fa-solid fa-chevron-down week-chevron"></i>
                    </div>
                </div>

                <div class="day-list" id="day-list-<?= $week_num ?>">
                    <?php foreach ($week_data['days'] as $day_num => $day_static): 
                        $db_mod = $grouped[$week_num][$day_num] ?? null;
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        $calendar_day = (($week_num - 1) * 7) + $day_num;
                        
                        
                        $is_unlocked_by_date = ($current_day_of_month == $calendar_day);
                        $has_hq_override     = in_array($db_mod['id'] ?? 0, $hq_overrides);
                        
                        
                        if ($calendar_day == 29) {
                            $day_static['title'] = "Project Submission";
                            $day_static['desc'] = "Final Project Submission Day. Upload your complete project files.";
                        } elseif ($calendar_day == 30) {
                            $day_static['title'] = "Final Presentation";
                            $day_static['desc'] = "Present your findings and project to the board of investigators.";
                        }

                        $is_visible = (($db_mod && $db_mod['is_visible']) && ($is_unlocked_by_date || $has_hq_override)) || $is_admin;
                        $is_opened = $db_mod && in_array($db_mod['id'], $opened_modules) && !$is_admin;

                        $display_title = $db_mod ? htmlspecialchars($db_mod['title']) : htmlspecialchars($day_static['title']);
                        $display_desc  = $db_mod ? htmlspecialchars($db_mod['description']) : htmlspecialchars($day_static['desc']);

                        $is_capstone = ($calendar_day == 29);
                        $is_ctf      = ($calendar_day == 28); 
                        $is_exercise = false; 
                    ?>
                    <div class="day-item" style="<?= (!$is_unlocked_by_date && !$is_admin) ? 'opacity: 0.6; cursor: not-allowed;' : '' ?>">
                        <div class="day-num-badge <?= $is_ctf ? 'special' : ($is_capstone ? 'special' : ($is_exercise ? 'special' : ($is_visible ? 'live' : 'locked'))) ?>">
                            <?php if ($is_ctf): ?>
                                <i class="fa-solid fa-display" style="font-size:0.9rem;"></i>
                            <?php elseif ($is_capstone): ?>
                                <i class="fa-solid fa-file-arrow-up" style="font-size:0.9rem;"></i>
                            <?php elseif ($is_exercise): ?>
                                <i class="fa-solid fa-pen-nib" style="font-size:0.9rem;"></i>
                            <?php else: ?>
                                D<?= $calendar_day ?>
                            <?php endif; ?>
                        </div>
                        <div class="day-info">
                            <div class="day-title <?= (!$is_visible && !$is_admin) ? 'locked-title' : '' ?>">
                                <?= $display_title ?>
                                <?php if ($calendar_day == 30): ?>
                                    <span class="status-pill ctf"><i class="fa-solid fa-display"></i> PRESENTATION</span>
                                <?php elseif ($calendar_day == 29): ?>
                                    <span class="status-pill capstone"><i class="fa-solid fa-file-arrow-up"></i> PROJECT</span>
                                <?php elseif ($is_visible): ?>
                                    <span class="status-pill live"><i class="fa-solid fa-circle" style="font-size:0.4rem;"></i> LIVE</span>
                                <?php else: ?>
                                    <span class="status-pill locked"><i class="fa-solid fa-lock" style="font-size:0.6rem;"></i> <?= (!$is_unlocked_by_date && !$is_admin && !$has_hq_override) ? 'WAIT FOR DATE' : 'LOCKED' ?></span>
                                <?php endif; ?>

                                <?php if ($has_hq_override && !$is_admin): ?>
                                    <span class="status-pill" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3);">
                                        <i class="fa-solid fa-key"></i> HQ OVERRIDE
                                    </span>
                                <?php endif; ?>

                                <?php if ($is_opened && !$is_admin): ?>
                                    <span class="status-pill" style="background: rgba(0,243,255,0.1); color: #00f3ff; border: 1px solid rgba(0,243,255,0.2);">
                                        <i class="fa-solid fa-eye"></i> VIEWED
                                    </span>
                                <?php endif; ?>

                                <?php if ($is_admin): ?>
                                    <span class="status-pill" style="background: rgba(234,179,8,0.1); color: #eab308; border: 1px solid rgba(234,179,8,0.2);">
                                        <i class="fa-solid fa-user-shield"></i> HQ OVERRIDE
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="day-desc <?= (!$is_visible && !$is_admin) ? 'locked-desc' : '' ?>">
                                <?php if ($is_visible || $is_admin): ?>
                                    <?= nl2br($display_desc) ?>
                                    
                                    <?php if ($is_ctf): ?>
                                        <?php
                                        
                                        $saved_steps = [];
                                        if ($current_submission && !empty($current_submission['partial_steps'])) {
                                            $saved_steps = explode(',', $current_submission['partial_steps']);
                                        }
                                        $partial_notes_saved = $current_submission['partial_notes'] ?? '';
                                        $points_earned = $current_submission['points_awarded'] ?? 0;
                                        ?>
                                        
                                        
                                        <div class="glass-panel" style="border: 1px solid rgba(0, 243, 255, 0.25); background: linear-gradient(135deg, rgba(2, 6, 23, 0.95), rgba(15, 23, 42, 0.8)); border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 0 25px rgba(0, 243, 255, 0.1); position: relative; overflow: hidden;">
                                            <div style="position: absolute; top:0; right:0; padding: 6px 12px; background: rgba(0, 243, 255, 0.1); border-bottom-left-radius: 8px; font-family: 'Share Tech Mono', monospace; font-size: 0.75rem; color:#00f3ff; border-left:1px solid rgba(0, 243, 255, 0.2); border-bottom:1px solid rgba(0, 243, 255, 0.2);">
                                                CTF-LAB-STATUS: ACTIVE
                                            </div>
                                            
                                            <h3 style="color:#00f3ff; margin-top:0; font-family:'Rajdhani',sans-serif; letter-spacing:1.5px; font-size:1.4rem; display:flex; align-items:center; gap:10px; text-shadow: 0 0 10px rgba(0, 243, 255, 0.3);">
                                                <i class="fa-solid fa-network-wired" style="animation: pulse 2s infinite;"></i> 🕵️ OPERATIONAL BRIEFING: PORTAL INTRUSION LAB
                                            </h3>
                                            
                                            <p style="color: #94a3b8; font-size: 0.85rem; line-height: 1.7; margin-bottom: 20px;">
                                                Command reports a hidden, unauthorized administrator interface resides within the staging cluster of <strong style="color: #fff;">DIGIT Corp's corporate intranet</strong>. Your target is to bypass entry validations, escalate active privileges, and capture the system flag.
                                            </p>
                                            
                                            
                                            <div style="background: rgba(0, 243, 255, 0.03); border: 1px solid rgba(0, 243, 255, 0.15); border-radius: 8px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                                                <div>
                                                    <span style="font-family:'Share Tech Mono', monospace; font-size:0.75rem; color:#64748b; display:block;">TARGET HOST ENVIRONMENT</span>
                                                    <strong style="color:#fff; font-family:'Share Tech Mono', monospace; font-size:0.85rem;">STAGING_IP: internal.digitcorp.local</strong>
                                                </div>
                                                <a href="../CTF/index.html" target="_blank" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:10px 18px; border-radius:6px; font-size:0.8rem; font-weight:bold; background: linear-gradient(135deg, #00f3ff, #0284c7); border:none; color:#020617; box-shadow: 0 0 15px rgba(0, 243, 255, 0.3); transition: 0.3s;">
                                                    <i class="fa-solid fa-terminal"></i> Initialize Cyber Lab Sandbox
                                                </a>
                                            </div>

                                            
                                            <div style="background:rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; font-family:'Share Tech Mono', monospace; font-size:0.85rem;">
                                                <span style="color:#94a3b8;">Current Score Contribution:</span>
                                                <span style="color:#fbbf24; font-weight:bold; font-size:1rem;"><i class="fa-solid fa-bolt"></i> +<?= $points_earned ?> / 100 Leaderboard Points</span>
                                            </div>

                                            
                                            <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:20px; gap:5px;">
                                                <button type="button" onclick="switchCtfTab('flag-tab')" class="ctf-tab-btn active-tab" id="btn-flag-tab" style="background:none; border:none; color:#fff; padding:10px 15px; font-family:'Rajdhani',sans-serif; font-weight:bold; font-size:0.9rem; cursor:pointer; border-bottom:2px solid #00f3ff; transition:0.3s;">🚩 CAPTURE FLAG (100 PTS)</button>
                                                <button type="button" onclick="switchCtfTab('partial-tab')" class="ctf-tab-btn" id="btn-partial-tab" style="background:none; border:none; color:#64748b; padding:10px 15px; font-family:'Rajdhani',sans-serif; font-weight:bold; font-size:0.9rem; cursor:pointer; border-bottom:2px solid transparent; transition:0.3s;">💾 SUBMIT MILESTONES (15 PTS/STEP)</button>
                                            </div>

                                            
                                            <div id="ctf-flag-tab" class="ctf-tab-content">
                                                <?php if ($ctf_completed): ?>
                                                    <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 15px; text-align: center;">
                                                        <div style="color:#10b981; font-size:2rem; margin-bottom:10px;"><i class="fa-solid fa-trophy"></i></div>
                                                        <h4 style="color:#10b981; margin:0 0 5px 0; font-family:'Rajdhani',sans-serif; font-size:1.2rem;">SYSTEM CAPTURED SUCCESSFULLY</h4>
                                                        <p style="color:#a7f3d0; font-size:0.8rem; margin:0; font-family:'Share Tech Mono', monospace;">FLAG: <?= esc($current_submission['flag_submitted'] ?? 'CAPTURED') ?></p>
                                                    </div>
                                                <?php else: ?>
                                                    <form method="POST" style="display:flex; flex-direction:column; gap:12px;">
                                                        <input type="hidden" name="action" value="submit_ctf_flag">
                                                        <label style="color:#94a3b8; font-size:0.8rem; font-family:'Share Tech Mono', monospace;">ENTER ACTIVE SYSTEM ROOT FLAG:</label>
                                                        <div style="display:flex; gap:10px;">
                                                            <input type="text" name="ctf_flag" placeholder="flag{...}" required style="flex:1; background: rgba(0,0,0,0.4); border:1px solid rgba(0, 243, 255, 0.3); border-radius:6px; color:#fff; padding:10px 15px; font-family:'Share Tech Mono',monospace; font-size:0.9rem; outline:none; transition:0.3s;" onfocus="this.style.borderColor='#00f3ff'">
                                                            <button type="submit" class="btn-primary" style="padding: 10px 22px; font-size:0.85rem; border:none; border-radius:6px; background:#00f3ff; color:#020617; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                                                                <i class="fa-solid fa-flag"></i> Capture Flag
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            
                                            <div id="ctf-partial-tab" class="ctf-tab-content" style="display:none;">
                                                <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
                                                    <input type="hidden" name="action" value="submit_ctf_partial">
                                                    
                                                    <div style="color:#94a3b8; font-size:0.8rem; font-family:'Share Tech Mono', monospace; margin-bottom:5px;">SELECT ALL COMPLETED RECON / ATTACK PHASES:</div>
                                                    
                                                    <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                                                        <?php
                                                        $milestones = [
                                                            'step1' => 'Step 1: Analyzed landing page context / Briefing guidelines (+15 Pts)',
                                                            'step2' => 'Step 2: Inspected landing page DOM / found crawlers comments (+15 Pts)',
                                                            'step3' => 'Step 3: Located robots.txt configuration file (+15 Pts)',
                                                            'step4' => 'Step 4: Discovered restricted admin panel login page (+15 Pts)',
                                                            'step5' => 'Step 5: Inspected login DOM / parsed hidden EMP_ID comments (+15 Pts)',
                                                            'step6' => 'Step 6: Bypassed entry gateway / clear Admin dashboard clearance (+15 Pts)'
                                                        ];
                                                        foreach ($milestones as $key => $label):
                                                            $checked = in_array($key, $saved_steps) ? 'checked' : '';
                                                        ?>
                                                            <label style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:6px; color:#e2e8f0; font-size:0.8rem; cursor:pointer; user-select:none; transition:0.2s;" onmouseover="this.style.borderColor='rgba(0,243,255,0.3)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'">
                                                                <input type="checkbox" name="steps[]" value="<?= $key ?>" <?= $checked ?> style="accent-color:#00f3ff; width:16px; height:16px;">
                                                                <span><?= $label ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                                        <label style="color:#94a3b8; font-size:0.8rem; font-family:'Share Tech Mono', monospace;">WRITE-UP / NOTES / DISCOVERIES:</label>
                                                        <textarea name="partial_notes" placeholder="Explain your discoveries so far... what strategies did you use? What blocks did you hit?" rows="4" style="background: rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; padding:10px; font-size:0.82rem; font-family:inherit; outline:none; resize:none; transition:0.3s;" onfocus="this.style.borderColor='rgba(0,243,255,0.4)'"><?= esc($partial_notes_saved) ?></textarea>
                                                    </div>

                                                    <button type="submit" class="btn-primary" style="padding: 10px 22px; align-self:flex-start; font-size:0.85rem; border:none; border-radius:6px; background:#fbbf24; color:#020617; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                                                        <i class="fa-solid fa-floppy-disk"></i> Save &amp; Upload Milestone Progress
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <script>
                                        function switchCtfTab(tabId) {
                                            // Hide all tab content
                                            document.querySelectorAll('.ctf-tab-content').forEach(el => el.style.display = 'none');
                                            // Show target content
                                            document.getElementById('ctf-' + tabId).style.display = 'block';
                                            
                                            // Reset active classes on buttons
                                            document.querySelectorAll('.ctf-tab-btn').forEach(btn => {
                                                btn.style.color = '#64748b';
                                                btn.style.borderBottomColor = 'transparent';
                                                btn.classList.remove('active-tab');
                                            });
                                            
                                            // Highlight selected button
                                            const activeBtn = document.getElementById('btn-' + tabId);
                                            activeBtn.style.color = '#fff';
                                            activeBtn.style.borderBottomColor = '#00f3ff';
                                            activeBtn.classList.add('active-tab');
                                        }
                                        </script>
                                    <?php endif; ?>

                                    <?php if ($db_mod && !empty($db_mod['file_path']) && !$is_ctf): ?>
                                        <br>
                                        <a href="view_module.php?id=<?= $db_mod['id'] ?>" 
                                           class="module-file-link" <?= $is_admin ? 'target="_blank"' : '' ?>>
                                            <i class="fa-solid fa-file-shield"></i> <?= ($is_opened && !$is_admin) ? 'Re-open Module Material' : 'Open Secure Module Material' ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($current_day_of_month > $calendar_day): ?>
                                        <span style="color:#ef4444;"><i class="fa-solid fa-clock-rotate-left"></i> ACCESS EXPIRED: This module was only available on Day <?= $calendar_day ?>.</span>
                                    <?php elseif ($current_day_of_month < $calendar_day): ?>
                                        This module will automatically unlock ONLY on Day <?= $calendar_day ?> of the month.
                                    <?php else: ?>
                                        This module will be unlocked by HQ Command shortly.
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function toggleWeek(weekNum) {
    const hdr  = document.getElementById('week-hdr-'  + weekNum);
    const list = document.getElementById('day-list-'  + weekNum);
    const isOpen = list.classList.contains('open');

    if (isOpen) {
        list.classList.remove('open');
        hdr.classList.remove('open');
        list.style.display = 'none';
    } else {
        list.classList.add('open');
        hdr.classList.add('open');
        list.style.display = 'block';
    }
}

// Auto-open week with most active modules
document.addEventListener('DOMContentLoaded', function() {
    toggleWeek(1); // Open Week 1 by default
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
