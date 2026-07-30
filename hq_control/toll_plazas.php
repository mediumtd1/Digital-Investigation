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

check_auth('Admin'); 

$portal = 'admin';
$page_title = 'All India Toll Plaza Directory | ' . SITE_NAME;

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
    die("TABLE CREATION ERROR: " . $e->getMessage());
}

$success_msg = '';
$error_msg = '';

if (isset($_SESSION['toast_msg'])) {
    $success_msg = $_SESSION['toast_msg'];
    unset($_SESSION['toast_msg']);
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM toll_plazas WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "Toll Plaza entry deleted successfully.";
        header("Location: toll_plazas.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_toll_plaza'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }

    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $plaza_code = trim($_POST['plaza_code']);
    $plaza_name = trim($_POST['plaza_name']);
    $type = trim($_POST['type']);
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $concessionaire = trim($_POST['concessionaire']);
    $location = trim($_POST['location']);

    if (empty($plaza_code) || empty($plaza_name)) {
        $error_msg = "Plaza Code and Plaza Name are required.";
    } else {
        try {
            if ($id) {
                
                $stmt = $pdo->prepare("UPDATE toll_plazas SET plaza_code = ?, plaza_name = ?, type = ?, state = ?, district = ?, concessionaire = ?, location = ? WHERE id = ?");
                $stmt->execute([$plaza_code, $plaza_name, $type, $state, $district, $concessionaire, $location, $id]);
                $_SESSION['toast_msg'] = "Toll Plaza updated successfully.";
            } else {
                
                $stmt = $pdo->prepare("INSERT INTO toll_plazas (plaza_code, plaza_name, type, state, district, concessionaire, location) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE plaza_name = VALUES(plaza_name), type = VALUES(type), state = VALUES(state), district = VALUES(district), concessionaire = VALUES(concessionaire), location = VALUES(location)");
                $stmt->execute([$plaza_code, $plaza_name, $type, $state, $district, $concessionaire, $location]);
                $_SESSION['toast_msg'] = "Toll Plaza added successfully.";
            }
            header("Location: toll_plazas.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_msg = "Duplicate entry: A Toll Plaza with code '{$plaza_code}' already exists.";
            } else {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_upload_csv'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($file_tmp, "r")) !== FALSE) {
            $inserted = 0;
            $updated = 0;
            $row_idx = 0;
            
            
            $stmt = $pdo->prepare("INSERT INTO toll_plazas (plaza_code, plaza_name, type, state, district, concessionaire, location) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE plaza_name = VALUES(plaza_name), type = VALUES(type), state = VALUES(state), district = VALUES(district), concessionaire = VALUES(concessionaire), location = VALUES(location)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_idx++;
                
                
                if ($row_idx === 1 && (preg_match('/code|name|type|state/i', $data[0]) || preg_match('/code|name|type|state/i', $data[1] ?? ''))) {
                    continue;
                }
                
                
                $p_code = isset($data[0]) ? trim($data[0]) : '';
                $p_name = isset($data[1]) ? trim($data[1]) : '';
                $p_type = isset($data[2]) ? trim($data[2]) : '';
                $p_state = isset($data[3]) ? trim($data[3]) : '';
                $p_dist = isset($data[4]) ? trim($data[4]) : '';
                $p_concess = isset($data[5]) ? trim($data[5]) : '';
                $p_loc = isset($data[6]) ? trim($data[6]) : '';
                
                if (empty($p_code) || empty($p_name)) {
                    continue; 
                }
                
                try {
                    
                    $chk = $pdo->prepare("SELECT id FROM toll_plazas WHERE plaza_code = ?");
                    $chk->execute([$p_code]);
                    if ($chk->fetch()) {
                        $updated++;
                    } else {
                        $inserted++;
                    }
                    
                    $stmt->execute([$p_code, $p_name, $p_type, $p_state, $p_dist, $p_concess, $p_loc]);
                } catch (Exception $e) {
                    
                }
            }
            fclose($handle);
            $_SESSION['toast_msg'] = "Bulk import completed. Inserted: {$inserted}, Updated: {$updated} entries.";
            header("Location: toll_plazas.php");
            exit;
        } else {
            $error_msg = "Failed to read the uploaded CSV file.";
        }
    } else {
        $error_msg = "Please select a valid CSV file.";
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$state_filter = isset($_GET['state']) ? trim($_GET['state']) : '';

$limit = 25; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

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

$states = $pdo->query("SELECT DISTINCT state FROM toll_plazas WHERE state != '' AND state IS NOT NULL ORDER BY state")->fetchAll(PDO::FETCH_COLUMN);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>All India Toll Plazas</h2>
            </div>
            <div class="topbar-right" style="display: flex; gap: 10px;">
                <button class="btn-primary" onclick="openBulkModal()" style="background: rgba(234, 179, 8, 0.2); border: 1px solid #eab308; color: #eab308;">
                    <i class="fa-solid fa-file-csv"></i> Bulk Upload CSV
                </button>
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Plaza
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if (!empty($success_msg)): ?>
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #6ee7b7;">
                <i class="fa-solid fa-circle-check"></i> <?= esc($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #fca5a5;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= esc($error_msg) ?>
            </div>
            <?php endif; ?>

            
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
                            <th style="text-align: center; background: rgba(0, 243, 255, 0.05); min-width: 180px; padding: 15px 20px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($toll_plazas)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No toll plaza records found.</td>
                            </tr>
                        <?php else: foreach ($toll_plazas as $tp): ?>
                            <tr>
                                <td style="font-weight: bold; color: #00f3ff; font-family: monospace; padding: 15px 20px;"><?= esc($tp['plaza_code']) ?></td>
                                <td style="font-weight: bold; color: #fff; padding: 15px 20px;"><?= esc($tp['plaza_name']) ?></td>
                                <td style="padding: 15px 20px;"><?= esc($tp['type'] ?: 'N/A') ?></td>
                                <td style="padding: 15px 20px;"><span class="status-badge" style="background: rgba(234,179,8,0.1); color: #eab308;"><?= esc($tp['state'] ?: 'N/A') ?></span></td>
                                <td style="padding: 15px 20px;"><?= esc($tp['district'] ?: 'N/A') ?></td>
                                <td style="font-size: 0.85rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 15px 20px;" title="<?= esc($tp['concessionaire']) ?>"><?= esc($tp['concessionaire'] ?: 'N/A') ?></td>
                                <td style="font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 15px 20px;" title="<?= esc($tp['location']) ?>"><?= esc($tp['location'] ?: 'N/A') ?></td>
                                <td style="text-align: center; background: rgba(0, 243, 255, 0.02); padding: 15px 20px;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button class="status-badge" style="background: #00f3ff; color: #000; cursor: pointer; border: none; font-weight: bold; padding: 5px 12px;" onclick='openEditModal(<?= htmlspecialchars(json_encode($tp), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fa-solid fa-pen"></i> EDIT
                                        </button>
                                        <a href="?delete=<?= $tp['id'] ?>" class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; text-decoration: none; padding: 5px 12px;" onclick="return confirm('Are you sure you want to delete this toll plaza record?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
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

<div id="tollPlazaModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 650px; width: 95%;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Toll Plaza Entry</h3>
            <span class="close" onclick="closeTollPlazaModal()">&times;</span>
        </div>
        <form method="POST" class="cyber-form">
            <?php csrf_field(); ?>
            <input type="hidden" name="save_toll_plaza" value="1">
            <input type="hidden" name="id" id="field_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Plaza Code *</label>
                    <input type="text" name="plaza_code" id="field_plaza_code" class="form-control" required placeholder="e.g. 12005">
                </div>
                <div class="form-group">
                    <label class="form-label">Plaza Name *</label>
                    <input type="text" name="plaza_name" id="field_plaza_name" class="form-control" required placeholder="e.g. Kherki Daula">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <input type="text" name="type" id="field_type" class="form-control" placeholder="e.g. Public Funded / BOT">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" id="field_state" class="form-control" placeholder="e.g. Haryana">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">District</label>
                    <input type="text" name="district" id="field_district" class="form-control" placeholder="e.g. Gurgaon">
                </div>
                <div class="form-group">
                    <label class="form-label">Concessionaire</label>
                    <input type="text" name="concessionaire" id="field_concessionaire" class="form-control" placeholder="e.g. Millennium City Expressways">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Location / Chainage Details</label>
                <textarea name="location" id="field_location" class="form-control" rows="3" placeholder="e.g. km 42.000 on NH-8"></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px; font-weight: bold; background: #00f3ff; color: #000; box-shadow: 0 0 15px rgba(0, 243, 255, 0.3); border: none;">
                Save Toll Plaza Entry
            </button>
        </form>
    </div>
</div>

<div id="bulkUploadModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 500px; width: 95%;">
        <div class="modal-header">
            <h3>Bulk CSV Import</h3>
            <span class="close" onclick="closeBulkModal()">&times;</span>
        </div>
        <form method="POST" class="cyber-form" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <input type="hidden" name="bulk_upload_csv" value="1">

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required style="padding: 10px; background: rgba(0,0,0,0.2);">
                <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                    CSV columns must be in this order:<br>
                    <strong>Plaza Code, Plaza Name, Type, State, District, Concessionaire, Location</strong>
                </small>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; font-weight: bold; background: #eab308; color: #000; box-shadow: 0 0 15px rgba(234, 179, 8, 0.3); border: none;">
                <i class="fa-solid fa-upload"></i> Start Upload & Merge
            </button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Toll Plaza Entry';
    document.getElementById('field_id').value = '';
    document.getElementById('field_plaza_code').value = '';
    document.getElementById('field_plaza_name').value = '';
    document.getElementById('field_type').value = '';
    document.getElementById('field_state').value = '';
    document.getElementById('field_district').value = '';
    document.getElementById('field_concessionaire').value = '';
    document.getElementById('field_location').value = '';
    document.getElementById('tollPlazaModal').style.display = 'flex';
}

function openEditModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Toll Plaza Entry';
    document.getElementById('field_id').value = data.id;
    document.getElementById('field_plaza_code').value = data.plaza_code;
    document.getElementById('field_plaza_name').value = data.plaza_name;
    document.getElementById('field_type').value = data.type;
    document.getElementById('field_state').value = data.state;
    document.getElementById('field_district').value = data.district;
    document.getElementById('field_concessionaire').value = data.concessionaire;
    document.getElementById('field_location').value = data.location;
    document.getElementById('tollPlazaModal').style.display = 'flex';
}

function closeTollPlazaModal() {
    document.getElementById('tollPlazaModal').style.display = 'none';
}

function openBulkModal() {
    document.getElementById('bulkUploadModal').style.display = 'flex';
}

function closeBulkModal() {
    document.getElementById('bulkUploadModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('cyber-modal')) {
        closeTollPlazaModal();
        closeBulkModal();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
