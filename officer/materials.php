<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (!function_exists('check_auth')) {
    require_once __DIR__ . '/../includes/functions.php';
}
check_auth('Officer');

$page_title = 'Study Materials | Officer Portal';

$study_materials = [];
try {
    $study_materials = $pdo->query("SELECT * FROM study_materials WHERE is_visible = 1 ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar" style="background: rgba(0,0,0,0.9); border-bottom: 2px solid #00f3ff; padding: 15px 30px;">
            <h2 style="margin:0; color:#00f3ff; font-family:'Rajdhani'; letter-spacing: 2px;">
                <i class="fa-solid fa-book-open-reader"></i> STUDY MATERIALS
            </h2>
        </header>

        <div style="padding: 30px;">

            <?php if (empty($study_materials)): ?>
                <div class="glass-panel" style="text-align: center; padding: 60px 20px; background: rgba(15,23,42,0.6); border: 1px dashed rgba(0,243,255,0.3);">
                    <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #00f3ff; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                    <p style="color: #64748b; font-weight: 600;">No study materials available yet.</p>
                    <p style="color: #475569; font-size: 0.85rem;">HQ will upload materials here for reference.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach($study_materials as $sm):
                        $ext = strtolower(pathinfo($sm['file_path'], PATHINFO_EXTENSION));
                        $icon  = 'fa-file-lines';
                        $color = '#00f3ff';
                        $bg    = 'rgba(0, 243, 255, 0.1)';
                        if ($ext === 'pdf') {
                            $icon = 'fa-file-pdf';   $color = '#ef4444'; $bg = 'rgba(239,68,68,0.1)';
                        } elseif ($ext === 'zip') {
                            $icon = 'fa-file-zipper'; $color = '#f59e0b'; $bg = 'rgba(245,158,11,0.1)';
                        } elseif ($ext === 'mp4') {
                            $icon = 'fa-file-video';  $color = '#a855f7'; $bg = 'rgba(168,85,247,0.1)';
                        } elseif (in_array($ext, ['doc','docx'])) {
                            $icon = 'fa-file-word';   $color = '#3b82f6'; $bg = 'rgba(59,130,246,0.1)';
                        } elseif (in_array($ext, ['ppt','pptx'])) {
                            $icon = 'fa-file-powerpoint'; $color = '#f97316'; $bg = 'rgba(249,115,22,0.1)';
                        } elseif (in_array($ext, ['html','htm'])) {
                            $icon = 'fa-file-code';  $color = '#10b981'; $bg = 'rgba(16,185,129,0.1)';
                        }
                    ?>
                    <div class="glass-panel" style="display: flex; gap: 20px; align-items: start; border-left: 5px solid <?= $color ?>; padding: 25px; background: rgba(15,23,42,0.4); border-radius: 12px;">
                        <div style="background: <?= $bg ?>; padding: 20px; border-radius: 8px; color: <?= $color ?>; flex-shrink: 0;">
                            <i class="fa-solid <?= $icon ?>" style="font-size: 2rem;"></i>
                        </div>
                        <div style="flex-grow: 1;">
                            <h4 style="color: <?= $color ?>; margin: 0 0 8px 0; font-family:'Rajdhani'; font-size: 1.1rem; letter-spacing: 1px;">
                                <?= esc($sm['title']) ?>
                            </h4>
                            <p style="font-size: 0.9rem; color: #94a3b8; margin: 0 0 15px 0; line-height: 1.6;">
                                <?= esc($sm['description'] ?: 'No description provided.') ?>
                            </p>
                            <?php if (!empty($sm['file_path'])): ?>
                                <a href="<?= BASE_URL ?>/officer/view_material.php?id=<?= $sm['id'] ?>" target="_blank"
                                   style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 22px; font-size: 0.8rem; background: <?= $color ?>; color: #020617; font-weight: 800; text-decoration: none; border-radius: 6px; font-family:'Rajdhani'; letter-spacing: 1px; transition: 0.2s;">
                                    <i class="fa-solid fa-lock"></i> SECURE VIEW <i class="fa-solid fa-arrow-right-long"></i>
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: #475569;">
                                    <i class="fa-solid fa-link-slash"></i> No file attached yet
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
