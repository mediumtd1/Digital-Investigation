<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$type  = $_GET['type']  ?? 'intern_attendance';
$month = $_GET['month'] ?? date('Y-m');

$report_title = 'Report';
$content = '';

switch ($type) {

    
    case 'intern_attendance':
        $report_title = 'Intern Attendance Report — ' . date('F Y', strtotime($month . '-01'));
        $stmt = $pdo->prepare("
            SELECT i.name, i.intern_id, i.college,
                   SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) as present_count,
                   COUNT(a.id) as total_days,
                   ROUND(SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id),0) * 100, 1) as pct
            FROM interns i
            LEFT JOIN intern_attendance a ON i.id = a.intern_id AND DATE_FORMAT(a.date,'%Y-%m') = ?
            GROUP BY i.id
            ORDER BY present_count DESC
        ");
        $stmt->execute([$month]);
        $rows = $stmt->fetchAll();

        $content .= '<table><thead><tr><th>#</th><th>Name</th><th>ID</th><th>College</th><th>Present</th><th>Total</th><th>%</th></tr></thead><tbody>';
        foreach ($rows as $i => $r) {
            $pct = $r['pct'] ?? 0;
            $color = $pct >= 80 ? '#166534' : ($pct >= 50 ? '#854d0e' : '#991b1b');
            $bg    = $pct >= 80 ? '#dcfce7' : ($pct >= 50 ? '#fef9c3' : '#fee2e2');
            $content .= "<tr>
                <td>" . ($i+1) . "</td>
                <td><strong>{$r['name']}</strong></td>
                <td class='mono'>{$r['intern_id']}</td>
                <td>{$r['college']}</td>
                <td><strong>{$r['present_count']}</strong></td>
                <td>{$r['total_days']}</td>
                <td><span style='background:{$bg};color:{$color};padding:2px 8px;border-radius:4px;font-weight:bold;'>{$pct}%</span></td>
            </tr>";
        }
        $content .= '</tbody></table>';
        break;

    
    case 'intern_performance':
        $report_title = 'Intern Performance Report — ' . date('d M Y');
        
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS ctf_submissions (id INT AUTO_INCREMENT PRIMARY KEY, intern_id INT NOT NULL, flag_submitted VARCHAR(255) DEFAULT NULL, is_correct TINYINT(1) DEFAULT 0, partial_steps VARCHAR(255) DEFAULT NULL, partial_notes TEXT DEFAULT NULL, points_awarded INT DEFAULT 0, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch(PDOException $e) {}
        
        $stmt = $pdo->query("
            SELECT i.name, i.intern_id, i.college,
                   (SELECT COUNT(*) * 10 FROM module_tracking WHERE intern_id = i.id) as mod_pts,
                   (SELECT COALESCE(SUM(score), 0) FROM task_assignments WHERE intern_id = i.id AND status='Completed' AND review_status='Approved') as task_pts,
                   (SELECT COUNT(*) * 5 FROM intern_attendance WHERE intern_id = i.id AND status='Present') as att_pts,
                   (SELECT COALESCE(SUM(points_awarded), 0) FROM ctf_submissions WHERE intern_id = i.id) as ctf_pts,
                   (SELECT COUNT(*) FROM module_tracking mt WHERE mt.intern_id=i.id) as modules_done,
                   (SELECT COUNT(*) FROM task_assignments ta WHERE ta.intern_id=i.id AND ta.status='Completed') as tasks_done
            FROM interns i
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as &$r) {
            $r['ctf_pts'] = (int)($r['ctf_pts'] ?? 0);
            $r['total'] = $r['mod_pts'] + $r['task_pts'] + $r['att_pts'] + $r['ctf_pts'];
        }
        unset($r);
        usort($rows, function($a, $b) {
            return $b['total'] - $a['total'];
        });

        $content .= '<table><thead><tr><th>Rank</th><th>Name</th><th>ID</th><th>Modules</th><th>Tasks</th><th>Mod Pts</th><th>Task Pts</th><th>Att Pts</th><th>CTF Pts</th><th>Total</th></tr></thead><tbody>';
        foreach ($rows as $i => $r) {
            $medal = $i == 0 ? '🥇 ' : ($i == 1 ? '🥈 ' : ($i == 2 ? '🥉 ' : ($i+1).'. '));
            $ctf_badge = $r['ctf_pts'] > 0 ? "<span style='background:#fef3c7;color:#b45309;padding:2px 6px;border-radius:3px;font-weight:bold;font-size:0.8em;'>CTF +{$r['ctf_pts']}</span>" : '<span style="color:#ccc;">—</span>';
            $content .= "<tr>
                <td><strong>{$medal}</strong></td>
                <td><strong>{$r['name']}</strong></td>
                <td class='mono'>{$r['intern_id']}</td>
                <td>{$r['modules_done']}</td>
                <td>{$r['tasks_done']}</td>
                <td>{$r['mod_pts']}</td>
                <td>{$r['task_pts']}</td>
                <td>{$r['att_pts']}</td>
                <td>{$ctf_badge}</td>
                <td><strong style='color:#1d4ed8;'>{$r['total']}</strong></td>
            </tr>";
        }
        $content .= '</tbody></table>';
        break;

    
    case 'officer_cases':
        $report_title = 'Officer Case Summary — ' . date('d M Y');
        $stmt = $pdo->query("
            SELECT o.name, o.officer_id, o.designation, o.district,
                   COUNT(f.id) as total_firs,
                   SUM(CASE WHEN f.status='Closed' THEN 1 ELSE 0 END) as closed,
                   SUM(CASE WHEN f.status='Under Investigation' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN f.status='Registered' THEN 1 ELSE 0 END) as pending
            FROM officers o
            LEFT JOIN firs f ON o.id = f.officer_id
            WHERE o.is_verified = 1
            GROUP BY o.id
            ORDER BY total_firs DESC
        ");
        $rows = $stmt ? $stmt->fetchAll() : [];

        $content .= '<table><thead><tr><th>#</th><th>Officer</th><th>ID</th><th>Designation</th><th>District</th><th>Total FIRs</th><th>Closed</th><th>Active</th><th>Pending</th></tr></thead><tbody>';
        foreach ($rows as $i => $r) {
            $content .= "<tr>
                <td>" . ($i+1) . "</td>
                <td><strong>{$r['name']}</strong></td>
                <td class='mono'>{$r['officer_id']}</td>
                <td>{$r['designation']}</td>
                <td>{$r['district']}</td>
                <td><strong>{$r['total_firs']}</strong></td>
                <td style='color:#166534;'>{$r['closed']}</td>
                <td style='color:#854d0e;'>{$r['active']}</td>
                <td style='color:#991b1b;'>{$r['pending']}</td>
            </tr>";
        }
        $content .= '</tbody></table>';
        break;

    
    case 'fir_stats':
        $report_title = 'FIR Global Statistics — ' . date('d M Y');
        $total = $pdo->query("SELECT COUNT(*) FROM firs")->fetchColumn();
        $closed= $pdo->query("SELECT COUNT(*) FROM firs WHERE status='Closed'")->fetchColumn();
        $active= $pdo->query("SELECT COUNT(*) FROM firs WHERE status='Under Investigation'")->fetchColumn();
        $pending=$pdo->query("SELECT COUNT(*) FROM firs WHERE status='Registered'")->fetchColumn();

        $content .= "<div class='summary-boxes'>
            <div class='sbox blue'><div class='sval'>{$total}</div><div class='slabel'>Total FIRs</div></div>
            <div class='sbox green'><div class='sval'>{$closed}</div><div class='slabel'>Closed</div></div>
            <div class='sbox amber'><div class='sval'>{$active}</div><div class='slabel'>Active</div></div>
            <div class='sbox red'><div class='sval'>{$pending}</div><div class='slabel'>Pending</div></div>
        </div>";

        
        $crime_rows = $pdo->query("SELECT crime_type, COUNT(*) as cnt FROM firs GROUP BY crime_type ORDER BY cnt DESC")->fetchAll();
        $content .= '<h3>Crime Type Distribution</h3>';
        $content .= '<table><thead><tr><th>#</th><th>Crime Type</th><th>Count</th><th>% of Total</th></tr></thead><tbody>';
        foreach ($crime_rows as $i => $r) {
            $pct = $total > 0 ? round(($r['cnt']/$total)*100,1) : 0;
            $content .= "<tr><td>".($i+1)."</td><td>{$r['crime_type']}</td><td><strong>{$r['cnt']}</strong></td><td>{$pct}%</td></tr>";
        }
        $content .= '</tbody></table>';

        
        $monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as m, COUNT(*) as cnt FROM firs GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 12")->fetchAll();
        $content .= '<h3>Monthly Filing Trend (Last 12 Months)</h3>';
        $content .= '<table><thead><tr><th>Month</th><th>FIRs Filed</th></tr></thead><tbody>';
        foreach ($monthly as $r) {
            $content .= "<tr><td>{$r['m']}</td><td><strong>{$r['cnt']}</strong></td></tr>";
        }
        $content .= '</tbody></table>';
        break;

    default:
        $report_title = 'Unknown Report';
        $content = '<p>Invalid report type.</p>';
}

$generated_by   = $_SESSION['user_name'] ?? 'HQ Admin';
$generated_at   = date('d F Y, h:i A');
$site_name      = defined('SITE_NAME') ? SITE_NAME : 'Digital Investigation Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= esc($report_title) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Arial', sans-serif; font-size: 12px; color: #1e293b; background: #fff; }

.report-page { max-width: 1050px; margin: 0 auto; padding: 30px; }

/* Header */
.report-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid #1e3a5f; }
.report-logo-area h1 { font-size:20px; color:#1e3a5f; font-weight:900; margin-bottom:4px; }
.report-logo-area p  { font-size:10px; color:#64748b; }
.report-meta { text-align:right; font-size:10px; color:#64748b; line-height:1.8; }

/* Title Block */
.report-title-block { background:#1e3a5f; color:#fff; padding:14px 20px; border-radius:6px; margin-bottom:25px; }
.report-title-block h2 { font-size:16px; font-weight:900; margin-bottom:2px; }
.report-title-block p  { font-size:10px; color:rgba(255,255,255,0.6); }

/* Summary Boxes */
.summary-boxes { display:flex; gap:15px; margin-bottom:25px; flex-wrap:wrap; }
.sbox { flex:1; min-width:120px; padding:16px; border-radius:8px; text-align:center; border:1px solid; }
.sval  { font-size:28px; font-weight:900; margin-bottom:4px; }
.slabel { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:1px; }
.sbox.blue  { background:#eff6ff; border-color:#bfdbfe; } .sbox.blue  .sval { color:#1d4ed8; }
.sbox.green { background:#f0fdf4; border-color:#bbf7d0; } .sbox.green .sval { color:#166534; }
.sbox.amber { background:#fefce8; border-color:#fde68a; } .sbox.amber .sval { color:#854d0e; }
.sbox.red   { background:#fef2f2; border-color:#fecaca; } .sbox.red   .sval { color:#991b1b; }

/* Table */
table { width:100%; border-collapse:collapse; margin-bottom:25px; font-size:11px; }
th { background:#1e3a5f; color:#fff; padding:10px 12px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:1px; }
td { padding:9px 12px; border-bottom:1px solid #e2e8f0; }
tr:nth-child(even) td { background:#f8fafc; }
tr:hover td { background:#eff6ff; }
.mono { font-family:'Courier New',monospace; font-size:10px; color:#475569; }
h3 { font-size:13px; color:#1e3a5f; margin:20px 0 12px; padding-bottom:6px; border-bottom:1px solid #e2e8f0; }

/* Footer */
.report-footer { margin-top:40px; padding-top:15px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; font-size:9px; color:#94a3b8; }

@media print {
    body { font-size: 11px; }
    .report-page { padding: 15px; max-width:100%; }
    .no-print { display:none; }
    @page { margin: 1.5cm; }
}
</style>
</head>
<body>
<div class="report-page">

    <div class="report-header">
        <div class="report-logo-area">
            <h1><?= esc($site_name) ?></h1>
            <p>Headquarters — Analytics &amp; Intelligence Division</p>
        </div>
        <div class="report-meta">
            Generated By: <strong><?= esc($generated_by) ?></strong><br>
            Generated At: <?= esc($generated_at) ?><br>
            Classification: <strong>OFFICIAL USE ONLY</strong>
        </div>
    </div>

    <div class="report-title-block">
        <h2><?= esc($report_title) ?></h2>
        <p>Automated report generated by the Cyber Portal Analytics Engine</p>
    </div>

    <?= $content ?>

    <div class="report-footer">
        <span><?= esc($site_name) ?> — Confidential Document</span>
        <span>Generated: <?= esc($generated_at) ?></span>
        <span>Authorized by: <?= esc($generated_by) ?></span>
    </div>

</div>

<div class="no-print" style="position:fixed;bottom:20px;right:20px;display:flex;gap:10px;">
    <button onclick="window.print()" style="background:#1d4ed8;color:#fff;border:none;padding:10px 22px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;">
        🖨️ Print / Save PDF
    </button>
    <button onclick="window.close()" style="background:#94a3b8;color:#fff;border:none;padding:10px 18px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;">
        ✕ Close
    </button>
</div>

<script>
// Auto-trigger print dialog after a short delay
window.addEventListener('load', function() {
    setTimeout(() => window.print(), 800);
});
</script>
</body>
</html>
