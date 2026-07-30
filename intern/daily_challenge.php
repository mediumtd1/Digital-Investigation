<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Intern');

$portal = 'intern';
$page_title = 'Daily Challenge | Intern Portal';
$intern_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
$stmt->execute([$intern_id]);
$intern = $stmt->fetch();

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$stmt = $pdo->prepare("SELECT * FROM daily_challenges WHERE date_active = ? AND batch = ? ORDER BY id ASC LIMIT 5");
$stmt->execute([$today, $intern['batch'] ?? '']);
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($challenges)) {
    
    $stmt = $pdo->prepare("SELECT * FROM daily_challenges WHERE date_active = ? AND (batch IS NULL OR batch = '') ORDER BY id ASC LIMIT 5");
    $stmt->execute([$today]);
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$submissions = [];
if (!empty($challenges)) {
    $challenge_ids = array_column($challenges, 'id');
    $placeholders = implode(',', array_fill(0, count($challenge_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM intern_challenges WHERE intern_id = ? AND challenge_id IN ($placeholders)");
    $stmt->execute(array_merge([$intern_id], $challenge_ids));
    $raw_subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    foreach ($raw_subs as $s) {
        $submissions[$s['challenge_id']] = $s;
    }
}

$stmt = $pdo->prepare("SELECT * FROM intern_quiz_sessions WHERE intern_id = ? AND date_active = ?");
$stmt->execute([$intern_id, $today]);
$session = $stmt->fetch();

$already_submitted = (count($submissions) === 5) || ($session && !empty($session['submitted_at']));

$msg = '';
$msg_type = '';

$time_limit_mins = !empty($challenges) ? (int)$challenges[0]['time_limit'] : 10;
$time_limit_secs = $time_limit_mins * 60;
$time_remaining = 0;
$has_started = ($session !== false);

if ($has_started && !$already_submitted) {
    $started_time = strtotime($session['started_at']);
    $elapsed = time() - $started_time;
    $time_remaining = $time_limit_secs - $elapsed;
    if ($time_remaining < 0) {
        $time_remaining = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_quiz'])) {
    if (!empty($challenges) && !$has_started && !$already_submitted) {
        try {
            $ins = $pdo->prepare("INSERT INTO intern_quiz_sessions (intern_id, date_active, started_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE started_at=started_at");
            $ins->execute([$intern_id, $today]);
            $_SESSION['toast_msg'] = "Daily Quiz started! Timer is now running.";
            $_SESSION['toast_type'] = "info";
        } catch (Exception $e) {}
    }
    header("Location: daily_challenge.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    if (empty($challenges) || $already_submitted || !$has_started) {
        header("Location: daily_challenge.php");
        exit;
    }

    $posted_answers = $_POST['answers'] ?? []; 
    $total_earned = 0;
    
    
    $started_time = strtotime($session['started_at']);
    $elapsed = time() - $started_time;
    $time_exceeded = ($elapsed > ($time_limit_secs + 15)); 

    try {
        $pdo->beginTransaction();

        foreach ($challenges as $c) {
            $cid = $c['id'];
            $selected_arr = $posted_answers[$cid] ?? []; 
            sort($selected_arr);
            $selected_str = implode(',', $selected_arr);

            $correct_arr = explode(',', $c['correct_options']);
            sort($correct_arr);
            $correct_str = implode(',', $correct_arr);

            $is_correct = ($selected_str === $correct_str) ? 1 : 0;
            
            $points_earned = 0;
            if (!$time_exceeded) {
                if ($is_correct) {
                    $points_earned = (int)$c['points'];
                } else {
                    
                    $n_correct = count($correct_arr);
                    $n_selected = count($selected_arr);
                    $has_incorrect = false;
                    foreach ($selected_arr as $sel) {
                        if (!in_array($sel, $correct_arr)) {
                            $has_incorrect = true;
                            break;
                        }
                    }
                    if ($n_selected > 0 && !$has_incorrect) {
                        $points_earned = (int)round(($n_selected / $n_correct) * (int)$c['points']);
                    }
                }
            }
            $total_earned += $points_earned;

            
            $ins = $pdo->prepare("INSERT INTO intern_challenges (intern_id, challenge_id, selected_options, is_correct, points_earned) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE selected_options=?, is_correct=?, points_earned=?");
            $ins->execute([$intern_id, $cid, $selected_str, $is_correct, $points_earned, $selected_str, $is_correct, $points_earned]);
        }

        
        $upd_sess = $pdo->prepare("UPDATE intern_quiz_sessions SET submitted_at = CURRENT_TIMESTAMP WHERE intern_id = ? AND date_active = ?");
        $upd_sess->execute([$intern_id, $today]);

        
        $current_streak = (int)$intern['streak_count'];
        $last_active = $intern['last_active_date'];
        
        if (empty($last_active)) {
            $new_streak = 1;
        } elseif ($last_active === $yesterday) {
            $new_streak = $current_streak + 1;
        } elseif ($last_active === $today) {
            $new_streak = $current_streak; 
        } else {
            $new_streak = 1; 
        }

        
        $upd = $pdo->prepare("UPDATE interns SET streak_count = ?, last_active_date = ? WHERE id = ?");
        $upd->execute([$new_streak, $today, $intern_id]);

        $pdo->commit();

        log_audit($pdo, $_SESSION['user_mapped_id'], 'Intern', "Completed daily 5-question challenge set. Total XP: " . $total_earned . ($time_exceeded ? " (Time Exceeded)" : ""));

        if ($time_exceeded) {
            $_SESSION['toast_msg'] = "Daily Quiz submitted but time limit exceeded! You earned 0 XP.";
            $_SESSION['toast_type'] = "warn";
        } else {
            $_SESSION['toast_msg'] = "Daily Quiz submitted! You earned +$total_earned XP today.";
            $_SESSION['toast_type'] = "success";
        }

        header("Location: daily_challenge.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error submitting answers: " . $e->getMessage();
        $msg_type = 'error';
    }
}

$streak = (int)($intern['streak_count'] ?? 0);
$last_active = $intern['last_active_date'];

if (!empty($last_active) && $last_active !== $today && $last_active !== $yesterday) {
    $streak = 0;
    $pdo->prepare("UPDATE interns SET streak_count = 0 WHERE id = ?")->execute([$intern_id]);
}

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left" style="display: flex; align-items: center; gap: 15px;">
                <img src="../assets/images/logo.png" style="height: 45px; width: 45px; object-fit: contain;">
                <h2 style="margin: 0; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">DAILY CHALLENGES</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php 
                        $u_photo = $intern['profile_photo'] ?? '';
                        $u_name = $intern['name'];
                        if (!empty($u_photo) && file_exists(__DIR__ . '/../' . $u_photo)) {
                            $photo_url = '../' . $u_photo;
                        } else {
                            $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($u_name) . "&background=00f3ff&color=000&bold=true";
                        }
                    ?>
                    <img src="<?= $photo_url ?>" alt="Profile" class="profile-pic" style="width:45px; height:45px; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent-blue);">
                    <div class="user-info">
                        <span class="user-name"><?= esc($intern['name']) ?></span>
                        <span class="user-role">Verified Intern</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content" style="padding: 30px;">

    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        
        <div class="glass-panel" style="display:flex; align-items:center; gap:20px; border-left: 4px solid var(--accent-blue);">
            <div style="width:60px; height:60px; background:rgba(0,243,255,0.1); border:1px solid rgba(0,243,255,0.3); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:var(--accent-blue);">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <div>
                <h1 style="font-family:'Rajdhani',sans-serif; font-size:1.8rem; font-weight:800; color:#fff; margin:0; letter-spacing:1px;">DAILY CYBER CHALLENGE</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin:4px 0 0;">Complete today's set of 5 questions to earn score points and build your daily streak!</p>
            </div>
        </div>

        
        <div class="glass-panel" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; border: 1px solid <?= $streak > 0 ? '#ef4444' : 'rgba(255,255,255,0.05)' ?>; background: <?= $streak > 0 ? 'rgba(239, 68, 68, 0.05)' : 'rgba(15,23,42,0.4)' ?>;">
            <div style="font-size: 2.2rem; margin-bottom: 5px; animation: flamePulse 1.5s infinite alternate;">
                🔥
            </div>
            <h3 style="margin: 0; font-family:'Rajdhani',sans-serif; color: #fff; font-size: 1.2rem; font-weight: 800;">
                <?= $streak ?> Day Streak
            </h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 4px 0 0;">
                <?= $last_active === $today ? 'Active Today!' : 'Solve today to keep streak active!' ?>
            </p>
        </div>

    </div>

    
    <div class="glass-panel" style="background: rgba(15,23,42,0.4); border: 1px solid rgba(255,255,255,0.05); padding: 30px;">
        
        <?php if (empty($challenges)): ?>
            
            <div style="text-align: center; padding: 40px 0;">
                <i class="fa-solid fa-hourglass-empty" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px; opacity:0.5;"></i>
                <h2 style="font-family:'Rajdhani',sans-serif; color: #fff; margin-bottom: 10px;">Check back tomorrow!</h2>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto;">HQ Admin has not scheduled today's 5-question challenge set yet. Your streak is safe!</p>
            </div>
        <?php else: ?>
            
            <?php if ($already_submitted): ?>
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px;">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; color:#fff; font-size:1.4rem;">Today's Results & Explanations</h3>
                    <span style="background:rgba(16,185,129,0.15); color:#10b981; padding:4px 12px; border-radius:12px; font-weight:bold; font-size:0.85rem;"><i class="fa-solid fa-check-double"></i> Submitted Today</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:25px;">
                    <?php foreach($challenges as $index => $c): 
                        $sub = $submissions[$c['id']] ?? ['selected_options' => '', 'is_correct' => 0, 'points_earned' => 0];
                        $selected_opts = explode(',', $sub['selected_options']);
                        $correct_opts = explode(',', $c['correct_options']);
                        $is_multiple = count($correct_opts) > 1;
                        
                        $border_color = ($sub['points_earned'] == $c['points']) ? '#10b981' : (($sub['points_earned'] > 0) ? '#f59e0b' : '#ef4444');
                        $points_color = ($sub['points_earned'] == $c['points']) ? '#10b981' : (($sub['points_earned'] > 0) ? '#f59e0b' : '#ef4444');
                    ?>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; padding: 20px; border-left: 5px solid <?= $border_color ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <strong style="color: #cbd5e1;">Question <?= $index + 1 ?> of 5</strong>
                                <?php if ($is_multiple): ?>
                                    <span style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; width: fit-content; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-square-check"></i> Multiple Options Question
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <span style="font-size:0.8rem; color:#cbd5e1;">Points earned:</span>
                                <strong style="font-family:monospace; color:<?= $points_color ?>; font-size:0.95rem;">
                                    <?= $sub['points_earned'] ?> / <?= $c['points'] ?> XP
                                </strong>
                            </div>
                        </div>

                        <p style="color:#fff; font-size: 1.1rem; line-height: 1.5; font-weight: 500; margin: 0 0 15px 0;">
                            <?= esc($c['question']) ?>
                        </p>

                        
                        <div style="display:grid; grid-template-columns: 1fr; gap:10px; margin-bottom:15px;">
                            <?php 
                            $opts = [
                                'A' => $c['option_a'],
                                'B' => $c['option_b'],
                                'C' => $c['option_c'],
                                'D' => $c['option_d']
                            ];
                            foreach($opts as $k => $val):
                                $b_color = 'rgba(255,255,255,0.04)';
                                $bg_color = 'rgba(0,0,0,0.1)';
                                $badge = '';

                                $was_selected = in_array($k, $selected_opts);
                                $is_correct_opt = in_array($k, $correct_opts);

                                if ($was_selected) {
                                    if ($is_correct_opt) {
                                        $b_color = '#10b981';
                                        $bg_color = 'rgba(16,185,129,0.08)';
                                        $badge = ' <span style="background:#10b981; color:#000; padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold; margin-left:10px;"><i class="fa-solid fa-check"></i> Selected Correctly</span>';
                                    } else {
                                        $b_color = '#ef4444';
                                        $bg_color = 'rgba(239,68,68,0.08)';
                                        $badge = ' <span style="background:#ef4444; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold; margin-left:10px;"><i class="fa-solid fa-xmark"></i> Selected Incorrectly</span>';
                                    }
                                } elseif ($is_correct_opt) {
                                    $b_color = '#10b981';
                                    $bg_color = 'rgba(16,185,129,0.03)';
                                    $badge = ' <span style="color:#10b981; font-size:0.8rem; margin-left:10px;"><i class="fa-solid fa-check"></i> Correct Choice</span>';
                                }
                            ?>
                                <div style="border: 1px solid <?= $b_color ?>; background: <?= $bg_color ?>; padding: 12px 15px; border-radius: 6px; color: #fff; display: flex; align-items: center; font-size: 0.95rem;">
                                    <strong style="color: var(--accent-blue); margin-right: 12px;"><?= $k ?>.</strong>
                                    <span><?= esc($val) ?></span>
                                    <?= $badge ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        
                        <div style="background: rgba(0, 243, 255, 0.03); border: 1px solid rgba(0, 243, 255, 0.08); border-radius: 6px; padding: 15px;">
                            <h5 style="margin: 0 0 5px 0; color: var(--accent-blue); font-family: 'Rajdhani', sans-serif; font-size:0.9rem; letter-spacing:0.5px;">
                                <i class="fa-solid fa-circle-info"></i> EXPLANATION
                            </h5>
                            <p style="margin: 0; color: #cbd5e1; font-size: 0.88rem; line-height: 1.5;">
                                <?= !empty($c['explanation']) ? esc($c['explanation']) : "No explanation provided for this challenge." ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif (!$has_started): ?>
                
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 4rem; margin-bottom: 20px; animation: pulseGlow 2s infinite alternate;">⏱️</div>
                    <h2 style="font-family:'Rajdhani',sans-serif; color: #fff; margin-bottom: 10px; font-weight:800; font-size:1.6rem; letter-spacing:0.5px;">READY FOR TODAY'S CHALLENGE?</h2>
                    <p style="color: #cbd5e1; max-width: 550px; margin: 0 auto 30px auto; font-size: 0.95rem; line-height: 1.6;">
                        Today's set contains exactly <strong>5 questions</strong>.<br>
                        You have a custom time limit of <strong><?= $time_limit_mins ?> minutes</strong> to complete the entire set.<br>
                        Once started, the countdown timer will begin and cannot be paused.
                    </p>
                    <form method="POST">
                        <button type="submit" name="start_quiz" class="btn-primary" style="background: linear-gradient(135deg, #00f3ff, #a855f7); border: none; border-radius: 8px; font-weight: bold; cursor: pointer; padding: 15px 40px; font-size:1.1rem; box-shadow: 0 0 25px rgba(0,243,255,0.4); display:inline-flex; align-items:center; gap:8px;">
                            Start Daily Quiz <i class="fa-solid fa-circle-play"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                
                <form method="POST" id="quizForm">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px; position: sticky; top: 0; background: #0c152b; z-index: 100; padding: 10px; border-radius:6px; border:1px solid rgba(255,255,255,0.04);">
                        <h3 style="margin:0; font-family:'Rajdhani',sans-serif; color:#fff; font-size:1.2rem; letter-spacing:0.5px;"><i class="fa-solid fa-file-pen" style="color:var(--accent-blue);"></i> Daily Challenge Set</h3>
                        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 8px 18px; border-radius: 8px; font-weight: bold; font-family: monospace; font-size: 1.1rem; display:flex; align-items:center; gap:8px;">
                            <i class="fa-regular fa-clock" style="animation: pulseGlow 1s infinite alternate;"></i> TIME LEFT: <span id="countdownTimer">--:--</span>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:25px; margin-bottom:30px;">
                        <?php foreach($challenges as $index => $c): 
                            $correct_opts = explode(',', $c['correct_options']);
                            $is_multiple = count($correct_opts) > 1;
                        ?>
                        <div style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.04); border-radius: 10px; padding: 20px; border-left: 4px solid #a855f7;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <strong style="color: #cbd5e1;">Question <?= $index + 1 ?> of 5</strong>
                                    <?php if ($is_multiple): ?>
                                        <span style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: bold; width: fit-content; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-square-check"></i> Note: This is a multiple options question.
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span style="background:rgba(0,243,255,0.12); color:var(--accent-blue); padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold; font-family:monospace;">
                                    <?= $c['points'] ?> XP
                                </span>
                            </div>

                            <p style="color:#fff; font-size: 1.1rem; line-height: 1.5; font-weight: 500; margin: 0 0 15px 0;">
                                <?= esc($c['question']) ?>
                            </p>

                            
                            <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                                <?php 
                                $opts = [
                                    'A' => $c['option_a'],
                                    'B' => $c['option_b'],
                                    'C' => $c['option_c'],
                                    'D' => $c['option_d']
                                ];
                                foreach($opts as $k => $val):
                                ?>
                                    <label style="border: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); padding: 12px 15px; border-radius: 6px; color: #fff; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.2s;" class="opt-label" onmouseover="this.style.borderColor='rgba(0,243,255,0.25)'; this.style.background='rgba(0,243,255,0.01)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'; this.style.background='rgba(0,0,0,0.2)';">
                                        <input type="checkbox" name="answers[<?= $c['id'] ?>][]" value="<?= $k ?>" style="accent-color: var(--accent-blue); width: 16px; height: 16px;">
                                        <strong style="color: var(--accent-blue);"><?= $k ?>.</strong>
                                        <span><?= esc($val) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top:20px; display:flex; justify-content:flex-end;">
                        <input type="hidden" name="submit_quiz" value="1">
                        <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #00f3ff, #a855f7); border: none; border-radius: 8px; font-weight: bold; cursor: pointer; padding: 12px 35px; font-size:1rem; box-shadow: 0 0 20px rgba(0,243,255,0.3);">
                            Submit Daily Challenge Set <i class="fa-solid fa-paper-plane" style="margin-left: 8px;"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</div>

<style>
@keyframes flamePulse {
    from { transform: scale(1); filter: drop-shadow(0 0 5px rgba(239, 68, 68, 0.4)); }
    to { transform: scale(1.15); filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.8)); }
}
@keyframes pulseGlow {
    from { opacity: 0.6; filter: drop-shadow(0 0 5px rgba(0, 243, 255, 0.4)); }
    to { opacity: 1; filter: drop-shadow(0 0 15px rgba(0, 243, 255, 0.8)); }
}
</style>

<script>
<?php if ($has_started && !$already_submitted): ?>
(function() {
    let timeLeft = <?= $time_remaining ?>;
    const timerDisplay = document.getElementById('countdownTimer');
    const form = document.getElementById('quizForm');

    function updateTimer() {
        if (timeLeft <= 0) {
            clearInterval(interval);
            timerDisplay.innerText = "EXPIRED";
            // Auto submit
            form.submit();
            return;
        }

        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;

        timerDisplay.innerText = 
            (minutes < 10 ? '0' : '') + minutes + ':' + 
            (seconds < 10 ? '0' : '') + seconds;

        timeLeft--;
    }

    updateTimer();
    const interval = setInterval(updateTimer, 1000);
})();
<?php endif; ?>
</script>

        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
