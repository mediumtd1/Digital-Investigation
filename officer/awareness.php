<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if(!function_exists('check_auth')) {
    require_once __DIR__ . '/../includes/functions.php';
}
check_auth('Officer');

$table = 'awareness_materials';
$page_title = 'Awareness Material | Digital Investigation';

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
            <h2 style="margin:0; color:#00f3ff; font-family:'Rajdhani'; letter-spacing: 2px;"><i class="fa-solid fa-lightbulb"></i> AWARENESS MATERIAL</h2>
        </header>

        <div style="padding: 30px; display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
            <?php if(empty($all_modules)): ?>
                <div class="glass-panel" style="grid-column: 1/-1; text-align:center; padding:60px; background: rgba(15,23,42,0.6); border: 1px dashed rgba(0,243,255,0.3);">
                    <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #00f3ff; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                    No Awareness Materials available in the secure database.
                </div>
            <?php else: foreach($all_modules as $m): ?>
                <div class="glass-panel" style="padding: 25px; transition: 0.3s; cursor: pointer; border: 1px solid rgba(0,243,255,0.1); background: rgba(15,23,42,0.4); border-radius: 12px; display: flex; flex-direction: column; justify-content: space-between;" 
                     onclick="window.open('<?= BASE_URL . '/' . esc($m['file_path']) ?>', '_blank')">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <span style="background: rgba(0,243,255,0.1); color: #00f3ff; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; font-family: 'Rajdhani';">
                                RES-ID: #<?= $m['id'] ?>
                            </span>
                            <i class="fa-solid fa-brain" style="color: #00f3ff; font-size: 1.4rem;"></i>
                        </div>
                        <h3 style="margin: 0 0 12px 0; font-size: 1.2rem; color: #fff; font-family: 'Rajdhani'; font-weight: 700;"><?= esc($m['title']) ?></h3>
                        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 20px; line-height: 1.6; height: 3.2em; overflow: hidden;">
                            <?= esc($m['description']) ?>
                        </p>
                    </div>
                    <div style="text-align: right; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                        <span style="color: #00f3ff; font-size: 0.8rem; font-weight: 800; letter-spacing: 1px;">EXPLORE RESOURCE <i class="fa-solid fa-arrow-right-long" style="margin-left: 5px;"></i></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>