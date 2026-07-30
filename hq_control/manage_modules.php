<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');
$page_title = 'Daily Modules Control | HQ';
$success = ''; $error = '';

try {
    $pdo->exec("ALTER TABLE internship_modules ADD COLUMN file_path VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS internship_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        week INT NOT NULL, day INT NOT NULL,
        title VARCHAR(255) NOT NULL, description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        is_visible TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_week_day (week, day)
    )");
} catch (PDOException $e) {}

try {
    $pdo->exec("ALTER TABLE internship_modules ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (PDOException $e) {}

$count = $pdo->query("SELECT COUNT(*) FROM internship_modules")->fetchColumn();
if ($count == 0) {
    $seed = [
        [1,1,"Intro to Cyber Crime","Understanding the landscape of modern digital threats and their impact.",0],
        [1,2,"Networking for Investigators","TCP/IP, Ports, and Protocol analysis fundamentals.",0],
        [1,3,"Linux for Forensics","Mastering the command line for forensic data retrieval.",0],
        [1,4,"OSINT Tools & Techniques","Gathering open-source intelligence from the web.",0],
        [1,5,"Web Security 101","How websites work and common vulnerabilities.",0],
        [1,6,"Weekly Assignment","Practical foundational assessment and submission.",0],
        [1,7,"Week 1 Live Q&A","Interact with senior investigators and mentors.",0],
        [2,1,"Digital Forensics, Chain of Custody & Evidence Handling","Core principles of evidence preservation, chain of custody, and forensic workflow.",0],
        [2,2,"Hash Value Calculation & WordPress Security","Understanding data integrity through hashing and securing CMS platforms.",0],
        [2,3,"Week 2 - Day 3 Module","Standard investigative module - Week 2.",0],
        [2,4,"Week 2 - Day 4 Module","Standard investigative module - Week 2.",0],
        [2,5,"Week 2 - Day 5 Module","Standard investigative module - Week 2.",0],
        [2,6,"Week 2 - Day 6 Module","Standard investigative module - Week 2.",0],
        [2,7,"Cloud Forensics Risks & Shared Responsibility Model","Investigating crimes in cloud environments (AWS/Azure/GCP).",0],
        [3,1,"Week 3 - Day 1 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,2,"Week 3 - Day 2 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,3,"Week 3 - Day 3 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,4,"Week 3 - Day 4 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,5,"Week 3 - Day 5 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,6,"Week 3 - Day 6 Lab Session","Hands-on practical training with live investigative tools.",0],
        [3,7,"Week 3 - Day 7 Lab Session","Hands-on practical training with live investigative tools.",0],
        [4,1,"Advanced Forensic Investigation Summary","Finalizing comprehensive investigation techniques and case reviews.",0],
        [4,2,"Week 4 - Day 2 Continuation","Continuing legal and technical wrap-up.",0],
        [4,3,"Cyber Laws (IT Act 2000 & Amendments)","Understanding the Information Technology Act and its relevance.",0],
        [4,4,"DPDP Act & Intermediary Guidelines","Digital Personal Data Protection Act and social media compliance.",0],
        [4,5,"AI, VR & AR in Cybersecurity & Forensics","Future of investigations: Artificial Intelligence and Immersive Tech.",0],
        [4,6,"Final Capstone Assignment","Submit your comprehensive investigative report to HQ.",0],
        [4,7,"Capture The Flag (CTF) Challenge","The ultimate practical challenge for all graduating interns.",0],
        [5,1,"Project Submission","Final Project Submission Day. Upload your complete project files.",0],
        [5,2,"Final Presentation","Present your findings and project to the board of investigators.",0],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO internship_modules (week,day,title,description,is_visible) VALUES (?,?,?,?,?)");
    foreach ($seed as $row) $stmt->execute($row);
    $success = "30 curriculum modules seeded successfully!";
}

// Auto-seed Week 5 modules if they are missing in DB
try {
    $w5_count = $pdo->query("SELECT COUNT(*) FROM internship_modules WHERE week = 5")->fetchColumn();
    if ($w5_count == 0) {
        $stmt_w5 = $pdo->prepare("INSERT IGNORE INTO internship_modules (week,day,title,description,is_visible) VALUES (?,?,?,?,?)");
        $stmt_w5->execute([5,1,"Project Submission","Final Project Submission Day. Upload your complete project files.",0]);
        $stmt_w5->execute([5,2,"Final Presentation","Present your findings and project to the board of investigators.",0]);
    }
} catch (PDOException $e) {}

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE internship_modules SET is_visible = 1 - is_visible WHERE id = ?")->execute([(int)$_GET['toggle']]);
    header("Location: manage_modules.php"); exit;
}

if (isset($_GET['week_on']))  { $pdo->prepare("UPDATE internship_modules SET is_visible=1 WHERE week=?")->execute([(int)$_GET['week_on']]);  header("Location: manage_modules.php"); exit; }
if (isset($_GET['week_off'])) { $pdo->prepare("UPDATE internship_modules SET is_visible=0 WHERE week=?")->execute([(int)$_GET['week_off']]); header("Location: manage_modules.php"); exit; }

if (isset($_POST['update_module'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $id    = (int)$_POST['mod_id'];
        $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $file_path = null;

    if (!empty($_FILES['mod_file']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/modules/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext  = strtolower(pathinfo($_FILES['mod_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','zip','mp4','html','htm'];
        if (in_array($ext, $allowed) && $_FILES['mod_file']['size'] < 50 * 1024 * 1024) {
            $fname = 'module_w' . (int)$_POST['week'] . 'd' . (int)$_POST['day'] . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['mod_file']['tmp_name'], $upload_dir . $fname)) {
                $file_path = $fname;
            }
        } else { $error = "Invalid file type or size exceeds 50MB."; }
    }

    if (!$error) {
        if ($file_path) {
            $pdo->prepare("UPDATE internship_modules SET title=?,description=?,file_path=? WHERE id=?")->execute([$title,$desc,$file_path,$id]);
        } else {
            $pdo->prepare("UPDATE internship_modules SET title=?,description=? WHERE id=?")->execute([$title,$desc,$id]);
        }
        $success = "Module updated successfully!";
    }
}
}

$modules = $pdo->query("SELECT * FROM internship_modules ORDER BY week ASC, day ASC")->fetchAll();
$weeks = [];
foreach ($modules as $m) $weeks[$m['week']][] = $m;

$week_titles = [1=>'Foundations of Cyber Investigation',2=>'Advanced Forensics & Hashing',3=>'Investigative Tools & Labs',4=>'Legal Framework & Future Tech', 5=>'Final Evaluation & Certification'];
$week_colors = [1=>'#00f3ff',2=>'#a855f7',3=>'#10b981',4=>'#f59e0b', 5=>'#10b981'];

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.mod-admin-wrap { padding: 30px; }
.info-bar { background: rgba(0,243,255,0.05); border: 1px solid rgba(0,243,255,0.15); border-radius: 10px; padding: 16px 22px; margin-bottom: 28px; font-size: 0.85rem; color: #94a3b8; }
.info-bar strong { color: #10b981; }
.week-block { margin-bottom: 28px; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); }
.week-bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.06); }
.week-bar h3 { margin:0; font-family:'Rajdhani',sans-serif; font-size: 1rem; color: #fff; display: flex; align-items: center; gap: 10px; }
.week-bar .wnum { font-size: 0.65rem; font-weight: 800; letter-spacing: 2px; }
.wc-btns { display: flex; gap: 8px; }
.btn-won  { background: rgba(16,185,129,.12); color: #10b981; border: 1px solid rgba(16,185,129,.4); padding: 5px 13px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; text-decoration: none; }
.btn-woff { background: rgba(239,68,68,.08); color: #ef4444; border: 1px solid rgba(239,68,68,.4); padding: 5px 13px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; text-decoration: none; }
.mod-row { display: grid; grid-template-columns: 44px 1fr auto; gap: 14px; align-items: center; padding: 13px 20px; background: rgba(15,23,42,.5); border-bottom: 1px solid rgba(255,255,255,.03); transition: .2s; }
.mod-row:hover { background: rgba(0,243,255,.02); }
.mod-row:last-child { border-bottom: none; }
.d-badge { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; font-family: 'Rajdhani', sans-serif; }
.d-vis   { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.4); color: #10b981; }
.d-hid   { background: rgba(71,85,105,.15); border: 1px solid rgba(71,85,105,.3); color: #475569; }
.mod-info .t { font-size: .88rem; font-weight: 600; color: #e2e8f0; margin-bottom: 3px; }
.mod-info .d { font-size: .75rem; color: #64748b; }
.mod-info .f-link { display: inline-flex; align-items: center; gap: 5px; font-size: 0.7rem; color: #00f3ff; text-decoration: none; margin-top: 4px; }
.mod-actions { display: flex; gap: 8px; align-items: center; }
.tog-btn { padding: 5px 11px; border-radius: 6px; text-decoration: none; font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.tog-on  { background: rgba(16,185,129,.12); color: #10b981; border: 1px solid rgba(16,185,129,.3); }
.tog-off { background: rgba(100,116,139,.1); color: #64748b; border: 1px solid rgba(100,116,139,.25); }
.edit-btn { background: rgba(0,243,255,.08); color: #00f3ff; border: 1px solid rgba(0,243,255,.25); padding: 5px 11px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; cursor: pointer; }

/* MODAL */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 10000; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal-box { background: #0f172a; border: 1px solid rgba(0,243,255,.2); border-radius: 14px; padding: 30px; width: 520px; max-width: 95vw; }
.modal-box h3 { font-family: 'Rajdhani', sans-serif; color: #fff; margin: 0 0 20px 0; font-size: 1.1rem; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.form-group input[type=text], .form-group textarea { width: 100%; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #fff; padding: 10px 14px; font-size: 0.85rem; outline: none; box-sizing: border-box; }
.form-group input[type=text]:focus, .form-group textarea:focus { border-color: rgba(0,243,255,.4); }
.form-group textarea { height: 80px; resize: vertical; }
.file-drop { border: 2px dashed rgba(0,243,255,.2); border-radius: 8px; padding: 18px; text-align: center; color: #475569; font-size: 0.8rem; cursor: pointer; position: relative; }
.file-drop:hover { border-color: rgba(0,243,255,.4); color: #94a3b8; }
.file-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.btn-save { background: linear-gradient(135deg, #00f3ff, #a855f7); color: #000; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; }
.btn-cancel { background: rgba(255,255,255,.05); color: #94a3b8; border: 1px solid rgba(255,255,255,.1); padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
.alert-s { background: rgba(16,185,129,.1); border: 1px solid #10b981; color: #10b981; padding: 13px 18px; border-radius: 8px; margin-bottom: 22px; font-size: .85rem; font-weight: 600; }
.alert-e { background: rgba(239,68,68,.1); border: 1px solid #ef4444; color: #ef4444; padding: 13px 18px; border-radius: 8px; margin-bottom: 22px; font-size: .85rem; font-weight: 600; }
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 28px; }
.stat-c { background: rgba(15,23,42,.7); border: 1px solid rgba(255,255,255,.06); border-radius: 10px; padding: 16px; text-align: center; }
.stat-c .n { font-size: 1.6rem; font-weight: 800; font-family: 'Rajdhani',sans-serif; color: var(--accent-blue); }
.stat-c .l { font-size: 0.68rem; color: #475569; text-transform: uppercase; letter-spacing: .5px; }
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:1.5px;">
                    <i class="fa-solid fa-book-open" style="color:#00f3ff;"></i> DAILY MODULES CONTROL
                </h2>
            </div>
            <div class="topbar-right" style="display:flex; gap:15px; align-items:center;">
                <a href="module_access_override.php" class="btn-primary" style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.4); color:#f59e0b; font-size:0.8rem; padding:8px 16px; border-radius:8px; text-decoration:none; font-family:'Rajdhani',sans-serif; font-weight:800; letter-spacing:1px; display:inline-flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-key"></i> MANAGE ACCESS OVERRIDES
                </a>
            </div>
        </header>
        <div class="dashboard-content mod-admin-wrap">

            <?php if($success): ?><div class="alert-s"><i class="fa-solid fa-check-circle"></i> <?= esc($success) ?></div><?php endif; ?>
            <?php if($error):   ?><div class="alert-e"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($error) ?></div><?php endif; ?>

            
            <?php
            $total = count($modules);
            $vis   = array_sum(array_column($modules,'is_visible'));
            $weeks_vis = 0;
            foreach ($weeks as $wn => $wd) { if (array_sum(array_column($wd,'is_visible')) > 0) $weeks_vis++; }
            ?>
            <div class="stats-row">
                <div class="stat-c"><div class="n"><?= $total ?></div><div class="l">Total Modules</div></div>
                <div class="stat-c"><div class="n" style="color:#10b981;"><?= $vis ?></div><div class="l">Live / Visible</div></div>
                <div class="stat-c"><div class="n" style="color:#ef4444;"><?= $total - $vis ?></div><div class="l">Hidden</div></div>
                <div class="stat-c"><div class="n" style="color:#f59e0b;"><?= $weeks_vis ?></div><div class="l">Weeks Active</div></div>
            </div>

            <div class="info-bar">
                <i class="fa-solid fa-circle-info" style="color:var(--accent-blue);"></i>
                Use <strong>SHOW WEEK</strong> / <strong style="color:#ef4444;">HIDE WEEK</strong> to bulk-control intern access.
                Click <strong style="color:#00f3ff;">EDIT</strong> to update a module's title, description, or upload a file (PDF, PPT, DOCX, ZIP, MP4 — max 50MB).
            </div>

            
            <?php foreach ($weeks as $wn => $days):
                $wcolor  = $week_colors[$wn] ?? '#00f3ff';
                $wtitle  = $week_titles[$wn] ?? "Week $wn";
                $wvis    = array_sum(array_column($days,'is_visible'));
            ?>
            <div class="week-block">
                <div class="week-bar">
                    <h3>
                        <span class="wnum" style="color:<?= $wcolor ?>;">WEEK <?= str_pad($wn,2,'0',STR_PAD_LEFT) ?></span>
                        <span style="color:#94a3b8;">— <?= htmlspecialchars($wtitle) ?></span>
                        <span style="font-size:0.72rem;color:#475569;font-weight:400;"><?= $wvis ?>/<?= count($days) ?> live</span>
                    </h3>
                    <div class="wc-btns">
                        <a href="?week_on=<?= $wn ?>"  class="btn-won"><i class="fa-solid fa-eye"></i> SHOW ALL</a>
                        <a href="?week_off=<?= $wn ?>" class="btn-woff"><i class="fa-solid fa-eye-slash"></i> HIDE ALL</a>
                    </div>
                </div>

                <?php foreach ($days as $m): ?>
                <div class="mod-row">
                    <div class="d-badge <?= $m['is_visible'] ? 'd-vis' : 'd-hid' ?>">D<?= $m['day'] ?></div>
                    <div class="mod-info">
                        <div class="t"><?= esc($m['title']) ?></div>
                        <div class="d"><?= esc(mb_substr($m['description'],0,90)) ?>...</div>
                        <?php if (!empty($m['file_path'])): ?>
                            <a href="../intern/view_module.php?id=<?= $m['id'] ?>" class="f-link" target="_blank">
                                <i class="fa-solid fa-file-shield"></i> Open Secure Viewer
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="mod-mod">
                        <?= date('M d, H:i', strtotime($m['created_at'])) ?>
                    </div>
                    <div class="mod-actions">
                        <button class="edit-btn" onclick="openEdit(<?= $m['id'] ?>, <?= $m['week'] ?>, <?= $m['day'] ?>, '<?= addslashes(esc($m['title'])) ?>', '<?= addslashes(esc($m['description'])) ?>')">
                            <i class="fa-solid fa-pen-to-square"></i> EDIT
                        </button>
                        <a href="?toggle=<?= $m['id'] ?>" class="tog-btn <?= $m['is_visible'] ? 'tog-on' : 'tog-off' ?>">
                            <i class="fa-solid <?= $m['is_visible'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                            <?= $m['is_visible'] ? 'LIVE' : 'HIDDEN' ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

        </div>
    </main>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3><i class="fa-solid fa-pen-to-square" style="color:var(--accent-blue);"></i> EDIT MODULE</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="mod_id" id="f_id">
            <input type="hidden" name="week"   id="f_week">
            <input type="hidden" name="day"    id="f_day">
            <div class="form-group">
                <label>Module Title</label>
                <input type="text" name="title" id="f_title" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="f_desc"></textarea>
            </div>
            <div class="form-group">
                <label>Upload Module File <span style="color:#475569;">(HTML, PDF, PPT, DOCX, ZIP, MP4 — max 50MB)</span></label>
                <div class="file-drop">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.4rem; margin-bottom:6px; display:block;"></i>
                    <span>Click or drag file here</span>
                    <input type="file" name="mod_file" accept=".html,.htm,.pdf,.doc,.docx,.ppt,.pptx,.zip,.mp4">
                </div>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="update_module" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> SAVE</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, week, day, title, desc) {
    document.getElementById('f_id').value    = id;
    document.getElementById('f_week').value  = week;
    document.getElementById('f_day').value   = day;
    document.getElementById('f_title').value = title;
    document.getElementById('f_desc').value  = desc;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
// Show filename on file select
document.querySelector('input[type=file]').addEventListener('change', function() {
    if(this.files[0]) this.parentElement.querySelector('span').innerText = this.files[0].name;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
