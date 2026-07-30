<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Session missing.']);
    exit;
}
if (!in_array($_SESSION['user_role'], ['Intern', 'Admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid role.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intern_id = $_SESSION['user_id'];
    $module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
    $today = date('Y-m-d');
    
    try {
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS intern_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id VARCHAR(100) NOT NULL,
            date DATE NOT NULL,
            status VARCHAR(50) DEFAULT 'Present',
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_attendance (intern_id, date)
        )");

        $stmt = $pdo->prepare("SELECT title, week, day FROM internship_modules WHERE id = ?");
        $stmt->execute([$module_id]);
        $mod = $stmt->fetch();
        
        if (!$mod) {
            echo json_encode(['success' => false, 'error' => 'Invalid module']);
            exit;
        }

        
        $mod_week = (int)$mod['week'];
        $mod_day  = (int)$mod['day'];
        $calendar_day = (($mod_week - 1) * 7) + $mod_day;
        
        
        $attendance_date = date('Y-m-') . str_pad($calendar_day, 2, '0', STR_PAD_LEFT);

        
        $is_admin = ($_SESSION['user_role'] === 'Admin');
        $current_day_of_month = (int)date('j');
        if (!$is_admin && $calendar_day != $current_day_of_month) {
            echo json_encode(['success' => false, 'error' => 'You can only mark attendance for today\'s module (Day ' . $current_day_of_month . ').']);
            exit;
        }

        
        $check_status = $pdo->prepare("SELECT internship_status FROM interns WHERE id = ?");
        $check_status->execute([$intern_id]);
        if ($check_status->fetchColumn() === 'Completed') {
            echo json_encode(['success' => false, 'error' => 'Internship already completed. You cannot repeat this routine.']);
            exit;
        }
        
        $remarks = "Completed Module: " . $mod['title'];
        
        
        $check = $pdo->prepare("SELECT remarks FROM intern_attendance WHERE intern_id = ? AND date = ?");
        $check->execute([$intern_id, $attendance_date]);
        $existing = $check->fetch();
        
        if ($existing) {
            
            $new_remarks = $existing['remarks'] ? $existing['remarks'] . ', ' . $remarks : $remarks;
            $upd = $pdo->prepare("UPDATE intern_attendance SET status = 'Present', remarks = ? WHERE intern_id = ? AND date = ?");
            $upd->execute([$new_remarks, $intern_id, $attendance_date]);
        } else {
            
            $ins = $pdo->prepare("INSERT INTO intern_attendance (intern_id, date, status, remarks) VALUES (?, ?, 'Present', ?)");
            $ins->execute([$intern_id, $attendance_date, $remarks]);
        }

        
        $cal_day = (int)date('j');
        if ($cal_day == 30) {
            $pdo->prepare("UPDATE interns SET internship_status = 'Completed' WHERE id = ?")->execute([$intern_id]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid method']);
?>
