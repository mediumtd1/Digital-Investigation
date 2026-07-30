<?php
require_once __DIR__ . '/config/config.php';
$page_title = 'FAQ | Digital Investigation Portal';
require_once __DIR__ . '/includes/header.php';

$faqs = [
    'General Information' => [
        ['q' => '1. What is Digital Investigation?', 'a' => 'DigitalInvestigation.in is a specialized, cutting-edge digital platform dedicated to bridging the gap between theoretical knowledge and practical expertise in the fields of cyber forensics, cybersecurity, and digital investigations. Designed to serve as a comprehensive ecosystem, the portal caters to both Law Enforcement Agencies (LEAs) requiring technical assistance and aspiring cybersecurity professionals looking for real-world, industry-vetted exposure.<br><br>Our core mission is to empower the next generation of cyber professionals while providing the vital tools and frameworks needed to investigate, analyze, and mitigate digital threats in an increasingly complex technical landscape.<br><br><strong>Key Pillars:</strong><br>• <strong>Law Enforcement Technical Support</strong> — Structured tools, analytical dashboards, and investigative resources.<br>• <strong>Elite Internship &amp; Training Program</strong> — Role-based access, hands-on digital investigation workflows.<br>• <strong>Community &amp; Leadership Development</strong> — Campus outreach, ambassador networks, and academic collaboration.'],
        ['q' => '2. Who can use the Digital Investigation Portal?', 'a' => 'Students, professionals, investigators, Law Enforcement Agencies (LEAs), and organizations can all use the portal based on their respective role-based access level.'],
        ['q' => '3. Is Digital Investigation a government website?', 'a' => 'No. DigitalInvestigation.in is not an official government website or agency.<br><br>We are a privately-owned, rapidly growing Indian company registered as an <strong>MSME</strong> under the Government of India. We specialize in building secure, authoritative digital forensics frameworks and bridge the gap between technology and security for both public and private institutions.'],
        ['q' => '4. How can I register on the portal?', 'a' => '<ol style="padding-left:20px; line-height:2.2;"><li><strong>Visit the Registration Page</strong> — If a peer invited you, click their referral link to credit their profile.</li><li><strong>Fill in Basic Information</strong> — Provide your full name, email, phone number, and a strong password.</li><li><strong>Select Your Role Profile</strong> — Choose Intern/Student, Law Enforcement Official, or Corporate Member to apply role-based access.</li><li><strong>Verify Email Address</strong> — Click the secure link in your inbox (check spam if not received within a minute).</li><li><strong>Complete Profile &amp; Final Approval</strong> — Fill required details; our team will verify and grant full dashboard access.</li></ol>'],
        ['q' => '5. Are there any fees or registration charges?', 'a' => '• <strong>Students &amp; Interns:</strong> 100% Free — no application, training, or certification fees.<br>• <strong>Law Enforcement Officials:</strong> Access to advanced forensic toolkits requires an active subscription of <strong>₹2,400/- INR annually</strong>.<br><br>All accounts undergo mandatory verification before dashboard features are fully unlocked.'],
        ['q' => '6. Why should I create an account?', 'a' => '<strong>🎓 For Students &amp; Interns:</strong><br>• 1-Month Free Internship on live industry projects.<br>• Daily Learning Tracks with emerging technology updates.<br>• Comprehensive Study Material on digital forensics and cybersecurity.<br><br><strong>👮 For Law Enforcement Agencies (LEAs):</strong><br>• Investigative Utilities — specialized tools for active digital investigations.<br>• Secure Data Frameworks — forensic intelligence management dashboards.'],
    ],
    'Account &amp; Login' => [
        ['q' => '7. I forgot my password. What should I do?', 'a' => 'Use the <strong>Forgot Password</strong> option on the login page. A secure reset link will be sent to your registered email address.'],
        ['q' => '8. Can I change my registered email address?', 'a' => 'Yes, through your profile settings or by raising a support ticket with the admin.'],
        ['q' => '9. How do I log in to my account?', 'a' => 'Use your registered email and password on the respective login page for your role (Intern or Officer portal).'],
        ['q' => '10. Why am I unable to log in?', 'a' => 'Common reasons include: incorrect credentials, pending verification by admin, or scheduled platform maintenance. If issues persist, raise a support ticket.'],
        ['q' => '11. Can multiple users share one account?', 'a' => '<strong>No. Account sharing is strictly against our security protocols.</strong><br><br>Our platform enforces a strict one-user-per-account policy. If our security system detects concurrent login from multiple devices or users, <span style="color:#f87171;">the account will be instantly suspended</span>.<br><br><div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:15px; margin-top:12px;"><strong style="color:#f87171;">⚠️ Security Enforcement Notice</strong><br>To maintain law-enforcement-grade platform security and ensure accurate data tracking for intern milestones, every user must operate solely through their own authenticated credentials. Shared access logs will trigger automatic security bans.</div>'],
    ],
    'Internship Program' => [
        ['q' => '12. What internship programs are available?', 'a' => 'We provide specialized, hands-on internship tracks built around real-world live projects:<br>• <strong>Cybersecurity</strong> — Network defense, threat mitigation, and secure system architectures.<br>• <strong>Cyber Forensics</strong> — Evidence gathering, data recovery, and forensic analysis methodologies.<br>• <strong>Digital Investigation</strong> — Operational intelligence, system tracing, and modern investigative workflows.<br><br><div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:15px; margin-top:12px;"><strong>📌 Regarding Paid Learning Modules:</strong> During your free internship runtime, our portal offers optional, advanced paid learning tracks. These are completely <strong>voluntary</strong> — not mandatory to complete your core internship.</div>'],
        ['q' => '13. Who can apply for internships?', 'a' => '• <strong>Students Within India:</strong> Open to students from all domains, streams, and branches at any recognized university or college. The core 1-month track remains free.<br>• <strong>International Students (Outside India):</strong> Eligible to apply exclusively via our <strong>Paid Learning Modules</strong> to access our digital forensic and cybersecurity training tracks.'],
        ['q' => '14. Is the internship online or offline?', 'a' => 'The internship is <strong>completely online</strong>. It operates on a remote, work-from-home (WFH) framework — you can complete your daily modules from home, college, or hostel without any mandatory physical travel.<br><br><div style="background:rgba(148,163,184,0.08); border:1px solid rgba(148,163,184,0.2); border-radius:8px; padding:15px; margin-top:12px;"><strong>📌 Financial Terms:</strong> This is a non-stipendiary, training-oriented internship program. We do not provide stipend, salary, or monetary compensation. The focus is on delivering high-value technical skill building and live project experience.</div>'],
        ['q' => '15. Will interns receive practical assignments?', 'a' => '<strong>Yes. Our training is entirely focused on hands-on deployment.</strong><br><br>Practical assignments, challenge modules, and live project updates are assigned through two channels:<br>• <strong>The Intern Portal:</strong> Tasks appear dynamically inside your secure student dashboard.<br>• <strong>Email Updates:</strong> Detailed project briefs, structural templates, and task milestones delivered to your registered email.'],
    ],
    'Certificates' => [
        ['q' => '16. Will I receive a certificate after completion?', 'a' => 'Yes, subject to eligibility criteria including task completion, attendance, and performance evaluation by HQ.'],
        ['q' => '17. How can I download my certificate?', 'a' => 'From your dashboard after successful completion of the program. Navigate to the <strong>Certificates</strong> section in your sidebar.'],
        ['q' => '18. Is certificate verification available?', 'a' => 'Yes. Certificate verification is available using your unique Certificate ID on our public verification page.'],
        ['q' => '19. What should I do if my certificate contains an error?', 'a' => '<strong>If your certificate has a spelling mistake or incorrect detail, it can be easily updated.</strong><br><br>Steps to follow:<br>• Log into your account dashboard at DigitalInvestigation.in.<br>• Navigate to the <strong>Support / Helpdesk</strong> section.<br>• Create a new support ticket directed to the Admin, stating the exact error and required correction.<br><br>Our administrative team will review your credentials and issue an updated, error-free certificate shortly.'],
        ['q' => '35. How can an employer verify my certificate?', 'a' => 'Verifying bodies need:<br>• <strong>Certificate ID:</strong> Unique alpha-numeric tracking ID on your certificate.<br>• <strong>Full Name:</strong> As registered on the portal.<br>• <strong>Allocated Batch:</strong> Program batch code or duration timeline.<br><br>These parameters can be cross-referenced against our verification engine to confirm achievement authenticity instantly, ensuring zero fraudulence.'],
        ['q' => '36. What information is displayed after verification?', 'a' => 'Name, program enrolled, certificate number, completion date, and current status.'],
    ],
    'LEA &amp; Officer Services' => [
        ['q' => '20. What services are available for LEAs?', 'a' => '<strong>📋 Operational &amp; Documentation Assets:</strong><br>• Directories &amp; LEA URLs — Official contact books and forensic portals.<br>• Letter Formats &amp; SOPs — Legal templates and Standard Operating Procedures.<br><br><strong>🔍 Tracking &amp; Field Databases:</strong><br>• All India PS List — Searchable Police Station locator database.<br>• All India Toll Plaza Tracker — Highway checkpoint data and visual mappings.<br><br><strong>💬 Telecom &amp; Social Media Forensics:</strong><br>• CDR &amp; IPDR Analysis — Map call and internet session logs.<br>• Social Media Analytics — Interpret WhatsApp, Facebook, and Instagram compliance reports.<br>• Email Header Analysis — Trace origin points and investigate phishing emails.<br><br><strong>🌐 Network &amp; Device Identity Tracking:</strong><br>• IP Lookup &amp; Suspect IP Tracker — Real-time network mapping.<br>• MAC &amp; IMEI Utilities — Hardware identity verification.<br><br><strong>🛡️ Cryptography, Media Analysis &amp; OSINT:</strong><br>• Bitcoin Finder, EXIF Data Grabber, Hash Calculators, Voice Analysis, OSINT &amp; IFSC Search.'],
        ['q' => '21. Can police officers access specialized resources?', 'a' => 'Yes. Verified police officers and law enforcement personnel get access to dedicated investigation utilities, SOPs, field databases, and forensic toolkits through the Officer Portal.'],
        ['q' => '22. Does the platform provide cybercrime investigation training?', 'a' => '• <strong>For Law Enforcement Agents:</strong> Specialized technical training, SOPs, and operational guides for utilizing the Investigation Lab for active cybercrime tracking and forensic handling.<br>• <strong>For Students &amp; Interns:</strong> Extensive, industry-vetted learning materials along with a 1-month free practical internship on live projects to master cybersecurity and digital forensic methodologies.'],
    ],
    'Services &amp; Features' => [
        ['q' => '23. What services does Digital Investigation provide?', 'a' => '<strong>🛡️ Cyber Investigation &amp; Forensics:</strong><br>• LEA &amp; Technical Support — Digital evidence handling and security assessments.<br>• CDR / IPDR &amp; Investigation Tools — Deep communication record analysis and OSINT tools.<br>• Legal-Technical Consultancy — Guidance on cybercrime compliance and digital evidence management.<br><br><strong>💻 Tech Development &amp; Hosting:</strong><br>• Custom Web Portals &amp; Websites — Secure investigation portals and workflow systems.<br>• Hosting &amp; Professional Email — Enterprise-grade server management.<br><br><strong>🎓 Educational &amp; Awareness Programs:</strong><br>• LEA &amp; Corporate Training — Specialized capacity-building in cyber law and forensics.<br>• Student Internships &amp; Awareness — Live-project training tracks and public cyber-safety workshops.'],
        ['q' => '24. Can organizations request customized training?', 'a' => '<strong>Yes, we provide fully tailored training and capacity-building frameworks.</strong><br><br>• <strong>Law Enforcement &amp; Government:</strong> Deep-dive forensic tool training, SOP alignment, and cybercrime investigation strategies.<br>• <strong>Corporates &amp; Businesses:</strong> Employee digital hygiene workshops, vulnerability assessment drills, and risk-mitigation training.<br>• <strong>Colleges &amp; Academic Groups:</strong> Student upskilling seminars, technical bootcamps, and institutional cyber-awareness campaigns.<br><br>To request a custom training outline, please raise an inquiry ticket through your administrative dashboard.'],
        ['q' => '25. Does Digital Investigation provide website security consultation?', 'a' => '<strong>Yes. We provide end-to-end technical consultation and security hardening for web infrastructure.</strong><br><br>• <strong>Website Security Hardening:</strong> Code audits and maintenance to shield platforms from cyber threats.<br>• <strong>Technical Security Assessments:</strong> Evaluation of database connections, authentication layers, and portals.<br>• <strong>Secure Server &amp; Hosting Solutions:</strong> Resilient server setups built to withstand unauthorized access.<br><br>Organizations can launch a consultation request directly via the admin support desk.'],
        ['q' => '32. Will new investigation tools be added?', 'a' => '<strong>Yes. Our platform is dynamically updated to keep pace with emerging cyber threats.</strong><br><br>• <strong>Continuous Tool Engineering:</strong> New forensic analysis modules are regularly designed, tested, and deployed.<br>• <strong>Advanced Investigation Techniques:</strong> Backend tracing algorithms constantly upgraded.<br>• <strong>Fresh Materials &amp; Frameworks:</strong> New SOPs and learning modules updated periodically for operational relevance.<br><br>All updates automatically populate into your dashboard as soon as they clear administrative vetting.'],
        ['q' => '33. Can users suggest new features?', 'a' => '<strong>Yes! We highly encourage feature suggestions, tool ideas, and workflow feedback.</strong><br><br>Active dashboard users can draft their ideas and submit them as proposals through the internal ticketing desk under the "Suggestions" category. Collaborative input from interns, students, and field officers helps our development team identify and prioritize real-world requirements.'],
    ],
    'Privacy &amp; Security' => [
        ['q' => '26. Is my personal information secure?', 'a' => '<strong>Yes. Data privacy and infrastructure security are foundational to our platform.</strong><br><br>• <strong>Role-Based Data Isolation:</strong> Strict access privileges keep your data inaccessible to unauthorized tiers.<br>• <strong>Anti-Session Hijacking:</strong> Live security monitors enforce a one-user-per-session limit, blocking concurrent access attempts.<br>• <strong>Mandatory Admin Audits:</strong> Every profile passes a rigorous verification phase before accessing active portal layers.<br>• <strong>Hardened Database Frameworks:</strong> Credentials hosted on secured, monitored servers protected by advanced firewalls and encryption.'],
        ['q' => '27. Will my data be shared with third parties?', 'a' => '<strong>No. We enforce a strict non-disclosure policy for external commercial third parties. Your data is never sold or repurposed.</strong><br><br>Data is only transmitted across verified, authorized channels:<br>• <strong>Law Enforcement Agencies:</strong> Profiles may be reviewed by authorized LEA authorities for verification and compliance tracking.<br>• <strong>Authorized Operational Partners:</strong> Specific metadata shared solely for internship validations and certification routing.'],
        ['q' => '28. Are communications encrypted?', 'a' => '<strong>Yes. Every communication channel is locked down using robust encryption standards.</strong><br><br>• <strong>End-to-Transit Encryption (HTTPS/TLS):</strong> All data between your device and our servers is fully encrypted.<br>• <strong>Secured Infrastructure Routing:</strong> Support tickets and admin workflows are processed through isolated, code-hardened server environments.<br>• <strong>Protected Communication Gates:</strong> Domain-specific enterprise mailing and secure database handshakes prevent data tampering.'],
    ],
    'Support' => [
        ['q' => '29. How can I contact support?', 'a' => '• <strong>Dashboard Support Tickets:</strong> Navigate to the Support section in your dashboard — fastest method for profile, assignment, or certificate issues.<br>• <strong>Official Email:</strong> <strong>support@digitalinvestigation.in</strong> — Include your User ID for quicker resolution. (Replied within 24–48 hours.)<br>• <strong>Direct Helpdesk:</strong> <strong>+91 7000816565</strong> — For urgent operational queries during business hours.<br>• <strong>Public Inquiry Form:</strong> Available on our primary landing page for non-registered visitors, organizations, or universities.'],
        ['q' => '30. What is the expected response time?', 'a' => '• <strong>Dashboard Support Tickets:</strong> Typically resolved within <strong>4 hours</strong>.<br>• <strong>Email Inquiries:</strong> Processed within <strong>24 to 48 hours</strong>.<br>• <strong>Telephonic Helpdesk:</strong> Handled in real-time during standard business hours.<br><br><em>Note: Response times may vary during weekends, national holidays, or high-volume internship enrollment periods.</em>'],
        ['q' => '31. How can I report a technical issue?', 'a' => '<strong>Technical issues must be reported through the ticketing system only — not via email or phone.</strong><br><br>Steps:<br>1. Log into your account dashboard.<br>2. Navigate to the <strong>Support / Helpdesk</strong> module on your sidebar.<br>3. Click <strong>Create Ticket</strong>, describe the exact error, and submit to the Admin.<br><br>This allows our backend engineers to isolate the session state, inspect database logs, and deploy a patch directly to your account.'],
        ['q' => '34. How can I stay updated about new programs?', 'a' => '<strong>Updates are broadcast directly to you — no manual checking needed.</strong><br><br>• <strong>Registered Email Alerts:</strong> High-priority notifications dispatched directly to your inbox.<br>• <strong>Official System Circulars:</strong> Formal administrative documents and platform notices posted inside your portal workspace.<br><br><em>Tip: Add our support email domain to your safe-sender whitelist and regularly check your dashboard notice board.</em>'],
    ],
];
?>

