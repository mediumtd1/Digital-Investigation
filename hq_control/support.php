<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Support Ticket Management | Admin Portal';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $status    = $_POST['status'];
    $comment   = trim($_POST['admin_comment']);
    $stmt = $pdo->prepare("UPDATE help_tickets SET status = ?, admin_comment = ? WHERE id = ?");
    $stmt->execute([$status, $comment, $ticket_id]);
    header("Location: support.php?msg=updated");
    exit;
}

$stmt        = $pdo->query("SELECT * FROM help_tickets WHERE status != 'Closed' ORDER BY priority='Critical' DESC, created_at ASC");
$tickets_raw = $stmt->fetchAll();
$tickets     = [];
foreach ($tickets_raw as $t) {
    $user_name = 'Unknown'; $user_unique_id = 'N/A';
    try {
        if ($t['user_role'] === 'Intern') {
            $u_stmt = $pdo->prepare("SELECT name, intern_id FROM interns WHERE id = ?");
            $u_stmt->execute([$t['user_id']]); $u = $u_stmt->fetch();
            if ($u) { $user_name = $u['name']; $user_unique_id = $u['intern_id']; }
        } elseif ($t['user_role'] === 'Officer') {
            $u_stmt = $pdo->prepare("SELECT name, badge_number FROM officers WHERE id = ?");
            $u_stmt->execute([$t['user_id']]); $u = $u_stmt->fetch();
            if ($u) { $user_name = $u['name']; $user_unique_id = $u['badge_number']; }
        }
    } catch (Exception $e) {}
    $t['user_name'] = $user_name; $t['user_unique_id'] = $user_unique_id;
    $tickets[] = $t;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Ticket Modal ────────────────────────────────────────────── */
.ticket-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.ticket-modal-overlay.active { display: flex; animation: fadeOverlay .2s ease; }
@keyframes fadeOverlay { from{opacity:0} to{opacity:1} }
@keyframes slideUp     { from{opacity:0;transform:translateY(36px) scale(.97)} to{opacity:1;transform:none} }

.ticket-modal {
    background: #0a0f1a;
    border: 1px solid rgba(0,212,255,.28);
    border-radius: 16px;
    width: 94%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 0 80px rgba(0,212,255,.1), 0 32px 80px rgba(0,0,0,.85);
    animation: slideUp .28s cubic-bezier(.34,1.56,.64,1);
    scrollbar-width: thin;
    scrollbar-color: rgba(0,212,255,.3) transparent;
}
.ticket-modal::-webkit-scrollbar { width:5px; }
.ticket-modal::-webkit-scrollbar-thumb { background:rgba(0,212,255,.3); border-radius:4px; }

.tm-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 24px 28px 18px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    position: sticky; top:0;
    background: #0a0f1a;
    z-index: 10;
}
.tm-body   { padding: 24px 28px; }
.tm-footer { padding: 16px 28px 26px; border-top: 1px solid rgba(255,255,255,.07); }

.tm-close {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.12);
    color: #fff;
    width:36px; height:36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.05rem;
    display:flex; align-items:center; justify-content:center;
    transition: background .2s, border-color .2s;
    flex-shrink:0;
}
.tm-close:hover { background:rgba(239,68,68,.28); border-color:#ef4444; }

.attach-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,212,255,.09);
    border: 1px solid rgba(0,212,255,.35);
    color: var(--accent-blue,#00d4ff);
    padding: 9px 18px;
    border-radius: 50px;
    font-size: .84rem;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, transform .15s;
    word-break: break-all;
}
.attach-pill:hover { background:rgba(0,212,255,.2); transform:translateY(-2px); }

.tm-update-form {
    display: grid;
    grid-template-columns: 1fr 190px 130px;
    gap: 12px;
    align-items: center;
}
@media(max-width:580px){
    .tm-update-form { grid-template-columns:1fr; }
}

.ticket-card {
    border-radius: 10px;
    padding: 18px 20px;
    background: rgba(255,255,255,.02);
    transition: box-shadow .2s;
    cursor: pointer;
}
.ticket-card:hover { box-shadow: 0 0 24px rgba(0,212,255,.1); }

