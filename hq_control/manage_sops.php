<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Manage SOPs | HQ Control';
$success = ''; $error = '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sops (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        week INT DEFAULT 1,
        day INT DEFAULT 1,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE sops ADD COLUMN is_visible TINYINT(1) DEFAULT 1"); } catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = "Security validation failed.";
    } elseif (isset($_POST['action_type'])) {
        $action = $_POST['action_type'];
        if ($action === 'save') {
            $id    = (int)($_POST['mod_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $file_path = null;
            if (empty($title)) {
                $error = "Title is required.";
            } else {
                if (!empty($_FILES['mod_file']['name'])) {
                    $upload_dir = __DIR__ . '/../uploads/sops/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $ext = strtolower(pathinfo($_FILES['mod_file']['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'html', 'htm'];
                    if (!in_array($ext, $allowed)) {
                        $error = "Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, HTML.";
                    } elseif ($_FILES['mod_file']['size'] > 50 * 1024 * 1024) {
                        $error = "File exceeds 50MB limit.";
                    } else {
                        $fname = 'sop_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['mod_file']['tmp_name'], $upload_dir . $fname)) {
                            $file_path = 'uploads/sops/' . $fname;
                        } else { $error = "Failed to upload file."; }
                    }
                }
                if (!$error) {
                    if ($id > 0) {
                        if ($file_path) {
                            $old = $pdo->prepare("SELECT file_path FROM sops WHERE id=?");
                            $old->execute([$id]);
                            $old_path = $old->fetchColumn();
                            if ($old_path && file_exists(__DIR__ . '/../' . $old_path)) @unlink(__DIR__ . '/../' . $old_path);
                            $pdo->prepare("UPDATE sops SET title=?, description=?, file_path=? WHERE id=?")->execute([$title, $desc, $file_path, $id]);
                        } else {
                            $pdo->prepare("UPDATE sops SET title=?, description=? WHERE id=?")->execute([$title, $desc, $id]);
                        }
                        $success = "SOP updated successfully!";
                    } else {
                        $pdo->prepare("INSERT INTO sops (title, description, file_path, is_visible) VALUES (?, ?, ?, 1)")->execute([$title, $desc, $file_path]);
                        $success = "SOP added successfully!";
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['mod_id'] ?? 0);
            if ($id > 0) {
                $old = $pdo->prepare("SELECT file_path FROM sops WHERE id=?");
                $old->execute([$id]);
                $old_path = $old->fetchColumn();
                if ($old_path && file_exists(__DIR__ . '/../' . $old_path)) @unlink(__DIR__ . '/../' . $old_path);
                $pdo->prepare("DELETE FROM sops WHERE id=?")->execute([$id]);
                $success = "SOP deleted.";
            }
        }
    }
}

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE sops SET is_visible = 1 - is_visible WHERE id=?")->execute([(int)$_GET['toggle']]);
    header("Location: manage_sops.php"); exit;
}