<style>
/* ── FAQ Page Styles ── */
.faq-page-wrap {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.faq-hero {
    text-align: center;
    margin-bottom: 50px;
    padding: 50px 30px;
    background: linear-gradient(135deg, rgba(0,243,255,0.05), rgba(168,85,247,0.05));
    border: 1px solid rgba(0,243,255,0.1);
    border-radius: 16px;
    position: relative;
    overflow: hidden;
}
.faq-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(ellipse at center, rgba(0,243,255,0.04) 0%, transparent 60%);
    animation: rotateBg 15s linear infinite;
    pointer-events: none;
}
@keyframes rotateBg {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.faq-hero h1 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 2.8rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 3px;
    margin: 0 0 12px;
    text-transform: uppercase;
}
.faq-hero h1 span { color: #00f3ff; }
.faq-hero p { color: #94a3b8; font-size: 1rem; margin: 0; }

/* Search */
.faq-search-wrap {
    position: relative;
    margin-bottom: 40px;
}
.faq-search-wrap i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #00f3ff;
    font-size: 1rem;
}
#faqSearch {
    width: 100%;
    padding: 16px 20px 16px 50px;
    background: rgba(15,23,42,0.8);
    border: 1px solid rgba(0,243,255,0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.3s;
}
#faqSearch:focus { border-color: rgba(0,243,255,0.6); box-shadow: 0 0 20px rgba(0,243,255,0.1); }
#faqSearch::placeholder { color: #475569; }

/* Category Label */
.faq-category {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 35px 0 16px;
    padding: 10px 18px;
    background: linear-gradient(90deg, rgba(0,243,255,0.08), transparent);
    border-left: 3px solid #00f3ff;
    border-radius: 0 8px 8px 0;
}
.faq-category span {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.85rem;
    font-weight: 800;
    color: #00f3ff;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* FAQ Item */
.faq-item {
    background: rgba(15,23,42,0.5);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: border-color 0.3s;
}
.faq-item:hover { border-color: rgba(0,243,255,0.2); }
.faq-item.open { border-color: rgba(0,243,255,0.35); }

.faq-q {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 18px 22px;
    cursor: pointer;
    font-weight: 600;
    color: #e2e8f0;
    font-size: 0.95rem;
    user-select: none;
    transition: background 0.2s;
}
.faq-q:hover { background: rgba(0,243,255,0.04); }
.faq-item.open .faq-q { color: #00f3ff; background: rgba(0,243,255,0.05); }

.faq-q-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0,243,255,0.1);
    border: 1px solid rgba(0,243,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.35s ease, background 0.3s;
    color: #00f3ff;
    font-size: 0.75rem;
}
.faq-item.open .faq-q-icon { transform: rotate(180deg); background: rgba(0,243,255,0.2); }

.faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.45s cubic-bezier(0.4,0,0.2,1), padding 0.3s;
    color: #94a3b8;
    font-size: 0.88rem;
    line-height: 1.85;
    padding: 0 22px;
}
.faq-item.open .faq-a {
    padding: 0 22px 20px;
}

/* No results */
#faqNoResults {
    display: none;
    text-align: center;
    padding: 60px 20px;
    color: #475569;
}
#faqNoResults i { font-size: 2.5rem; margin-bottom: 15px; display: block; }

