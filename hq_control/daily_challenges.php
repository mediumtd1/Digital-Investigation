<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$page_title = 'Daily Challenge Management — HQ Control';
$portal = 'admin';

$msg = '';
$msg_type = '';

$batches = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC");
    $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$_rolling_batches = [];
for ($i = -3; $i <= 12; $i++) {
    $_rolling_batches[] = date('F Y', strtotime("$i month")) . " Batch";
}
foreach ($_rolling_batches as $m) {
    if (!in_array($m, $batches)) {
        $batches[] = $m;
    }
}

usort($batches, function($a, $b) {
    return strtotime(str_replace(' Batch', '', $a)) - strtotime(str_replace(' Batch', '', $b));
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_set') {
            $date_active = $_POST['date_active'] ?? '';
            $time_limit  = max(1, (int)($_POST['time_limit'] ?? 10));
            $batch_name  = trim($_POST['batch'] ?? '');
            $questions = $_POST['q'] ?? []; 

            if (empty($date_active)) {
                $msg = "Please specify a valid active date.";
                $msg_type = 'error';
            } elseif (count($questions) !== 5) {
                $msg = "You must provide exactly 5 questions for a daily challenge set.";
                $msg_type = 'error';
            } else {
                try {
                    $pdo->beginTransaction();

                    
                    
                    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
                    if ($is_edit) {
                        $old_date = $_POST['old_date'] ?? $date_active;
                        $old_batch = $_POST['old_batch'] ?? $batch_name;
                        $pdo->prepare("DELETE FROM daily_challenges WHERE date_active = ? AND (batch = ? OR (batch IS NULL AND ? = ''))")->execute([$old_date, $old_batch, $old_batch]);
                    } else {
                        
                        $check = $pdo->prepare("SELECT COUNT(*) FROM daily_challenges WHERE date_active = ? AND (batch = ? OR (batch IS NULL AND ? = ''))");
                        $check->execute([$date_active, $batch_name, $batch_name]);
                        if ($check->fetchColumn() > 0) {
                            throw new Exception("A challenge set is already scheduled for " . date('d M, Y', strtotime($date_active)) . " for batch '" . ($batch_name ?: 'All Batches') . "'. Please edit the existing set or choose another date/batch.");
                        }
                    }

                    
                    $stmt = $pdo->prepare("INSERT INTO daily_challenges (question, option_a, option_b, option_c, option_d, correct_options, points, explanation, time_limit, date_active, batch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($questions as $q) {
                        $q_text = trim($q['question'] ?? '');
                        $opt_a  = trim($q['option_a'] ?? '');
                        $opt_b  = trim($q['option_b'] ?? '');
                        $opt_c  = trim($q['option_c'] ?? '');
                        $opt_d  = trim($q['option_d'] ?? '');
                        
                        
                        $correct_arr = $q['correct'] ?? [];
                        $correct_str = implode(',', $correct_arr);
                        
                        $points = (int)($q['points'] ?? 2);
                        $explanation = trim($q['explanation'] ?? '');

                        if ($q_text === '' || $opt_a === '' || $opt_b === '' || $opt_c === '' || $opt_d === '' || $correct_str === '') {
                            throw new Exception("All fields (question text, options, and at least one correct choice) are required for all 5 questions.");
                        }

                        $stmt->execute([$q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct_str, $points, $explanation, $time_limit, $date_active, $batch_name]);
                    }

                    $pdo->commit();
                    $msg = "Daily challenge set of 5 questions saved successfully for " . date('d M, Y', strtotime($date_active)) . " (" . ($batch_name ?: 'All Batches') . ")!";
                    $msg_type = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $msg = $e->getMessage();
                    $msg_type = 'error';
                }
            }
        } elseif ($_POST['action'] === 'delete_set') {
            $date = $_POST['date_active'] ?? '';
            $batch = $_POST['batch'] ?? '';
            if (!empty($date)) {
                $pdo->prepare("DELETE FROM daily_challenges WHERE date_active = ? AND (batch = ? OR (batch IS NULL AND ? = ''))")->execute([$date, $batch, $batch]);
                $msg = "Challenge set for " . date('d M, Y', strtotime($date)) . " (" . ($batch ?: 'All Batches') . ") deleted successfully.";
                $msg_type = 'warn';
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM daily_challenges ORDER BY date_active DESC, batch ASC, id ASC");
$all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_challenges = [];
foreach ($all_rows as $row) {
    $key = $row['date_active'] . '|' . ($row['batch'] ?? '');
    $grouped_challenges[$key][] = $row;
}

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/_sidebar.php'; ?>

<div class="main-content" style="padding: 30px; padding-top: 80px;">

    
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:30px; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="width:50px;height:50px;background:linear-gradient(135deg,#00f3ff,#a855f7);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;box-shadow:0 0 20px rgba(0,243,255,0.4); color:#fff;">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <div>
                <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:800;background:linear-gradient(90deg,#00f3ff,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0;letter-spacing:1px;">DAILY CHALLENGE SETS</h1>
                <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;">Create daily challenge sets (exactly 5 questions each) with single or multi-correct answers.</p>
            </div>
        </div>
        <div>
            <button onclick="openSetModal()" class="btn-primary" style="background: linear-gradient(135deg, #00f3ff, #a855f7); border: none; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus"></i> Schedule 5-Question Set
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div style="background:<?= $msg_type==='success'?'rgba(16,185,129,0.1)':($msg_type==='error'?'rgba(239,68,68,0.1)':'rgba(245,158,11,0.1)') ?>;border:1px solid <?= $msg_type==='success'?'#10b981':($msg_type==='error'?'#ef4444':'#f59e0b') ?>;border-radius:8px;padding:12px 18px;color:<?= $msg_type==='success'?'#10b981':($msg_type==='error'?'#ef4444':'#f59e0b') ?>;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
        <i class="fa-solid <?= $msg_type==='success'?'fa-circle-check':($msg_type==='error'?'fa-circle-xmark':'fa-triangle-exclamation') ?>"></i> <?= esc($msg) ?>
    </div>
    <?php endif; ?>

    
    <?php if (empty($grouped_challenges)): ?>
        <div class="glass-panel" style="text-align: center; padding: 40px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color: var(--text-muted); font-style: italic; margin: 0;">No challenge sets scheduled yet. Create one above!</p>
        </div>
    <?php else: ?>
        <?php foreach($grouped_challenges as $key => $qs): 
            list($date, $batch) = explode('|', $key);
            $today = date('Y-m-d');
            $status_lbl = '';
            $status_style = '';
            if ($date === $today) {
                $status_lbl = 'ACTIVE TODAY';
                $status_style = 'background: rgba(16,185,129,0.2); color:#10b981; border: 1px solid #10b981;';
            } elseif ($date < $today) {
                $status_lbl = 'PAST SET';
                $status_style = 'background: rgba(148,163,184,0.15); color:#94a3b8; border: 1px solid rgba(148,163,184,0.3);';
            } else {
                $status_lbl = 'UPCOMING';
                $status_style = 'background: rgba(59,130,246,0.2); color:#60a5fa; border: 1px solid #3b82f6;';
            }
        ?>
        <div class="glass-panel" style="background: rgba(15,23,42,0.4); border: 1px solid rgba(255,255,255,0.05); padding: 20px; margin-bottom: 25px;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:12px; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <span style="font-size:1.1rem; font-weight:bold; color:#fff; font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">
                        <i class="fa-solid fa-calendar-day" style="color:var(--accent-blue);"></i> <?= date('d M, Y', strtotime($date)) ?>
                    </span>
                    <span style="padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold; <?= $status_style ?>"><?= $status_lbl ?></span>
                    <span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold;"><i class="fa-regular fa-clock"></i> <?= (int)($qs[0]['time_limit'] ?? 10) ?> Mins</span>
                    <span style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold;"><i class="fa-solid fa-users"></i> <?= esc($batch ?: 'All Batches') ?></span>
                </div>
                <div style="display:flex; gap:10px;">
                    <button onclick='editSet(<?= json_encode($date) ?>, <?= json_encode($batch) ?>, <?= json_encode($qs) ?>)' class="btn-primary" style="padding: 5px 12px; background: rgba(59,130,246,0.2); border: 1px solid #3b82f6; color:#60a5fa; font-size:0.8rem; border-radius:6px; cursor:pointer;">
                        <i class="fa-solid fa-edit"></i> Edit Set
                    </button>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this 5-question challenge set for <?= esc($batch ?: 'All Batches') ?>?');" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="delete_set">
                        <input type="hidden" name="date_active" value="<?= $date ?>">
                        <input type="hidden" name="batch" value="<?= esc($batch) ?>">
                        <button type="submit" class="btn-primary" style="padding: 5px 12px; background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color:#f87171; font-size:0.8rem; border-radius:6px; cursor:pointer;">
                            <i class="fa-solid fa-trash"></i> Delete Set
                        </button>
                    </form>
                </div>
            </div>

            
            <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                <?php foreach($qs as $index => $q): 
                    $correct_opts = explode(',', $q['correct_options']);
                ?>
                <div style="background: rgba(0,0,0,0.2); border-radius: 8px; padding: 15px; border-left: 3px solid #a855f7;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-weight:bold; color:#cbd5e1; font-size:0.9rem;">Question <?= $index + 1 ?>:</span>
                        <span style="color:#00f3ff; font-size:0.8rem; font-family:monospace;"><?= $q['points'] ?> XP</span>
                    </div>
                    <p style="color:#fff; margin:0 0 10px 0; font-size:0.95rem;"><?= esc($q['question']) ?></p>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; font-size:0.82rem; color:#94a3b8;">
                        <div style="<?= in_array('A', $correct_opts)?'color:#10b981; font-weight:bold;':'' ?>">A: <?= esc($q['option_a']) ?></div>
                        <div style="<?= in_array('B', $correct_opts)?'color:#10b981; font-weight:bold;':'' ?>">B: <?= esc($q['option_b']) ?></div>
                        <div style="<?= in_array('C', $correct_opts)?'color:#10b981; font-weight:bold;':'' ?>">C: <?= esc($q['option_c']) ?></div>
                        <div style="<?= in_array('D', $correct_opts)?'color:#10b981; font-weight:bold;':'' ?>">D: <?= esc($q['option_d']) ?></div>
                    </div>
                    <div style="margin-top:10px; font-size:0.8rem; color:#cbd5e1;">
                        <strong>Correct Answer(s):</strong> 
                        <span style="background:rgba(16,185,129,0.2); color:#10b981; padding:2px 6px; border-radius:4px; font-weight:bold; margin-left:5px; font-family:monospace;">
                            <?= implode(', ', $correct_opts) ?>
                        </span>
                        <?php if(!empty($q['explanation'])): ?>
                            <span style="color:#64748b; font-style:italic; margin-left:15px;">— "<?= esc($q['explanation']) ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="setModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; justify-content:center; align-items:center; padding: 20px;">
    <div class="glass-panel" style="background:#0b1329; border: 1px solid rgba(0,243,255,0.2); max-width: 850px; width:100%; max-height:92vh; overflow-y:auto; padding:25px; box-shadow: 0 0 40px rgba(0,243,255,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:20px;">
            <h3 id="modalTitle" style="margin:0; font-family:'Rajdhani',sans-serif; font-size:1.3rem; color:#fff; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-calendar-plus" style="color:#00f3ff;"></i> Schedule Daily Challenge Set
            </h3>
            <button onclick="closeModal()" style="background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer; font-weight:bold;">&times;</button>
        </div>

        <form method="POST" id="setForm">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="action" value="save_set">
            <input type="hidden" name="is_edit" id="isEditInput" value="0">
            <input type="hidden" name="old_date" id="oldDateInput" value="">
            <input type="hidden" name="old_batch" id="oldBatchInput" value="">

            
            <div style="margin-bottom:25px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <label style="display:block; margin-bottom:8px; color:#00f3ff; font-size:0.95rem; font-weight:bold; font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">Active Date for this Challenge Set *</label>
                    <input type="date" name="date_active" id="f_date_active" required style="width: 200px; padding:10px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:#fff; font-size:1rem;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; color:#00f3ff; font-size:0.95rem; font-weight:bold; font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">Time Limit (Minutes) *</label>
                    <input type="number" name="time_limit" id="f_time_limit" required value="10" min="1" max="180" style="width: 150px; padding:10px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:#fff; font-size:1rem;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; color:#00f3ff; font-size:0.95rem; font-weight:bold; font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">Target Batch *</label>
                    <select name="batch" id="f_batch" style="width: 220px; padding:10px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:#fff; font-size:1rem;">
                        <option value="" style="background: #0f172a; color:#fff;">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= esc($b) ?>" style="background: #0f172a; color:#fff;"><?= esc($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            
            <div style="margin-bottom:20px;">
                <h4 style="color:#fff; font-family:'Rajdhani',sans-serif; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:5px; margin-bottom:15px;">Configure the 5 Questions</h4>
                
                <?php for($i=0; $i<5; $i++): ?>
                <div style="background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.03); border-radius: 8px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #a855f7;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-weight:bold; color:#a855f7; font-size:1rem; font-family:'Rajdhani',sans-serif;">QUESTION <?= $i+1 ?></span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label style="font-size:0.8rem; color:#cbd5e1;">XP points:</label>
                            <input type="number" name="q[<?= $i ?>][points]" id="q_<?= $i ?>_points" value="2" min="1" max="100" style="width:70px; padding:5px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:#fff; text-align:center;">
                        </div>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.85rem; font-weight:bold;">Question / Quiz Text *</label>
                        <textarea name="q[<?= $i ?>][question]" id="q_<?= $i ?>_text" rows="2" required style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-family:inherit; resize:vertical;"></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.82rem;">Option A *</label>
                            <input type="text" name="q[<?= $i ?>][option_a]" id="q_<?= $i ?>_opt_a" required style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.82rem;">Option B *</label>
                            <input type="text" name="q[<?= $i ?>][option_b]" id="q_<?= $i ?>_opt_b" required style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.82rem;">Option C *</label>
                            <input type="text" name="q[<?= $i ?>][option_c]" id="q_<?= $i ?>_opt_c" required style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.82rem;">Option D *</label>
                            <input type="text" name="q[<?= $i ?>][option_d]" id="q_<?= $i ?>_opt_d" required style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff;">
                        </div>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.85rem; font-weight:bold;">Correct Options * (Select one or more to support multiple answers)</label>
                        <div style="display:flex; gap:20px; align-items:center; background:rgba(0,0,0,0.2); padding:10px; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                            <label style="color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="q[<?= $i ?>][correct][]" value="A" id="q_<?= $i ?>_check_a" style="accent-color:#10b981; width:16px; height:16px;"> Option A
                            </label>
                            <label style="color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="q[<?= $i ?>][correct][]" value="B" id="q_<?= $i ?>_check_b" style="accent-color:#10b981; width:16px; height:16px;"> Option B
                            </label>
                            <label style="color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="q[<?= $i ?>][correct][]" value="C" id="q_<?= $i ?>_check_c" style="accent-color:#10b981; width:16px; height:16px;"> Option C
                            </label>
                            <label style="color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="q[<?= $i ?>][correct][]" value="D" id="q_<?= $i ?>_check_d" style="accent-color:#10b981; width:16px; height:16px;"> Option D
                            </label>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:5px; color:#cbd5e1; font-size:0.82rem;">Answer Explanation</label>
                        <input type="text" name="q[<?= $i ?>][explanation]" id="q_<?= $i ?>_explanation" class="form-control" style="width:100%; padding:8px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff;" placeholder="Explain correct options...">
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid rgba(255,255,255,0.1); padding-top:15px; position:sticky; bottom:0; background:#0b1329; padding: 15px 0 0 0;">
                <button type="button" onclick="closeModal()" class="btn-primary" style="background:#475569; padding: 10px 20px; border-radius:6px; font-weight:bold; cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #00f3ff, #a855f7); padding: 10px 20px; border-radius:6px; font-weight:bold; cursor:pointer;">Save 5-Question Set</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSetModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-calendar-plus" style="color:#00f3ff;"></i> Schedule Daily Challenge Set';
    document.getElementById('isEditInput').value = '0';
    document.getElementById('oldDateInput').value = '';
    document.getElementById('oldBatchInput').value = '';
    document.getElementById('f_date_active').value = new Date().toISOString().split('T')[0];
    document.getElementById('f_time_limit').value = '10';
    document.getElementById('f_batch').value = '';
    document.getElementById('f_date_active').disabled = false;

    // Reset all 5 forms
    for (let i = 0; i < 5; i++) {
        document.getElementById(`q_${i}_text`).value = '';
        document.getElementById(`q_${i}_opt_a`).value = '';
        document.getElementById(`q_${i}_opt_b`).value = '';
        document.getElementById(`q_${i}_opt_c`).value = '';
        document.getElementById(`q_${i}_opt_d`).value = '';
        document.getElementById(`q_${i}_points`).value = '2';
        document.getElementById(`q_${i}_explanation`).value = '';
        
        document.getElementById(`q_${i}_check_a`).checked = false;
        document.getElementById(`q_${i}_check_b`).checked = false;
        document.getElementById(`q_${i}_check_c`).checked = false;
        document.getElementById(`q_${i}_check_d`).checked = false;
    }

    document.getElementById('setModal').style.display = 'flex';
}

function editSet(date, batch, qs) {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color:#a855f7;"></i> Edit Daily Challenge Set';
    document.getElementById('isEditInput').value = '1';
    document.getElementById('oldDateInput').value = date;
    document.getElementById('oldBatchInput').value = batch;
    document.getElementById('f_date_active').value = date;
    document.getElementById('f_time_limit').value = qs[0] ? (qs[0].time_limit || 10) : 10;
    document.getElementById('f_batch').value = batch;
    
    // Fill all 5 forms
    for (let i = 0; i < 5; i++) {
        let q = qs[i] || { question: '', option_a: '', option_b: '', option_c: '', option_d: '', points: 2, explanation: '', correct_options: '' };
        
        document.getElementById(`q_${i}_text`).value = q.question;
        document.getElementById(`q_${i}_opt_a`).value = q.option_a;
        document.getElementById(`q_${i}_opt_b`).value = q.option_b;
        document.getElementById(`q_${i}_opt_c`).value = q.option_c;
        document.getElementById(`q_${i}_opt_d`).value = q.option_d;
        document.getElementById(`q_${i}_points`).value = q.points;
        document.getElementById(`q_${i}_explanation`).value = q.explanation;
        
        let correctOpts = q.correct_options.split(',');
        document.getElementById(`q_${i}_check_a`).checked = correctOpts.includes('A');
        document.getElementById(`q_${i}_check_b`).checked = correctOpts.includes('B');
        document.getElementById(`q_${i}_check_c`).checked = correctOpts.includes('C');
        document.getElementById(`q_${i}_check_d`).checked = correctOpts.includes('D');
    }

    document.getElementById('setModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('setModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
