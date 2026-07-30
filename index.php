<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email)) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $message])) {
            $_SESSION['toast_msg'] = "Thank you! Your message has been received.";
            $_SESSION['toast_type'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_career'])) {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? 'Cyber Security Intern');
    $cover = trim($_POST['cover_letter'] ?? '');

    if (!empty($name) && !empty($email)) {
        $full_message = "POSITION APPLIED: " . $position . "\n\nCOVER LETTER / BACKGROUND:\n" . $cover;
        $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $full_message])) {
            $_SESSION['toast_msg'] = "Application submitted successfully! Our team will contact you.";
            $_SESSION['toast_type'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Investigation Portal | Cyber Security Internship & VAPT</title>
    <meta name="description" content="Apply for a premium internship in digital cyber crime investigation, cyber security training, and hands-on VAPT. Enhance your digital forensics knowledge.">
    <meta name="keywords" content="internship in digital cyber crime, cyber crime investigation, cyber security internship, vapt internship, digital forensics training, security analysis">
    
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Rajdhani:wght@400;500;600;700&display=swap">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <script src="assets/js/hacker-fx.js" defer></script>
    <style>
        /* Anti-Copy CSS */
        body, .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        @media print { body { display: none !important; } }

        /* --- RESPONSIVE FIXES --- */
        @media (max-width: 992px) {
            .split-container { flex-direction: column !important; height: auto !important; min-height: 100vh; }
            .pane { width: 100% !important; min-height: 50vh; padding: 60px 20px !important; }
            
            .main-header { padding: 10px 20px !important; position: relative; }
            .main-header nav { 
                display: none; 
                position: absolute; 
                top: 100%; 
                left: 0; 
                width: 100%; 
                background: #0b1120; 
                flex-direction: column; 
                padding: 20px; 
                gap: 15px; 
                border-bottom: 2px solid #00f3ff;
                z-index: 1000;
            }
            .main-header nav.active { display: flex !important; }
            .mobile-toggle { display: block !important; }
            
            .info-grid { grid-template-columns: 1fr !important; }
            .modal-content { width: 95% !important; margin: 10px !important; }
        }

        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            color: #00f3ff;
            cursor: pointer;
            background: transparent;
            border: none;
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableInspector(event);">
    <script>
        function disableInspector(e) {
            const isModifier = e.ctrlKey || e.metaKey;
            const key = e.key ? e.key.toLowerCase() : String.fromCharCode(e.keyCode).toLowerCase();

            // Disable F12
            if (e.keyCode === 123 || e.key === 'F12') {
                e.preventDefault();
                return false;
            }

            if (isModifier) {
                // Block view source (U), save page (S), print (P), copy (C), cut (X), select all (A)
                if (['u', 's', 'p', 'c', 'x', 'a'].includes(key)) {
                    e.preventDefault();
                    return false;
                }
                // Block inspect element (I), developer tool console (J)
                if (key === 'i' || key === 'j') {
                    e.preventDefault();
                    return false;
                }
            }
        }
        document.addEventListener('copy', (e) => { e.preventDefault(); return false; });
    </script>

    <header class="main-header">
        <div class="logo" style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/images/logo.png" alt="Logo" class="brand-logo" style="height: 70px; width: auto; object-fit: contain;">
            <span style="font-size: 1.2rem; letter-spacing: 1px; font-weight: 800; font-family: 'Rajdhani', sans-serif;">DIGITAL INVESTIGATION</span>
        </div>
        
        <button class="mobile-toggle" onclick="document.querySelector('.main-header nav').classList.toggle('active')">
            <i class="fas fa-bars"></i>
        </button>

        <nav>
            <a href="#" id="themeToggleBtn" onclick="toggleTheme(event)" title="Toggle Day/Night Mode">
                <i class="fas fa-sun"></i>
            </a>
            <a href="#" onclick="openModal('howToApplyModal')" style="color: #00f3ff; font-weight: bold;"><i class="fa-solid fa-graduation-cap"></i> How to Apply</a>
            <a href="faq.php" style="color: #eab308; font-weight: bold;" target="_blank"><i class="fa-solid fa-circle-question"></i> FAQ</a>
            <a href="#" onclick="openModal('careerModal')">Career</a>
            <a href="#" onclick="openModal('aboutModal')">About Us</a>
            <a href="#" onclick="openModal('servicesModal')">Services</a>
            <a href="#" onclick="openModal('contactModal')">Contact</a>
        </nav>
    </header>

<script>
    function updateThemeUI(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        const icon = document.querySelector('#themeToggleBtn i');
        if (icon) { icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun'; }
    }
    function toggleTheme(e) {
        if (e) e.preventDefault();
        const current = localStorage.getItem('theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        updateThemeUI(next);
    }
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        updateThemeUI(savedTheme);
        window.onclick = function(event) {
            if (event.target.className === 'cyber-modal') { event.target.style.display = 'none'; }
        }
    });
</script>

    <main class="split-container">
        <section class="pane intern-pane">
            <div class="overlay"></div>
            <div class="content">
                <i class="fas fa-user-graduate icon"></i>
                <h2>For Interns</h2>
                <p>Enhance your knowledge with structured cyber security training, forensics basics, and hands-on modules.</p>
                <a href="intern/login.php" class="btn-cyber">
                    <span class="btn-content">Start Learning <i class="fas fa-arrow-right"></i></span>
                </a>
            </div>
        </section>

        <section class="pane lea-pane">
            <div class="overlay"></div>
            <div class="content">
                <i class="fas fa-user-secret icon"></i>
                <h2>For LEA</h2>
                <p>Advanced investigative tools, IPDR analysis, and technical tips to enhance your field operations.</p>
                <a href="officer/login.php" class="btn-cyber btn-lea">
                    <span class="btn-content">Access Tools <i class="fas fa-lock"></i></span>
                </a>
            </div>
        </section>

        <div class="scroll-down-indicator" onclick="window.scrollTo({top: window.innerHeight - 80, behavior: 'smooth'})">
            <span>Scroll Down</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </main>

    <script>
        window.addEventListener('scroll', function() {
            const indicator = document.querySelector('.scroll-down-indicator');
            if (indicator) {
                if (window.scrollY > 50) {
                    indicator.style.opacity = '0';
                    indicator.style.pointerEvents = 'none';
                } else {
                    indicator.style.opacity = '1';
                    indicator.style.pointerEvents = 'auto';
                }
            }
        });
    </script>

    <section class="info-container fade-in-up">
        <div class="info-grid">
            
            <div class="glass-panel portal-column">
                <h3 class="section-title"><i class="fas fa-newspaper"></i> Articles</h3>
                <div class="news-feed" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 5");
                    $articles = $stmt->fetchAll();
                    if (empty($articles)):
                        echo "<p style='text-align:center; padding:20px; color:var(--text-muted);'>No articles yet.</p>";
                    else: foreach($articles as $article): 
                        $article_url = BASE_URL . "/read_article.php?id=" . $article['id'];
                    ?>
                        <article class="news-item-card" style="cursor: pointer; position: relative;" onclick="window.location.href='read_article.php?id=<?= $article['id'] ?>'">
                            <span class="news-date"><?= date('M d, Y', strtotime($article['created_at'])) ?></span>
                            <h4><?= esc($article['title']) ?></h4>
                            <p><?= substr(esc($article['description']), 0, 100) ?>...</p>
                            <div style="text-align: right; margin-top: -10px;">
                                <button class="btn-share" onclick="event.stopPropagation(); shareLink('<?= $article_url ?>')" style="padding: 4px 10px; font-size: 0.75rem; background: rgba(0, 243, 255, 0.05);">
                                    <i class="fa-solid fa-share-nodes"></i> Share Link
                                </button>
                            </div>
                        </article>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            
            <div class="glass-panel news-column">
                <h3 class="section-title"><i class="fas fa-rss"></i> Cyber News</h3>
                <div class="news-feed" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM cyber_news ORDER BY created_at DESC LIMIT 5");
                    $news_items = $stmt->fetchAll();
                    if (empty($news_items)):
                        echo "<p style='text-align:center; padding:20px; color:var(--text-muted);'>No news yet.</p>";
                    else: foreach($news_items as $news): 
                        $news_url = BASE_URL . "/read_news.php?id=" . $news['id'];
                    ?>
                        <article class="news-item-card" style="cursor: pointer; position: relative;" onclick="window.location.href='read_news.php?id=<?= $news['id'] ?>'">
                            <span class="news-date"><?= date('M d, Y', strtotime($news['created_at'])) ?></span>
                            <h4><?= esc($news['title']) ?></h4>
                            <p><?= substr(esc($news['description']), 0, 100) ?>...</p>
                            <div style="text-align: right; margin-top: -10px;">
                                <button class="btn-share" onclick="event.stopPropagation(); shareLink('<?= $news_url ?>')" style="padding: 4px 10px; font-size: 0.75rem; background: rgba(0, 243, 255, 0.05);">
                                    <i class="fa-solid fa-share-nodes"></i> Share Link
                                </button>
                            </div>
                        </article>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </section>

    
    <div id="toast-container" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

    <script>
        function shareLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    triggerToast("Link copied to clipboard! Ready to share.", "success");
                }).catch(err => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(url) {
            const tempInput = document.createElement("textarea");
            tempInput.value = url;
            tempInput.style.position = 'fixed';
            tempInput.style.top = '0';
            tempInput.style.left = '0';
            tempInput.style.opacity = '0';
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    triggerToast("Link copied to clipboard! Ready to share.", "success");
                } else {
                    triggerToast("Could not copy link automatically.", "error");
                }
            } catch (err) {
                triggerToast("Could not copy link automatically.", "error");
            }
            document.body.removeChild(tempInput);
        }

        function triggerToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast     = document.createElement('div');
            const iconMap   = { success:'fa-circle-check', error:'fa-circle-xmark' };
            const icon      = iconMap[type] || 'fa-circle-check';

            toast.className = `cyber-toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa-solid ${icon} toast-icon"></i>
                <span>${message}</span>
                <span class="toast-close" onclick="this.parentElement.remove()">&times;</span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 350);
            }, 3500);
        }
    </script>

    
    <div id="contentDetailModal" class="cyber-modal">
        <div class="glass-panel modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 id="contentDetailTitle">Investigation Log</h3>
                <span class="close" onclick="closeModal('contentDetailModal')">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px 30px; font-family: 'Inter', sans-serif;">
                <div id="contentDetailDate" style="color: var(--accent-blue); font-size: 0.9rem; font-weight: bold; margin-bottom: 15px; font-family: 'Rajdhani', sans-serif;"></div>
                <div id="contentDetailBody" style="line-height: 1.8; color: #cbd5e1; font-size: 1rem; white-space: pre-wrap;"></div>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 15px 30px; text-align: right;">
                <button class="btn-primary" onclick="closeModal('contentDetailModal')" style="background-color: var(--accent-highlight); color: #fff; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer;">Acknowledge</button>
            </div>
        </div>
    </div>

    <script>
        function openContentModal(title, date, body) {
            document.getElementById('contentDetailTitle').innerText = title;
            document.getElementById('contentDetailDate').innerText = date;
            document.getElementById('contentDetailBody').innerText = body;
            openModal('contentDetailModal');
        }
    </script>

    
    <div id="aboutModal" class="cyber-modal">
        <div class="glass-panel modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-circle-info" style="color:#00f3ff; margin-right:8px;"></i> Mission Intel</h3>
                <span class="close" onclick="closeModal('aboutModal')">&times;</span>
            </div>
            <div class="modal-body" style="padding: 30px; font-family: 'Inter', sans-serif;">
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px;">
                    
                    <div style="line-height: 1.8; color: #cbd5e1; font-size: 1rem; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 30px;">
                        <p style="margin-bottom: 20px;">
                            <strong>DigitalInvestigation</strong> is a dedicated web platform designed to support Private investigators, security agencies, and students in the field of digital and cyber investigation. Our mission is to bridge the gap between technology and investigation by providing reliable tools, expert guidance, and practical learning opportunities.
                        </p>
                        <p style="margin-bottom: 20px;">
                            This platform serves as a technical support hub, enabling investigators to access resources and assistance for digital evidence handling, private forensics analysis, and data security investigation. At the same time, students can benefit through internship opportunities, gaining hands-on experience and exposure to real-world corporate cyber security practices.
                        </p>
                        <div style="background: rgba(0,243,255,0.03); border-radius: 8px; padding: 20px; border-left: 3px solid #ff9900; margin: 25px 0;">
                            <h4 style="color: #ff9900; margin-top: 0; font-family: 'Rajdhani', sans-serif;">
                                <i class="fa-solid fa-crown"></i> Leadership
                            </h4>

                            DigitalInvestigation is founded and directed by
                            <strong>Surbhi Shrivastava (LLB, B.E., PGDCA)</strong>,
                            Founder & Director, Digital Investigation.

                            With a multidisciplinary background in law,
                            engineering, and information technology,
                            she leads the organization's vision for
                            cyber investigation, digital evidence support,
                        capacity building, and technology-driven
                        investigation services.
                        </div>
                    </div>

                    
                    <div>
                        <h4 class="cyan" style="border-bottom: 1px solid rgba(255, 215, 0, 0.3); padding-bottom: 10px; margin-bottom: 20px; font-family: 'Rajdhani', sans-serif; text-align: center; color: #ffd700;"><i class="fa-solid fa-crown"></i> LEADERSHIP</h4>
                        <div class="team-node-card" onclick="openHackerCard('surbhi')" style="border-color: #ffd700; background: rgba(255, 215, 0, 0.05); margin-bottom: 30px;">
                            <div class="node-icon" style="border-color: #ffd700; color: #ffd700;">
                                <img src="assets/images/surbhi.jpeg" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            </div>
                            <div class="node-info">
                                <span class="node-name">Surbhi Shrivastava</span>
                                <span class="node-role" style="color: #ffd700;">
                                    Founder & Director, Digital Investigation
                                </span>
                            </div>
                            <i class="fa-solid fa-chevron-right node-arrow" style="color: #ffd700;"></i>
                        </div>

                        <h4 class="cyan" style="border-bottom: 1px solid rgba(0, 243, 255, 0.2); padding-bottom: 10px; margin-bottom: 20px; font-family: 'Rajdhani', sans-serif; text-align: center;">Core Team</h4>
                        
                        <div class="team-nodes" style="display: flex; flex-direction: column; gap: 15px;">
                            <div class="team-node-card" onclick="openHackerCard('prateek')">
                                <div class="node-icon" style="border-color:#ffd700; overflow:hidden;">
                                    <img src="assets/images/prateek_mishra.png"
                                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                </div>

                                <div class="node-info">
                                    <span class="node-name">Prateek Mishra</span>
                                    <span class="node-role">Chief Systems Architect</span>
                                </div>
								
                                <i class="fa-solid fa-chevron-right node-arrow"></i>
                            </div>
                            <div class="team-node-card" onclick="openHackerCard('palak')">
                                <div class="node-icon" style="color: #3b82f6; border-color: #3b82f6; overflow:hidden;">
                                    <img src="assets/images/palak.png" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                </div>
								
                                <div class="node-info">
                                    <span class="node-name">Palak Rai</span>
                                    <span class="node-role">Lead Architect & Developer, Intern Portal</span>
                                </div>
                                <i class="fa-solid fa-chevron-right node-arrow"></i>
                            </div>
							<div class="team-node-card" onclick="openHackerCard('unnati')">
                                <div class="node-icon" style="color: #a855f7; border-color: #a855f7; overflow:hidden;">
                                    <img src="assets/images/unnati_photo.jpeg" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                </div>
                                <div class="node-info">
                                    <span class="node-name">Unnati Saxena</span>
                                    <span class="node-role">Internship Curriculum Designer</span>
                                </div>
                                <i class="fa-solid fa-chevron-right node-arrow"></i>
                            </div>
							
                        </div>
                        
                        <div style="margin-top: 30px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <h5 style="margin-bottom: 10px; color: #00f3ff; font-family:'Rajdhani'; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px;">Official Inquiries</h5>
                            <p style="font-size: 0.85rem; margin-bottom: 5px; color:#cbd5e1;"><i class="fas fa-envelope"></i> support@digitalinvestigation.in</p>
                            <p style="font-size: 0.85rem; color:#cbd5e1;"><i class="fas fa-phone"></i> +91 7000816565</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div id="hacker-modal-overlay" class="hacker-overlay" style="z-index: 10001;" onclick="closeHackerCard()">
        <div id="hacker-card-container" class="hacker-card-content" onclick="event.stopPropagation()">
            
        </div>
    </div>

    <style>
    .team-node-card { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 10px 15px; border-radius: 4px; cursor: pointer; transition: 0.3s; text-align: left; }
    .team-node-card:hover { background: rgba(0, 243, 255, 0.05); border-color: #00f3ff; box-shadow: 0 0 15px rgba(0,243,255,0.2); }
    .node-icon { width: 45px; height: 45px; border: 1px solid #00f3ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #00f3ff; font-size: 1.2rem; flex-shrink: 0; background: rgba(0, 243, 255, 0.05); }
    .node-info { display: flex; flex-direction: column; }
    .node-name { color: #fff; font-weight: bold; font-size: 1rem; font-family: 'Rajdhani', sans-serif; }
    .node-role { color: #94a3b8; font-size: 0.8rem; }
    .node-arrow { margin-left: auto; color: rgba(255,255,255,0.2); font-size: 0.8rem; }
    .hacker-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(15px); display: none; align-items: center; justify-content: center; }
    .hacker-card-content { width: 450px; background: #020617; border: 1px solid #00f3ff; border-radius: 4px; padding: 25px; position: relative; box-shadow: 0 0 70px rgba(0, 243, 255, 0.3); text-align: left; }
    .hacker-photo-placeholder { width: 100px; height: 100px; border: 1px solid #00f3ff; background: rgba(0, 243, 255, 0.05); display: flex; align-items: center; justify-content: center; color: #00f3ff; position: relative; border-radius: 4px; overflow: hidden; }
    .hacker-photo-placeholder::after { content: 'Uplink Pending'; position: absolute; bottom: 5px; font-size: 0.4rem; text-transform: uppercase; }
    </style>

    <script>
    const teamData = {
        surbhi: {
            name: "Surbhi Shrivastava",
            role: "Founder & Director, Digital Investigation",
            desc: "Surbhi Shrivastava leads Digital Investigation with a multidisciplinary background in law, engineering, and computer applications. She oversees strategic planning, legal and technical consultancy services, cyber investigation initiatives, capacity building programmes, and organizational development.",
            color: "#ffd700",
            photo: "assets/images/surbhi.jpeg",
            contact: "Founder & Director"
        },
        prateek: { 
            name: "Prateek Mishra", 
            role: "Chief Systems Architect", 
            desc: "Prateek Mishra is the Founder and the core technical architect behind Digital Investigation. He has been responsible for designing and developing the primary infrastructure, investigation systems, and technical architecture powering the platform. His work focuses on building scalable digital investigation solutions, system integration, and practical technology-driven platforms for real-world applications.", 
            linkedin: "https://www.linkedin.com/in/prateek-mishra0111/", 
            color: "#ffd700",
            photo: "assets/images/prateek_mishra.png",
            email: "prateek@digitalinvestigation.in",
            phone: "+91 6355208514"
        },
        unnati: { 
            name: "Unnati Saxena", 
            role: "Internship Curriculum Designer", 
            desc: "Unnati Saxena has been responsible for designing the internship curriculum, learning structure, and training modules for the program. Her work focuses on creating organized learning pathways, practical activities, and structured module systems that help interns gain hands-on exposure, technical understanding, and a more industry-oriented learning experience throughout the internship journey.", 
            linkedin: "https://www.linkedin.com/in/saxenaunnati/", 
            color: "#a855f7",
            photo: "assets/images/unnati_photo.jpeg"
        },
        palak: { 
            name: "Palak Rai", 
            role: "Lead Architect & Developer, Intern Portal", 
            desc: "Palak Rai designed and developed the complete Intern Portal infrastructure for the Digital Investigation Internship Program. Her work focused on creating a centralized, modern, and user-friendly platform where interns can manage tasks, access modules, track performance, use investigation utilities, and interact with different internship resources through a structured digital environment. She has also worked on improving overall system usability, workflow management, and interface experience for students.", 
            linkedin: "https://www.linkedin.com/in/palak-rai-96930931a/",
            color: "#3b82f6",
            photo: "assets/images/palak.png"
        }
    };
    function openHackerCard(id) {
        console.log("Opening node: " + id);
        const data = teamData[id];
        const container = document.getElementById('hacker-card-container');
        
        const photoHtml = data.photo 
            ? `<img src="${data.photo}" style="width: 100%; height: 100%; object-fit: cover; border: 1px solid ${data.color};">`
            : `<i class="fa-solid fa-user-secret" style="font-size: 2.5rem;"></i>`;

        const contactHtml = data.email 
            ? `<div style="margin-top: 15px; font-size: 0.75rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                <div style="color: ${data.color}; margin-bottom: 3px;"><i class="fa-solid fa-envelope"></i> ${data.email}</div>
                <div style="color: #94a3b8;"><i class="fa-solid fa-phone"></i> ${data.phone}</div>
               </div>`
            : (data.contact ? `<div style="margin-top: 15px; font-size: 0.75rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; color: ${data.color}; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;"><i class="fa-solid fa-scale-balanced"></i> ${data.contact}</div>` : '');

        const extraHtml = data.education 
            ? `<div style="margin-top: 15px; font-size: 0.7rem; color: #94a3b8; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px;">
                <div style="color: ${data.color}; font-weight: bold; margin-bottom: 5px;">CREDENTIALS:</div>
                <div style="margin-bottom: 5px;"><i class="fa-solid fa-graduation-cap"></i> ${data.education}</div>
                <div><i class="fa-solid fa-building-shield"></i> ${data.affiliations}</div>
               </div>`
            : '';

        container.innerHTML = `
            <div style="position: absolute; top: 10px; right: 15px; color: ${data.color}; font-family: monospace; font-size: 0.6rem;">NODE: FOUNDER_01</div>
            <div style="display:flex; gap:20px; margin-bottom:20px;">
                <div class="hacker-photo-placeholder" style="width: 100px; height: 100px; flex-shrink: 0; border-color: ${data.color};">
                    ${photoHtml}
                </div>
                <div>
                    <h2 style="color: #fff; margin: 0; font-family: 'Rajdhani', sans-serif; font-size: 1.4rem;">${data.name}</h2>
                    <div style="color: ${data.color}; font-size: 0.8rem; font-weight: bold; margin-top: 5px;">${data.role}</div>
                    <div style="margin-top: 15px;">
                        ${data.linkedin ? `<a href="${data.linkedin}" target="_blank" style="color: #fff; text-decoration: none; background: ${data.color}; color: #000; padding: 4px 10px; font-size: 0.7rem; font-weight: bold; border-radius: 2px;"><i class="fa-brands fa-linkedin"></i> LINKEDIN</a>` : ''}
                    </div>
                </div>
            </div>
            <div style="color: #94a3b8; font-size: 0.9rem; line-height: 1.6; font-family: 'Courier New', monospace; background: rgba(255,255,255,0.02); padding: 12px; border-left: 2px solid ${data.color};">
                ${data.desc}
                ${extraHtml}
                ${contactHtml}
            </div>
            <div style="margin-top: 15px; text-align: right;">
                <button onclick="closeHackerCard()" style="background: transparent; border: 1px solid #475569; color: #475569; padding: 5px 12px; cursor: pointer; font-size: 0.6rem;">CLOSE_CONNECTION</button>
            </div>`;
        document.getElementById('hacker-modal-overlay').style.display = 'flex';
    }
    function closeHackerCard() { document.getElementById('hacker-modal-overlay').style.display = 'none'; }
    </script>

    
     <div id="servicesModal" class="cyber-modal">
    <div class="glass-panel modal-content" style="max-width:1100px;">
        <div class="modal-header">
    <h3>
        <i class="fa-solid fa-gears" style="color:#00f3ff;margin-right:10px;"></i>
        Our Services
    </h3>

    <span class="close" onclick="closeModal('servicesModal')">&times;</span>
</div>

    <div class="modal-body" style="padding:25px;">

    
    <p style="
        color:#94a3b8;
        margin-bottom:25px;
        line-height:1.8;
        font-size:1rem;
        max-width:900px;">
        Digital Investigation provides specialized cyber investigation,
        technical consultancy, digital intelligence support, training,
        and technology development services for investigators,
        law enforcement agencies, organizations, and students.
    </p>
     
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;">

            <div class="glass-panel">
                <h4>Technical Support</h4>
                <p>End-to-end technical assistance for cyber investigations, digital evidence handling, forensic workflows, security assessments and investigation platforms.</p>
            </div>

            <div class="glass-panel">
                <h4>Legal & Technical Investigation Consultancy</h4>
                <p>Professional consultancy combining legal expertise and technical analysis for cybercrime investigations, compliance requirements and digital evidence management.</p>
            </div>

            <div class="glass-panel">
                <h4>LEA Investigation Support</h4>
                <p>Dedicated support for Law Enforcement Agencies including cyber intelligence gathering, digital evidence analysis and investigation assistance.</p>
            </div>

            <div class="glass-panel">
                <h4>Cyber Investigation Tools</h4>
                <p>Specialized tools and utilities designed for cyber investigations, OSINT collection, intelligence gathering and digital analysis.</p>
            </div>

            <div class="glass-panel">
                <h4>CDR / IPDR Analysis</h4>
                <p>Advanced communication record analysis to identify links, communication patterns and investigative leads.</p>
            </div>

            <div class="glass-panel">
                <h4>Training for LEAs</h4>
                <p>Cybercrime investigation, digital evidence preservation, cyber law awareness and forensic training programmes.</p>
            </div>

            <div class="glass-panel">
                <h4>Internship Programmes</h4>
                <p>Practical learning opportunities for students interested in cyber security, investigations and digital forensics.</p>
            </div>

            <div class="glass-panel">
                <h4>Cyber Awareness Programmes</h4>
                <p>Awareness workshops for schools, colleges, corporate organizations and government departments.</p>
            </div>

            <div class="glass-panel">
                <h4>Website Development</h4>
                <p>Professional website design, development, deployment, maintenance and security hardening services.</p>
            </div>

            <div class="glass-panel">
                <h4>Hosting & Server Solutions</h4>
                <p>Reliable hosting infrastructure, server deployment, maintenance and performance optimization.</p>
            </div>

            <div class="glass-panel">
                <h4>Professional Email Services</h4>
                <p>Business email setup, domain email solutions and secure communication infrastructure.</p>
            </div>

            <div class="glass-panel">
                <h4>Custom Web Portal Development</h4>
                <p>Investigation portals, training platforms, management systems and workflow automation solutions.</p>
            </div>
            </div> 

        </div>

    </div>
</div>

</div>

    
    <div id="howToApplyModal" class="cyber-modal">
        <div class="glass-panel modal-content fade-in-up" style="max-width: 850px; border-color: #00f3ff;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0, 243, 255, 0.2);">
                <h3><i class="fas fa-graduation-cap" style="color: #00f3ff; margin-right: 10px;"></i> How to Apply & Portal Benefits</h3>
                <span class="close" onclick="closeModal('howToApplyModal')">&times;</span>
            </div>
            <div class="modal-body" style="font-family: 'Inter', sans-serif; padding: 25px; max-height: 75vh; overflow-y: auto;">
                
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;">
                    
                    
                    <div>
                        <h4 style="color: #00f3ff; font-family: 'Rajdhani', sans-serif; font-size: 1.3rem; margin-top: 0; margin-bottom: 20px; border-bottom: 1px dashed rgba(0, 243, 255, 0.2); padding-bottom: 5px;">
                            <i class="fa-solid fa-list-check"></i> Application Process
                        </h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="display: flex; gap: 15px;">
                                <div style="width: 30px; height: 30px; background: rgba(0, 243, 255, 0.1); border: 1px solid #00f3ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #00f3ff; font-weight: bold; flex-shrink: 0;">1</div>
                                <div>
                                    <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 1rem; font-family: 'Rajdhani', sans-serif; color: #00f3ff;">Register & Select Training Batch</h5>
                                    <p style="color: #cbd5e1; margin: 0; font-size: 0.88rem; line-height: 1.5;">Click the registration link below. Fill in your personal details, college credentials, select your target monthly training batch, and verify your email instantly using the OTP sent to your mailbox.</p>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 15px;">
                                <div style="width: 30px; height: 30px; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #10b981; font-weight: bold; flex-shrink: 0;">2</div>
                                <div>
                                    <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 1rem; font-family: 'Rajdhani', sans-serif; color: #10b981;">Immediate Portal Access</h5>
                                    <p style="color: #cbd5e1; margin: 0; font-size: 0.88rem; line-height: 1.5;">Once email verification is complete, your account is activated instantly! You can log in and start exploring your learning modules right away.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div style="background: rgba(0, 243, 255, 0.02); border: 1px solid rgba(0, 243, 255, 0.1); padding: 20px; border-radius: 8px;">
                        <h4 style="color: #eab308; font-family: 'Rajdhani', sans-serif; font-size: 1.3rem; margin-top: 0; margin-bottom: 20px; border-bottom: 1px dashed rgba(234, 179, 8, 0.2); padding-bottom: 5px;">
                            <i class="fa-solid fa-gift"></i> What Interns Receive
                        </h4>
                        
                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px;">
                            <li style="display: flex; gap: 10px; align-items: flex-start;">
                                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-top: 3px;"></i>
                                <span style="color: #cbd5e1; font-size: 0.9rem;"><strong>Structured Modules:</strong> Access practical tutorials on Digital Forensics, OSINT, and Cyber Law.</span>
                            </li>
                            <li style="display: flex; gap: 10px; align-items: flex-start;">
                                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-top: 3px;"></i>
                                <span style="color: #cbd5e1; font-size: 0.9rem;"><strong>Daily Practical Tasks:</strong> Test your skills with live challenges and real-world investigations.</span>
                            </li>
                            <li style="display: flex; gap: 10px; align-items: flex-start;">
                                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-top: 3px;"></i>
                                <span style="color: #cbd5e1; font-size: 0.9rem;"><strong>Live Leaderboard:</strong> Compete with fellow interns on challenges to secure high ranks.</span>
                            </li>
                            <li style="display: flex; gap: 10px; align-items: flex-start;">
                                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-top: 3px;"></i>
                                <span style="color: #cbd5e1; font-size: 0.9rem;"><strong>Verification Certificate:</strong> Earn an official Cyber Security Internship Certificate on completion.</span>
                            </li>
                            <li style="display: flex; gap: 10px; align-items: flex-start;">
                                <i class="fa-solid fa-circle-check" style="color: #10b981; margin-top: 3px;"></i>
                                <span style="color: #cbd5e1; font-size: 0.9rem;"><strong>Performance Report Card:</strong> Detailed evaluations highlighting your strengths and areas of expertise.</span>
                            </li>
                        </ul>
                    </div>

                </div>
                
                <div style="text-align: center; margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                    <a href="intern/signup.php" class="btn-cyber" style="display: inline-block; padding: 10px 25px;">
                        <span class="btn-content">Register Now <i class="fas fa-arrow-right"></i></span>
                    </a>
                </div>

            </div>
        </div>
    </div>

    
    <div id="careerModal" class="cyber-modal">
        <div class="glass-panel modal-content fade-in-up" style="max-width: 650px;">
            <div class="modal-header">
                <h3><i class="fas fa-briefcase" style="color: #00f3ff; margin-right: 10px;"></i> Career & Opportunities</h3>
                <span class="close" onclick="closeModal('careerModal')">&times;</span>
            </div>
            <div class="modal-body" style="font-family: 'Inter', sans-serif;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 6px;">
                        <h4 style="color:#00f3ff; margin-top:0; font-family:'Rajdhani'; font-size:1.2rem; font-weight:700;">Cyber Internships</h4>
                        <p style="color:#cbd5e1; font-size:0.9rem; line-height:1.6; margin:0;">Join our specialized programs for students. Gain hands-on exposure to real-world SOPs and investigative tools.</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 6px; border-left: 3px solid #eab308;">
                        <h4 style="color:#eab308; margin-top:0; font-family:'Rajdhani'; font-size:1.2rem; font-weight:700;">Marketing Executives</h4>
                        <p style="color:#cbd5e1; font-size:0.9rem; line-height:1.6; margin:0;">We are expanding! Regional vacancies open in <strong>Uttar Pradesh, Maharashtra, and Rajasthan</strong>. Help us scale digital security awareness.</p>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding: 25px; text-align: center; background: rgba(0,0,0,0.3); border-radius: 8px; border: 1px solid rgba(0,243,255,0.1);">
                    <p style="margin-bottom: 12px; color: #94a3b8; font-size: 0.95rem;">Interested in joining our mission?</p>
                    <p style="font-size: 1.1rem; margin: 0; color:#fff;">
                        Send your resume to: <br>
                        <a href="mailto:support@digitalinvestigation.in" style="font-weight: bold; text-decoration: none; color: #00f3ff; border-bottom: 1px dashed #00f3ff; margin-top:8px; display:inline-block;">support@digitalinvestigation.in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    ->
    <div id="contactModal" class="cyber-modal">
        <div class="glass-panel modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-envelope" style="color: #00f3ff; margin-right: 10px;"></i> Secure Contact Desk</h3>
                <span class="close" onclick="closeModal('contactModal')">&times;</span>
            </div>
            <div class="modal-body" style="font-family: 'Inter', sans-serif;">
                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px;">
                    Send encrypted diagnostic reports, tool inquiries, or system feedback directly to the administrators.
                </p>
                <form method="POST" class="cyber-form">
                    <input type="hidden" name="submit_contact" value="1">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="text" name="full_name" placeholder="Full Name" required class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="email" name="email" placeholder="Email Address" required class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="tel" name="phone" placeholder="Mobile (Optional)" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <textarea name="message" rows="4" placeholder="Type your message or enquiry here..." required class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Send Message
                    </button>
                </form>
                
                <div style="margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom: 8px; font-size:0.85rem;">
                        <span style="color:#64748b;">Direct SMTP:</span>
                        <span style="color:#00f3ff; font-weight:700;">support@digitalinvestigation.in</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                        <span style="color:#64748b;">Response Time:</span>
                        <span style="color:#10b981; font-weight:700;">Under 4 Hours</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="home-footer" style="text-align:center; padding:20px; color:var(--text-muted);">
        <p>&copy; 2026 Digital Investigation Portal</p>
    </footer>

</body>
</html>