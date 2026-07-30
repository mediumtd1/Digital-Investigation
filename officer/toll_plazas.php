<?php

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';
} catch (Exception $e) {
    error_log("Boot error: " . $e->getMessage());
    die("Service unavailable. Please try again later.");

}

check_auth('Officer'); 

$portal = 'officer';
$page_title = 'All India Toll Plaza List | ' . SITE_NAME;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS toll_plazas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plaza_code VARCHAR(100) NOT NULL UNIQUE,
        plaza_name VARCHAR(255) NOT NULL,
        type VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        district VARCHAR(100) NULL,
        concessionaire VARCHAR(255) NULL,
        location TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$state_filter = isset($_GET['state']) ? trim($_GET['state']) : '';

$limit = 25; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$toll_plazas = [];
$total_rows = 0;
$total_pages = 0;

if (!empty($search) || !empty($state_filter)) {
    $query_parts = [];
    $params = [];

    if (!empty($search)) {
        $query_parts[] = "(plaza_code LIKE ? OR plaza_name LIKE ? OR district LIKE ? OR concessionaire LIKE ? OR location LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($state_filter)) {
        $query_parts[] = "state = ?";
        $params[] = $state_filter;
    }

    $where_clause = "";
    if (count($query_parts) > 0) {
        $where_clause = "WHERE " . implode(" AND ", $query_parts);
    }

    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM toll_plazas {$where_clause}");
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $limit;

    
    $select_sql = "SELECT * FROM toll_plazas {$where_clause} ORDER BY plaza_name ASC LIMIT {$limit} OFFSET {$offset}";
    $stmt = $pdo->prepare($select_sql);
    $stmt->execute($params);
    $toll_plazas = $stmt->fetchAll();
}

$states = $pdo->query("SELECT DISTINCT state FROM toll_plazas WHERE state != '' AND state IS NOT NULL ORDER BY state")->fetchAll(PDO::FETCH_COLUMN);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
/* Disable Text Selection and Copying */
.no-select-container {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
}

/* Print Prevention Overrides */
@media print {
    body, html, .portal-container, .main-content, .dashboard-content, .glass-panel {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
    }
}
</style>

