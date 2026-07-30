<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Operational SOPs | HQ Control';

try { $pdo->exec("ALTER TABLE sops ADD COLUMN is_visible TINYINT(1) DEFAULT 1"); } catch (PDOException $e) {}

try {
    $all_modules = $pdo->query("SELECT * FROM sops WHERE is_visible=1 ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    $all_modules = [];
}

require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar" style="background:rgba(0,0,0,0.9);border-bottom:2px solid #00f3ff;padding:15px 30px;">
            <h2 style="margin:0;color:#00f3ff;font-family:'Rajdhani';letter-spacing:2px;"><i class="fa-solid fa-file-shield"></i> SOPs &amp; PROTOCOLS</h2>
            <a href="manage_sops.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;text-decoration:none;">
                <i class="fa-solid fa-gear"></i> Manage SOPs
            </a>
        </header>

        <div style="padding:30px;display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:25px;">
            <?php if (empty($all_modules)): ?>
                <div class="glass-panel" style="grid-column:1/-1;text-align:center;padding:60px;background:rgba(15,23,42,0.6);border:1px dashed rgba(0,243,255,0.3);">
                    <i class="fa-solid fa-folder-open" style="font-size:3rem;color:#00f3ff;opacity:0.3;margin-bottom:20px;display:block;"></i>
                    No Standard Operating Procedures available. <a href="manage_sops.php" style="color:#00f3ff;">Upload SOPs →</a>
                </div>
            <?php else: foreach ($all_modules as $m): ?>
                <div class="glass-panel" style="padding:25px;transition:0.3s;cursor:pointer;border:1px solid rgba(0,243,255,0.1);background:rgba(15,23,42,0.4);border-radius:12px;display:flex;flex-direction:column;justify-content:space-between;"
                     onclick="openSecureViewer('<?= addslashes(esc($m['title'])) ?>','<?= BASE_URL . '/' . esc($m['file_path']) ?>')">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:15px;">
                            <span style="background:rgba(0,243,255,0.1);color:#00f3ff;padding:4px 10px;border-radius:6px;font-size:0.75rem;font-weight:800;font-family:'Rajdhani';">
                                SECURE-ID: #<?= $m['id'] ?>
                            </span>
                            <i class="fa-solid fa-shield-halved" style="color:#00f3ff;font-size:1.4rem;"></i>
                        </div>
                        <h3 style="margin:0 0 12px 0;font-size:1.2rem;color:#fff;font-family:'Rajdhani';font-weight:700;"><?= esc($m['title']) ?></h3>
                        <p style="font-size:0.85rem;color:#94a3b8;margin-bottom:20px;line-height:1.6;height:3.2em;overflow:hidden;">
                            <?= esc($m['description']) ?>
                        </p>
                    </div>
                    <div style="text-align:right;border-top:1px solid rgba(255,255,255,0.05);padding-top:15px;">
                        <span style="color:#00f3ff;font-size:0.8rem;font-weight:800;letter-spacing:1px;"><i class="fa-solid fa-lock"></i> SECURE VIEW <i class="fa-solid fa-arrow-right-long" style="margin-left:5px;"></i></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>

<!-- Secure Viewer Modal -->
<div id="secureViewer" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.98);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(15px);">
    <div class="viewer-content" style="width:98vw;height:95vh;background:#000;border:1px solid #00f3ff;border-radius:12px;display:flex;flex-direction:column;box-shadow:0 0 50px rgba(0,243,255,0.3);">
        <div class="viewer-header" style="padding:16px 24px;border-bottom:1px solid rgba(0,243,255,0.3);display:flex;justify-content:space-between;align-items:center;background:rgba(0,243,255,0.05);flex-shrink:0;">
            <div>
                <span style="color:#00f3ff;font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:3px;display:block;margin-bottom:4px;">HQ Secure Document Viewer</span>
                <h3 id="vTitle" style="margin:0;font-family:'Rajdhani';color:#fff;letter-spacing:1px;font-size:1.3rem;">SOP Document</h3>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div id="pdfNav" style="display:none;align-items:center;gap:10px;">
                    <button id="btnPrev" onclick="pdfChangePage(-1)" style="background:rgba(0,243,255,0.1);border:1px solid rgba(0,243,255,0.4);color:#00f3ff;padding:7px 16px;border-radius:8px;cursor:pointer;font-family:'Rajdhani';font-weight:700;font-size:0.85rem;">&#8592; PREV</button>
                    <span id="pdfPageInfo" style="color:#94a3b8;font-size:0.85rem;min-width:90px;text-align:center;white-space:nowrap;"></span>
                    <button id="btnNext" onclick="pdfChangePage(1)" style="background:rgba(0,243,255,0.1);border:1px solid rgba(0,243,255,0.4);color:#00f3ff;padding:7px 16px;border-radius:8px;cursor:pointer;font-family:'Rajdhani';font-weight:700;font-size:0.85rem;">NEXT &#8594;</button>
                </div>
                <button onclick="closeViewer()" style="background:rgba(239,68,68,0.1);border:1px solid #ef4444;color:#ef4444;width:42px;height:42px;border-radius:10px;font-size:1.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
        </div>

        <div id="viewerBody" style="flex-grow:1;background:#020406;position:relative;overflow:hidden;display:flex;flex-direction:column;">
            <!-- Watermark -->
            <div style="position:absolute;inset:0;pointer-events:none;z-index:200;opacity:0.04;display:flex;flex-wrap:wrap;justify-content:center;align-content:center;overflow:hidden;">
                <?php
                    $wm_text = esc($_SESSION['user_name'] ?? 'HQ Admin') . ' | ' . esc($_SESSION['user_mapped_id'] ?? 'ADMIN');
                    for ($i=0; $i<35; $i++) echo "<span style='font-size:18px;font-weight:bold;padding:35px;transform:rotate(-30deg);white-space:nowrap;color:#fff;'>$wm_text</span>";
                ?>
            </div>
            <!-- Loader -->
            <div id="pdfLoader" style="display:none;position:absolute;inset:0;z-index:150;background:rgba(2,4,6,0.95);flex-direction:column;align-items:center;justify-content:center;gap:18px;">
                <div class="pdf-spin"></div>
                <span style="color:#00f3ff;font-family:'Rajdhani';font-size:1rem;letter-spacing:2px;text-transform:uppercase;">Decrypting Secure Document...</span>
            </div>
            <!-- Error -->
            <div id="pdfError" style="display:none;position:absolute;inset:0;z-index:150;background:rgba(2,4,6,0.95);flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:40px;text-align:center;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5rem;color:#ef4444;"></i>
                <div style="color:#ef4444;font-family:'Rajdhani';font-size:1.1rem;letter-spacing:1px;">SECURE ACCESS FAILED</div>
                <div id="pdfErrorMsg" style="color:#64748b;font-size:0.85rem;"></div>
            </div>
            <!-- PDF Wrap -->
            <div id="pdfWrap" style="display:none;flex:1;overflow-y:auto;overflow-x:hidden;background:#0a0f1a;padding:20px;box-sizing:border-box;justify-content:center;align-items:flex-start;">
                <canvas id="pdfCanvas" style="display:block;max-width:100%;margin:0 auto;box-shadow:0 0 30px rgba(0,243,255,0.12);pointer-events:none;-webkit-user-drag:none;"></canvas>
            </div>
            <!-- Iframe fallback -->
            <iframe id="vFrame" sandbox="allow-same-origin allow-scripts allow-forms" style="display:none;flex:1;width:100%;border:none;filter:invert(1) hue-rotate(170deg) brightness(1.2) contrast(1.1);"></iframe>
        </div>

        <div style="padding:12px 24px;border-top:1px solid rgba(0,243,255,0.3);font-size:0.78rem;color:#00f3ff;text-align:center;background:rgba(0,0,0,0.85);font-weight:bold;letter-spacing:1px;flex-shrink:0;">
            <i class="fa-solid fa-lock"></i> SECURE READ-ONLY SESSION &nbsp;|&nbsp; DOWNLOAD DISABLED &nbsp;|&nbsp; ACTION LOGGED FOR COMPLIANCE
        </div>
    </div>
