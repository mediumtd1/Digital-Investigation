<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'Dashboard | Intern Portal';

$intern_id = $_SESSION['user_id'];
$intern = ['name' => 'Intern', 'intern_id' => 'N/A', 'college' => 'N/A', 'course' => 'N/A', 'status' => 'Pending'];

try {
    
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN internship_status ENUM('Ongoing', 'Completed') DEFAULT 'Ongoing'"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE interns ADD COLUMN batch VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS internship_programmes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price VARCHAR(50),
        duration VARCHAR(50),
        image_url VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
    $stmt->execute([$intern_id]);
    $db_intern = $stmt->fetch();
    if ($db_intern) $intern = $db_intern;
} catch (Exception $e) {}

$attendance_present = 0; $attendance_total = 0; $tasks_completed = 0; $tasks_total = 0; $avg_score = 0; $cert_total = 0;

try {
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM intern_attendance WHERE intern_id = ? AND status = 'Present'");
    $stmt->execute([$intern_id]);
    $res = $stmt->fetch();
    $attendance_present = $res['total'] ?? 0;
} catch (Exception $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        priority ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
        assigned_by VARCHAR(100),
        due_date DATE,
        attachment VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT,
        intern_id INT,
        status ENUM('Assigned', 'In Progress', 'Completed') DEFAULT 'Assigned',
        submitted_at DATETIME,
        submission_file VARCHAR(255),
        feedback TEXT,
        score INT DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS advertisements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        image_url VARCHAR(255),
        link_url VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$programmes = [];
try {
    $stmt = $pdo->query("SELECT * FROM internship_programmes WHERE is_active = 1 ORDER BY created_at DESC");
    $programmes = $stmt->fetchAll();
} catch (Exception $e) {}

$best_intern = null;
$wall_of_fame = [];
try {
    $stmt = $pdo->query("SELECT * FROM featured_interns ORDER BY id DESC LIMIT 1");
    $best_intern = $stmt->fetch();
    
    if ($best_intern) {
        $stmt = $pdo->prepare("SELECT * FROM featured_interns WHERE id != ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$best_intern['id']]);
        $wall_of_fame = $stmt->fetchAll();
    }
} catch (Exception $e) {}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (!validate_csrf($_GET['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Security Error: Invalid CSRF token.");
    }
    if (isset($_SESSION['user_mapped_id'])) {
        log_audit($pdo, $_SESSION['user_mapped_id'], 'Intern', 'Logged Out');
    }
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    header("Location: login.php");
    exit;
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
/* Fancy Notification Bell Styles */
.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    transition: 0.3s;
}
.notification-bell:hover { background: rgba(0,243,255,0.1); }
.bell-pulse {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 10px;
    height: 10px;
    background: #00f3ff;
    border-radius: 50%;
    box-shadow: 0 0 10px #00f3ff;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 243, 255, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 243, 255, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 243, 255, 0); }
}

/* Academy Grid Styles */
.academy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 20px;
}
.programme-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    transition: 0.3s;
    display: flex;
    flex-direction: column;
}
.programme-card:hover { transform: translateY(-5px); border-color: var(--accent-blue); box-shadow: 0 10px 30px rgba(0,243,255,0.1); }
.programme-img { width: 100%; height: 180px; object-fit: cover; border-bottom: 1px solid var(--border-color); }
.programme-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
.programme-price { font-size: 1.25rem; color: #10b981; font-weight: bold; margin: 10px 0; }
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>

    
    <main class="main-content">
        
        <header class="topbar">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png" style="height: 45px; width: 45px; object-fit: contain;">
                <h2 style="margin: 0; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">INTERN CONSOLE</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php 
                        $u_photo = $intern['profile_photo'] ?? '';
                        $u_name = $intern['name'];
                        if (!empty($u_photo) && file_exists(__DIR__ . '/../' . $u_photo)) {
                            $photo_url = '../' . $u_photo;
                        } else {
                            $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($u_name) . "&background=00f3ff&color=000&bold=true";
                        }
                    ?>
                    <img src="<?= $photo_url ?>" alt="Profile" class="profile-pic" style="width:45px; height:45px; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent-blue);">
                    <div class="user-info">
                        <span class="user-name"><?= esc($intern['name']) ?></span>
                        <span class="user-role">Verified Intern</span>
                    </div>
                </div>
            </div>
        </header>

        
        <div class="dashboard-content">
            
            
            <div class="glass-panel profile-summary-card" style="margin-bottom: 25px; border-left: 4px solid var(--accent-blue);">
                <div style="position: relative;">
                    <img src="../<?= esc($intern['profile_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-summary-pic">
                    <div class="status-dot"></div>
                </div>
                <div class="profile-details">
                    <h3><?= esc($intern['name']) ?> <span class="status-badge"><i class="fa-solid fa-circle-check"></i> Verified</span></h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div class="intern-badge"><i class="fa-solid fa-fingerprint"></i> ID: <?= esc($intern['intern_id']) ?></div>
                        <?php if (!empty($intern['batch'])): ?>
                            <div class="intern-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff; border-color: rgba(0, 243, 255, 0.3); font-weight: bold;">
                                <i class="fa-solid fa-calendar-days"></i> Batch: <?= esc($intern['batch']) ?>
                            </div>
                        <?php else: ?>
                            <div class="intern-badge" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                                <i class="fa-solid fa-hourglass-half"></i> Batch: Not Assigned
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="color: var(--text-muted); margin-top: 10px; font-size: 0.85rem;">
                        <i class="fa-solid fa-building-columns"></i> <?= esc($intern['college']) ?><br>
                        <i class="fa-solid fa-calendar-day"></i> Enrolled: <?= date('d M, Y', strtotime($intern['created_at'])) ?>
                    </p>
                </div>
            </div>

            
            <div style="margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-family: 'Rajdhani', sans-serif; color: #fff; margin: 0; display: flex; align-items: center; gap: 12px;">
                        <i class="fa-solid fa-graduation-cap" style="color: var(--accent-blue);"></i> INTERNSHIP PROGRAMMES
                    </h2>
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Enhance your forensic skill-set</span>
                </div>

                <div class="academy-grid">
                    <?php if (empty($programmes)): ?>
                        <div class="glass-panel" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                            <p style="color: var(--text-muted);">No new programmes available at the moment. Stay tuned!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($programmes as $prog): ?>
                            <div class="programme-card">
                                <img src="../<?= esc($prog['image_url'] ?: 'assets/images/cyber-banner.jpg') ?>" class="programme-img">
                                <div class="programme-content">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <h3 style="margin: 0; font-size: 1.1rem; color: #fff;"><?= esc($prog['title']) ?></h3>
                                        <span style="background: rgba(0,243,255,0.1); color: var(--accent-blue); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;"><?= esc($prog['duration']) ?></span>
                                    </div>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 15px;">
                                        <?= esc($prog['description']) ?>
                                    </p>
                                    <div class="programme-price"><?= esc($prog['price']) ?></div>
                                    <a href="#" class="btn-primary" style="text-align: center; padding: 10px; border-radius: 6px; font-size: 0.9rem;">
                                        JOIN NOW <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                
                
                <div class="glass-panel">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Recent Tasks</h3>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT t.title, ta.status, t.due_date FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.intern_id = ? ORDER BY t.due_date DESC LIMIT 3");
                        $stmt->execute([$intern_id]);
                        $recent_tasks = $stmt->fetchAll();
                    } catch (Exception $e) { $recent_tasks = []; }
                    
                    if(count($recent_tasks) > 0) {
                        echo '<table style="width: 100%; text-align: left; border-collapse: collapse;">';
                        echo '<thead><tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);"><th style="padding: 10px;">Task Title</th><th>Status</th><th>Due Date</th></tr></thead><tbody>';
                        foreach($recent_tasks as $task) {
                            $status_color = $task['status'] == 'Completed' ? 'var(--success)' : ($task['status'] == 'In Progress' ? '#f59e0b' : 'var(--danger)');
                            echo '<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">';
                            echo '<td style="padding: 12px 10px;">' . esc($task['title']) . '</td>';
                            echo '<td><span style="color: '.$status_color.'; font-size: 0.85rem; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.6rem; vertical-align: middle;"></i> ' . esc($task['status']) . '</span></td>';
                            echo '<td style="color: var(--text-muted); font-size: 0.9rem;">' . date('d M, Y', strtotime($task['due_date'])) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="color: var(--text-muted);">No tasks assigned yet.</p>';
                    }
                    ?>
                </div>

                
                <div class="glass-panel" style="padding: 20px; display: flex; flex-direction: column;">
                    <!-- Tab Headers -->
                    <div style="display: flex; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; gap: 15px;">
                        <button onclick="switchFeaturedTab('best-month')" id="tab-btn-best" style="background:none; border:none; border-bottom:2px solid var(--accent-blue); color:#fff; font-weight:700; padding-bottom:8px; cursor:pointer; font-size:0.9rem;">
                            <i class="fa-solid fa-trophy" style="color:#fbbf24; margin-right:5px;"></i> Best of Month
                        </button>
                        <button onclick="switchFeaturedTab('wall-fame')" id="tab-btn-wall" style="background:none; border:none; border-bottom:2px solid transparent; color:var(--text-muted); font-weight:600; padding-bottom:8px; cursor:pointer; font-size:0.9rem;">
                            <i class="fa-solid fa-ranking-star" style="color:#a855f7; margin-right:5px;"></i> Wall of Fame
                        </button>
                    </div>

                    <!-- Tab 1: Best of Month -->
                    <div id="tab-best-month">
                        <?php if (empty($best_intern)): ?>
                            <div style="text-align:center; padding: 30px 10px; color: var(--text-muted);">
                                <i class="fa-solid fa-award" style="font-size: 2.5rem; margin-bottom: 10px; opacity:0.3;"></i>
                                <p style="font-size:0.85rem;">No featured intern for this month yet.<br>Keep performing best to be featured here!</p>
                            </div>
                        <?php else:
                            $share_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../featured.php?t=' . $best_intern['share_token'];
                        ?>
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <div style="background: rgba(0, 243, 255, 0.08); color: var(--accent-blue); padding: 5px 10px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; align-self: flex-start;">
                                    <?= esc($best_intern['featured_month']) ?>
                                </div>

                                <?php if ($best_intern['media_url']): ?>
                                    <div style="border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); background:#020617;">
                                        <?php if ($best_intern['media_type'] === 'video'): ?>
                                            <video src="../<?= esc($best_intern['media_url']) ?>" controls style="width:100%; display:block; max-height:220px; object-fit:contain;"></video>
                                        <?php else: ?>
                                            <img src="../<?= esc($best_intern['media_url']) ?>" style="width:100%; display:block; max-height:220px; object-fit:cover;">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <h4 style="margin: 5px 0 2px 0; font-size: 1.1rem; color: #fff; font-weight:700;"><?= esc($best_intern['name']) ?></h4>
                                <p style="margin: 0; color: #64748b; font-size: 0.8rem; font-weight:600;"><i class="fa-solid fa-graduation-cap"></i> <?= esc($best_intern['college']) ?></p>
                                
                                <p style="color: #94a3b8; font-size: 0.82rem; line-height: 1.5; margin: 10px 0; background:rgba(255,255,255,0.02); padding:10px; border-radius:6px; border: 1px solid rgba(255,255,255,0.04); max-height: 120px; overflow-y: auto;">
                                    <?= nl2br(esc($best_intern['description'])) ?>
                                </p>

                                <div style="display:flex; gap:10px; margin-top:5px;">
                                    <input type="text" readonly value="<?= esc($share_link) ?>" id="dashboard_share_url" style="display:none;">
                                    <button onclick="copyDashboardLink()" class="btn-primary" style="flex:1; padding:8px 12px; font-size:0.8rem; font-weight:700; border-radius:6px; border:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                                        <i class="fa-solid fa-share-nodes"></i> Share Link
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Wall of Fame -->
                    <div id="tab-wall-fame" style="display: none; max-height: 400px; overflow-y: auto;">
                        <?php if (empty($wall_of_fame)): ?>
                            <div style="text-align:center; padding: 30px 10px; color: var(--text-muted);">
                                <p style="font-size:0.85rem;">No previous features recorded.</p>
                            </div>
                        <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:15px;">
                                <?php foreach ($wall_of_fame as $past):
                                    $past_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../featured.php?t=' . $past['share_token'];
                                ?>
                                    <div style="display:flex; align-items:center; gap:12px; padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.04);">
                                        <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; flex-shrink:0; border:1px solid rgba(255,255,255,0.1); background:#020617;">
                                            <?php if ($past['media_type'] === 'video'): ?>
                                                <video src="../<?= esc($past['media_url']) ?>" style="width:100%; height:100%; object-fit:cover;" muted></video>
                                            <?php elseif ($past['media_url']): ?>
                                                <img src="../<?= esc($past['media_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($past['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex-grow:1; min-width:0;">
                                            <div style="font-weight:700; color:#fff; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= esc($past['name']) ?></div>
                                            <div style="font-size:0.72rem; color:#00f3ff; font-weight:600; margin-top:1px;"><?= esc($past['featured_month']) ?></div>
                                        </div>
                                        <button onclick="navigator.clipboard.writeText('<?= esc($past_link) ?>'); showToast('Link copied!', 'success');" style="background:rgba(255,255,255,0.05); border:none; border-radius:4px; padding:6px 8px; color:#94a3b8; cursor:pointer;" title="Copy Share Link">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <script>
                function switchFeaturedTab(tab) {
                    const bestTab = document.getElementById('tab-best-month');
                    const wallTab = document.getElementById('tab-wall-fame');
                    const btnBest = document.getElementById('tab-btn-best');
                    const btnWall = document.getElementById('tab-btn-wall');

                    if (tab === 'best-month') {
                        bestTab.style.display = 'block';
                        wallTab.style.display = 'none';
                        btnBest.style.borderBottomColor = 'var(--accent-blue)';
                        btnBest.style.color = '#fff';
                        btnWall.style.borderBottomColor = 'transparent';
                        btnWall.style.color = 'var(--text-muted)';
                    } else {
                        bestTab.style.display = 'none';
                        wallTab.style.display = 'block';
                        btnBest.style.borderBottomColor = 'transparent';
                        btnBest.style.color = 'var(--text-muted)';
                        btnWall.style.borderBottomColor = 'var(--accent-blue)';
                        btnWall.style.color = '#fff';
                    }
                }

                function copyDashboardLink() {
                    const copyText = document.getElementById("dashboard_share_url");
                    navigator.clipboard.writeText(copyText.value);
                    showToast("Shareable link copied to clipboard!", "success");
                }
                </script>

            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
