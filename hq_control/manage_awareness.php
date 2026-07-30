<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$table = 'awareness_materials';
$page_title = 'Awareness Materials Management | HQ';
$success = ''; $error = '';

if (isset($_POST['update_module'])) {
    $id = (int)$_POST['mod_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $week = (int)$_POST['week'];
    $day = (int)$_POST['day'];
    $file_path = null;

    if (!empty($_FILES['mod_file']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/awareness/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['mod_file']['name'], PATHINFO_EXTENSION));
        $fname = 'awareness_w' . $week . 'd' . $day . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['mod_file']['tmp_name'], $upload_dir . $fname)) {
            $file_path = 'uploads/awareness/' . $fname;
        }
    }

    if ($id > 0) {
        if ($file_path) {
            $pdo->prepare("UPDATE $table SET title=?, description=?, file_path=?, week=?, day=? WHERE id=?")->execute([$title, $desc, $file_path, $week, $day, $id]);
        } else {
            $pdo->prepare("UPDATE $table SET title=?, description=?, week=?, day=? WHERE id=?")->execute([$title, $desc, $week, $day, $id]);
        }
    } else {
        $pdo->prepare("INSERT INTO $table (week, day, title, description, file_path) VALUES (?,?,?,?,?)")->execute([$week, $day, $title, $desc, $file_path]);
    }
    $success = "Material updated successfully!";
}

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE $table SET is_visible = 1 - is_visible WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header("Location: manage_awareness.php"); exit;
}

$modules = $pdo->query("SELECT * FROM $table ORDER BY week ASC, day ASC")->fetchAll();
$weeks = [];
foreach ($modules as $m) $weeks[$m['week']][] = $m;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2><i class="fa-solid fa-eye"></i> Awareness Materials (Module Format)</h2>
            <button class="btn-primary" onclick="openEdit(0, 1, 1, '', '')">+ Add Material</button>
        </header>
        <div class="dashboard-content" style="padding: 30px;">
            <?php if($success): ?><div class="alert-s"><?= esc($success) ?></div><?php endif; ?>
            
            <?php foreach ($weeks as $wn => $days): ?>
            <div class="glass-panel" style="margin-bottom: 25px;">
                <h3 style="color:#10b981; font-family:'Rajdhani';">WEEK <?= $wn ?></h3>
                <?php foreach ($days as $m): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-weight:bold; color:#fff;">Day <?= $m['day'] ?>: <?= esc($m['title']) ?></div>
                        <div style="font-size:0.8rem; color:#64748b;"><?= esc(substr($m['description'],0,100)) ?>...</div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="edit-btn" onclick="openEdit(<?= $m['id'] ?>, <?= $m['week'] ?>, <?= $m['day'] ?>, '<?= addslashes(esc($m['title'])) ?>', '<?= addslashes(esc($m['description'])) ?>')">EDIT</button>
                        <a href="?toggle=<?= $m['id'] ?>" class="tog-btn <?= $m['is_visible'] ? 'tog-on' : 'tog-off' ?>"><?= $m['is_visible'] ? 'LIVE' : 'HIDDEN' ?></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<div class="modal-overlay" id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:500px; padding:30px;">
        <h3 id="modalTitle">Edit Awareness Material</h3>
        <form method="POST" enctype="multipart/form-data" class="cyber-form">
            <input type="hidden" name="mod_id" id="f_id">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group"><label>Week</label><input type="number" name="week" id="f_week" class="form-control" required></div>
                <div class="form-group"><label>Day</label><input type="number" name="day" id="f_day" class="form-control" required></div>
            </div>
            <div class="form-group"><label>Title</label><input type="text" name="title" id="f_title" class="form-control" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" id="f_desc" class="form-control"></textarea></div>
            <div class="form-group"><label>File (PDF/Image)</label><input type="file" name="mod_file" class="form-control"></div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="update_module" class="btn-primary">Save Material</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, week, day, title, desc) {
    document.getElementById('f_id').value = id;
    document.getElementById('f_week').value = week;
    document.getElementById('f_day').value = day;
    document.getElementById('f_title').value = title;
    document.getElementById('f_desc').value = desc;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() { document.getElementById('editModal').style.display = 'none'; }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