.view-btn {
    background: rgba(0,212,255,.1);
    border: 1px solid rgba(0,212,255,.3);
    color: var(--accent-blue,#00d4ff);
    padding: 6px 14px;
    border-radius: 8px;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: .05em;
    transition: background .2s, transform .15s;
    white-space: nowrap;
}
.view-btn:hover { background:rgba(0,212,255,.22); transform:translateY(-1px); }

.desc-box {
    background: rgba(0,0,0,.35);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px;
    padding: 16px;
    font-family: monospace;
    font-size: .87rem;
    line-height: 1.75;
    color: #c8d6e5;
    white-space: pre-wrap;
    word-break: break-word;
    margin-bottom: 20px;
}
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2>Support Management Console</h2>
        </header>
        <div class="dashboard-content">
            <div class="glass-panel">
                <h3 class="section-heading">
                    <i class="fa-solid fa-headset"></i> Pending Support Requests
                    <span style="font-size:.8rem;font-weight:400;color:var(--text-muted);margin-left:8px;"><?= count($tickets) ?> open</span>
                </h3>

                <?php if(empty($tickets)): ?>
                    <p style="text-align:center;color:var(--text-muted);padding:50px 20px;">
                        <i class="fa-solid fa-circle-check" style="font-size:2.2rem;color:#10b981;display:block;margin-bottom:12px;"></i>
                        Operations Clear — No pending tickets.
                    </p>
                <?php endif; ?>

                <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach($tickets as $t):
                    $rc = ($t['user_role']=='Officer') ? 'var(--danger)' : 'var(--accent-blue)';
                    $pc = '#64748b';
                    if($t['priority']=='Critical') $pc='#ef4444';
                    if($t['priority']=='High')     $pc='#f59e0b';
                    $sc = '#64748b';
                    if($t['status']=='Open')        $sc='#3b82f6';
                    if($t['status']=='In Progress') $sc='#f59e0b';
                    if($t['status']=='Resolved')    $sc='#10b981';
                    $has_att = !empty($t['attachment']);
                    $json_t  = htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="ticket-card glass-panel" style="border-left:5px solid <?= $rc ?>;"
                     onclick='openTicketModal(<?= $json_t ?>)'>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:6px;">
                                <span class="badge" style="background:<?= $rc ?>;color:#fff;font-size:.67rem;">
                                    <?= strtoupper($t['user_role']) ?> REQUEST
                                </span>
                                <?php if($has_att): ?>
                                <span style="background:rgba(0,212,255,.1);border:1px solid rgba(0,212,255,.35);
                                      color:var(--accent-blue);padding:2px 10px;border-radius:50px;
                                      font-size:.67rem;font-weight:700;">
                                    <i class="fa-solid fa-paperclip"></i> Attachment
                                </span>
                                <?php endif; ?>
                            </div>
                            <h4 style="margin:0 0 4px;font-size:.98rem;"><?= esc($t['subject']) ?></h4>
                            <p style="color:var(--text-muted);font-size:.8rem;margin:0;">
                                <?= esc($t['user_name']) ?> &nbsp;·&nbsp;
                                <strong><?= esc($t['user_unique_id']) ?></strong>
                                &nbsp;·&nbsp; <?= date('d M, h:i A', strtotime($t['created_at'])) ?>
                            </p>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:7px;flex-shrink:0;">
                            <span style="color:<?= $pc ?>;font-weight:800;font-size:.72rem;white-space:nowrap;">
                                <i class="fa-solid fa-triangle-exclamation"></i> <?= strtoupper($t['priority']) ?>
                            </span>
                            <span class="badge" style="background:<?= $sc ?>;color:#fff;font-size:.7rem;"><?= esc($t['status']) ?></span>
                            <button class="view-btn" onclick="event.stopPropagation();openTicketModal(<?= $json_t ?>)">
                                <i class="fa-solid fa-eye"></i> VIEW
                            </button>
                        </div>
                    </div>
                    <p style="font-size:.8rem;color:var(--text-muted);margin:11px 0 0;
                               white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= esc(substr($t['description'],0,170)) ?><?= strlen($t['description'])>170?'…':'' ?>
                    </p>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TICKET DETAIL MODAL
════════════════════════════════════════════════════════════ -->
<div class="ticket-modal-overlay" id="tmOverlay" onclick="tmClose(event)">
  <div class="ticket-modal" id="tmBox" onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="tm-header">
      <div style="flex:1;min-width:0;">
        <div id="tm-badges" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;"></div>
        <h3 id="tm-subject" style="margin:0;font-size:1.1rem;line-height:1.45;"></h3>
        <p  id="tm-meta"    style="color:var(--text-muted);font-size:.8rem;margin:6px 0 0;"></p>
      </div>
      <button class="tm-close" onclick="tmCloseBtn()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Body -->
    <div class="tm-body">
      <div class="desc-box" id="tm-desc"></div>

      <!-- Attachment block -->
      <div id="tm-att-wrap" style="display:none;margin-bottom:20px;">
        <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">
          <i class="fa-solid fa-paperclip"></i> Attachment
        </p>
        <a id="tm-att-link" href="#" target="_blank" class="attach-pill">
          <i class="fa-solid fa-file-arrow-down"></i>
          <span id="tm-att-name">View File</span>
        </a>
        <div id="tm-img-wrap" style="display:none;margin-top:14px;">
          <img id="tm-img" src="" alt="Attachment preview"
               style="max-width:100%;max-height:360px;border-radius:10px;
                      border:1px solid rgba(255,255,255,.1);object-fit:contain;">
        </div>
      </div>

      <!-- Previous admin reply -->
      <div id="tm-admin-wrap" style="display:none;
           background:rgba(16,185,129,.05);border-left:3px solid #10b981;
           padding:12px 16px;border-radius:6px;margin-bottom:20px;">
        <p style="font-size:.76rem;font-weight:700;color:#10b981;margin:0 0 4px;">
          <i class="fa-solid fa-user-shield"></i> Previous Admin Note
        </p>
        <p id="tm-admin-txt" style="font-size:.85rem;margin:0;color:var(--text-primary);"></p>
      </div>
    </div>

    <!-- Footer — update form -->
    <div class="tm-footer">
      <form method="POST" class="tm-update-form">
        <input type="hidden" name="update_ticket" value="1">
        <input type="hidden" name="ticket_id"     id="tm-tid"    value="">
        <input type="text"   name="admin_comment" id="tm-cmt"
               class="form-control" placeholder="Resolution / Inquiry notes..." style="height:44px;">
        <select name="status" id="tm-status" class="form-control" style="height:44px;">
          <option value="Open">Open</option>
          <option value="In Progress">In Progress</option>
          <option value="Resolved">Resolved</option>
          <option value="Closed">Closed</option>
        </select>
        <button type="submit" class="btn-primary" style="height:44px;white-space:nowrap;">
          <i class="fa-solid fa-check-double"></i> UPDATE
        </button>
      </form>
    </div>

  </div>
</div>

<script>
const tmOverlay = document.getElementById('tmOverlay');

function openTicketModal(t) {
    const rc = (t.user_role==='Officer') ? 'var(--danger)' : 'var(--accent-blue)';
    let pc = '#64748b';
    if(t.priority==='Critical') pc='#ef4444';
    if(t.priority==='High')     pc='#f59e0b';
    let sc = '#64748b';
    if(t.status==='Open')        sc='#3b82f6';
    if(t.status==='In Progress') sc='#f59e0b';
    if(t.status==='Resolved')    sc='#10b981';

    document.getElementById('tm-badges').innerHTML =
        `<span class="badge" style="background:${rc};color:#fff;font-size:.67rem">${esc(t.user_role).toUpperCase()} REQUEST</span>
         <span style="color:${pc};font-weight:800;font-size:.72rem"><i class="fa-solid fa-triangle-exclamation"></i> ${esc(t.priority).toUpperCase()} PRIORITY</span>
         <span class="badge" style="background:${sc};color:#fff;font-size:.7rem">${esc(t.status)}</span>`;

    document.getElementById('tm-subject').textContent = t.subject || '';
    document.getElementById('tm-meta').innerHTML =
        `Raised by: <strong>${esc(t.user_name)}</strong> &nbsp;·&nbsp; UID: <strong>${esc(t.user_unique_id)}</strong> &nbsp;·&nbsp; ${fmtDate(t.created_at)}`;
    document.getElementById('tm-desc').textContent = t.description || '';

    // Attachment
    const attWrap = document.getElementById('tm-att-wrap');
    if (t.attachment && t.attachment.trim() !== '') {
        attWrap.style.display = 'block';
        const fileUrl  = '../' + t.attachment;
        const fileName = t.attachment.split('/').pop();
        document.getElementById('tm-att-link').href = fileUrl;
        document.getElementById('tm-att-name').textContent = fileName;
        const imgExts = ['jpg','jpeg','png','gif','webp','bmp'];
        const ext = fileName.split('.').pop().toLowerCase();
        const imgWrap = document.getElementById('tm-img-wrap');
        const imgEl   = document.getElementById('tm-img');
        if (imgExts.includes(ext)) {
            imgWrap.style.display = 'block';
            imgEl.src = fileUrl;
        } else {
            imgWrap.style.display = 'none';
            imgEl.src = '';
        }
    } else {
        attWrap.style.display = 'none';
    }

    // Admin comment
    const admWrap = document.getElementById('tm-admin-wrap');
    if (t.admin_comment && t.admin_comment.trim() !== '') {
        admWrap.style.display = 'block';
        document.getElementById('tm-admin-txt').textContent = t.admin_comment;
    } else {
        admWrap.style.display = 'none';
    }

    // Pre-fill form
    document.getElementById('tm-tid').value = t.id;
    document.getElementById('tm-cmt').value = t.admin_comment || '';
    const sel = document.getElementById('tm-status');
    for (let o of sel.options) o.selected = (o.value === t.status);

    tmOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function tmCloseBtn() {
    tmOverlay.classList.remove('active');
    document.body.style.overflow = '';
}
function tmClose(e) { if(e.target===tmOverlay) tmCloseBtn(); }
document.addEventListener('keydown', e => { if(e.key==='Escape') tmCloseBtn(); });

function esc(s) {
    if(!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtDate(s) {
    if(!s) return '';
    const d = new Date(s);
    if(isNaN(d)) return s;
    return d.toLocaleDateString('en-IN',{day:'2-digit',month:'short'}) + ', ' +
           d.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true});
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
