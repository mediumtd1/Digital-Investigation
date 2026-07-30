<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'FIR Management | ' . SITE_NAME;
$officer_id = $_SESSION['user_id'];

$year = date('Y');
$district_code = strtoupper(substr($_SESSION['user_mapped_id'], 10, 3)); 
if (strlen($district_code) != 3) {
    
    $stmt = $pdo->prepare("SELECT district FROM officers WHERE id = ?");
    $stmt->execute([$officer_id]);
    $district = $stmt->fetchColumn();
    $district_code = strtoupper(substr($district, 0, 3));
}

$stmt = $pdo->prepare("SELECT fir_number FROM firs WHERE fir_number LIKE ? ORDER BY id DESC LIMIT 1");
$stmt->execute(["FIR/CYB/$year/$district_code/%"]);
$latest_fir = $stmt->fetchColumn();

if ($latest_fir) {
    $parts = explode('/', $latest_fir);
    $seq = (int)end($parts);
    $seq++;
} else {
    $seq = 1;
}
$new_fir_number = "FIR/CYB/$year/$district_code/" . str_pad($seq, 4, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_fir'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    
    $comp_json = json_encode([
        'address' => $_POST['comp_address'] ?? '',
        'age' => (int)$_POST['comp_age'],
        'gender' => $_POST['comp_gender'] ?? '',
        'district' => $_POST['comp_district'] ?? '',
        'aadhaar' => $_POST['comp_aadhaar'] ?? ''
    ]);

    $crime_details_json = json_encode([
        'platform' => $_POST['platform'] ?? '',
        'financial_loss' => $_POST['financial_loss'] ?? '',
        'detailed_desc' => $_POST['description'] ?? ''
    ]);

    $accused_json = json_encode([
        'name' => $_POST['accused_name'] ?? '',
        'address' => $_POST['accused_address'] ?? '',
        'mobile' => $_POST['accused_mobile'] ?? '',
        'ip' => $_POST['accused_ip'] ?? '',
        'social' => $_POST['accused_social'] ?? '',
        'email' => $_POST['accused_email'] ?? ''
    ]);

    $incident_date = $_POST['incident_date'] . ' ' . $_POST['incident_time'] . ':00';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO firs (fir_number, complainant_name, complainant_mobile, complainant_email, complainant_address, crime_type, incident_date, description, accused_details, assigned_officer_id, filed_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Registered')
        ");
        
        $stmt->execute([
            $new_fir_number,
            trim($_POST['comp_name']),
            trim($_POST['comp_mobile']),
            trim($_POST['comp_email']),
            $comp_json, 
            $_POST['crime_type'],
            $incident_date,
            $crime_details_json, 
            $accused_json, 
            $officer_id, 
            $officer_id
        ]);
        
        $fir_id = $pdo->lastInsertId();

        
        if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/evidence/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            foreach ($_FILES['evidence']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['evidence']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['evidence']['name'][$key], PATHINFO_EXTENSION));
                    if (in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png', 'txt', 'csv'])) {
                        
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                        $allowed_mimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'text/plain', 'text/csv'];
                        
                        if (in_array($mime, $allowed_mimes)) {
                            $new_name = uniqid('evd_', true) . '.' . $ext;
                            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                            
                            $ev_stmt = $pdo->prepare("INSERT INTO evidence (case_id, file_name, file_path, file_type, uploaded_by, custody_log) VALUES (?, ?, ?, ?, ?, ?)");
                            $ev_stmt->execute([
                                $fir_id,
                                $_FILES['evidence']['name'][$key],
                                'uploads/evidence/' . $new_name,
                                $ext,
                                $officer_id,
                                json_encode([['date' => date('Y-m-d H:i:s'), 'action' => 'Uploaded during FIR creation', 'officer' => $officer_id]])
                            ]);
                            }
                        }
                    }
                }
            }
        }

        
        $cd_stmt = $pdo->prepare("INSERT INTO case_diary_entries (fir_id, officer_id, entry_date, notes) VALUES (?, ?, NOW(), ?)");
        $cd_stmt->execute([$fir_id, $officer_id, "FIR Filed and Registered. Initial assessment underway."]);

        $pdo->commit();

        log_audit($pdo, $officer_id, 'Officer', 'Filed new FIR: ' . $new_fir_number);
        $_SESSION['toast_msg'] = "FIR Registered Successfully: " . $new_fir_number;
        $_SESSION['toast_type'] = "success";
        
        header("Location: print_fir.php?id=" . $fir_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $form_error = "Failed to file FIR: " . $e->getMessage();
    }
}

$active_tab = $_GET['tab'] ?? 'new'; 

