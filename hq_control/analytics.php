<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'System Analytics & Reporting | ' . SITE_NAME;

$total_interns = $pdo->query("SELECT COUNT(*) FROM interns")->fetchColumn();
$total_officers = $pdo->query("SELECT COUNT(*) FROM officers")->fetchColumn();
$open_firs = $pdo->query("SELECT COUNT(*) FROM firs WHERE status IN ('Registered', 'Under Investigation')")->fetchColumn();
$cases_solved = $pdo->query("SELECT COUNT(*) FROM firs WHERE status = 'Closed'")->fetchColumn();

$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$stmt = $pdo->prepare("
    SELECT date, 
           COUNT(id) as total_entries,
           SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present_count
    FROM intern_attendance 
    WHERE date >= ?
    GROUP BY date ORDER BY date ASC
");
$stmt->execute([$thirty_days_ago]);
$att_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart1_labels = [];
$chart1_data = [];
foreach($att_data as $row) {
    $chart1_labels[] = date('M d', strtotime($row['date']));
    $pct = $row['total_entries'] > 0 ? round(($row['present_count'] / $row['total_entries']) * 100) : 0;
    $chart1_data[] = $pct;
}

$six_months_ago = date('Y-m-d', strtotime('-6 months'));
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_str, COUNT(*) as count 
    FROM firs 
    WHERE created_at >= ?
    GROUP BY month_str ORDER BY month_str ASC
");
$stmt->execute([$six_months_ago]);
$fir_trend = $stmt->fetchAll();

$chart2_labels = [];
$chart2_data = [];
foreach($fir_trend as $row) {
    $chart2_labels[] = date('F Y', strtotime($row['month_str'] . '-01'));
    $chart2_data[] = (int)$row['count'];
}

$crime_dist = $pdo->query("SELECT crime_type, COUNT(*) as count FROM firs GROUP BY crime_type ORDER BY count DESC")->fetchAll();
$chart3_labels = [];
$chart3_data = [];
foreach($crime_dist as $row) {
    $chart3_labels[] = $row['crime_type'];
    $chart3_data[] = (int)$row['count'];
}

$stmt = $pdo->query("
    SELECT i.name, AVG(ps.score) as avg_score 
    FROM performance_scores ps
    JOIN interns i ON ps.intern_id = i.id
    GROUP BY i.id 
    ORDER BY avg_score DESC LIMIT 5
");
$top_interns = $stmt->fetchAll();
$chart4_labels = [];
$chart4_data = [];
foreach($top_interns as $row) {
    $chart4_labels[] = $row['name'];
    $chart4_data[] = isset($row['avg_score']) ? round($row['avg_score'], 1) : 0;
}

$status_dist = $pdo->query("SELECT status, COUNT(*) as count FROM firs GROUP BY status")->fetchAll();
$chart5_labels = [];
$chart5_data = [];
foreach($status_dist as $row) {
    $chart5_labels[] = $row['status'];
    $chart5_data[] = (int)$row['count'];
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Cyber Analytical Frame (CAF)</h2>
            </div>
            <div class="topbar-right">
                <div style="background: rgba(255,255,255,0.05); padding: 5px; border-radius: 6px; display: flex; gap: 5px;">
                    <button onclick="setTheme('dark')" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fa-solid fa-moon"></i></button>
                    <button onclick="setTheme('light')" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="fa-solid fa-sun"></i></button>
                    <button onclick="setTheme('system')" style="background:none; border:none; color:var(--text-primary); cursor:pointer;"><i class="fa-solid fa-desktop"></i></button>
                </div>
            </div>
        </header>

        <div class="dashboard-content">

            
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
                <div class="glass-panel stat-card" style="border-left: 4px solid #3b82f6;">
                    <div class="stat-value" style="font-size: 2.2rem;"><?= $total_interns ?></div>
                    <div class="stat-label">Total Intern Matrix</div>
                </div>
                <div class="glass-panel stat-card" style="border-left: 4px solid #eab308;">
                    <div class="stat-value" style="font-size: 2.2rem;"><?= $total_officers ?></div>
                    <div class="stat-label">Active Officer Cadre</div>
                </div>
                <div class="glass-panel stat-card" style="border-left: 4px solid #ef4444;">
                    <div class="stat-value" style="font-size: 2.2rem;"><?= $open_firs ?></div>
                    <div class="stat-label">Open / Pending Dockets</div>
                </div>
                <div class="glass-panel stat-card" style="border-left: 4px solid #10b981;">
                    <div class="stat-value" style="font-size: 2.2rem;"><?= $cases_solved ?></div>
                    <div class="stat-label">Total Cases Concluded</div>
                </div>
            </div>

            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                
                <div class="glass-panel chart-container">
                    <h3 style="margin-bottom: 20px; color: #94a3b8; font-size: 1rem; text-transform: uppercase;">Intern Attendance Efficiency (Last 30 Days)</h3>
                    <canvas id="chartAttendance" height="100"></canvas>
                </div>
                
                <div class="glass-panel chart-container">
                    <h3 style="margin-bottom: 20px; color: #94a3b8; font-size: 1rem; text-transform: uppercase;">Docket Vector Distribution</h3>
                    <canvas id="chartCrimeType" height="200"></canvas>
                </div>

            </div>

            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                
                <div class="glass-panel chart-container">
                    <h3 style="margin-bottom: 20px; color: #94a3b8; font-size: 1rem; text-transform: uppercase;">FIR Aggregation Trend (6 Mos)</h3>
                    <canvas id="chartFIRTrend" height="150"></canvas>
                </div>
                
                <div class="glass-panel chart-container">
                    <h3 style="margin-bottom: 20px; color: #94a3b8; font-size: 1rem; text-transform: uppercase;">Top Performers Matrix</h3>
                    <canvas id="chartTopInterns" height="150"></canvas>
                </div>

                <div class="glass-panel chart-container">
                    <h3 style="margin-bottom: 20px; color: #94a3b8; font-size: 1rem; text-transform: uppercase;">Total Docket Status Core</h3>
                    <canvas id="chartCaseStatus" height="150"></canvas>
                </div>

            </div>

            
            <div class="glass-panel" style="border-top: 4px solid #8b5cf6;">
                <h3 style="margin-bottom: 20px;"><i class="fa-solid fa-file-pdf"></i> Official PDF Report Generation Sequence</h3>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    
                    
                    <form action="export_pdf.php" target="_blank" method="GET" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; flex: 1; min-width: 200px;">
                        <input type="hidden" name="type" value="intern_attendance">
                        <strong style="display:block; margin-bottom: 10px; color: #60a5fa;">Intern Attendance</strong>
                        <input type="month" name="month" class="form-control" value="<?= date('Y-m') ?>" style="margin-bottom: 10px;">
                        <button type="submit" class="btn-primary" style="width: 100%;"><i class="fa-solid fa-download"></i> Extract</button>
                    </form>

                    <form action="export_pdf.php" target="_blank" method="GET" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; flex: 1; min-width: 200px;">
                        <input type="hidden" name="type" value="intern_performance">
                        <strong style="display:block; margin-bottom: 10px; color: #a78bfa;">Intern Performance</strong>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">Exports aggregate scoring & assignments.</div>
                        <button type="submit" class="btn-primary" style="width: 100%; background: #8b5cf6;"><i class="fa-solid fa-download"></i> Extract</button>
                    </form>

                    <form action="export_pdf.php" target="_blank" method="GET" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; flex: 1; min-width: 200px;">
                        <input type="hidden" name="type" value="officer_cases">
                        <strong style="display:block; margin-bottom: 10px; color: #fb923c;">Officer Case Summary</strong>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">Exports assignments vs completion ratio mapping.</div>
                        <button type="submit" class="btn-primary" style="width: 100%; background: #f97316;"><i class="fa-solid fa-download"></i> Extract</button>
                    </form>

                    <form action="export_pdf.php" target="_blank" method="GET" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; flex: 1; min-width: 200px;">
                        <input type="hidden" name="type" value="fir_stats">
                        <strong style="display:block; margin-bottom: 10px; color: #34d399;">FIR Global Statistics</strong>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">Total topological breakdown of cyber trends.</div>
                        <button type="submit" class="btn-primary" style="width: 100%; background: #10b981;"><i class="fa-solid fa-download"></i> Extract</button>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
// Theme Controls
function setTheme(mode) {
    if (mode === 'system') {
        const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
        document.body.className = isDark ? 'dark-theme' : 'light-theme';
    } else {
        document.body.className = mode + '-theme';
    }
    localStorage.setItem('themePref', mode);
    // Reload charts to update colors natively if needed
    Chart.defaults.color = (document.body.className === 'light-theme') ? '#1e293b' : '#94a3b8';
}
setTheme(localStorage.getItem('themePref') || 'system');
Chart.defaults.color = '#94a3b8'; // Default Dark Font Render

// Shared Palette
const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'];

// 1. Intern Attendance Bar
new Chart(document.getElementById('chartAttendance'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart1_labels) ?>,
        datasets: [{
            label: 'Attendance %',
            data: <?= json_encode($chart1_data) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.4)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
});

// 2. Crime Type Pie
new Chart(document.getElementById('chartCrimeType'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($chart3_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart3_data) ?>,
            backgroundColor: colors,
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// 3. FIR Trend Line
new Chart(document.getElementById('chartFIRTrend'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart2_labels) ?>,
        datasets: [{
            label: 'FIRs Filed',
            data: <?= json_encode($chart2_data) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// 4. Top Interns Bar
new Chart(document.getElementById('chartTopInterns'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart4_labels) ?>,
        datasets: [{
            label: 'Avg Score',
            data: <?= json_encode($chart4_data) ?>,
            backgroundColor: 'rgba(139, 92, 246, 0.5)',
            borderColor: '#8b5cf6',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});

// 5. Case Status Doughnut
new Chart(document.getElementById('chartCaseStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart5_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart5_data) ?>,
            backgroundColor: ['#64748b', '#f59e0b', '#f97316', '#10b981'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
