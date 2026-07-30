<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';

$page_title = "Draft New Notice";

// Fetch Cases
$cases_stmt = $pdo->prepare("SELECT * FROM cases WHERE investigating_officer_id = ? AND status = 'Open'");
$cases_stmt->execute([$_SESSION['user_id']]);
$cases = $cases_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Notice | CyberNotice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hidden { display: none; }
        .step-indicator { display: flex; gap: 20px; margin-bottom: 2rem; }
        .step { opacity: 0.5; font-weight: 500; }
        .step.active { opacity: 1; color: var(--police-blue); border-bottom: 2px solid var(--police-blue); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="form-container">
            <div class="step-indicator">
                <span class="step active" id="step1-indicator">1. Case & Type</span>
                <span class="step" id="step2-indicator">2. Recipient Details</span>
                <span class="step" id="step3-indicator">3. Target Info</span>
            </div>

            <form id="noticeForm" action="notices/save_notice.php" method="POST">
                <!-- Step 1: Case & Type -->
                <div id="step1">
                    <div class="form-group">
                        <label for="case_id">Select Case File (FIR)</label>
                        <select name="case_id" id="case_id" required>
                            <option value="">-- Choose Case --</option>
                            <?php foreach ($cases as $case): ?>
                                <option value="<?php echo $case['id']; ?>"><?php echo $case['fir_number']; ?> - <?php echo $case['complainant_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notice_type">Legal Provision / Notice Type</label>
                        <select name="notice_type" id="notice_type" required onchange="updateFields()">
                            <option value="">-- Select Type --</option>
                            <option value="94_BNSS_BANK">Section 94 BNSS - Bank Statement/Freeze</option>
                            <option value="94_BNSS_ISP">Section 94 BNSS - ISP/Telecom (IPDR/CDR)</option>
                            <option value="94_BNSS_SOCIAL">Section 94 BNSS - Social Media/Email Data</option>
                            <option value="179_BNSS_WITNESS">Section 179 BNSS - Summons to Witness</option>
                        </select>
                    </div>

                    <button type="button" class="btn" onclick="nextStep(2)">Next Step</button>
                </div>

                <!-- Step 2: Recipient Details -->
                <div id="step2" class="hidden">
                    <div class="form-group">
                        <label for="recipient_name">Recipient Name / Organization</label>
                        <input type="text" name="recipient_name" id="recipient_name" placeholder="e.g. Nodal Officer, HDFC Bank" required>
                    </div>
                    <div class="form-group">
                        <label for="recipient_address">Full Address</label>
                        <textarea name="recipient_address" id="recipient_address" rows="3" placeholder="Office address..."></textarea>
                    </div>
                    <button type="button" class="btn" style="background: var(--text-dim);" onclick="prevStep(1)">Back</button>
                    <button type="button" class="btn" onclick="nextStep(3)">Next Step</button>
                </div>

                <!-- Step 3: Target Info (Dynamic) -->
                <div id="step3" class="hidden">
                    <div id="dynamic-fields">
                        <!-- Fields populated via JS -->
                    </div>

                    <button type="button" class="btn" style="background: var(--text-dim);" onclick="prevStep(2)">Back</button>
                    <button type="submit" class="btn">Generate Notice</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function nextStep(step) {
            document.getElementById('step' + (step-1)).classList.add('hidden');
            document.getElementById('step' + step).classList.remove('hidden');
            document.getElementById('step' + (step-1) + '-indicator').classList.remove('active');
            document.getElementById('step' + step + '-indicator').classList.add('active');
        }

        function prevStep(step) {
            document.getElementById('step' + (step+1)).classList.add('hidden');
            document.getElementById('step' + step).classList.remove('hidden');
            document.getElementById('step' + (step+1) + '-indicator').classList.remove('active');
            document.getElementById('step' + step + '-indicator').classList.add('active');
        }

        function updateFields() {
            const type = document.getElementById('notice_type').value;
            const container = document.getElementById('dynamic-fields');
            let html = '';

            if (type === '94_BNSS_BANK') {
                html = `
                    <div class="form-group">
                        <label>Account / Target Identifier</label>
                        <div style="display: flex; gap: 10px;">
                            <select name="data[identifier_type]" style="width: 30%;" required>
                                <option value="Bank Account">Bank Account</option>
                                <option value="UPI ID">UPI ID</option>
                                <option value="Wallet ID">Wallet ID</option>
                                <option value="Phone Number">Phone Number</option>
                                <option value="Card Number">Card Number</option>
                                <option value="Transaction ID">Transaction ID</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" name="data[target_id]" placeholder="Enter the account details..." style="width: 70%;" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Period (From - To)</label>
                        <input type="text" name="data[period]" placeholder="e.g. 01/01/2026 to 15/05/2026">
                    </div>
                    <div class="form-group">
                        <label>Action Required</label>
                        <select name="data[action]">
                            <option value="statement">Provide Statement</option>
                            <option value="freeze">Debit Freeze Account</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                `;
            } else if (type === '94_BNSS_ISP') {
                html = `
                    <div class="form-group">
                        <label>Account / Target Identifier</label>
                        <div style="display: flex; gap: 10px;">
                            <select name="data[identifier_type]" style="width: 30%;" required>
                                <option value="IP Address">IP Address</option>
                                <option value="Phone Number">Phone Number</option>
                                <option value="Email ID">Email ID</option>
                                <option value="URL">URL</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" name="data[target_id]" placeholder="Enter the target details..." style="width: 70%;" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Timestamp (IST)</label>
                        <input type="text" name="data[timestamp]" placeholder="e.g. 2026-05-16 14:30:00">
                    </div>
                `;
            } else if (type === '94_BNSS_SOCIAL') {
                html = `
                    <div class="form-group" id="block_account_group">
                        <label style="display: flex; align-items: center; gap: 10px; font-weight: bold; cursor: pointer; background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; margin-bottom: 0;">
                            <input type="checkbox" name="data[block_account]" value="1" style="width: auto; transform: scale(1.2);"> 
                            Block Suspected Profile / URL under Sec 79(3)b IT Act
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Account / Target Identifier</label>
                        <div style="display: flex; gap: 10px;">
                            <select name="data[identifier_type]" style="width: 30%;" required>
                                <option value="Username">Username</option>
                                <option value="Phone Number">Phone Number</option>
                                <option value="Email ID">Email ID</option>
                                <option value="IP Address">IP Address</option>
                                <option value="Unique ID">Unique ID</option>
                                <option value="Transaction ID">Transaction ID</option>
                                <option value="URL">URL</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" name="data[target_id]" placeholder="Enter the target details..." style="width: 70%;" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Period of Incident</label>
                        <input type="text" name="data[period]" placeholder="e.g. 1st Jan to 15th Jan 2026">
                    </div>
                    <div class="form-group">
                        <label>Additional Details</label>
                        <textarea name="data[additional]" rows="3" placeholder="Any specific requirements..."></textarea>
                    </div>
                `;
            } else if (type === '179_BNSS_WITNESS') {
                html = `
                    <div class="form-group">
                        <label>Witness Name</label>
                        <input type="text" name="data[witness_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Appearance Date & Time</label>
                        <input type="datetime-local" name="data[appearance_time]" required>
                    </div>
                `;
            }

            container.innerHTML = html;
        }
    </script>
</body>
</html>
