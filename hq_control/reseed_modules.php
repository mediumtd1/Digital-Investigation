<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

// Force re-seed: truncate and insert corrected curriculum
$pdo->exec("DELETE FROM internship_modules");

$seed = [
    [1,1,"Intro to Cyber Crime","Understanding the landscape of modern digital threats and their impact.",0],
    [1,2,"Networking for Investigators","TCP/IP, Ports, and Protocol analysis fundamentals.",0],
    [1,3,"Linux for Forensics","Mastering the command line for forensic data retrieval.",0],
    [1,4,"OSINT Tools & Techniques","Gathering open-source intelligence from the web.",0],
    [1,5,"Web Security 101","How websites work and common vulnerabilities.",0],
    [1,6,"Weekly Assignment","Practical foundational assessment and submission.",0],
    [1,7,"Week 1 Live Q&A","Interact with senior investigators and mentors.",0],

    [2,1,"Digital Forensics, Chain of Custody & Evidence Handling","Core principles of evidence preservation, chain of custody, and forensic workflow.",0],
    [2,2,"Hash Value Calculation & WordPress Security","Understanding data integrity through hashing and securing CMS platforms.",0],
    [2,3,"Week 2 - Day 3 Module","Standard investigative module - Week 2.",0],
    [2,4,"Week 2 - Day 4 Module","Standard investigative module - Week 2.",0],
    [2,5,"Week 2 - Day 5 Module","Standard investigative module - Week 2.",0],
    [2,6,"Week 2 - Day 6 Module","Standard investigative module - Week 2.",0],
    [2,7,"Cloud Forensics Risks & Shared Responsibility Model","Investigating crimes in cloud environments (AWS/Azure/GCP).",0],

    [3,1,"Week 3 - Day 1 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,2,"Week 3 - Day 2 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,3,"Week 3 - Day 3 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,4,"Week 3 - Day 4 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,5,"Week 3 - Day 5 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,6,"Week 3 - Day 6 Lab Session","Hands-on practical training with live investigative tools.",0],
    [3,7,"Week 3 - Day 7 Lab Session","Hands-on practical training with live investigative tools.",0],

    [4,1,"Advanced Forensic Investigation Summary","Finalizing comprehensive investigation techniques and case study reviews.",0],
    [4,2,"Week 4 - Day 2 Continuation","Continuing legal and technical wrap-up of case analysis.",0],
    [4,3,"Cyber Laws (IT Act 2000 & Amendments)","Understanding the Information Technology Act and its legal relevance.",0],
    [4,4,"DPDP Act & Intermediary Guidelines","Digital Personal Data Protection Act and social media platform compliance.",0],
    [4,5,"AI, VR & AR in Cybersecurity & Forensics","Future of investigations: Artificial Intelligence and Immersive Technologies.",0],
    [4,6,"Final Capstone Assignment","Submit your comprehensive investigative report to HQ Command.",0],
    [4,7,"Capture The Flag (CTF) Challenge","The ultimate practical challenge for all graduating interns.",0],
    [5,1,"Project Submission","Final Project Submission Day. Upload your complete project files.",0],
    [5,2,"Final Presentation","Present your findings and project to the board of investigators.",0],
];

$stmt = $pdo->prepare("INSERT INTO internship_modules (week,day,title,description,is_visible) VALUES (?,?,?,?,?)");
foreach ($seed as $row) $stmt->execute($row);

echo "<script>alert('30 modules re-seeded! Redirecting...'); window.location='manage_modules.php';</script>";
?>