<div class="portal-container no-select-container" id="protectedContent">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>All India Toll Plazas</h2>
            </div>
            <div class="topbar-right">
                <span class="badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff; border: 1px solid rgba(0, 243, 255, 0.2);">
                    <i class="fa-solid fa-eye"></i> View-Only Access Enabled
                </span>
            </div>
        </header>

        <div class="dashboard-content">
            
            <div class="glass-panel" style="margin-bottom: 20px; padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search by plaza code, name, district, location..." value="<?= esc($search) ?>">
                    </div>
                    <div style="width: 200px;">
                        <select name="state" class="form-control">
                            <option value="">All States</option>
                            <?php foreach($states as $st): ?>
                                <option value="<?= esc($st) ?>" <?= $state_filter == $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="background: #00f3ff; color: #000; font-weight: bold; min-width: 140px; box-shadow: 0 0 20px rgba(0, 243, 255, 0.4); border: none;">
                        <i class="fa-solid fa-filter"></i> APPLY FILTER
                    </button>
                    <?php if($search || $state_filter): ?>
                        <a href="toll_plazas.php" class="btn-primary" style="background: rgba(255,255,255,0.05); text-decoration: none; border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <i class="fa-solid fa-rotate-left"></i> RESET
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            
            <div class="glass-panel" style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="min-width: 100px; padding: 10px 14px;">Plaza Code</th>
                            <th style="min-width: 170px; padding: 10px 14px;">Plaza Name</th>
                            <th style="min-width: 100px; padding: 10px 14px;">Type</th>
                            <th style="min-width: 130px; padding: 10px 14px;">State</th>
                            <th style="min-width: 120px; padding: 10px 14px;">District</th>
                            <th style="min-width: 160px; padding: 10px 14px;">Concessionaire</th>
                            <th style="min-width: 200px; padding: 10px 14px;">Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($search) && empty($state_filter)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #00f3ff; padding: 40px; font-weight: bold; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">
                                    <i class="fa-solid fa-circle-info" style="color: #00f3ff; margin-right: 8px;"></i>
                                    PLEASE ENTER A SEARCH QUERY OR SELECT A STATE FILTER TO VIEW TOLL PLAZAS
                                </td>
                            </tr>
                        <?php elseif (empty($toll_plazas)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No toll plaza records found.</td>
                            </tr>
                        <?php else: foreach ($toll_plazas as $tp): ?>
                            <tr>
                                <td style="font-weight: bold; color: #00f3ff; font-family: monospace; padding: 10px 14px;"><?= esc($tp['plaza_code']) ?></td>
                                <td style="font-weight: bold; color: #fff; padding: 10px 14px;"><?= esc($tp['plaza_name']) ?></td>
                                <td style="padding: 10px 14px;"><?= esc($tp['type'] ?: 'N/A') ?></td>
                                <td style="padding: 10px 14px;"><span class="status-badge" style="background: rgba(234,179,8,0.1); color: #eab308;"><?= esc($tp['state'] ?: 'N/A') ?></span></td>
                                <td style="padding: 10px 14px;"><?= esc($tp['district'] ?: 'N/A') ?></td>
                                <td style="font-size: 0.85rem; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 10px 14px;" title="<?= esc($tp['concessionaire']) ?>"><?= esc($tp['concessionaire'] ?: 'N/A') ?></td>
                                <td style="font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 10px 14px;" title="<?= esc($tp['location']) ?>"><?= esc($tp['location'] ?: 'N/A') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                
                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-top: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.9rem;">
                            Showing page <?= $page ?> of <?= $total_pages ?> (Total <?= $total_rows ?> entries)
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <?php if ($page > 1): ?>
                                <a href="?page=1&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>" class="btn-primary" style="padding: 5px 10px; text-decoration: none;">&laquo; First</a>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>" class="btn-primary" style="padding: 5px 10px; text-decoration: none;">Prev</a>
                            <?php endif; ?>

                            <?php 
                            $start_p = max(1, $page - 2);
                            $end_p = min($total_pages, $page + 2);
                            for ($i = $start_p; $i <= $end_p; $i++): 
                                $active_style = ($i === $page) ? 'background:#00f3ff; color:#000; font-weight:bold;' : '';
                            ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>" class="btn-primary" style="padding: 5px 12px; text-decoration: none; <?= $active_style ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>" class="btn-primary" style="padding: 5px 10px; text-decoration: none;">Next</a>
                                <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&state=<?= urlencode($state_filter) ?>" class="btn-primary" style="padding: 5px 10px; text-decoration: none;">Last &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const protectedContainer = document.getElementById("protectedContent");

    // 1. Disable Right Click Context Menu
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
        alert("Action Restricted: Context menu is disabled on this secure directory.");
        return false;
    });

    // 2. Disable Copy/Cut
    document.addEventListener("copy", function(e) {
        e.preventDefault();
        alert("Action Restricted: Copying data from this page is prohibited.");
        return false;
    });
    document.addEventListener("cut", function(e) {
        e.preventDefault();
        return false;
    });

    // 3. Intercept Keyboard Shortcuts
    document.addEventListener("keydown", function(e) {
        // Prevent Print: Ctrl+P or Cmd+P
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert("Action Restricted: Printing/Saving PDF of this directory is prohibited.");
            return false;
        }

        // Prevent Save: Ctrl+S or Cmd+S
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert("Action Restricted: Saving this page is prohibited.");
            return false;
        }

        // Prevent Copy Shortcut: Ctrl+C or Cmd+C
        if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert("Action Restricted: Copying data from this page is prohibited.");
            return false;
        }

        // Prevent Developer Options: F12, Ctrl+Shift+I, Cmd+Opt+I, Ctrl+Shift+J, Ctrl+U
        if (e.key === 'F12' || 
            ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'i' || e.key === 'j')) ||
            ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U'))) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert("Security System: Developer inspection tools are disabled.");
            return false;
        }
    });

    // 4. Double Guard: Blur and Hide content during print spooling
    window.addEventListener("beforeprint", function() {
        protectedContainer.style.display = "none";
    });
    window.addEventListener("afterprint", function() {
        protectedContainer.style.display = "flex";
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
