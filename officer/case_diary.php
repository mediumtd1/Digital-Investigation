<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Secure Investigation Notes | Officer Portal';
$officer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? 'Untitled Intel');
    $content = $_POST['content'] ?? '';
    $tags = trim($_POST['tags'] ?? '');
    $is_important = isset($_POST['is_important']) ? 1 : 0;
    
    if (isset($_POST['note_id']) && !empty($_POST['note_id'])) {
        $stmt = $pdo->prepare("UPDATE officer_notes SET title = ?, content = ?, tags = ?, is_important = ? WHERE id = ? AND officer_id = ?");
        $stmt->execute([$title, $content, $tags, $is_important, $_POST['note_id'], $officer_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO officer_notes (officer_id, title, content, tags, is_important) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$officer_id, $title, $content, $tags, $is_important]);
    }
    header("Location: case_diary.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security check failed. Refresh and try again.");
    }
    $stmt = $pdo->prepare("DELETE FROM officer_notes WHERE id = ? AND officer_id = ?");
    $stmt->execute([(int)$_POST['delete_note_id'], $officer_id]);
    header("Location: case_diary.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM officer_notes WHERE officer_id = ? ORDER BY is_important DESC, updated_at DESC");
$stmt->execute([$officer_id]);
$notes = $stmt->fetchAll();

$current_note = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM officer_notes WHERE id = ? AND officer_id = ?");
    $stmt->execute([$_GET['edit'], $officer_id]);
    $current_note = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.notes-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 20px;
    height: calc(100vh - 160px);
}
.notes-sidebar {
    overflow-y: auto;
    border-right: 1px solid var(--border-color);
    padding-right: 10px;
}
.note-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    padding: 15px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}
.note-card:hover { border-left-color: var(--accent-blue); background: var(--glass-bg); }
.note-card.active { border-left-color: var(--accent-blue); background: var(--accent-glow); }
.note-card.important { border-left-color: var(--danger); }
.note-card.important i { color: var(--danger); }

.editor-pane {
    display: flex;
    flex-direction: column;
}
.editor-title {
    background: transparent;
    border: none;
    border-bottom: 2px solid var(--border-color);
    color: var(--text-primary);
    font-size: 1.8rem;
    font-weight: 800;
    padding: 10px 0;
    margin-bottom: 15px;
    width: 100%;
}
.editor-title:focus { outline: none; border-bottom-color: var(--accent-blue); }
.editor-content {
    flex-grow: 1;
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 20px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 1.1rem;
    resize: none;
    border-radius: 4px;
}
.editor-content:focus { outline: none; border-color: var(--accent-blue); }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h2>Digital Field Notes (Encrypted)</h2>
            <div class="topbar-right">
                <a href="case_diary.php" class="btn-primary" style="padding: 10px 20px;"><i class="fa-solid fa-plus"></i> NEW INTEL</a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="notes-layout">
                
                <div class="notes-sidebar">
                    <?php if (empty($notes)): ?>
                        <p style="color: var(--text-muted); text-align: center; margin-top: 40px;">No notes found.</p>
                    <?php endif; ?>
                    <?php foreach ($notes as $n): ?>
                        <div class="note-card <?= ($current_note && $current_note['id'] == $n['id']) ? 'active' : '' ?> <?= $n['is_important'] ? 'important' : '' ?>" onclick="location.href='?edit=<?= $n['id'] ?>'">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <h4 style="margin:0; font-size: 1.1rem;"><?= $n['is_important'] ? '<i class="fa-solid fa-circle-exclamation"></i> ' : '' ?><?= esc($n['title']) ?></h4>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Securely shred this note?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="delete_note_id" value="<?= $n['id'] ?>">
                                        <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.8rem; padding:0;"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 5px 0 10px;"><?= substr(strip_tags($n['content']), 0, 80) ?>...</p>
                            <div style="font-size: 0.7rem; color: var(--accent-highlight);">
                                <i class="fa-regular fa-clock"></i> <?= date('d M, Y', strtotime($n['updated_at'])) ?>
                                <?php if($n['tags']): ?>
                                    <span style="margin-left: 10px;"><i class="fa-solid fa-tags"></i> <?= esc($n['tags']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                
                <div class="editor-pane">
                    <form method="POST" style="height: 100%; display: flex; flex-direction: column;">
                        <input type="hidden" name="note_id" value="<?= $current_note['id'] ?? '' ?>">
                        <input type="text" name="title" class="editor-title" placeholder="Note Title..." value="<?= esc($current_note['title'] ?? '') ?>" required>
                        
                        <div style="margin-bottom: 15px; display: flex; gap: 20px; align-items: center;">
                            <input type="text" name="tags" class="form-control" placeholder="Tags (e.g. Evidence, IP_Logs)" value="<?= esc($current_note['tags'] ?? '') ?>" style="max-width: 300px; padding: 8px;">
                            <label style="cursor: pointer; user-select: none;">
                                <input type="checkbox" name="is_important" <?= ($current_note['is_important'] ?? 0) ? 'checked' : '' ?>> 
                                <span style="color: var(--danger); font-weight: bold; margin-left: 5px;">MARK AS CRITICAL</span>
                            </label>
                            <div style="flex-grow: 1;"></div>
                            <button type="submit" class="btn-primary" style="padding: 10px 30px; background: var(--success);"><i class="fa-solid fa-cloud-arrow-up"></i> SAVE TO SECURE CLOUD</button>
                        </div>

                        <textarea name="content" class="editor-content" placeholder="Type investigation findings here... This area supports text and terminal logs."><?= esc($current_note['content'] ?? '') ?></textarea>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
