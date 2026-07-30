<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Manage Nodal Directories | ' . SITE_NAME;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM directories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "Directory entry deleted successfully.";
        $_SESSION['toast_type'] = "success";
    }
    header("Location: manage_directories.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator_name = trim($_POST['operator_name']);
    $nodal_name = trim($_POST['nodal_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $category = trim($_POST['category']);
    
    
    $logo = isset($_POST['current_logo']) ? $_POST['current_logo'] : null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/images/logos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $file_name)) {
                $logo = 'assets/images/logos/' . $file_name;
            }
        }
    }
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $stmt = $pdo->prepare("UPDATE directories SET operator_name=?, nodal_name=?, email=?, mobile=?, address=?, category=?, logo=? WHERE id=?");
        $stmt->execute([$operator_name, $nodal_name, $email, $mobile, $address, $category, $logo, $id]);
        $_SESSION['toast_msg'] = "Directory updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO directories (operator_name, nodal_name, email, mobile, address, category, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$operator_name, $nodal_name, $email, $mobile, $address, $category, $logo]);
        $_SESSION['toast_msg'] = "Directory added successfully.";
    }
    $_SESSION['toast_type'] = "success";
    header("Location: manage_directories.php");
    exit;
}

$categories = $pdo->query("SELECT DISTINCT category FROM directories WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM directories WHERE 1=1";
$params = [];
if ($search) {
    $query .= " AND (operator_name LIKE ? OR nodal_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY operator_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Nodal Officer Directory</h2>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Entry
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="margin-bottom: 25px; padding: 15px 20px;">
                <form method="GET" style="display: flex; gap: 15px;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="search" class="form-control" placeholder="Search directories..." value="<?= esc($search) ?>">
                    </div>
                    <button type="submit" class="btn-primary">Search</button>
                    <?php if($search): ?>
                        <a href="manage_directories.php" class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Operator / TSP</th>
                            <th>Nodal Name</th>
                            <th>Category</th>
                            <th>Contact Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No directory entries found.</td>
                            </tr>
                        <?php else: foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if($item['logo']): ?>
                                        <img src="<?= BASE_URL . '/' . esc($item['logo']) ?>" style="width: 40px; height: 40px; object-fit: contain; background: white; padding: 2px; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-building"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: bold; color: #fff;"><?= esc($item['operator_name']) ?></td>
                                <td style="color: #eab308;"><?= esc($item['nodal_name'] ?: 'N/A') ?></td>
                                <td><span style="background: rgba(0,243,255,0.1); color: #00f3ff; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;"><?= esc($item['category']) ?></span></td>
                                <td style="font-size: 0.85rem;">
                                    <div><i class="fa-solid fa-envelope" style="color: #00f3ff; width: 15px;"></i> <?= esc($item['email']) ?></div>
                                    <div style="margin-top: 4px;"><i class="fa-solid fa-phone" style="color: #eab308; width: 15px;"></i> <?= esc($item['mobile']) ?></div>
                                </td>
                                <td>
                                    <button class="status-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff; border: none; cursor: pointer; margin-right: 5px;" onclick='openEditModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="?delete=<?= $item['id'] ?>" class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; text-decoration: none;" onclick="return confirm('Delete this directory entry?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="dirModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Directory Entry</h3>
            <span class="close" onclick="this.closest('.cyber-modal').style.display='none'">&times;</span>
        </div>
        <form method="POST" class="cyber-form" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <input type="hidden" name="current_logo" id="current_logo" value="">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Operator / Company Name *</label>
                    <input type="text" name="operator_name" id="operator_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <input type="text" name="category" id="category" class="form-control" required list="catList">
                    <datalist id="catList">
                        <?php foreach($categories as $c): ?><option value="<?= esc($c) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Nodal Officer Name</label>
                    <input type="text" name="nodal_name" id="nodal_name" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile / Phone</label>
                    <input type="text" name="mobile" id="mobile" class="form-control">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Full Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Upload New Logo (Optional)</label>
                    <input type="file" name="logo" class="form-control" style="padding: 8px;">
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">Leave blank to keep existing logo.</small>
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;" id="modalBtn">Save Entry</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Directory Entry';
    document.getElementById('edit_id').value = '';
    document.getElementById('current_logo').value = '';
    document.getElementById('operator_name').value = '';
    document.getElementById('category').value = 'TSP';
    document.getElementById('nodal_name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('mobile').value = '';
    document.getElementById('address').value = '';
    document.getElementById('modalBtn').innerText = 'Add Entry';
    document.getElementById('dirModal').style.display = 'flex';
}

function openEditModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Directory Entry';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('current_logo').value = data.logo || '';
    document.getElementById('operator_name').value = data.operator_name;
    document.getElementById('category').value = data.category;
    document.getElementById('nodal_name').value = data.nodal_name;
    document.getElementById('email').value = data.email;
    document.getElementById('mobile').value = data.mobile;
    document.getElementById('address').value = data.address;
    document.getElementById('modalBtn').innerText = 'Update Entry';
    document.getElementById('dirModal').style.display = 'flex';
}

window.onclick = function(event) {
    if (event.target.classList.contains('cyber-modal')) {
        event.target.style.display = "none";
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
