<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer');
$portal = $is_admin ? 'admin' : 'officer';
$page_title = 'WhatsApp Report Reader | ' . SITE_NAME;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.tool-topbar { background:rgba(0,0,0,0.85); backdrop-filter:blur(15px); border-bottom:1px solid var(--border-color); padding:12px 25px; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.tool-body { flex:1; overflow-y:auto; padding:25px; background:var(--bg-primary); }
.upload-zone { border:2px dashed rgba(37,211,102,0.4); border-radius:8px; padding:50px 30px; text-align:center; cursor:pointer; transition:all 0.3s; background:rgba(37,211,102,0.03); }
.upload-zone:hover, .upload-zone.drag { border-color:#25d366; background:rgba(37,211,102,0.08); }
.upload-zone i { font-size:3rem; color:#25d366; margin-bottom:15px; display:block; }
.msg-bubble { padding:10px 15px; border-radius:8px; margin-bottom:8px; max-width:75%; font-size:0.9rem; line-height:1.5; }
.msg-in { background:rgba(255,255,255,0.07); border-radius:0 8px 8px 8px; margin-right:auto; }
.msg-out { background:rgba(37,211,102,0.12); border-radius:8px 0 8px 8px; margin-left:auto; border-left:3px solid #25d366; }
.msg-sender { font-size:0.72rem; font-weight:bold; color:#25d366; margin-bottom:4px; }
.msg-time { font-size:0.68rem; color:#64748b; margin-top:4px; text-align:right; }
.msg-date-divider { text-align:center; margin:15px 0; }
.msg-date-divider span { background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; font-size:0.75rem; padding:4px 15px; border-radius:20px; }
.stat-chip { background:rgba(37,211,102,0.1); border:1px solid rgba(37,211,102,0.3); color:#25d366; padding:8px 18px; border-radius:20px; font-size:0.85rem; font-weight:bold; }
.msg-media { color:#64748b; font-style:italic; font-size:0.85rem; }
#chatBox { height:500px; overflow-y:auto; padding:20px; background:rgba(0,0,0,0.3); border-radius:8px; border:1px solid rgba(255,255,255,0.05); }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content" style="padding:0; overflow:hidden; height:100vh; display:flex; flex-direction:column;">
        <header class="tool-topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <img src="../assets/images/logo.png" style="height:38px; width:38px; object-fit:contain;">
                <div>
                    <h2 style="margin:0; font-size:1.2rem; font-family:'Rajdhani',sans-serif; letter-spacing:1px;"><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> WhatsApp Report Reader</h2>
                    <small style="color:#94a3b8; font-size:0.7rem;">Upload WhatsApp exported chat (.txt) to analyse</small>
                </div>
            </div>
            <button id="exportBtn" onclick="exportReport()" style="display:none; background:rgba(37,211,102,0.1); border:1px solid #25d366; color:#25d366; padding:8px 18px; border-radius:4px; cursor:pointer; font-family:'Rajdhani',sans-serif; font-weight:bold;"><i class="fa-solid fa-file-export"></i> EXPORT CSV</button>
        </header>
        <div class="tool-body">

            <div id="uploadSection">
                <div class="upload-zone" id="dropZone" onclick="document.getElementById('fileInput').click()" ondragover="event.preventDefault(); this.classList.add('drag')" ondragleave="this.classList.remove('drag')" ondrop="handleDrop(event)">
                    <i class="fa-brands fa-whatsapp"></i>
                    <h3 style="color:#25d366; margin:0 0 10px 0; font-family:'Rajdhani',sans-serif;">Upload WhatsApp Chat Export</h3>
                    <p style="color:#94a3b8; margin:0;">Export from WhatsApp → Chat → More → Export Chat (Without Media)<br>Then upload the <strong style="color:#e2e8f0;">_chat.txt</strong> file here</p>
                    <input type="file" id="fileInput" accept=".txt" style="display:none" onchange="readFile(this.files[0])">
                </div>
            </div>

            <div id="analysisSection" style="display:none;">
                <div class="glass-panel" style="padding:20px; margin-bottom:20px;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <span class="stat-chip" id="statTotal"></span>
                            <span class="stat-chip" id="statParticipants" style="color:#60a5fa; border-color:#60a5fa; background:rgba(96,165,250,0.1);"></span>
                            <span class="stat-chip" id="statMedia" style="color:#f59e0b; border-color:#f59e0b; background:rgba(245,158,11,0.1);"></span>
                            <span class="stat-chip" id="statDays" style="color:#a855f7; border-color:#a855f7; background:rgba(168,85,247,0.1);"></span>
                        </div>
                        <button onclick="resetTool()" style="background:transparent; border:1px solid #475569; color:#94a3b8; padding:6px 15px; border-radius:4px; cursor:pointer; font-size:0.8rem;">← New File</button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 280px; gap:20px;">
                    <div class="glass-panel" style="padding:0; overflow:hidden;">
                        <div style="padding:15px 20px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:10px; justify-content:space-between;">
                            <h4 style="margin:0; font-family:'Rajdhani',sans-serif; color:#25d366;"><i class="fa-solid fa-comments"></i> Conversation Timeline</h4>
                            <input type="text" id="searchMsg" placeholder="Search messages..." oninput="filterMessages()" style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); color:#fff; padding:6px 12px; border-radius:4px; font-size:0.8rem; width:200px; outline:none;">
                        </div>
                        <div id="chatBox"></div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <div class="glass-panel" style="padding:20px;">
                            <h4 style="margin:0 0 15px 0; color:#60a5fa; font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-ranking-star"></i> Top Senders</h4>
                            <div id="senderStats"></div>
                        </div>
                        <div class="glass-panel" style="padding:20px;">
                            <h4 style="margin:0 0 15px 0; color:#f59e0b; font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-clock"></i> Activity by Hour</h4>
                            <div id="hourStats"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
let allMessages = [];

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropZone').classList.remove('drag');
    readFile(e.dataTransfer.files[0]);
}

function readFile(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => parseChat(e.target.result);
    reader.readAsText(file, 'UTF-8');
}

function parseChat(text) {
    const regex = /^(\d{1,2}\/\d{1,2}\/\d{2,4}),\s(\d{1,2}:\d{2}(?::\d{2})?(?:\s?[APap][Mm])?)\s-\s(.*?):\s(.+)$/;
    const sysRegex = /^(\d{1,2}\/\d{1,2}\/\d{2,4}),\s(\d{1,2}:\d{2}(?::\d{2})?)\s-\s(.+)$/;
    const lines = text.split('\n');
    const msgs = [];
    let lastMsg = null;

    for (const line of lines) {
        const m = line.match(regex);
        if (m) {
            lastMsg = { date: m[1], time: m[2].trim(), sender: m[3].trim(), text: m[4].trim() };
            msgs.push(lastMsg);
        } else if (lastMsg && line.trim() && !line.match(sysRegex)) {
            lastMsg.text += '\n' + line.trim();
        }
    }

    if (msgs.length === 0) {
        alert('Could not parse the chat file. Please make sure it is a valid WhatsApp export (.txt).');
        return;
    }

    allMessages = msgs;
    renderAnalysis(msgs);
}

function renderAnalysis(msgs) {
    document.getElementById('uploadSection').style.display = 'none';
    document.getElementById('analysisSection').style.display = 'block';
    document.getElementById('exportBtn').style.display = 'block';

    const senders = {}, hours = Array(24).fill(0), dates = new Set();
    let mediaCount = 0;

    msgs.forEach(m => {
        senders[m.sender] = (senders[m.sender] || 0) + 1;
        if (m.text.includes('<Media omitted>') || m.text.includes('image omitted') || m.text.includes('video omitted') || m.text.includes('audio omitted') || m.text.includes('sticker omitted')) mediaCount++;
        const h = parseInt(m.time.split(':')[0]);
        if (!isNaN(h)) hours[h < 24 ? h : 0]++;
        dates.add(m.date);
    });

    document.getElementById('statTotal').textContent = `💬 ${msgs.length} Messages`;
    document.getElementById('statParticipants').textContent = `👥 ${Object.keys(senders).length} Participants`;
    document.getElementById('statMedia').textContent = `📎 ${mediaCount} Media`;
    document.getElementById('statDays').textContent = `📅 ${dates.size} Days`;

    const box = document.getElementById('chatBox');
    let lastDate = '', html = '';
    msgs.forEach(m => {
        if (m.date !== lastDate) {
            html += `<div class="msg-date-divider"><span>${m.date}</span></div>`;
            lastDate = m.date;
        }
        const isMedia = m.text.includes('omitted');
        const senderList = Object.keys(senders);
        const isFirst = senderList.indexOf(m.sender) === 0;
        html += `<div class="msg-bubble ${isFirst ? 'msg-out' : 'msg-in'}" data-text="${m.text.toLowerCase()} ${m.sender.toLowerCase()}">
            <div class="msg-sender">${m.sender}</div>
            <div class="${isMedia ? 'msg-media' : ''}">${isMedia ? '📎 ' + m.text : m.text.replace(/\n/g,'<br>')}</div>
            <div class="msg-time">${m.time}</div>
        </div>`;
    });
    box.innerHTML = html;

    const sorted = Object.entries(senders).sort((a,b) => b[1]-a[1]).slice(0,8);
    const max = sorted[0]?.[1] || 1;
    const colors = ['#25d366','#60a5fa','#f59e0b','#a855f7','#ef4444','#10b981','#f97316','#06b6d4'];
    document.getElementById('senderStats').innerHTML = sorted.map(([name, count], i) => `
        <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:4px;">
                <span style="color:#e2e8f0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:160px;" title="${name}">${name.length>20?name.substring(0,18)+'…':name}</span>
                <span style="color:${colors[i%colors.length]}; font-weight:bold;">${count}</span>
            </div>
            <div style="background:rgba(255,255,255,0.05); border-radius:20px; height:6px;">
                <div style="width:${Math.round(count/max*100)}%; background:${colors[i%colors.length]}; height:6px; border-radius:20px; transition:width 0.5s;"></div>
            </div>
        </div>`).join('');

    const maxH = Math.max(...hours) || 1;
    document.getElementById('hourStats').innerHTML = hours.map((c, h) => c > 0 ? `
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
            <span style="color:#64748b; font-size:0.72rem; width:35px; text-align:right;">${String(h).padStart(2,'0')}:00</span>
            <div style="flex:1; background:rgba(255,255,255,0.05); border-radius:4px; height:10px;">
                <div style="width:${Math.round(c/maxH*100)}%; background:linear-gradient(90deg,#25d366,#10b981); height:10px; border-radius:4px;"></div>
            </div>
            <span style="color:#94a3b8; font-size:0.72rem; width:25px;">${c}</span>
        </div>` : '').join('');
}

function filterMessages() {
    const q = document.getElementById('searchMsg').value.toLowerCase();
    document.querySelectorAll('.msg-bubble').forEach(el => {
        el.style.display = !q || el.dataset.text?.includes(q) ? 'block' : 'none';
    });
}

function resetTool() {
    allMessages = [];
    document.getElementById('uploadSection').style.display = 'block';
    document.getElementById('analysisSection').style.display = 'none';
    document.getElementById('exportBtn').style.display = 'none';
    document.getElementById('fileInput').value = '';
}

function exportReport() {
    const rows = [['Date','Time','Sender','Message']];
    allMessages.forEach(m => rows.push([m.date, m.time, m.sender, '"' + m.text.replace(/"/g,'""') + '"']));
    const csv = rows.map(r => r.join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'whatsapp_report_' + Date.now() + '.csv';
    a.click();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>