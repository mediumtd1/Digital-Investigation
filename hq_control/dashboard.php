<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Admin Dashboard | ' . SITE_NAME;

$stats = [
    'total_interns' => 0,
    'active_interns' => 0,
    'total_officers' => 0,
    'active_officers' => 0,
    'pending_tasks' => 0,
    'open_tickets' => 0,
    'total_articles' => 0
];

try {
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS help_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_role VARCHAR(50),
        subject VARCHAR(255),
        message TEXT,
        status ENUM('Open','In Progress','Closed') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stats['total_interns']   = $pdo->query("SELECT COUNT(*) FROM interns")->fetchColumn();
    $stats['active_interns']  = $pdo->query("SELECT COUNT(*) FROM interns WHERE status = 'Active'")->fetchColumn();
    $stats['total_officers']  = $pdo->query("SELECT COUNT(*) FROM officers WHERE is_verified = 1")->fetchColumn();
    $stats['active_officers'] = $pdo->query("SELECT COUNT(*) FROM officers WHERE is_verified = 1")->fetchColumn();
    $stats['pending_tasks']   = $pdo->query("SELECT COUNT(*) FROM task_assignments WHERE status != 'Completed'")->fetchColumn();
    $stats['open_tickets']    = $pdo->query("SELECT COUNT(*) FROM help_tickets WHERE status = 'Open'")->fetchColumn();
    $stats['total_articles']  = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $stats['pending_officers']= $pdo->query("SELECT COUNT(*) FROM officers WHERE is_verified = 0")->fetchColumn();
} catch (\Exception $e) {
    $error_msg = "Critical System Error: " . $e->getMessage();
}

$is_system_empty = ($stats['total_interns'] == 0 && $stats['total_officers'] == 0);