</div>

<style>
body,.glass-panel{-webkit-user-select:none;user-select:none;}
@media print{body{display:none!important;}}
.pdf-spin{width:48px;height:48px;border:3px solid rgba(0,243,255,0.15);border-top-color:#00f3ff;border-radius:50%;animation:pdfSpin 0.85s linear infinite;}
@keyframes pdfSpin{to{transform:rotate(360deg);}}
#pdfWrap::-webkit-scrollbar{width:5px;}
#pdfWrap::-webkit-scrollbar-track{background:#0a0f1a;}
#pdfWrap::-webkit-scrollbar-thumb{background:rgba(0,243,255,0.3);border-radius:3px;}
</style>

<script>
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}
var _pdfDoc=null,_curPage=1,_totalPages=0,_rendering=false;
function _showLoader(on){document.getElementById('pdfLoader').style.display=on?'flex':'none';}
function _showError(msg){document.getElementById('pdfErrorMsg').textContent=msg;document.getElementById('pdfError').style.display='flex';_showLoader(false);}
function _showPdf(on){document.getElementById('pdfWrap').style.display=on?'flex':'none';document.getElementById('pdfNav').style.display=on?'flex':'none';}
function pdfRenderPage(num){
    if(!_pdfDoc||_rendering)return;
    _rendering=true;
    _pdfDoc.getPage(num).then(function(page){
        var canvas=document.getElementById('pdfCanvas'),ctx=canvas.getContext('2d'),wrap=document.getElementById('pdfWrap');
        var avail=(wrap.clientWidth>40)?(wrap.clientWidth-40):(window.innerWidth*0.94-40);
        var scale=Math.max(0.5,Math.min(avail/page.getViewport({scale:1}).width,2.2));
        var vp=page.getViewport({scale:scale});
        canvas.width=vp.width;canvas.height=vp.height;
        canvas.style.filter='invert(1) hue-rotate(170deg) brightness(1.1) contrast(1.05)';
        page.render({canvasContext:ctx,viewport:vp}).promise.then(function(){
            _rendering=false;
            document.getElementById('pdfPageInfo').textContent='Page '+num+' of '+_totalPages;
            document.getElementById('btnPrev').disabled=(num<=1);
            document.getElementById('btnNext').disabled=(num>=_totalPages);
            document.getElementById('pdfWrap').scrollTop=0;
        });
    }).catch(function(err){_rendering=false;_showError('Page render failed: '+err.message);});
}
window.pdfChangePage=function(delta){var next=_curPage+delta;if(next<1||next>_totalPages||_rendering)return;_curPage=next;pdfRenderPage(_curPage);};
window.openSecureViewer=function(title,url){
    document.getElementById('vTitle').textContent=title;
    document.getElementById('secureViewer').style.display='flex';
    document.body.style.overflow='hidden';
    _showLoader(false);_showPdf(false);
    document.getElementById('pdfError').style.display='none';
    document.getElementById('vFrame').style.display='none';
    document.getElementById('vFrame').src='';
    _pdfDoc=null;_curPage=1;_totalPages=0;_rendering=false;
    var isPdf=url.toLowerCase().lastIndexOf('.pdf')===url.toLowerCase().length-4;
    if(isPdf){
        if(typeof pdfjsLib==='undefined'){_showError('PDF viewer failed to load.');return;}
        _showLoader(true);
        pdfjsLib.getDocument({url:url,withCredentials:true}).promise.then(function(pdf){
            _pdfDoc=pdf;_totalPages=pdf.numPages;_curPage=1;
            _showLoader(false);_showPdf(true);
            requestAnimationFrame(function(){pdfRenderPage(1);});
        }).catch(function(err){_showError((err&&err.message)?err.message:'Could not load secure document.');});
    } else {
        document.getElementById('vFrame').style.display='block';
        document.getElementById('vFrame').src=url;
    }
};
window.closeViewer=function(){
    document.getElementById('secureViewer').style.display='none';
    document.getElementById('vFrame').src='';
    _showPdf(false);_showLoader(false);
    document.getElementById('pdfError').style.display='none';
    var old=document.getElementById('pdfCanvas'),fresh=document.createElement('canvas');
    fresh.id='pdfCanvas';fresh.style.cssText=old.style.cssText;
    old.parentNode.replaceChild(fresh,old);
    _pdfDoc=null;_curPage=1;_totalPages=0;_rendering=false;
    document.body.style.overflow='auto';
};
// Anti-Screenshot: blur content when window loses focus
var _vBody=document.getElementById('viewerBody');
window.addEventListener('blur',function(){if(_vBody)_vBody.style.filter='blur(30px)';});
window.addEventListener('focus',function(){
    if(_vBody)_vBody.style.filter='';
    var c=document.getElementById('pdfCanvas');
    if(c&&c.width>0)c.style.filter='invert(1) hue-rotate(170deg) brightness(1.1) contrast(1.05)';
    var f=document.getElementById('vFrame');
    if(f)f.style.filter='invert(1) hue-rotate(170deg) brightness(1.2) contrast(1.1)';
});
// Anti-Leakage
document.addEventListener('contextmenu',function(e){e.preventDefault();});
document.addEventListener('dragstart',function(e){e.preventDefault();});
document.addEventListener('selectstart',function(e){e.preventDefault();});
document.addEventListener('keydown',function(e){
    if(e.ctrlKey||e.metaKey){
        if(['p','s','u','c','x','a','j'].includes(e.key.toLowerCase()))e.preventDefault();
    }
    if(e.key==='F12'||e.key==='PrintScreen')e.preventDefault();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
