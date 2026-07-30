<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer');
$portal = $is_admin ? 'admin' : 'officer';
$page_title = 'Instagram Report Reader | ' . SITE_NAME;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.tool-topbar { background:rgba(0,0,0,0.85); backdrop-filter:blur(15px); border-bottom:1px solid var(--border-color); padding:12px 25px; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.tool-body { flex:1; overflow-y:auto; padding:25px; background:var(--bg-primary); }
.upload-zone { border:2px dashed rgba(225,48,108,0.4); border-radius:8px; padding:50px 30px; text-align:center; cursor:pointer; transition:all 0.3s; background:rgba(225,48,108,0.03); }
.upload-zone:hover, .upload-zone.drag { border-color:#e1306c; background:rgba(225,48,108,0.08); }
.insta-gradient { background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.section-card { background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:8px; margin-bottom:15px; overflow:hidden; }
.section-header { padding:14px 20px; background:rgba(225,48,108,0.08); border-bottom:1px solid rgba(225,48,108,0.2); display:flex; align-items:center; justify-content:space-between; cursor:pointer; }
.section-header h4 { margin:0; color:#e1306c; font-family:'Rajdhani',sans-serif; font-size:1rem; }
.section-body { padding:20px; display:none; }
.section-body.open { display:block; }
.data-tag { display:inline-block; background:rgba(225,48,108,0.1); border:1px solid rgba(225,48,108,0.3); color:#f06292; padding:4px 10px; border-radius:20px; font-size:0.78rem; margin:3px; }
.data-row { padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.04); display:flex; gap:15px; font-size:0.88rem; }
.data-row:last-child { border-bottom:none; }
.data-key { color:#94a3b8; min-width:160px; }
.data-val { color:#e2e8f0; font-family:monospace; word-break:break-all; }
.stat-pill { background:rgba(225,48,108,0.1); border:1px solid rgba(225,48,108,0.3); color:#e1306c; padding:8px 16px; border-radius:20px; font-size:0.85rem; font-weight:bold; }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content" style="padding:0; overflow:hidden; height:100vh; display:flex; flex-direction:column;">
        <header class="tool-topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <img src="../assets/images/logo.png" style="height:38px; width:38px; object-fit:contain;">
                <div>
                    <h2 style="margin:0; font-size:1.2rem; font-family:'Rajdhani',sans-serif; letter-spacing:1px;"><i class="fa-brands fa-instagram" style="color:#e1306c;"></i> <span class="insta-gradient">Instagram Report Reader</span></h2>
                    <small style="color:#94a3b8; font-size:0.7rem;">Upload Instagram Data Export (JSON) for forensic analysis</small>
                </div>
            </div>
            <button id="exportBtn" onclick="exportCSV()" style="display:none; background:rgba(225,48,108,0.1); border:1px solid #e1306c; color:#e1306c; padding:8px 18px; border-radius:4px; cursor:pointer; font-family:'Rajdhani',sans-serif; font-weight:bold;"><i class="fa-solid fa-file-export"></i> EXPORT CSV</button>
        </header>
        <div class="tool-body">
            <div id="uploadSection">
                <div class="upload-zone" id="dropZone" onclick="document.getElementById('fileInput').click()" ondragover="event.preventDefault(); this.classList.add('drag')" ondragleave="this.classList.remove('drag')" ondrop="handleDrop(event)">
                    <i class="fa-brands fa-instagram" style="font-size:3rem; color:#e1306c; margin-bottom:15px; display:block;"></i>
                    <h3 style="color:#e1306c; margin:0 0 10px 0; font-family:'Rajdhani',sans-serif;">Upload Instagram Data Export</h3>
                    <p style="color:#94a3b8; margin:0;">Go to Instagram → Settings → Security → Download Data<br>Upload any JSON file from the downloaded archive</p>
                    <input type="file" id="fileInput" accept=".json" style="display:none" onchange="readFile(this.files[0])">
                </div>
                <div style="margin-top:20px; background:rgba(225,48,108,0.05); border:1px solid rgba(225,48,108,0.2); border-radius:8px; padding:20px;">
                    <h4 style="color:#e1306c; margin:0 0 12px 0; font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-circle-info"></i> Supported Instagram Data Files</h4>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php
                        $files = ['account_information.json','personal_information.json','followers.json','following.json','messages/inbox/*.json','liked_posts.json','comments.json','profile_changes.json','login_activity.json','ads_information.json'];
                        foreach($files as $f) echo "<span class='data-tag'>$f</span>";
                        ?>
                    </div>
                </div>
            </div>

            <div id="analysisSection" style="display:none;">
                <div class="glass-panel" style="padding:20px; margin-bottom:20px;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
                        <div id="statsRow" style="display:flex; gap:12px; flex-wrap:wrap;"></div>
                        <button onclick="resetTool()" style="background:transparent; border:1px solid #475569; color:#94a3b8; padding:6px 15px; border-radius:4px; cursor:pointer; font-size:0.8rem;">← New File</button>
                    </div>
                </div>
                <div id="sectionsContainer"></div>
            </div>
        </div>
    </main>
</div>

<script>
let parsedData = {};

function handleDrop(e) { e.preventDefault(); document.getElementById('dropZone').classList.remove('drag'); readFile(e.dataTransfer.files[0]); }

function readFile(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        try { parseInstagram(JSON.parse(e.target.result), file.name); }
        catch(err) { alert('Invalid JSON file. Please upload a valid Instagram data export file.'); }
    };
    reader.readAsText(file, 'UTF-8');
}

function parseInstagram(data, fileName) {
    parsedData = data;
    document.getElementById('uploadSection').style.display = 'none';
    document.getElementById('analysisSection').style.display = 'block';
    document.getElementById('exportBtn').style.display = 'block';

    const container = document.getElementById('sectionsContainer');
    const statsRow = document.getElementById('statsRow');
    container.innerHTML = '';
    statsRow.innerHTML = `<span class="stat-pill">📄 ${fileName}</span><span class="stat-pill" style="color:#a855f7; border-color:#a855f7; background:rgba(168,85,247,0.1);">🔑 ${Object.keys(data).length} Sections</span>`;

    renderSection(container, 'Full Data Dump', data, '📋');

    if (Array.isArray(data)) { renderList(container, 'Records', data); return; }

    Object.entries(data).forEach(([key, val]) => {
        const label = key.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
        if (Array.isArray(val)) renderList(container, label, val);
        else if (typeof val === 'object' && val) renderSection(container, label, val, '📂');
        else renderKeyVal(container, label, { value: val });
    });
}

function renderSection(container, title, obj, icon='📂') {
    const div = document.createElement('div');
    div.className = 'section-card';
    const entries = Object.entries(obj);
    div.innerHTML = `
        <div class="section-header" onclick="this.nextElementSibling.classList.toggle('open')">
            <h4>${icon} ${title} <span style="color:#64748b; font-size:0.75rem; font-weight:normal;">(${entries.length} fields)</span></h4>
            <i class="fa-solid fa-chevron-down" style="color:#e1306c;"></i>
        </div>
        <div class="section-body">
            ${entries.map(([k,v]) => `<div class="data-row"><span class="data-key">${k.replace(/_/g,' ')}</span><span class="data-val">${typeof v === 'object' ? '<pre style="margin:0; font-size:0.8rem; color:#94a3b8; max-height:150px; overflow:auto;">' + JSON.stringify(v, null, 2) + '</pre>' : String(v)}</span></div>`).join('')}
        </div>`;
    container.appendChild(div);
}

function renderList(container, title, arr) {
    const div = document.createElement('div');
    div.className = 'section-card';
    const rows = arr.slice(0, 100).map(item => {
        if (typeof item === 'string') return `<div class="data-row"><span class="data-val">${item}</span></div>`;
        if (typeof item === 'object') return `<div class="data-row" style="flex-direction:column;">${Object.entries(item).map(([k,v]) => `<div style="display:flex; gap:10px;"><span class="data-key" style="font-size:0.8rem;">${k}</span><span class="data-val" style="font-size:0.8rem;">${String(v)}</span></div>`).join('')}</div>`;
        return `<div class="data-row">${item}</div>`;
    }).join('');
    div.innerHTML = `
        <div class="section-header" onclick="this.nextElementSibling.classList.toggle('open')">
            <h4>📋 ${title} <span style="color:#64748b; font-size:0.75rem; font-weight:normal;">(${arr.length} records${arr.length>100?' — showing first 100':''})</span></h4>
            <i class="fa-solid fa-chevron-down" style="color:#e1306c;"></i>
        </div>
        <div class="section-body">${rows}</div>`;
    container.appendChild(div);
}

function renderKeyVal(container, title, obj) {
    const div = document.createElement('div');
    div.className = 'section-card';
    div.innerHTML = `<div class="section-header"><h4>🔹 ${title}</h4></div><div class="section-body open"><div class="data-row"><span class="data-val">${JSON.stringify(obj.value)}</span></div></div>`;
    container.appendChild(div);
}

function resetTool() {
    parsedData = {};
    document.getElementById('uploadSection').style.display = 'block';
    document.getElementById('analysisSection').style.display = 'none';
    document.getElementById('exportBtn').style.display = 'none';
    document.getElementById('fileInput').value = '';
}

function exportCSV() {
    const rows = [['Key','Value']];
    const flatten = (obj, prefix='') => {
        if (Array.isArray(obj)) obj.forEach((v,i) => flatten(v, `${prefix}[${i}]`));
        else if (typeof obj === 'object' && obj) Object.entries(obj).forEach(([k,v]) => flatten(v, prefix ? `${prefix}.${k}` : k));
        else rows.push([prefix, '"' + String(obj).replace(/"/g,'""') + '"']);
    };
    flatten(parsedData);
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(rows.map(r => r.join(',')).join('\n'));
    a.download = 'instagram_report_' + Date.now() + '.csv';
    a.click();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>