$recent_activities = [];
try {
    $recent_activities = $pdo->query("
        SELECT a.*, COALESCE(i.name, o.name, 'System User') as user_name 
        FROM audit_logs a 
        LEFT JOIN interns i ON a.user_mapped_id = i.intern_id 
        LEFT JOIN officers o ON a.user_mapped_id = o.officer_id 
        ORDER BY a.created_at DESC LIMIT 10
    ")->fetchAll();
} catch (\Exception $e) {
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (!validate_csrf($_GET['csrf_token'] ?? '')) {
        http_response_code(403);
        die("Security Error: Invalid CSRF token.");
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

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        
        <header class="topbar">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png" style="height: 45px; width: 45px; object-fit: contain;">
                <h2 style="margin: 0;">Admin Dashboard</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <div
                        style="width: 40px; height: 40px; background: #eab308; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: black; font-weight: bold; font-size: 1.2rem;">
                        A</div>
                    <div class="user-info">
                        <span class="user-name">System Administrator</span>
                        <span class="user-role">Super Admin</span>
                        <span style="font-family: monospace; font-size: 0.8rem; color: #00f3ff; margin-top:2px;">IP:
                            <?= esc($_SERVER['REMOTE_ADDR']) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content" style="padding: 20px;">

        <?php if (isset($error_msg)): ?>
            <div class="glass-panel" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem;"></i>
                <div>
                    <h4 style="margin: 0;">System Diagnostic Alert</h4>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;"><?= esc($error_msg) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($is_system_empty): ?>
            <div class="glass-panel" style="background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308; color: #eab308; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fa-solid fa-plug-circle-exclamation" style="font-size: 2rem;"></i>
                    <div>
                        <h4 style="margin: 0;">Database appears to be uninitialized or empty</h4>
                        <p style="margin: 5px 0 0 0; font-size: 0.9rem;">No intern or officer records found. Check your database connection or re-import the SQL schema.</p>
                    </div>
                </div>
                <a href="reseed_modules.php" class="btn-primary" style="background: #eab308; color: #000;">Run Diagnostics</a>
            </div>
        <?php endif; ?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap:18px; margin-bottom:25px;">
    <div class="glass-panel stat-card" style="border-left:4px solid #60a5fa; cursor:pointer;" onclick="location.href='interns.php'">
        <i class="fa-solid fa-user-graduate stat-icon" style="color:#60a5fa;"></i>
        <div class="stat-value" style="color:#60a5fa;"><?= $stats['total_interns'] ?></div>
        <div class="stat-label">Total Interns</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;"><?= $stats['active_interns'] ?> active</div>
    </div>
    <div class="glass-panel stat-card" style="border-left:4px solid #94a3b8; cursor:pointer;" onclick="location.href='officers.php'">
        <i class="fa-solid fa-user-tie stat-icon" style="color:#94a3b8;"></i>
        <div class="stat-value" style="color:#94a3b8;"><?= $stats['total_officers'] ?></div>
        <div class="stat-label">Active Officers</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;"><?= $stats['pending_officers'] ?? 0 ?> pending approval</div>
    </div>
    <div class="glass-panel stat-card" style="border-left:4px solid #f59e0b; cursor:pointer;" onclick="location.href='tasks.php'">
        <i class="fa-solid fa-list-check stat-icon" style="color:#f59e0b;"></i>
        <div class="stat-value" style="color:#f59e0b;"><?= $stats['pending_tasks'] ?></div>
        <div class="stat-label">Pending Reviews</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;">Task submissions</div>
    </div>
    <div class="glass-panel stat-card" style="border-left:4px solid #ef4444; cursor:pointer;" onclick="location.href='support.php'">
        <i class="fa-solid fa-headset stat-icon" style="color:#ef4444;"></i>
        <div class="stat-value" style="color:#ef4444;"><?= $stats['open_tickets'] ?></div>
        <div class="stat-label">Open Tickets</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;">Support desk</div>
    </div>
    <div class="glass-panel stat-card" style="border-left:4px solid #00f3ff; cursor:pointer;" onclick="location.href='manage_articles.php'">
        <i class="fa-solid fa-newspaper stat-icon" style="color:#00f3ff;"></i>
        <div class="stat-value" style="color:#00f3ff;"><?= $stats['total_articles'] ?></div>
        <div class="stat-label">Published Articles</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;">Website content</div>
    </div>
    <div class="glass-panel stat-card" style="border-left:4px solid #ef4444; cursor:pointer;" onclick="location.href='pending_officers.php'">
        <i class="fa-solid fa-user-clock stat-icon" style="color:#ef4444;"></i>
        <div class="stat-value" style="color:#ef4444;"><?= $stats['pending_officers'] ?? 0 ?></div>
        <div class="stat-label">Pending Officers</div>
        <div style="font-size:0.7rem; color:#475569; margin-top:3px;">Awaiting verification</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    
    <div class="glass-panel">
        <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Recent
            System Activity</h3>
        <?php if (count($recent_activities) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User Name & ID</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activities as $log): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= date('d M, Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td>
                                <div style="font-weight: bold; color: var(--text-primary);"><?= esc($log['user_name'] ?? 'System User') ?></div>
                                <div style="font-family: monospace; color: #93c5fd; font-size: 0.8rem;"><?= esc($log['user_mapped_id'] ?: 'System') ?></div>
                            </td>
                            <td><span class="status-badge"
                                    style="background: rgba(234,179,8,0.1); color: #eab308;"><?= esc($log['user_role'] ?: 'System') ?></span>
                            </td>
                            <td><?= esc($log['action']) ?></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?= esc($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted);">No activity logged yet.</p>
        <?php endif; ?>
    </div>

    
    <div class="glass-panel">
        <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Status
            Overview</h3>
        <div style="margin-bottom: 20px;">
            <h4 style="color: var(--text-muted); margin-bottom: 5px;">Pending Tasks / Reviews</h4>
            <div style="font-size: 2rem; font-weight: bold; color: #f59e0b;"><?= $stats['pending_tasks'] ?></div>
        </div>
        <div>
            <h4 style="color: var(--text-muted); margin-bottom: 5px;">Open Support Tickets</h4>
            <div style="font-size: 2rem; font-weight: bold; color: #ef4444;"><?= $stats['open_tickets'] ?></div>
        </div>
        <div style="margin-top: 30px;">
            <a href="interns.php" class="btn-primary"
                style="display: block; width: 100%; text-align: center; margin-bottom: 10px;">Manage Interns</a>
            <a href="officers.php" class="btn-primary"
                style="display: block; width: 100%; text-align: center; background: #475569; margin-bottom: 10px;">Manage Officers</a>
            <a href="manage_articles.php" class="btn-primary"
                style="display: block; width: 100%; text-align: center; background: #0f172a; border: 1px solid #00f3ff; color: #00f3ff;">Manage Knowledge Articles</a>
        </div>
    </div>
</div>
</div>
</main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>