$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['crime_type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_date_from = trim($_GET['date_from'] ?? '');
$filter_date_to = trim($_GET['date_to'] ?? '');

$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(fir_number LIKE ? OR complainant_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_type !== '') {
    $where_clauses[] = "crime_type = ?";
    $params[] = $filter_type;
}
if ($filter_status !== '') {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
}
if ($filter_date_from !== '') {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to !== '') {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
}

$where_sql = implode(' AND ', $where_clauses);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM firs WHERE $where_sql");
$count_stmt->execute($params);
$total_firs = $count_stmt->fetchColumn();
$total_pages = ceil($total_firs / $limit);

$stmt = $pdo->prepare("SELECT id, fir_number, complainant_name, crime_type, created_at, status FROM firs WHERE $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$fir_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>FIR Management Gateway</h2>
            </div>
        </header>

        <div class="dashboard-content">
            
            <?php if (isset($form_error)): ?>
                <div style="background: rgba(220, 38, 38, 0.2); border-left: 4px solid var(--danger); padding: 15px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> <?= esc($form_error) ?>
                </div>
            <?php endif; ?>

            
            <div style="display: flex; gap: 10px; margin-bottom: 25px;">
                <a href="?tab=new" class="glass-panel" style="flex:1; text-align:center; padding: 15px; font-size: 1.1rem; font-weight: bold; border-bottom: 4px solid <?= $active_tab == 'new' ? '#ef4444' : 'transparent' ?>; color: <?= $active_tab == 'new' ? 'white' : 'var(--text-muted)' ?>;">
                    <i class="fa-solid fa-folder-plus"></i> File New FIR
                </a>
                <a href="?tab=all" class="glass-panel" style="flex:1; text-align:center; padding: 15px; font-size: 1.1rem; font-weight: bold; border-bottom: 4px solid <?= $active_tab == 'all' ? '#3b82f6' : 'transparent' ?>; color: <?= $active_tab == 'all' ? 'white' : 'var(--text-muted)' ?>;">
                    <i class="fa-solid fa-layer-group"></i> All FIR Directory
                </a>
            </div>

            
            <?php if ($active_tab === 'new'): ?>
            <div class="glass-panel" style="border-top: 4px solid #ef4444;">
                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="file_fir" value="1">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                        <h3 style="margin:0;"><i class="fa-solid fa-file-pen"></i> Initial Registration Form</h3>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 5px 15px; border-radius: 4px; font-family: monospace; font-size: 1.1rem; font-weight: bold; color: #ef4444;">
                            <?= $new_fir_number ?>
                        </div>
                    </div>

                    
                    <h4 style="color: #60a5fa; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;">A. Complainant Details</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="comp_name" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Age *</label><input type="number" name="comp_age" class="form-control" required min="10"></div>
                        <div class="form-group"><label class="form-label">Gender *</label>
                            <select name="comp_gender" class="form-control" required>
                                <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Mobile Number *</label><input type="tel" name="comp_mobile" class="form-control" required placeholder="10 Digits"></div>
                        <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="comp_email" class="form-control"></div>
                        <div class="form-group"><label class="form-label">Aadhaar (Optional)</label><input type="text" name="comp_aadhaar" class="form-control" placeholder="12 Digits"></div>
                        <div class="form-group" style="grid-column: span 2;"><label class="form-label">Residential Address *</label><input type="text" name="comp_address" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">District *</label>
                            <select name="comp_district" class="form-control" required>
                                <option value="Bhopal">Bhopal</option><option value="Indore">Indore</option><option value="Gwalior">Gwalior</option><option value="Ujjain">Ujjain</option><option value="Jabalpur">Jabalpur</option>
                            </select>
                        </div>
                    </div>

                    
                    <h4 style="color: #60a5fa; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px;">B. Incident Intelligence</h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                        <div class="form-group"><label class="form-label">Nature of Cyber Crime *</label>
                            <select name="crime_type" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="Online Fraud">Online Fraud</option>
                                <option value="Identity Theft">Identity Theft</option>
                                <option value="Hacking">Hacking</option>
                                <option value="Cyberbullying">Cyberbullying</option>
                                <option value="Phishing">Phishing</option>
                                <option value="Child Exploitation">Child Exploitation</option>
                                <option value="Ransomware">Ransomware</option>
                                <option value="Social Media Crime">Social Media Crime</option>
                                <option value="Email Spoofing">Email Spoofing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="form-group"><label class="form-label">Date *</label><input type="date" name="incident_date" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">Time *</label><input type="time" name="incident_time" class="form-control" required></div>
                        </div>
                        <div class="form-group"><label class="form-label">Platform/Website Involved</label><input type="text" name="platform" class="form-control" placeholder="e.g. Facebook, fake-bank.com"></div>
                        <div class="form-group"><label class="form-label">Financial Loss (₹) (Optional)</label><input type="number" name="financial_loss" class="form-control" placeholder="Amount in INR"></div>
                        <div class="form-group" style="grid-column: span 2;"><label class="form-label">Detailed Description of Event *</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Include chronological details..." required minlength="100"></textarea>
                        </div>
                        <div class="form-group" style="grid-column: span 2;"><label class="form-label">Evidence Documentation (Images/PDFs/Logs)</label>
                            <input type="file" name="evidence[]" class="form-control" multiple accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.txt,.csv" style="background: rgba(0,0,0,0.2);">
                        </div>
                    </div>

                    
                    <details style="margin-bottom: 30px; background: rgba(255,255,255,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <summary style="color: #60a5fa; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1px; cursor: pointer; outline: none; font-weight: bold;">
                            <i class="fa-solid fa-chevron-down"></i> C. Suspect/Accused Details (Optional)
                        </summary>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px;">
                            <div class="form-group"><label class="form-label">Name / Alias</label><input type="text" name="accused_name" class="form-control"></div>
                            <div class="form-group"><label class="form-label">Mobile Number</label><input type="text" name="accused_mobile" class="form-control"></div>
                            <div class="form-group"><label class="form-label">IP Address (if any)</label><input type="text" name="accused_ip" class="form-control" placeholder="xxx.xxx.xxx.xxx"></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="email" name="accused_email" class="form-control"></div>
                            <div class="form-group"><label class="form-label">Social Media Handle / URL</label><input type="text" name="accused_social" class="form-control"></div>
                            <div class="form-group"><label class="form-label">Known Location/Address</label><input type="text" name="accused_address" class="form-control"></div>
                        </div>
                    </details>

                    <div style="text-align: right; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <button type="submit" class="btn-primary" style="background-color: #ef4444; padding: 12px 30px; font-size: 1.1rem;">
                            <i class="fa-solid fa-gavel"></i> Formulate & Register FIR
                        </button>
                    </div>

                </form>
            </div>
            
            
            <?php else: ?>
            <div class="glass-panel" style="border-top: 4px solid #3b82f6;">
                
                <form method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                    <input type="hidden" name="tab" value="all">
                    <input type="text" name="search" class="form-control" placeholder="Search FIR No, Name" value="<?= esc($search) ?>">
                    <select name="crime_type" class="form-control">
                        <option value="">All Crime Types</option>
                        <option value="Online Fraud" <?= $filter_type=='Online Fraud'?'selected':'' ?>>Online Fraud</option>
                        <option value="Identity Theft" <?= $filter_type=='Identity Theft'?'selected':'' ?>>Identity Theft</option>
                        <option value="Hacking" <?= $filter_type=='Hacking'?'selected':'' ?>>Hacking</option>
                        <option value="Cyberbullying" <?= $filter_type=='Cyberbullying'?'selected':'' ?>>Cyberbullying</option>
                    </select>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Registered" <?= $filter_status=='Registered'?'selected':'' ?>>Registered</option>
                        <option value="Under Investigation" <?= $filter_status=='Under Investigation'?'selected':'' ?>>Under Investigation</option>
                        <option value="Chargesheet Filed" <?= $filter_status=='Chargesheet Filed'?'selected':'' ?>>Chargesheet Filed</option>
                        <option value="Closed" <?= $filter_status=='Closed'?'selected':'' ?>>Closed</option>
                    </select>
                    <div style="display: flex; gap:5px; align-items:center;">
                        <input type="date" name="date_from" class="form-control" style="font-size: 0.8rem;" value="<?= esc($filter_date_from) ?>">
                        <span style="color:var(--text-muted);">-</span>
                        <input type="date" name="date_to" class="form-control" style="font-size: 0.8rem;" value="<?= esc($filter_date_to) ?>">
                    </div>
                    <button type="submit" class="btn-primary" style="background: #3b82f6;"><i class="fa-solid fa-filter"></i></button>
                </form>

                <table class="dataTable">
                    <thead>
                        <tr>
                            <th>FIR Number</th>
                            <th>Complainant Name</th>
                            <th>Crime Category</th>
                            <th>Filing Date</th>
                            <th>Status Badge</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fir_list as $f): 
                            $badge_class = 'badge-info';
                            if ($f['status'] == 'Closed') $badge_class = 'badge-success';
                            if ($f['status'] == 'Under Investigation') $badge_class = 'badge-warning';
                            if ($f['status'] == 'Chargesheet Filed') $badge_class = 'badge-warning'; 
                        ?>
                        <tr>
                            <td style="font-family: monospace; color: #93c5fd; font-weight: bold; font-size: 0.95rem;"><?= esc($f['fir_number']) ?></td>
                            <td><?= esc($f['complainant_name']) ?></td>
                            <td style="color: #cbd5e1;"><?= esc($f['crime_type']) ?></td>
                            <td style="color: #94a3b8;"><?= date('d/M/Y', strtotime($f['created_at'])) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= esc($f['status']) ?></span></td>
                            <td>
                                <a href="fir_detail.php?id=<?= $f['id'] ?>" class="btn-primary" style="padding: 5px 12px; font-size: 0.8rem; background: transparent; border: 1px solid #3b82f6; color: #3b82f6; border-radius: 3px; text-decoration: none;">
                                    View Logic <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($fir_list)): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding:30px;">No registered cases match criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 5px;">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?tab=all&p=<?= $i ?>&search=<?= urlencode($search) ?>&crime_type=<?= urlencode($filter_type) ?>&status=<?= urlencode($filter_status) ?>" style="padding: 6px 12px; background: <?= $page == $i ? 'var(--accent-highlight)' : 'rgba(255,255,255,0.05)' ?>; color: <?= $page == $i ? '#fff' : 'var(--text-muted)' ?>; border-radius: 4px; text-decoration: none;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