/* Stats bar */
.faq-stats {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 40px;
}
.faq-stat {
    text-align: center;
    padding: 16px 28px;
    background: rgba(15,23,42,0.6);
    border: 1px solid rgba(0,243,255,0.1);
    border-radius: 12px;
}
.faq-stat .num { font-family: 'Rajdhani'; font-size: 2rem; font-weight: 800; color: #00f3ff; }
.faq-stat .lbl { font-size: 0.75rem; color: #64748b; letter-spacing: 1px; text-transform: uppercase; }
</style>

<div class="faq-page-wrap">

    
    <div class="faq-hero">
        <h1>Frequently Asked <span>Questions</span></h1>
        <p>Everything you need to know about the Digital Investigation Portal</p>
    </div>

    
    <div class="faq-stats">
        <?php
        $totalQ = 0;
        foreach ($faqs as $cat => $items) $totalQ += count($items);
        ?>
        <div class="faq-stat"><div class="num"><?= $totalQ ?></div><div class="lbl">Questions</div></div>
        <div class="faq-stat"><div class="num"><?= count($faqs) ?></div><div class="lbl">Categories</div></div>
        <div class="faq-stat"><div class="num">24/7</div><div class="lbl">Portal Access</div></div>
    </div>

    
    <div class="faq-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="faqSearch" placeholder="Search FAQs... (e.g. certificate, login, internship)">
    </div>

    
    <?php foreach ($faqs as $cat => $items): ?>
    <div class="faq-category-group">
        <div class="faq-category">
            <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
            <span><?= $cat ?></span>
        </div>
        <?php foreach ($items as $i => $faq): ?>
        <div class="faq-item" id="faq-<?= md5($faq['q']) ?>">
            <div class="faq-q" onclick="toggleFAQ(this)">
                <span><?= htmlspecialchars($faq['q'], ENT_QUOTES) ?></span>
                <div class="faq-q-icon"><i class="fa-solid fa-chevron-down"></i></div>
            </div>
            <div class="faq-a"><?= $faq['a'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div id="faqNoResults">
        <i class="fa-solid fa-circle-question"></i>
        <strong>No results found.</strong><br>
        Try different keywords or <a href="<?= BASE_URL ?>/intern/support.php" style="color:#00f3ff;">raise a support ticket</a>.
    </div>

</div>

<script>
function toggleFAQ(el) {
    const item = el.closest('.faq-item');
    const ans  = item.querySelector('.faq-a');
    const isOpen = item.classList.contains('open');

    // Close all others
    document.querySelectorAll('.faq-item.open').forEach(o => {
        o.classList.remove('open');
        o.querySelector('.faq-a').style.maxHeight = null;
    });

    if (!isOpen) {
        item.classList.add('open');
        ans.style.maxHeight = ans.scrollHeight + 'px';
        // Smooth scroll into view
        setTimeout(() => item.scrollIntoView({ behavior:'smooth', block:'nearest' }), 50);
    }
}

document.getElementById('faqSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let visibleCount = 0;

    document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.innerText.toLowerCase();
        const match = !q || text.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    // Show/hide category headers based on visible children
    document.querySelectorAll('.faq-category-group').forEach(grp => {
        const hasVisible = [...grp.querySelectorAll('.faq-item')].some(i => i.style.display !== 'none');
        grp.style.display = hasVisible ? '' : 'none';
    });

    document.getElementById('faqNoResults').style.display = visibleCount === 0 ? 'block' : 'none';
});

// Open FAQ from URL hash e.g. faq.php#faq-<md5>
document.addEventListener('DOMContentLoaded', () => {
    if (location.hash) {
        const el = document.querySelector(location.hash + ' .faq-q');
        if (el) { toggleFAQ(el); }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
