<?php
require_once 'config/db.php';
// Fetch company data for JS from directories table
$companies_stmt = $pdo->query("SELECT category, operator_name AS name, nodal_name AS nodal_officer, address, id FROM directories WHERE category != '' AND operator_name != ''");
$company_data = $companies_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberNotice | Investigative Drafting Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const companyDB = <?php echo json_encode($company_data); ?>;
    </script>
</head>
<body>
    <nav class="top-nav">
        <a href="index.php" class="nav-box active">
            <i class="fas fa-edit"></i>
            <span>Draft Notice</span>
        </a>
    </nav>

    <main class="main-content">
        <div class="form-container">
            <div class="step-indicator" id="indicator">
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
                <div class="step-dot"></div>
                <div class="step-dot"></div>
            </div>

            <form id="noticeForm" action="notices/save_notice.php" method="POST">
                
                <!-- Step 1: Office & IO Details -->
                <div id="step1">
                    <h2 class="form-title">Officer & Office Details</h2>
                    <p class="form-subtitle">Information for the letterhead and signature</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Investigating Officer (IO) Name</label>
                            <input type="text" name="io_name" placeholder="e.g. Rajesh Kumar" required>
                        </div>
                        <div class="form-group">
                            <label>Rank</label>
                            <input type="text" name="io_rank" placeholder="e.g. Sub-Inspector" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Police Station / Office Name</label>
                        <input type="text" name="office_name" placeholder="e.g. Cyber Crime Cell, New Delhi" required>
                    </div>
                    <div class="form-group">
                        <label>Office Address</label>
                        <textarea name="office_address" rows="2" placeholder="Full address of the station..."></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Email ID</label>
                            <input type="email" name="office_email" placeholder="official@police.gov.in">
                        </div>
                        <div class="form-group">
                            <label>Telephone / Fax</label>
                            <input type="text" name="office_phone" placeholder="011-23456789">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Continue to Case Info</button>
                    </div>
                </div>

                <!-- Step 2: Ref & Date -->
                <div id="step2" class="hidden">
                    <h2 class="form-title">Reference & Date</h2>
                    <p class="form-subtitle">Formal identifiers for the letter</p>
                    
                    <div class="form-group">
                        <label>Reference Number (Dotted for pen entry)</label>
                        <input type="text" name="reference_no" value="CN/......../<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Letter Date</label>
                        <input type="text" value="<?php echo date('d/m/Y'); ?>" readonly style="background: rgba(15, 23, 42, 0.3);">
                    </div>
                    <div class="form-group">
                        <label>FIR / Case Number (Optional)</label>
                        <input type="text" name="fir_number" placeholder="e.g. FIR 123/2026">
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Continue to Category</button>
                    </div>
                </div>

                <!-- Step 3: Category & Company -->
                <div id="step3" class="hidden">
                    <h2 class="form-title">Recipient Category</h2>
                    <p class="form-subtitle">Choose who you are sending the notice to</p>
                    
                    <div class="category-grid">
                        <?php
                        $icon_map = [
                            'bank' => 'fas fa-university',
                            'social' => 'fas fa-share-alt',
                            'shopping' => 'fas fa-shopping-cart',
                            'ecommerce' => 'fas fa-shopping-cart',
                            'tsp' => 'fas fa-broadcast-tower',
                            'isp' => 'fas fa-broadcast-tower',
                            'telecom' => 'fas fa-phone',
                            'payment' => 'fas fa-credit-card',
                            'wallet' => 'fas fa-wallet',
                            'crypto' => 'fas fa-coins',
                            'domain' => 'fas fa-globe',
                            'hosting' => 'fas fa-server'
                        ];

                        foreach (array_keys($company_data) as $cat):
                            $cat_lower = strtolower($cat);
                            $icon = 'fas fa-ellipsis-h'; // Default
                            foreach ($icon_map as $key => $ico) {
                                if (strpos($cat_lower, $key) !== false) {
                                    $icon = $ico;
                                    break;
                                }
                            }
                        ?>
                            <div class="category-card" onclick="selectCategory('<?= esc($cat) ?>', this)">
                                <i class="<?= $icon ?>"></i>
                                <span><?= esc($cat) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="category" id="selected_category" required>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Select Company / Platform</label>
                            <select name="company_dropdown" id="company_name" onchange="autoPopulateDetails()">
                                <option value="">-- First Select Category --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nodal Officer / Recipient Name</label>
                            <input type="text" name="recipient_name" id="recipient_name" placeholder="Automatic populate..." required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Company Address</label>
                        <textarea name="recipient_address" id="recipient_address" rows="3" placeholder="Automatic populate..."></textarea>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">Continue to Crime Info</button>
                    </div>
                </div>

                <!-- Step 4: Crime & Dynamic Fields -->
                <div id="step4" class="hidden">
                    <h2 class="form-title">Crime Details</h2>
                    <p class="form-subtitle">Specific information about the investigation</p>
                    
                    <div class="form-group">
                        <label>Type of Crime</label>
                        <select name="crime_type" id="crime_type">
                            <option value="">-- Select Crime Type --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Specific Information Required (Select all that apply)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(15, 23, 42, 0.3); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="Registration Details (Name, Mobile, Email)" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">Registration Details</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="IP Logs with Timestamp (IST)" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">IP Logs & Timestamps</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="KYC Documents / Identity Proof" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">KYC Documents</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="Linked Bank Account / UPI Details" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">Linked Bank/UPI</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="Login / Logout History" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">Login/Logout History</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="requirements[]" value="Device Information (IMEI/MAC)" style="width: auto;"> 
                                <span style="font-size: 0.9rem;">Device Info</span>
                            </label>
                        </div>
                    </div>

                    <div id="dynamic-fields">
                        <div class="form-group" id="block_account_group" style="display: none;">
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
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(3)">Back</button>
                        <button type="submit" class="btn btn-primary">Draft Final Letter</button>
                    </div>
                </div>

            </form>
        </div>
    </main>

    <script src="assets/js/categories.js"></script>
    <script>
        let currentStep = 1;
        
        function nextStep(step) {
            document.getElementById('step' + currentStep).classList.add('hidden');
            document.getElementById('step' + step).classList.remove('hidden');
            
            const dots = document.querySelectorAll('.step-dot');
            dots[step-1].classList.add('active');
            
            currentStep = step;
        }

        function prevStep(step) {
            document.getElementById('step' + currentStep).classList.add('hidden');
            document.getElementById('step' + step).classList.remove('hidden');
            
            const dots = document.querySelectorAll('.step-dot');
            dots[currentStep-1].classList.remove('active');
            
            currentStep = step;
        }

        function selectCategory(cat, el) {
            document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('selected_category').value = cat;
            populateCompanies(cat);
            populateCrimes(cat);

            const blockGroup = document.getElementById('block_account_group');
            if (blockGroup) {
                if (cat === 'Social Media') {
                    blockGroup.style.display = 'block';
                } else {
                    blockGroup.style.display = 'none';
                    const blockCheckbox = document.querySelector('input[name="data[block_account]"]');
                    if (blockCheckbox) blockCheckbox.checked = false;
                }
            }
        }

        function updateCrimeDropdown() {
            // Already handled by populateCrimes in selectCategory, 
            // but can add company-specific crime filters here if needed.
        }
    </script>
</body>
</html>
