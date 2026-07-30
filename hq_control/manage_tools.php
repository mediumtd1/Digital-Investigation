<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Manage Investigation Tools | ' . SITE_NAME;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM investigation_tools WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "Tool deleted successfully.";
        $_SESSION['toast_type'] = "success";
    }
    header("Location: manage_tools.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tool_name = trim($_POST['tool_name']);
    $filename = trim($_POST['filename']);
    $icon = trim($_POST['icon']) ?: 'fa-toolbox';
    $url = trim($_POST['url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        
        $id = (int)$_POST['edit_id'];
        $stmt = $pdo->prepare("UPDATE investigation_tools SET tool_name=?, filename=?, icon=?, url=?, is_active=? WHERE id=?");
        $stmt->execute([$tool_name, $filename, $icon, $url, $is_active, $id]);
        $_SESSION['toast_msg'] = "Tool updated successfully.";
    } else {
        
        $stmt = $pdo->prepare("INSERT INTO investigation_tools (tool_name, filename, icon, url, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tool_name, $filename, $icon, $url, $is_active]);
        $_SESSION['toast_msg'] = "Tool added successfully.";
    }
    $_SESSION['toast_type'] = "success";
    header("Location: manage_tools.php");
    exit;
}

$tools = $pdo->query("SELECT * FROM investigation_tools ORDER BY tool_name ASC")->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Investigation Tools</h2>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Tool
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Tool Name</th>
                            <th>Filename</th>
                            <th>External URL</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tools)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No tools found.</td>
                            </tr>
                        <?php else: foreach ($tools as $item): ?>
                            <tr>
                                <td style="font-size: 1.2rem; color: #00f3ff; text-align: center;"><i class="fa-solid <?= esc($item['icon']) ?>"></i></td>
                                <td style="font-weight: bold; color: #fff;"><?= esc($item['tool_name']) ?></td>
                                <td style="font-family: monospace; color: #eab308;"><?= esc($item['filename']) ?></td>
                                <td>
                                    <?php if($item['url']): ?>
                                        <a href="<?= esc($item['url']) ?>" target="_blank" class="cyan" title="<?= esc($item['url']) ?>"><i class="fa-solid fa-link"></i> Link</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($item['is_active']): ?>
                                        <span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="status-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff; border: none; cursor: pointer; margin-right: 5px;" onclick='openEditModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="?delete=<?= $item['id'] ?>" class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; text-decoration: none;" onclick="return confirm('Are you sure you want to delete this tool?')">
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

<div id="toolModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Tool</h3>
            <span class="close" onclick="this.closest('.cyber-modal').style.display='none'">&times;</span>
        </div>
        <form method="POST" class="cyber-form">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <div class="form-group">
                <label class="form-label">Tool Name</label>
                <input type="text" name="tool_name" id="tool_name" class="form-control" required placeholder="e.g. WhatsApp Reader">
            </div>
            <div class="form-group">
                <label class="form-label">Filename (e.g. whatsapp_reader.php)</label>
                <input type="text" name="filename" id="filename" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">FontAwesome Icon Class</label>
                <input type="text" name="icon" id="icon" class="form-control" placeholder="e.g. fa-whatsapp" value="fa-toolbox">
            </div>
            <div class="form-group">
                <label class="form-label">External Tool URL (Optional)</label>
                <input type="url" name="url" id="url" class="form-control" placeholder="https://...">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked style="width: 18px; height: 18px;">
                <label class="form-label" style="margin: 0;">Show in Sidebar</label>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;" id="modalBtn">Save Tool</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Tool';
    document.getElementById('edit_id').value = '';
    document.getElementById('tool_name').value = '';
    document.getElementById('filename').value = '';
    document.getElementById('icon').value = 'fa-toolbox';
    document.getElementById('url').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('modalBtn').innerText = 'Add Tool';
    document.getElementById('toolModal').style.display = 'flex';
}

function openEditModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Tool';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('tool_name').value = data.tool_name;
    document.getElementById('filename').value = data.filename;
    document.getElementById('icon').value = data.icon;
    document.getElementById('url').value = data.url;
    document.getElementById('is_active').checked = (data.is_active == 1);
    document.getElementById('modalBtn').innerText = 'Update Tool';
    document.getElementById('toolModal').style.display = 'flex';
}

window.onclick = function(event) {
    let modal = document.getElementById('toolModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
