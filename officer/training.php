<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if(!function_exists('check_auth')) {
    require_once __DIR__ . '/../includes/functions.php';
}
check_auth('Officer');

$table = 'officer_training';
$page_title = 'Training Material | Digital Investigation';

try {
    $pdo->exec("ALTER TABLE $table ADD COLUMN IF NOT EXISTS week INT DEFAULT 1");
    $pdo->exec("ALTER TABLE $table ADD COLUMN IF NOT EXISTS day INT DEFAULT 1");
    $pdo->exec("ALTER TABLE $table ADD COLUMN IF NOT EXISTS is_visible TINYINT(1) DEFAULT 1");
} catch (Exception $e) {
    try { $pdo->exec("ALTER TABLE $table ADD COLUMN week INT DEFAULT 1"); } catch(Exception $ex){}
    try { $pdo->exec("ALTER TABLE $table ADD COLUMN day INT DEFAULT 1"); } catch(Exception $ex){}
    try { $pdo->exec("ALTER TABLE $table ADD COLUMN is_visible TINYINT(1) DEFAULT 1"); } catch(Exception $ex){}
}

try {
    $all_modules = $pdo->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    die("Critical Error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar" style="background: rgba(0,0,0,0.9); border-bottom: 2px solid #00f3ff; padding: 15px 30px;">
            <h2 style="margin:0; color:#00f3ff; font-family:'Rajdhani'; letter-spacing: 2px;"><i class="fa-solid fa-graduation-cap"></i> TRAINING MATERIAL</h2>
        </header>

        <div style="padding: 30px; display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
            <?php if(empty($all_modules)): ?>
                <div class="glass-panel" style="grid-column: 1/-1; text-align:center; padding:60px; background: rgba(15,23,42,0.6); border: 1px dashed rgba(0,243,255,0.3);">
                    <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #00f3ff; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                    No Training Materials available in the secure database.
                </div>
            <?php else: foreach($all_modules as $m): ?>
                <div class="glass-panel" style="padding: 25px; transition: 0.3s; cursor: pointer; border: 1px solid rgba(0,243,255,0.1); background: rgba(15,23,42,0.4); border-radius: 12px; display: flex; flex-direction: column; justify-content: space-between;" 
                     onclick="openSecureViewer('<?= addslashes(esc($m['title'])) ?>', '<?= BASE_URL . '/' . esc($m['file_path']) ?>')">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <span style="background: rgba(0,243,255,0.1); color: #00f3ff; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; font-family: 'Rajdhani';">
                                MOD-ID: #<?= $m['id'] ?>
                            </span>
                            <i class="fa-solid fa-book-open-reader" style="color: #00f3ff; font-size: 1.4rem;"></i>
                        </div>
                        <h3 style="margin: 0 0 12px 0; font-size: 1.2rem; color: #fff; font-family: 'Rajdhani'; font-weight: 700;"><?= esc($m['title']) ?></h3>
                        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 20px; line-height: 1.6; height: 3.2em; overflow: hidden;">
                            <?= esc($m['description']) ?>
                        </p>
                    </div>
                    <div style="text-align: right; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                        <span style="color: #00f3ff; font-size: 0.8rem; font-weight: 800; letter-spacing: 1px;"><i class="fa-solid fa-lock"></i> SECURE VIEW <i class="fa-solid fa-arrow-right-long" style="margin-left: 5px;"></i></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>

<div id="secureViewer" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.98); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(15px);">
    <div class="viewer-content" style="width:98vw; height:95vh; background:#000; border:1px solid #00f3ff; border-radius:12px; display:flex; flex-direction:column; box-shadow: 0 0 50px rgba(0,243,255,0.3);">
        <div class="viewer-header" style="padding:20px 30px; border-bottom:1px solid rgba(0,243,255,0.3); display:flex; justify-content:space-between; align-items:center; background:rgba(0,243,255,0.05);">
            <div>
                <span style="color:#00f3ff; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:3px; display:block; margin-bottom:5px;">Digital Investigation Tactical Viewer</span>
                <h3 id="vTitle" style="margin:0; font-family:'Rajdhani'; color:#fff; letter-spacing:1px; font-size: 1.4rem;">Training Document</h3>
            </div>
            <button onclick="closeViewer()" style="background:rgba(239, 68, 68, 0.1); border:1px solid #ef4444; color:#ef4444; width:45px; height:45px; border-radius:10px; font-size:1.8rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition: 0.3s;">&times;</button>
        </div>
        <div class="viewer-body" style="flex-grow:1; background:#020406; position:relative; overflow:hidden;">
            
            <div class="watermark-overlay" style="position:absolute; inset:0; pointer-events:none; z-index:100; opacity:0.04; display:flex; flex-wrap:wrap; justify-content:center; align-content:center; overflow:hidden;">
                <?php 
                    $wm_text = esc($_SESSION['user_name']) . " | " . esc($_SESSION['user_mapped_id']);
                    for($i=0; $i<35; $i++) echo "<span style='font-size:18px; font-weight:bold; padding:35px; transform:rotate(-30deg); white-space:nowrap; color:#fff;'>$wm_text</span>"; 
                ?>
            </div>
            <iframe id="vFrame" style="width:100%; height:100%; border:none; filter:invert(1) hue-rotate(170deg) brightness(1.2) contrast(1.1); transition: filter 0.2s ease;"></iframe>
        </div>
        <div class="viewer-footer" style="padding:15px 30px; border-top:1px solid rgba(0,243,255,0.3); font-size:0.8rem; color:#00f3ff; text-align:center; background:rgba(0,0,0,0.8); font-weight: bold; letter-spacing: 1px;">
            <i class="fa-solid fa-lock"></i> SECURE READ-ONLY TRAINING SESSION | ACTION LOGGED FOR SECURITY COMPLIANCE
        </div>
    </div>
</div>

<script>
function openSecureViewer(title, url) {
    document.getElementById('vTitle').innerText = title;
    document.getElementById('vFrame').src = url + (url.includes('?') ? '&' : '?') + 'toolbar=0&navpanes=0';
    document.getElementById('secureViewer').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeViewer() {
    document.getElementById('secureViewer').style.display = 'none';
    document.getElementById('vFrame').src = '';
    document.body.style.overflow = 'auto';
}

// Anti-Screenshot (Focus loss blur)
window.addEventListener('blur', () => {
    document.getElementById('vFrame').style.filter = 'blur(30px)';
});
window.addEventListener('focus', () => {
    document.getElementById('vFrame').style.filter = 'invert(1) hue-rotate(170deg) brightness(1.2) contrast(1.1)';
});

// Anti-Leakage Controls
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());
document.addEventListener('selectstart', e => e.preventDefault());
document.addEventListener('keydown', e => {
    // Block standard keys: Ctrl/Cmd + P, S, U, C, X, A
    if (e.ctrlKey || e.metaKey) {
        if (['p', 's', 'u', 'c', 'x', 'a'].includes(e.key.toLowerCase())) {
            e.preventDefault();
        }
    }
    // Block F12
    if (e.key === "F12") {
        e.preventDefault();
    }
});
</script>

<style>
body, .glass-panel { -webkit-user-select: none; user-select: none; }
@media print { body { display: none !important; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>