$sops = $pdo->query("SELECT * FROM sops ORDER BY id DESC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2><i class="fa-solid fa-file-shield"></i> SOP Management</h2>
            <button class="btn-primary" onclick="openModal(0,'','')">
                <i class="fa-solid fa-plus"></i> Add New SOP
            </button>
        </header>
        <div class="dashboard-content" style="padding:30px;">
            <?php if ($success): ?>
                <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;color:#10b981;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= esc($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:rgba(239,68,68,0.1);border:1px solid #ef4444;color:#f87171;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= esc($error) ?>
                </div>
            <?php endif; ?>
            <div class="glass-panel">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid var(--border-color);">
                    <h3 style="margin:0;color:var(--text-primary);"><i class="fa-solid fa-list" style="color:#00f3ff;"></i> All SOPs (<?= count($sops) ?>)</h3>
                </div>
                <?php if (empty($sops)): ?>
                    <div style="text-align:center;padding:60px;color:var(--text-muted);">
                        <i class="fa-solid fa-folder-open" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:15px;"></i>
                        No SOPs uploaded yet. Click <strong>Add New SOP</strong> to get started.
                    </div>
                <?php else: ?>
                    <?php foreach ($sops as $m): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 10px;border-bottom:1px solid rgba(255,255,255,0.05);">
                        <div style="flex:1;">
                            <div style="font-weight:700;color:#fff;margin-bottom:4px;">
                                <i class="fa-solid fa-file-shield" style="color:#00f3ff;margin-right:8px;"></i>
                                <?= esc($m['title']) ?>
                                <?php if (!$m['is_visible']): ?>
                                    <span style="background:rgba(239,68,68,0.15);color:#ef4444;font-size:0.65rem;padding:2px 8px;border-radius:4px;margin-left:8px;text-transform:uppercase;">Hidden</span>
                                <?php else: ?>
                                    <span style="background:rgba(16,185,129,0.15);color:#10b981;font-size:0.65rem;padding:2px 8px;border-radius:4px;margin-left:8px;text-transform:uppercase;">Live</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:0.8rem;color:#64748b;"><?= esc(substr($m['description'],0,120)) ?><?= strlen($m['description'])>120?'...':'' ?></div>
                            <?php if ($m['file_path']): ?>
                            <div style="font-size:0.72rem;color:#475569;margin-top:4px;"><i class="fa-solid fa-paperclip"></i> <?= esc(basename($m['file_path'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:8px;flex-shrink:0;margin-left:20px;">
                            <button onclick="openModal(<?= $m['id'] ?>,'<?= addslashes(esc($m['title'])) ?>','<?= addslashes(esc($m['description'])) ?>')"
                                style="background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.4);color:#60a5fa;padding:6px 14px;border-radius:6px;font-size:0.78rem;cursor:pointer;">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <a href="?toggle=<?= $m['id'] ?>"
                                style="background:<?= $m['is_visible']?'rgba(245,158,11,0.15)':'rgba(16,185,129,0.15)' ?>;border:1px solid <?= $m['is_visible']?'#f59e0b':'#10b981' ?>;color:<?= $m['is_visible']?'#f59e0b':'#10b981' ?>;padding:6px 14px;border-radius:6px;font-size:0.78rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                                <i class="fa-solid fa-<?= $m['is_visible']?'eye-slash':'eye' ?>"></i> <?= $m['is_visible']?'Hide':'Show' ?>
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete this SOP permanently?')" style="display:inline;">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action_type" value="delete">
                                <input type="hidden" name="mod_id" value="<?= $m['id'] ?>">
                                <button type="submit" style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);color:#f87171;padding:6px 14px;border-radius:6px;font-size:0.78rem;cursor:pointer;">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div id="sopModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(8px);">
    <div class="glass-panel" style="width:560px;max-width:95vw;padding:35px;border-top:4px solid #00f3ff;">
        <h3 id="modalHeading" style="margin:0 0 24px 0;color:#00f3ff;font-family:'Rajdhani';letter-spacing:1px;">
            <i class="fa-solid fa-file-shield"></i> Add New SOP
        </h3>
        <form method="POST" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <input type="hidden" name="action_type" value="save">
            <input type="hidden" name="mod_id" id="modal_id" value="0">
            <div class="form-group" style="margin-bottom:18px;">
                <label class="form-label">SOP Title *</label>
                <input type="text" name="title" id="modal_title" class="form-control" placeholder="e.g. Cyber FIR Filing Procedure" required>
            </div>
            <div class="form-group" style="margin-bottom:18px;">
                <label class="form-label">Description</label>
                <textarea name="description" id="modal_desc" class="form-control" rows="3" placeholder="Brief overview of this SOP..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label">Upload File (PDF, DOC, DOCX, PPT, HTML — Max 50MB)</label>
                <input type="file" name="mod_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.html,.htm">
                <div id="modal_file_hint" style="font-size:0.75rem;color:#475569;margin-top:5px;"></div>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="button" onclick="closeModal()" style="flex:1;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;padding:10px;border-radius:8px;cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex:2;padding:10px;background:#00f3ff;color:#000;font-weight:700;">
                    <i class="fa-solid fa-floppy-disk"></i> Save SOP
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function openModal(id,title,desc){
    document.getElementById('modal_id').value=id;
    document.getElementById('modal_title').value=title;
    document.getElementById('modal_desc').value=desc;
    document.getElementById('modalHeading').innerHTML=id>0?'<i class="fa-solid fa-pen-to-square"></i> Edit SOP':'<i class="fa-solid fa-file-shield"></i> Add New SOP';
    document.getElementById('modal_file_hint').textContent=id>0?'Leave blank to keep existing file.':'';
    document.getElementById('sopModal').style.display='flex';
}
function closeModal(){document.getElementById('sopModal').style.display='none';}
document.getElementById('sopModal').addEventListener('click',function(e){if(e.target===this)closeModal();});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
