<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'System Audit Logs | ' . SITE_NAME;

if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $search = trim($_GET['search'] ?? '');
    $role = trim($_GET['role'] ?? '');
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to = trim($_GET['date_to'] ?? '');

    $where = ["1=1"];
    $params = [];
    if($search !== '') { $where[] = "action LIKE ?"; $params[] = "%$search%"; }
    if($role !== '') { $where[] = "user_role = ?"; $params[] = $role; }
    if($date_from !== '') { $where[] = "DATE(created_at) >= ?"; $params[] = $date_from; }
    if($date_to !== '') { $where[] = "DATE(created_at) <= ?"; $params[] = $date_to; }
    
    $where_sql = implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT id, created_at, user_mapped_id, user_role, action, ip_address FROM audit_logs WHERE $where_sql ORDER BY created_at DESC");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=System_Audit_Logs_'. date('Ymd') .'.csv');
    $output = fopen('php://output', 'w');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Log ID', 'Timestamp', 'User Identifier', 'Role', 'Action Executed', 'IP Address']);
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['created_at'],
            $log['user_mapped_id'],
            $log['user_role'],
            $log['action'],
            $log['ip_address']
        ]);
    }
    fclose($output);
    exit;
}

$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];
if($search !== '') { $where[] = "action LIKE ?"; $params[] = "%$search%"; }
if($role !== '') { $where[] = "user_role = ?"; $params[] = $role; }
if($date_from !== '') { $where[] = "DATE(created_at) >= ?"; $params[] = $date_from; }
if($date_to !== '') { $where[] = "DATE(created_at) <= ?"; $params[] = $date_to; }

$where_sql = implode(' AND ', $where);

$c_stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE $where_sql");
$c_stmt->execute($params);
$total_rows = $c_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$audit_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Cyber Security | Audit Tracker</h2>
            </div>
            
            <div class="topbar-right">
                
                <div style="background: rgba(255,255,255,0.05); padding: 5px; border-radius: 6px; display: flex; gap: 5px;">
                    <button onclick="setTheme('dark')" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fa-solid fa-moon"></i></button>
                    <button onclick="setTheme('light')" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fa-solid fa-sun"></i></button>
                    <button onclick="setTheme('system')" style="background:none; border:none; color:var(--text-primary); cursor:pointer;"><i class="fa-solid fa-desktop"></i></button>
                </div>
            </div>
        </header>

        <div class="dashboard-content">

                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid var(--border-color);">
                    <input type="text" name="search" class="form-control" placeholder="Search keywords..." value="<?= esc($search) ?>" style="flex: 2; min-width: 200px;">
                    
                    <select name="role" class="form-control" style="flex: 1; min-width: 150px;">
                        <option value="">All Roles</option>
                        <option value="Admin" <?= $role=='Admin'?'selected':'' ?>>Admin Base</option>
                        <option value="Officer" <?= $role=='Officer'?'selected':'' ?>>Officer Division</option>
                        <option value="Intern" <?= $role=='Intern'?'selected':'' ?>>Intern Force</option>
                    </select>

                    <div style="display: flex; gap: 5px; align-items: center; flex: 2; min-width: 250px;">
                        <input type="date" name="date_from" class="form-control" value="<?= esc($date_from) ?>">
                        <span style="color: var(--text-muted);">to</span>
                        <input type="date" name="date_to" class="form-control" value="<?= esc($date_to) ?>">
                    </div>

                    <button type="submit" class="btn-primary" style="flex: none; background: #64748b;">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                    <button type="submit" name="export_csv" value="1" class="btn-primary" style="flex: none; background: #10b981;">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </button>
                </form>
            </div>

            <div class="glass-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th># ID</th>
                            <th>Timestamp</th>
                            <th>Identifier</th>
                            <th>Role / Access</th>
                            <th>Action Executed</th>
                            <th>IPv4/IPv6 Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($audit_data as $log): 
                            
                            $row_bg = '';
                            $role_color = 'var(--text-muted)';
                            if ($log['user_role'] === 'Admin') {
                                $row_bg = 'background: rgba(59, 130, 246, 0.05);';
                                $role_color = '#3b82f6'; 
                            } elseif ($log['user_role'] === 'Officer') {
                                $row_bg = 'background: rgba(249, 115, 22, 0.05);';
                                $role_color = '#f97316'; 
                            } elseif ($log['user_role'] === 'Intern') {
                                $row_bg = 'background: rgba(16, 185, 129, 0.05);';
                                $role_color = '#10b981'; 
                            }
                        ?>
                        <tr style="<?= $row_bg ?>">
                            <td style="color: var(--text-muted); font-size: 0.8rem;"><?= $log['id'] ?></td>
                            <td style="font-family: monospace; font-size: 0.85rem; color: #cbd5e1;"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td style="font-weight: bold; color: var(--text-primary);"><?= esc($log['user_mapped_id']) ?></td>
                            <td><span style="color: <?= $role_color ?>; font-weight: bold; text-transform: uppercase; font-size: 0.8rem;"><i class="fa-solid fa-circle" style="font-size: 6px; vertical-align: middle;"></i> <?= esc($log['user_role']) ?></span></td>
                            <td style="color: #e2e8f0; font-size: 0.95rem;"><?= esc($log['action']) ?></td>
                            <td style="font-family: monospace; font-size: 0.85rem; color: #94a3b8;"><?= esc($log['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($audit_data)): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding:30px;">No tracking blocks match query.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 5px;">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?p=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" style="padding: 6px 12px; background: <?= $page == $i ? 'var(--accent-highlight)' : 'rgba(255,255,255,0.05)' ?>; color: <?= $page == $i ? '#fff' : 'var(--text-muted)' ?>; border-radius: 4px; text-decoration: none;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
