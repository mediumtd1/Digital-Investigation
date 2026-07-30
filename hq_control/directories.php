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
$page_title = 'Nodal Officer Directory | ' . SITE_NAME;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM directories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "Directory entry deleted successfully.";
        $_SESSION['toast_type'] = "success";
    }
    header("Location: directories.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_directory'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $operator = trim($_POST['operator_name']);
    $nodal = trim($_POST['nodal_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $category = trim($_POST['category']);
    $address = trim($_POST['address']);

    $logo = isset($_POST['existing_logo']) ? $_POST['existing_logo'] : null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/logos/';
        $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo = 'uploads/logos/' . $file_name;
        }
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE directories SET operator_name=?, nodal_name=?, email=?, mobile=?, category=?, address=?, logo=? WHERE id=?");
        $stmt->execute([$operator, $nodal, $email, $mobile, $category, $address, $logo, $id]);
        $_SESSION['toast_msg'] = "Entry updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO directories (operator_name, nodal_name, email, mobile, category, address, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$operator, $nodal, $email, $mobile, $category, $address, $logo]);
        $_SESSION['toast_msg'] = "New entry added successfully.";
    }
    $_SESSION['toast_type'] = "success";
    header("Location: directories.php");
    exit;
}

$categories = $pdo->query("SELECT DISTINCT category FROM directories WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

$query = "SELECT * FROM directories WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (operator_name LIKE ? OR nodal_name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($cat_filter) {
    $query .= " AND category = ?";
    $params[] = $cat_filter;
}

$query .= " ORDER BY operator_name ASC";
$items = $pdo->prepare($query);
$items->execute($params);
$directories = $items->fetchAll();

