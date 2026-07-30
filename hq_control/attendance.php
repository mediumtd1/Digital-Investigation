<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Attendance Management | ' . SITE_NAME;

$target_date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

if (isset($_GET['export']) && $_GET['export'] == '1') {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    $start_date = "$year-$month-01";
    $end_date = date("Y-m-t", strtotime($start_date));
    
    $stmt = $pdo->prepare("
        SELECT i.name, i.intern_id, a.date, a.status 
        FROM interns i
        JOIN intern_attendance a ON i.id = a.intern_id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY i.name, a.date
    ");
    $stmt->execute([$start_date, $end_date]);
    $export_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Attendance_Report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Intern Name', 'Intern ID', 'Date', 'Status']);
    foreach ($export_data as $data) fputcsv($output, $data);
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $insert_stmt = $pdo->prepare("
        INSERT INTO intern_attendance (intern_id, date, status, remarks) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)
    ");
    
    $date_to_save = $_POST['attendance_date'];
    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
        foreach ($_POST['attendance'] as $intern_id => $data) {
            if (!empty($data['status'])) {
                $insert_stmt->execute([$intern_id, $date_to_save, $data['status'], trim($data['remarks'] ?? '')]);
            }
        }
    }
    header("Location: attendance.php?date=" . urlencode($date_to_save) . "&success=1");
    exit;
}

// ── DATA FETCH (no pagination — load all active interns) ────────────────────
$sql = "
    SELECT i.id, i.name, i.intern_id, i.college, a.status, a.remarks 
    FROM interns i 
    LEFT JOIN intern_attendance a ON i.id = a.intern_id AND a.date = ? 
    WHERE i.status = 'Active' 
";
$params = [$target_date];
if ($search) {
    $sql .= " AND (i.name LIKE ? OR i.intern_id LIKE ? OR i.college LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY i.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_interns = count($interns);


require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Attendance Management</h2>
            </div>
            <div class="topbar-right">
                <span class="badge" style="background: rgba(0,243,255,0.1); color: #00f3ff; border: 1px solid #00f3ff;">HQ ACCESS</span>
            </div>
        </header>

        <div class="dashboard-content" style="padding: 20px;">
            
            <div class="glass-panel" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <input type="date" name="date" class="form-control" value="<?= esc($target_date) ?>" onchange="this.form.submit()" style="width: auto; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid #334155;">
                    <input type="text" name="search" class="form-control" placeholder="Search Intern..." value="<?= esc($search) ?>" style="width: 250px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid #334155;">
                    <button type="submit" class="btn-primary" style="background: #334155;">Search</button>
                </form>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-primary" onclick="markAll('Present')" style="background-color: #10b981;">Mark All Present</button>
                    <a href="?export=1&month=<?= date('m', strtotime($target_date)) ?>&year=<?= date('Y', strtotime($target_date)) ?>" class="btn-primary" style="background: #4f46e5;">Export CSV</a>
                </div>
            </div>

            <div class="glass-panel">
                <form method="POST">
                    <input type="hidden" name="save_attendance" value="1">
                    <input type="hidden" name="attendance_date" value="<?= esc($target_date) ?>">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">
                        <h3 style="margin:0; color: #fff;">Attendance for <?= date('d F, Y', strtotime($target_date)) ?></h3>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <span style="font-size:0.78rem; color:#64748b;"><?= $total_interns ?> interns</span>
                            <button type="submit" class="btn-primary">Save All Changes</button>
                        </div>
                    </div>

                    <?php if (count($interns) > 0): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Intern Details</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interns as $intern): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: bold; color: #fff;"><?= esc($intern['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #94a3b8; font-family: monospace;"><?= esc($intern['intern_id']) ?> | <?= esc($intern['college']) ?></div>
                                    </td>
                                    <td>
                                        <select name="attendance[<?= $intern['id'] ?>][status]" class="form-control stat-select" style="background: #0f172a; color: #fff; border: 1px solid #334155;">
                                            <option value="">-- Select --</option>
                                            <option value="Present" <?= ($intern['status'] == 'Present') ? 'selected' : '' ?>>Present</option>
                                            <option value="Absent" <?= ($intern['status'] == 'Absent') ? 'selected' : '' ?>>Absent</option>
                                            <option value="Late" <?= ($intern['status'] == 'Late') ? 'selected' : '' ?>>Late</option>
                                            <option value="Holiday" <?= ($intern['status'] == 'Holiday') ? 'selected' : '' ?>>Holiday</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[<?= $intern['id'] ?>][remarks]" class="form-control" placeholder="Remarks..." value="<?= esc($intern['remarks'] ?? '') ?>" style="background: #0f172a; color: #fff; border: 1px solid #334155;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-solid fa-user-slash" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No active interns match your criteria.</p>
                        </div>
                    <?php endif; ?>
                </form>



            </div>
        </div>
    </main>
</div>

<script>
    function markAll(statusVal) {
        document.querySelectorAll('.stat-select').forEach(select => select.value = statusVal);
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
