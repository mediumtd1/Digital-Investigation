<?php
require_once 'config/db.php';

$id = $_GET['id'] ?? 0;
// No session check as per new requirement
$stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->execute([$id]);
$notice = $stmt->fetch();

if (!$notice) {
    die("Notice not found.");
}

$data = json_decode($notice['content_json'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Notice | CyberNotice</title>
    <style>
        @media print {
            @page { margin: 0; size: A4 portrait; }
            .no-print { display: none !important; }
            * { box-sizing: border-box !important; }
            
            html, body { 
                background: white !important; 
                color: black !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                width: auto !important;
                max-width: none !important;
                font-size: 11pt !important; 
                line-height: 1.3 !important; 
            }
            .notice-paper { 
                box-shadow: none !important; 
                border: none !important; 
                padding: 0.5cm 1.5cm !important; 
                margin: 0 !important;
                min-height: auto !important; 
                width: auto !important;
                max-width: none !important;
            }
            .letterhead { margin-bottom: 10px !important; padding-bottom: 5px !important; border-bottom: 1px solid #000 !important; }
            .letterhead h1 { font-size: 15pt !important; margin: 0 !important; }
            .letterhead p { font-size: 10pt !important; margin: 2px 0 !important; }
            .ref-row { margin-bottom: 10px !important; font-size: 11pt !important; }
            .heading { margin: 12px 0 !important; font-size: 12pt !important; padding: 0 !important; }
            .recipient { margin-bottom: 10px !important; font-size: 11pt !important; line-height: 1.2 !important; }
            .content { font-size: 11pt !important; line-height: 1.3 !important; }
            .content p { margin-bottom: 6px !important; }
            .target-box { padding: 10px !important; margin: 10px 0 !important; }
            .target-box p { margin-top: 2px !important; margin-bottom: 0 !important; }
            .target-box ul { margin-top: 2px !important; margin-bottom: 0 !important; }
            .action-box { padding: 8px !important; margin-top: 8px !important; border-width: 2px !important; font-size: 10pt !important; }
            .legal-box { padding-left: 10px !important; margin: 10px 0 !important; font-size: 9pt !important; }
            .legal-box p { margin-bottom: 2px !important; line-height: 1.2 !important; }
            .signature { margin-top: 15px !important; font-size: 11pt !important; line-height: 1.2 !important; }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            color: #111;
            max-width: 850px;
            margin: 0 auto;
            padding: 40px;
            background: #e5e7eb;
        }
        .notice-paper {
            background: white;
            padding: 70px 80px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            min-height: 1000px;
            position: relative;
        }
        .letterhead {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .letterhead h1 { font-size: 20pt; margin: 0; font-weight: bold; }
        .letterhead p { font-size: 11pt; margin: 3px 0; }
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            font-size: 10pt;
            margin-top: 5px;
            font-style: italic;
        }
        .ref-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 40px; 
            font-weight: bold;
            font-size: 12pt;
        }
        .heading { 
            text-align: center; 
            text-decoration: underline; 
            font-weight: bold; 
            margin: 40px 0;
            font-size: 14pt;
        }
        .recipient { margin-bottom: 30px; font-size: 12pt; }
        .content { text-align: justify; font-size: 12pt; }
        .signature { 
            margin-top: 80px; 
            float: right; 
            text-align: center; 
            width: 300px; 
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">Print Notice</button>
    <a href="index.php" class="no-print" style="position: fixed; top: 20px; left: 20px; color: #4b5563; text-decoration: none; font-weight: 600;">&larr; Back to Draft</a>

    <div class="notice-paper">
        <div class="letterhead">
            <h1><?php echo strtoupper($notice['office_name']); ?></h1>
            <p><?php echo $notice['office_address']; ?></p>
            <div class="contact-info">
                <span>Email: <?php echo $notice['office_email']; ?></span>
                <span>Tel: <?php echo $notice['office_phone']; ?></span>
            </div>
        </div>

        <div class="ref-row">
            <div>Ref No: <?php echo $notice['reference_no']; ?></div>
            <div>Date: <?php echo date('d/m/Y', strtotime($notice['created_at'])); ?></div>
        </div>

        <div class="recipient">
            To,<br>
            <strong>The Nodal Officer / Authorized Signatory</strong><br>
            <?php echo $notice['recipient_name']; ?><br>
        </div>

        <div class="heading">
            <?php if (!empty($data['block_account']) && $data['block_account'] == "1"): ?>
                NOTICE UNDER SECTION 94 BNSS AND 79(3)(B) OF IT ACT
            <?php elseif (!empty($data['category']) && strtolower($data['category']) === 'social media'): ?>
                NOTICE UNDER SECTION 94 OF BHARATIYA NAGARIK SURAKSHA SANHITA (BNSS), 2023
            <?php else: ?>
                NOTICE UNDER SECTION 94 OF BHARATIYA NAGARIK SURAKSHA SANHITA (BNSS), 2023
            <?php endif; ?>
        </div>

        <div class="content">
            <p>Whereas, an investigation is being conducted by the undersigned in a criminal matter related to <strong><?php echo $notice['notice_type']; ?></strong>.</p>

            <p>During the course of investigation, it has been revealed that certain electronic data/information associated with your platform is essential for the purpose of the said investigation and for the discovery of facts related to the case.</p>

            <p>In exercise of powers conferred under <strong>Section 94 of BNSS, 2023</strong> (corresponding to Section 91 of CrPC), you are hereby directed to provide the following details associated with the target mentioned below:</p>

            <div class="target-box" style="background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0;"><strong>Target Identifier:</strong> [<?php echo htmlspecialchars($data['identifier_type'] ?? 'ID'); ?>] <?php echo htmlspecialchars($data['target_id'] ?? 'N/A'); ?></p>
                <p style="margin: 10px 0 0 0;"><strong>Period of Interest:</strong> <?php echo $data['period'] ?? 'N/A'; ?></p>
                
                <?php if(!empty($data['requirements'])): ?>
                <div style="margin: 15px 0 0 0;">
                    <strong>Information Required:</strong>
                    <ul style="margin: 5px 0 0 20px; padding: 0;">
                        <?php foreach($data['requirements'] as $req): ?>
                            <li><?php echo $req; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if(!empty($data['additional'])): ?>
                <p style="margin: 15px 0 0 0;"><strong>Special Instructions:</strong> <?php echo nl2br(htmlspecialchars($data['additional'])); ?></p>
                <?php endif; ?>

                <?php if(!empty($data['block_account']) && $data['block_account'] == "1"): ?>
                <div class="action-box" style="margin: 15px 0 0 0; padding: 10px; background-color: #fee2e2; border-left: 4px solid #ef4444; border-radius: 4px; color: #991b1b;">
                    <strong>Action Required:</strong> You are hereby directed to expeditiously block the aforementioned URL/Account under Section 79(3)(b) of the Information Technology Act, 2000.
                </div>
                <?php endif; ?>
            </div>

            <p>The requested information/logs should be provided in electronic format within the stipulated time frame. You are also requested to preserve the said data until further directions.</p>
            
            <div class="legal-box" style="font-size: 10pt; border-left: 3px solid #000; padding-left: 15px; margin: 25px 0;">
                <p style="margin: 0 0 8px 0;"><strong>Relevant Legal Provisions:</strong></p>
                <p style="margin: 0 0 8px 0; text-align: justify;"><strong>Section 94, Bharatiya Nagarik Suraksha Sanhita (BNSS), 2023:</strong> Empowers a police officer making an investigation to issue a written order to any person or entity to produce any document or other thing (including electronic records) which is necessary or desirable for the purposes of any investigation, inquiry, trial or other proceeding.</p>
                <?php if((!empty($data['block_account']) && $data['block_account'] == "1") || (!empty($data['category']) && strtolower($data['category']) === 'social media')): ?>
                <p style="margin: 0; text-align: justify;"><strong>Section 79(3)(b), Information Technology Act, 2000:</strong> Mandates that an intermediary, upon receiving actual knowledge or on being notified by the appropriate Government or its agency that any information, data or communication link residing in or connected to a computer resource controlled by the intermediary is being used to commit the unlawful act, shall expeditiously remove or disable access to that material.</p>
                <?php endif; ?>
            </div>

            <p>Non-compliance with this legal notice may attract penal action under the relevant provisions of the Bharatiya Nyaya Sanhita (BNS) and Information Technology Act.</p>
        </div>

        <div class="signature">
            <br><br>
            (Signature & Seal)<br>
            <strong><?php echo $notice['io_name']; ?></strong><br>
            <?php echo $notice['io_rank']; ?><br>
            <?php echo $notice['office_name']; ?>
        </div>

        <div style="clear: both;"></div>
    </div>
</body>
</html>