$total_count = $pdo->query("SELECT COUNT(*) FROM directories")->fetchColumn();
echo "";
if (count($directories) == 0 && $total_count > 0) {
    
    $directories = $pdo->query("SELECT * FROM directories LIMIT 50")->fetchAll();
    echo "";
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Nodal Officer Directory</h2>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Entry
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            
            <div class="glass-panel" style="margin-bottom: 20px; padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search by operator, name, email..." value="<?= esc($search) ?>">
                    </div>
                    <div style="width: 200px;">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= esc($cat) ?>" <?= $cat_filter == $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="background: #00f3ff; color: #000; font-weight: bold; min-width: 140px; box-shadow: 0 0 20px rgba(0, 243, 255, 0.4); border: none;">
                        <i class="fa-solid fa-filter"></i> APPLY FILTER
                    </button>
                    <?php if($search || $cat_filter): ?>
                        <a href="directories.php" class="btn-primary" style="background: rgba(255,255,255,0.05); text-decoration: none; border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                            <i class="fa-solid fa-rotate-left"></i> RESET
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass-panel" style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th style="min-width: 200px;">Operator / Entity</th>
                            <th>Nodal Person</th>
                            <th style="min-width: 250px;">Contact Info</th>
                            <th>Category</th>
                            <th style="text-align: center; background: rgba(0, 243, 255, 0.05);">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($directories)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No entries found matching your criteria.</td>
                            </tr>
                        <?php else: foreach ($directories as $item): ?>
                            <tr>
                                <td>
                                    <?php if($item['logo']): ?>
                                        <img src="<?= BASE_URL . '/' . esc($item['logo']) ?>" style="width: 40px; height: 40px; object-fit: contain; background: white; padding: 2px; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                            <i class="fa-solid fa-building"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                 <td style="cursor: pointer;" onclick='openVisitingCard(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                    <div style="font-weight: bold; color: #fff;"><?= esc($item['operator_name']) ?> <i class="fa-solid fa-address-card" style="font-size: 0.7rem; color: var(--primary-glow); margin-left: 5px;"></i></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= esc($item['address']) ?>">
                                        <?= esc($item['address']) ?>
                                    </div>
                                </td>
                                <td style="font-size: 0.9rem; cursor: pointer;" onclick='openVisitingCard(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'><?= esc($item['nodal_name'] ?: 'Not Available') ?></td>
                                <td style="cursor: pointer;" onclick='openVisitingCard(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                    <div style="font-size: 0.85rem; color: #00f3ff;"><i class="fa-solid fa-envelope" style="width: 15px;"></i> <?= esc($item['email']) ?></div>
                                    <div style="font-size: 0.85rem; color: #eab308;"><i class="fa-solid fa-phone" style="width: 15px;"></i> <?= esc($item['mobile']) ?></div>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff;"><?= esc($item['category']) ?></span>
                                </td>
                                <td style="text-align: center; background: rgba(0, 243, 255, 0.02);">
                                    <div style="display: flex; gap: 10px; justify-content: center;">
                                        <button class="status-badge" style="background: #00f3ff; color: #000; cursor: pointer; border: none; font-weight: bold; padding: 5px 12px;" onclick='openEditModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                            <i class="fa-solid fa-pen"></i> EDIT
                                        </button>
                                        <a href="?delete=<?= $item['id'] ?>" class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; text-decoration: none; padding: 5px 12px;" onclick="return confirm('Are you sure you want to delete this entry?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="visitingCardModal" class="cyber-modal" style="display: none;">
    <div class="modal-content" style="background: transparent; border: none; box-shadow: none; max-width: 500px;">
        <div class="visiting-card shadow-lg" id="visitingCard">
            <span class="close" style="position: absolute; right: 20px; top: 15px; color: #fff; z-index: 10;" onclick="closeVisitingCard()">&times;</span>
            
            <div class="card-header-accent"></div>
            
            <div class="card-body-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                    <div id="vc_logo_container">
                        <img id="vc_logo" src="" style="max-height: 60px; max-width: 120px; background: white; padding: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div style="text-align: right;">
                        <div id="vc_category" style="color: #00f3ff; font-weight: bold; letter-spacing: 1px; font-size: 0.8rem; text-transform: uppercase;">TSP</div>
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 2px;">CERTIFIED PARTNER</div>
                    </div>
                </div>

                <h2 id="vc_operator" style="color: #fff; margin: 0 0 5px 0; font-size: 1.6rem; letter-spacing: -0.5px;">Reliance Jio Infocomm</h2>
                <div style="width: 40px; height: 3px; background: #00f3ff; margin-bottom: 20px;"></div>

                <div style="margin-bottom: 25px;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Nodal Officer</div>
                    <div id="vc_nodal" style="color: #eab308; font-size: 1.2rem; font-weight: 600;">Sandeep Aggarwal</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <div class="vc-info-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span id="vc_email">nodal.officer@jio.com</span>
                    </div>
                    <div class="vc-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <span id="vc_mobile">+91 9876543210</span>
                    </div>
                    <div class="vc-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span id="vc_address" style="font-size: 0.8rem; line-height: 1.4;">Reliance Corporate Park, Thane Belapur Road, Ghansoli, Navi Mumbai</span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer-branding">
                <span>DIGITAL INVESTIGATION PORTAL</span>
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>
</div>

<style>
.visiting-card {
    background: linear-gradient(135deg, #1a1c2c 0%, #0d0e17 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    width: 100%;
    position: relative;
    overflow: hidden;
    color: #fff;
    padding: 0;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.card-header-accent {
    height: 10px;
    background: linear-gradient(90deg, #00f3ff, #eab308);
}

.card-body-content {
    padding: 40px;
}

.vc-info-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.vc-info-item i {
    color: #00f3ff;
    width: 20px;
    font-size: 1.1rem;
}

.card-footer-branding {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.3);
    letter-spacing: 2px;
}

.visiting-card::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 243, 255, 0.05) 0%, transparent 70%);
    pointer-events: none;
}
</style>

<div id="dirModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 600px; width: 95%;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Directory Entry</h3>
            <span class="close" onclick="closeDirModal()">&times;</span>
        </div>
        <form method="POST" class="cyber-form" enctype="multipart/form-data">
            <input type="hidden" name="save_directory" value="1">
            <input type="hidden" name="id" id="field_id">
            <input type="hidden" name="existing_logo" id="field_existing_logo">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Operator Name *</label>
                    <input type="text" name="operator_name" id="field_operator" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <input type="text" name="category" id="field_category" class="form-control" required list="category_list">
                    <datalist id="category_list">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= esc($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Nodal Officer Name</label>
                    <input type="text" name="nodal_name" id="field_nodal" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number(s)</label>
                    <input type="text" name="mobile" id="field_mobile" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email ID(s)</label>
                <input type="text" name="email" id="field_email" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Office Address</label>
                <textarea name="address" id="field_address" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Company Logo (Square preferred)</label>
                <input type="file" name="logo" class="form-control" style="padding: 8px;">
                <div id="logo_preview_container" style="margin-top: 10px; display: none;">
                    <img id="logo_preview" src="" style="height: 50px; background: white; padding: 5px; border-radius: 4px;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;">Save Directory Entry</button>
        </form>
    </div>
</div>

<script>
function openVisitingCard(data) {
    document.getElementById('vc_operator').innerText = data.operator_name;
    document.getElementById('vc_nodal').innerText = data.nodal_name || 'Not Available';
    document.getElementById('vc_email').innerText = data.email || 'N/A';
    document.getElementById('vc_mobile').innerText = data.mobile || 'N/A';
    document.getElementById('vc_category').innerText = data.category;
    document.getElementById('vc_address').innerText = data.address || 'Address not listed';
    
    if(data.logo) {
        document.getElementById('vc_logo').src = '<?= BASE_URL ?>/' + data.logo;
        document.getElementById('vc_logo_container').style.display = 'block';
    } else {
        document.getElementById('vc_logo_container').style.display = 'none';
    }
    
    document.getElementById('visitingCardModal').style.display = 'flex';
}

function closeVisitingCard() {
    document.getElementById('visitingCardModal').style.display = 'none';
}

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Directory Entry';
    document.getElementById('field_id').value = '';
    document.getElementById('field_operator').value = '';
    document.getElementById('field_nodal').value = '';
    document.getElementById('field_email').value = '';
    document.getElementById('field_mobile').value = '';
    document.getElementById('field_category').value = '';
    document.getElementById('field_address').value = '';
    document.getElementById('field_existing_logo').value = '';
    document.getElementById('logo_preview_container').style.display = 'none';
    document.getElementById('dirModal').style.display = 'flex';
}

function openEditModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Directory Entry';
    document.getElementById('field_id').value = data.id;
    document.getElementById('field_operator').value = data.operator_name;
    document.getElementById('field_nodal').value = data.nodal_name;
    document.getElementById('field_email').value = data.email;
    document.getElementById('field_mobile').value = data.mobile;
    document.getElementById('field_category').value = data.category;
    document.getElementById('field_address').value = data.address;
    document.getElementById('field_existing_logo').value = data.logo;
    
    if(data.logo) {
        document.getElementById('logo_preview').src = '<?= BASE_URL ?>/' + data.logo;
        document.getElementById('logo_preview_container').style.display = 'block';
    } else {
        document.getElementById('logo_preview_container').style.display = 'none';
    }
    
    document.getElementById('dirModal').style.display = 'flex';
}

function closeDirModal() {
    document.getElementById('dirModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('cyber-modal')) {
        closeDirModal();
        closeVisitingCard();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>