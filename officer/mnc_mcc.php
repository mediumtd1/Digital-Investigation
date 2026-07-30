<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer');
$portal = $is_admin ? 'admin' : 'officer';
$page_title = 'MNC MCC List | ' . SITE_NAME;

$mnc_data = json_decode('[{"mcc":"405","mnc":"1","operator":"Reliance (Used for Jio in some area)","circle":"Andhra Pradesh and Telangana"},{"mcc":"404","mnc":"1","operator":"Vi (Vodafone Idea)","circle":"Haryana"},{"mcc":"404","mnc":"2","operator":"AirTel","circle":"Punjab"},{"mcc":"405","mnc":"3","operator":"Reliance (Used for Jio in some area)","circle":"Bihar"},{"mcc":"404","mnc":"3","operator":"AirTel","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"4","operator":"Reliance (Used for Jio in some area)","circle":"Chennai"},{"mcc":"404","mnc":"4","operator":"Vi (Vodafone Idea)","circle":"Delhi & NCR"},{"mcc":"405","mnc":"5","operator":"Reliance (Used for Jio in some area)","circle":"Delhi & NCR"},{"mcc":"404","mnc":"5","operator":"Vi (Vodafone Idea)","circle":"Gujarat"},{"mcc":"405","mnc":"6","operator":"Reliance (Used for Jio in some area)","circle":"Gujarat"},{"mcc":"405","mnc":"7","operator":"Reliance (Used for Jio in some area)","circle":"Haryana"},{"mcc":"404","mnc":"7","operator":"Vi (Vodafone Idea)","circle":"Andhra Pradesh and Telangana"},{"mcc":"405","mnc":"8","operator":"Reliance (Used for Jio in some area)","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"9","operator":"Reliance (Used for Jio in some area)","circle":"Jammu & Kashmir"},{"mcc":"404","mnc":"9","operator":"Reliance (Used for Jio in some area)","circle":"Assam"},{"mcc":"405","mnc":"10","operator":"Reliance (Used for Jio in some area)","circle":"Karnataka"},{"mcc":"404","mnc":"10","operator":"AirTel","circle":"Delhi & NCR"},{"mcc":"405","mnc":"11","operator":"Reliance (Used for Jio in some area)","circle":"Kerala"},{"mcc":"404","mnc":"11","operator":"Vi (Vodafone Idea)","circle":"Delhi & NCR"},{"mcc":"405","mnc":"12","operator":"Reliance (Used for Jio in some area)","circle":"Kolkata"},{"mcc":"404","mnc":"12","operator":"Vi (Vodafone Idea)","circle":"Haryana"},{"mcc":"405","mnc":"13","operator":"Reliance (Used for Jio in some area)","circle":"Maharashtra & Goa"},{"mcc":"404","mnc":"13","operator":"Vi (Vodafone Idea)","circle":"Andhra Pradesh and Telangana"},{"mcc":"405","mnc":"14","operator":"Reliance (Used for Jio in some area)","circle":"Madhya Pradesh"},{"mcc":"404","mnc":"14","operator":"Vi (Vodafone Idea)","circle":"Punjab"},{"mcc":"405","mnc":"15","operator":"Reliance (Used for Jio in some area)","circle":"Mumbai"},{"mcc":"404","mnc":"15","operator":"Vi (Vodafone Idea)","circle":"Uttar Pradesh (East)"},{"mcc":"404","mnc":"16","operator":"Airtel","circle":"North East"},{"mcc":"405","mnc":"17","operator":"Reliance (Used for Jio in some area)","circle":"Orissa"},{"mcc":"404","mnc":"17","operator":"AIRCEL (Not in Use)","circle":"West Bengal"},{"mcc":"405","mnc":"18","operator":"Reliance (Used for Jio in some area)","circle":"Punjab"},{"mcc":"404","mnc":"18","operator":"Reliance (Used for Jio in some area)","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"19","operator":"Reliance (Used for Jio in some area)","circle":"Rajasthan"},{"mcc":"404","mnc":"19","operator":"Vi (Vodafone Idea)","circle":"Kerala"},{"mcc":"405","mnc":"20","operator":"Reliance (Used for Jio in some area)","circle":"Tamil Nadu"},{"mcc":"404","mnc":"20","operator":"Vi (Vodafone Idea)","circle":"Mumbai"},{"mcc":"405","mnc":"21","operator":"Reliance (Used for Jio in some area)","circle":"Uttar Pradesh (East)"},{"mcc":"404","mnc":"21","operator":"Loop Mobile (Not in Use)","circle":"Mumbai"},{"mcc":"405","mnc":"22","operator":"Reliance (Used for Jio in some area)","circle":"Uttar Pradesh (West)"},{"mcc":"404","mnc":"22","operator":"Vi (Vodafone Idea)","circle":"Maharashtra & Goa"},{"mcc":"405","mnc":"23","operator":"Reliance (Used for Jio in some area)","circle":"West Bengal"},{"mcc":"404","mnc":"24","operator":"Vi (Vodafone Idea)","circle":"Gujarat"},{"mcc":"405","mnc":"25","operator":"Airtel (Old TATA DOCOMO)","circle":"Andhra Pradesh and Telangana"},{"mcc":"404","mnc":"25","operator":"AIRCEL (Not in Use)","circle":"Bihar"},{"mcc":"405","mnc":"26","operator":"Airtel (Old TATA DOCOMO)","circle":"Assam"},{"mcc":"405","mnc":"27","operator":"Airtel (Old TATA DOCOMO)","circle":"Bihar\/Jharkhand"},{"mcc":"404","mnc":"27","operator":"Vi (Vodafone Idea)","circle":"Maharashtra & Goa"},{"mcc":"405","mnc":"28","operator":"Airtel (Old TATA DOCOMO)","circle":"Chennai"},{"mcc":"404","mnc":"28","operator":"AIRCEL (Not in Use)","circle":"Orissa"},{"mcc":"405","mnc":"29","operator":"Airtel (Old TATA DOCOMO)","circle":"Delhi"},{"mcc":"404","mnc":"29","operator":"AIRCEL (Not in Use)","circle":"Assam"},{"mcc":"405","mnc":"30","operator":"Airtel (Old TATA DOCOMO)","circle":"Gujarat"},{"mcc":"404","mnc":"30","operator":"Vi (Vodafone Idea)","circle":"Kolkata"},{"mcc":"405","mnc":"31","operator":"Airtel (Old TATA DOCOMO)","circle":"Haryana"},{"mcc":"404","mnc":"31","operator":"AirTel","circle":"Kolkata"},{"mcc":"405","mnc":"32","operator":"Airtel (Old TATA DOCOMO)","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"33","operator":"Airtel (Old TATA DOCOMO)","circle":"Jammu & Kashmir"},{"mcc":"405","mnc":"34","operator":"Airtel (Old TATA DOCOMO)","circle":"Karnataka"},{"mcc":"404","mnc":"34","operator":"BSNL","circle":"Haryana"},{"mcc":"405","mnc":"35","operator":"Airtel (Old TATA DOCOMO)","circle":"Kerala"},{"mcc":"404","mnc":"35","operator":"AIRCEL (Not in Use)","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"36","operator":"Airtel (Old TATA DOCOMO)","circle":"Kolkata"},{"mcc":"404","mnc":"36","operator":"Reliance (Used for Jio in some area)","circle":"Bihar & Jharkhand"},{"mcc":"405","mnc":"37","operator":"Airtel (Old TATA DOCOMO)","circle":"Maharashtra & Goa"},{"mcc":"404","mnc":"37","operator":"AIRCEL (Not in Use)","circle":"Jammu & Kashmir"},{"mcc":"405","mnc":"38","operator":"Airtel (Old TATA DOCOMO)","circle":"Madhya Pradesh"},{"mcc":"404","mnc":"38","operator":"BSNL","circle":"Assam"},{"mcc":"405","mnc":"39","operator":"Airtel (Old TATA DOCOMO)","circle":"Mumbai"},{"mcc":"404","mnc":"40","operator":"AirTel","circle":"Chennai"},{"mcc":"405","mnc":"41","operator":"Airtel (Old TATA DOCOMO)","circle":"Orissa"},{"mcc":"404","mnc":"41","operator":"AIRCEL (Not in Use)","circle":"Chennai"},{"mcc":"405","mnc":"42","operator":"Airtel (Old TATA DOCOMO)","circle":"Punjab"},{"mcc":"404","mnc":"42","operator":"AIRCEL (Not in Use)","circle":"Tamil Nadu"},{"mcc":"405","mnc":"43","operator":"Airtel (Old TATA DOCOMO)","circle":"Rajasthan"},{"mcc":"404","mnc":"43","operator":"Vi (Vodafone Idea)","circle":"Tamil Nadu"},{"mcc":"405","mnc":"44","operator":"Airtel (Old TATA DOCOMO)","circle":"Tamil Nadu including Chennai"},{"mcc":"404","mnc":"44","operator":"Vi (Vodafone Idea)","circle":"Karnataka"},{"mcc":"405","mnc":"45","operator":"Airtel (Old TATA DOCOMO)","circle":"Uttar Pradesh (E)"},{"mcc":"404","mnc":"45","operator":"Airtel","circle":"Karnataka"},{"mcc":"405","mnc":"46","operator":"Airtel (Old TATA DOCOMO)","circle":"Uttar Pradesh (W) & Uttarakhand"},{"mcc":"404","mnc":"46","operator":"Vi (Vodafone Idea)","circle":"Kerala"},{"mcc":"405","mnc":"47","operator":"Airtel (Old TATA DOCOMO)","circle":"West Bengal"},{"mcc":"404","mnc":"48","operator":"Dishnet Wireless","circle":"Unknown"},{"mcc":"404","mnc":"49","operator":"Airtel","circle":"Andhra Pradesh and Telangana"},{"mcc":"404","mnc":"50","operator":"Reliance (Used for Jio in some area)","circle":"North East"},{"mcc":"405","mnc":"51","operator":"AirTel","circle":"West Bengal"},{"mcc":"404","mnc":"51","operator":"BSNL","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"52","operator":"AirTel","circle":"Bihar & Jharkhand"},{"mcc":"404","mnc":"52","operator":"Reliance (Used for Jio in some area)","circle":"Orissa"},{"mcc":"405","mnc":"53","operator":"AirTel","circle":"Orissa"},{"mcc":"404","mnc":"53","operator":"BSNL","circle":"Punjab"},{"mcc":"405","mnc":"54","operator":"AirTel","circle":"Uttar Pradesh (East)"},{"mcc":"404","mnc":"54","operator":"BSNL","circle":"Uttar Pradesh (West)"},{"mcc":"405","mnc":"55","operator":"Airtel","circle":"Jammu & Kashmir"},{"mcc":"404","mnc":"55","operator":"BSNL","circle":"Uttar Pradesh (East)"},{"mcc":"405","mnc":"56","operator":"AirTel","circle":"Assam"},{"mcc":"404","mnc":"56","operator":"Vi (Vodafone Idea)","circle":"Uttar Pradesh (West)"},{"mcc":"404","mnc":"57","operator":"BSNL","circle":"Gujarat"},{"mcc":"404","mnc":"58","operator":"BSNL","circle":"Madhya Pradesh & Chhattisgarh"},{"mcc":"404","mnc":"59","operator":"BSNL","circle":"Rajasthan"},{"mcc":"404","mnc":"60","operator":"Vi (Vodafone Idea)","circle":"Rajasthan"},{"mcc":"404","mnc":"62","operator":"BSNL","circle":"Jammu & Kashmir"},{"mcc":"404","mnc":"64","operator":"BSNL","circle":"Chennai"},{"mcc":"405","mnc":"66","operator":"Vi (Vodafone Idea)","circle":"Uttar Pradesh (West)"},{"mcc":"404","mnc":"66","operator":"BSNL","circle":"Maharashtra & Goa"},{"mcc":"405","mnc":"67","operator":"Vi (Vodafone Idea)","circle":"West Bengal"},{"mcc":"404","mnc":"67","operator":"Reliance (Used for Jio in some area)","circle":"Madhya Pradesh & Chhattisgarh"},{"mcc":"404","mnc":"68","operator":"DOLPHIN","circle":"Delhi & NCR"},{"mcc":"404","mnc":"69","operator":"DOLPHIN","circle":"Mumbai"},{"mcc":"405","mnc":"70","operator":"Vi (Vodafone Idea)","circle":"Bihar & Jharkhand"},{"mcc":"404","mnc":"70","operator":"AirTel","circle":"Rajasthan"},{"mcc":"404","mnc":"71","operator":"BSNL","circle":"Karnataka (Bangalore)"},{"mcc":"404","mnc":"72","operator":"BSNL","circle":"Kerala"},{"mcc":"404","mnc":"73","operator":"BSNL","circle":"Andhra Pradesh and Telangana"},{"mcc":"404","mnc":"74","operator":"BSNL","circle":"West Bengal"},{"mcc":"404","mnc":"75","operator":"BSNL","circle":"Bihar"},{"mcc":"404","mnc":"76","operator":"BSNL","circle":"Orissa"},{"mcc":"404","mnc":"77","operator":"BSNL","circle":"North East"},{"mcc":"404","mnc":"78","operator":"Vi (Vodafone Idea)","circle":"Madhya Pradesh & Chattishgarh"},{"mcc":"404","mnc":"79","operator":"BSNL","circle":"Andaman Nicobar"},{"mcc":"404","mnc":"80","operator":"BSNL","circle":"Tamil Nadu"},{"mcc":"404","mnc":"81","operator":"BSNL","circle":"Kolkata"},{"mcc":"404","mnc":"82","operator":"Vi (Vodafone Idea)","circle":"Himachal Pradesh"},{"mcc":"404","mnc":"83","operator":"Reliance (Used for Jio in some area)","circle":"Kolkata"},{"mcc":"404","mnc":"84","operator":"Vi (Vodafone Idea)","circle":"Chennai"},{"mcc":"404","mnc":"85","operator":"Reliance (Used for Jio in some area)","circle":"West Bengal"},{"mcc":"404","mnc":"86","operator":"Vi (Vodafone Idea)","circle":"Karnataka"},{"mcc":"404","mnc":"87","operator":"Vi (Vodafone Idea)","circle":"Rajasthan"},{"mcc":"404","mnc":"88","operator":"Vi (Vodafone Idea)","circle":"Vodafone Punjab"},{"mcc":"404","mnc":"89","operator":"Vi (Vodafone Idea)","circle":"Uttar Pradesh (East)"},{"mcc":"404","mnc":"90","operator":"AirTel","circle":"Maharashtra"},{"mcc":"404","mnc":"91","operator":"AIRCEL (Not in Use)","circle":"Kolkata"},{"mcc":"404","mnc":"92","operator":"AirTel","circle":"Mumbai"},{"mcc":"404","mnc":"93","operator":"AirTel","circle":"Madhya Pradesh"},{"mcc":"404","mnc":"94","operator":"AirTel","circle":"Tamil Nadu"},{"mcc":"404","mnc":"95","operator":"AirTel","circle":"Kerala"},{"mcc":"404","mnc":"96","operator":"AirTel","circle":"Haryana"},{"mcc":"404","mnc":"97","operator":"AirTel","circle":"Uttar Pradesh (West)"},{"mcc":"404","mnc":"98","operator":"AirTel","circle":"Gujarat"},{"mcc":"405","mnc":"750","operator":"Vi (Vodafone Idea)","circle":"Jammu & Kashmir"},{"mcc":"405","mnc":"751","operator":"Vi (Vodafone Idea)","circle":"Assam"},{"mcc":"405","mnc":"752","operator":"Vi (Vodafone Idea)","circle":"Bihar & Jharkhand"},{"mcc":"405","mnc":"753","operator":"Vi (Vodafone Idea)","circle":"Orissa"},{"mcc":"405","mnc":"754","operator":"Vi (Vodafone Idea)","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"755","operator":"Vi (Vodafone Idea)","circle":"North East"},{"mcc":"405","mnc":"756","operator":"Vi (Vodafone Idea)","circle":"Madhya Pradesh & Chhattisgarh"},{"mcc":"405","mnc":"799","operator":"Vi (Vodafone Idea)","circle":"Mumbai"},{"mcc":"405","mnc":"800","operator":"AIRCEL (Not in Use)","circle":"Delhi & NCR"},{"mcc":"405","mnc":"801","operator":"AIRCEL (Not in Use)","circle":"Andhra Pradesh and Telangana"},{"mcc":"405","mnc":"803","operator":"AIRCEL (Not in Use)","circle":"Karnataka"},{"mcc":"405","mnc":"804","operator":"AIRCEL (Not in Use)","circle":"Maharashtra & Goa"},{"mcc":"405","mnc":"805","operator":"AIRCEL (Not in Use)","circle":"Mumbai"},{"mcc":"405","mnc":"806","operator":"AIRCEL (Not in Use)","circle":"Rajasthan"},{"mcc":"405","mnc":"809","operator":"AIRCEL (Not in Use)","circle":"Kerala"},{"mcc":"405","mnc":"810","operator":"AIRCEL (Not in Use)","circle":"Uttar Pradesh (East)"},{"mcc":"405","mnc":"811","operator":"AIRCEL (Not in Use)","circle":"Uttar Pradesh (West)"},{"mcc":"405","mnc":"818","operator":"Uninor","circle":"Uttar Pradesh (West)"},{"mcc":"405","mnc":"819","operator":"Uninor","circle":"Andhra Pradesh and Telangana"},{"mcc":"405","mnc":"820","operator":"Uninor","circle":"Karnataka"},{"mcc":"405","mnc":"821","operator":"Uninor","circle":"Kerala"},{"mcc":"405","mnc":"822","operator":"Uninor","circle":"Kolkata"},{"mcc":"405","mnc":"827","operator":"Videocon Datacom","circle":"Gujarat"},{"mcc":"405","mnc":"840","operator":"Jio","circle":"West Bengal"},{"mcc":"405","mnc":"845","operator":"Vi (Vodafone Idea)","circle":"Assam"},{"mcc":"405","mnc":"846","operator":"Vi (Vodafone Idea)","circle":"Jammu & Kashmir"},{"mcc":"405","mnc":"847","operator":"Vi (Vodafone Idea)","circle":"Karnataka"},{"mcc":"405","mnc":"848","operator":"Vi (Vodafone Idea)","circle":"Kolkata"},{"mcc":"405","mnc":"849","operator":"Vi (Vodafone Idea)","circle":"North East"},{"mcc":"405","mnc":"850","operator":"Vi (Vodafone Idea)","circle":"Orissa"},{"mcc":"405","mnc":"851","operator":"Vi (Vodafone Idea)","circle":"Punjab"},{"mcc":"405","mnc":"852","operator":"Vi (Vodafone Idea)","circle":"Tamil Nadu"},{"mcc":"405","mnc":"853","operator":"Vi (Vodafone Idea)","circle":"West Bengal"},{"mcc":"405","mnc":"854","operator":"Jio","circle":"Andhra Pradesh"},{"mcc":"405","mnc":"855","operator":"Jio","circle":"Assam"},{"mcc":"405","mnc":"856","operator":"Jio","circle":"Bihar"},{"mcc":"405","mnc":"857","operator":"Jio","circle":"Gujarat"},{"mcc":"405","mnc":"858","operator":"Jio","circle":"Haryana"},{"mcc":"405","mnc":"859","operator":"Jio","circle":"Himachal Pradesh"},{"mcc":"405","mnc":"860","operator":"Jio","circle":"Jammu & Kashmir"},{"mcc":"405","mnc":"861","operator":"Jio","circle":"Karnataka"},{"mcc":"405","mnc":"862","operator":"Jio","circle":"Kerala"},{"mcc":"405","mnc":"863","operator":"Jio","circle":"Madhya Pradesh"},{"mcc":"405","mnc":"864","operator":"Jio","circle":"Maharashtra"},{"mcc":"405","mnc":"865","operator":"Jio","circle":"North East"},{"mcc":"405","mnc":"866","operator":"Jio","circle":"Orissa"},{"mcc":"405","mnc":"867","operator":"Jio","circle":"Punjab"},{"mcc":"405","mnc":"868","operator":"Jio","circle":"Rajasthan"},{"mcc":"405","mnc":"869","operator":"Jio","circle":"Tamil Nadu (incl. Chennai)"},{"mcc":"405","mnc":"870","operator":"Jio","circle":"Uttar Pradesh (West)"},{"mcc":"405","mnc":"871","operator":"Jio","circle":"Uttar Pradesh (East)"},{"mcc":"405","mnc":"872","operator":"Jio","circle":"Delhi"},{"mcc":"405","mnc":"873","operator":"Jio","circle":"Kolkata"},{"mcc":"405","mnc":"874","operator":"Jio","circle":"Mumbai"},{"mcc":"405","mnc":"880","operator":"Uninor","circle":"West Bengal"},{"mcc":"405","mnc":"908","operator":"Vi (Vodafone Idea)","circle":"Andhra Pradesh and Telangana"},{"mcc":"405","mnc":"909","operator":"Vi (Vodafone Idea)","circle":"Delhi"},{"mcc":"405","mnc":"910","operator":"Vi (Vodafone Idea)","circle":"Haryana"},{"mcc":"405","mnc":"911","operator":"Vi (Vodafone Idea)","circle":"Maharashtra"},{"mcc":"405","mnc":"927","operator":"Uninor","circle":"Gujarat"},{"mcc":"405","mnc":"929","operator":"Uninor","circle":"Maharashtra"}]', true);

$search = isset($_GET['search']) ? trim(strtolower($_GET['search'])) : '';

if ($search !== '') {
    $filtered_data = array_filter($mnc_data, function($item) use ($search) {
        return (strpos(strtolower($item['mcc']), $search) !== false) ||
               (strpos(strtolower($item['mnc']), $search) !== false) ||
               (strpos(strtolower($item['operator']), $search) !== false) ||
               (strpos(strtolower($item['circle']), $search) !== false);
    });
} else {
    $filtered_data = $mnc_data;
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.dir-card {
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    border: 1px solid rgba(255,255,255,0.05);
}
.dir-card:hover {
    transform: translateY(-5px);
    border-color: rgba(0, 243, 255, 0.3) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
.card-header-accent {
    height: 4px;
    background: linear-gradient(90deg, #00f3ff, #eab308);
    border-radius: 4px 4px 0 0;
    margin: -20px -20px 15px -20px;
}
.operator-badge {
    color: #00f3ff;
    font-size: 0.7rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: rgba(0, 243, 255, 0.1);
    padding: 3px 8px;
    border-radius: 12px;
}
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content no-select">
        <header class="topbar">
            <div class="topbar-left">
                <h2>MNC MCC Database</h2>
            </div>
            <div class="topbar-right">
                <div class="status-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff;">
                    <i class="fa-solid fa-mobile-screen"></i> <?= count($filtered_data) ?> Records
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            <div class="glass-panel" style="margin-bottom: 25px; padding: 20px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" class="form-control" style="padding-left: 45px;" placeholder="Search by Operator, Circle, MCC, or MNC..." value="<?= esc($_GET['search'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-primary">Search</button>
                    <?php if($search): ?>
                        <a href="mnc_mcc.php" class="btn-primary" style="background: rgba(255,255,255,0.05); text-decoration: none; border: 1px solid var(--border-color);">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (empty($filtered_data)): ?>
                    <div class="glass-panel" style="text-align: center; padding: 50px;">
                        <i class="fa-solid fa-ghost" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted);">No records found matching your search.</p>
                    </div>
                <?php else: ?>
                    <div class="glass-panel" style="display: flex; padding: 12px 20px; background: rgba(0,0,0,0.4); border-bottom: 2px solid rgba(0, 243, 255, 0.3); font-size: 0.8rem; font-weight: bold; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                        <div style="flex: 2; min-width: 200px;">Operator / Circle</div>
                        <div style="flex: 1; min-width: 100px; text-align: center;">MCC</div>
                        <div style="flex: 1; min-width: 100px; text-align: center;">MNC</div>
                        <div style="width: 50px; text-align: center;">View</div>
                    </div>
                <?php foreach ($filtered_data as $item): ?>
                    <div class="glass-panel dir-card" style="padding: 15px 20px; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; display: flex; align-items: center; gap: 15px;" onclick='openMncCard(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                        
                        <div style="flex: 2; min-width: 200px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="color: #fff; font-size: 1.05rem; font-weight: 600;"><?= esc($item['operator']) ?></div>
                            <div style="color: #eab308; font-size: 0.85rem; font-weight: 500;"><i class="fa-solid fa-location-dot" style="margin-right:4px;"></i> <?= esc($item['circle']) ?></div>
                        </div>

                        <div style="flex: 1; min-width: 100px; text-align: center;">
                            <span style="font-family: monospace; font-size: 1rem; color: #fff; background: rgba(0,243,255,0.1); padding: 4px 12px; border-radius: 4px; border: 1px solid rgba(0,243,255,0.2);"><?= esc($item['mcc']) ?></span>
                        </div>

                        <div style="flex: 1; min-width: 100px; text-align: center;">
                            <span style="font-family: monospace; font-size: 1rem; color: #fff; background: rgba(234,179,8,0.1); padding: 4px 12px; border-radius: 4px; border: 1px solid rgba(234,179,8,0.2);"><?= esc($item['mnc']) ?></span>
                        </div>

                        <div style="width: 50px; text-align: center; color: var(--text-muted);">
                            <i class="fa-solid fa-expand"></i>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>

<div id="mncCardModal" class="cyber-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div class="modal-content" style="background: transparent; border: none; box-shadow: none; max-width: 500px; width: 90%;">
        <div class="visiting-card shadow-lg" style="background: linear-gradient(135deg, #1a1c2c 0%, #0d0e17 100%); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; width: 100%; position: relative; overflow: hidden; color: #fff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <span class="close" style="position: absolute; right: 20px; top: 15px; color: #fff; z-index: 10; cursor: pointer; font-size: 1.5rem;" onclick="closeMncCard()">&times;</span>
            
            <div class="card-header-accent" style="height: 10px; background: linear-gradient(90deg, #00f3ff, #eab308);"></div>
            
            <div class="card-body-content" style="padding: 40px;">
                <div style="text-align: right; margin-bottom: 20px;">
                    <div style="color: #00f3ff; font-weight: bold; letter-spacing: 1px; font-size: 0.8rem; text-transform: uppercase;">Network Provider</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 2px;">TELECOM DB</div>
                </div>

                <h2 id="mc_operator" style="color: #fff; margin: 0 0 5px 0; font-size: 1.6rem; letter-spacing: -0.5px;">Operator Name</h2>
                <div style="width: 40px; height: 3px; background: #00f3ff; margin-bottom: 20px;"></div>

                <div style="margin-bottom: 25px;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Service Area (Circle)</div>
                    <div id="mc_circle" style="color: #eab308; font-size: 1.2rem; font-weight: 600;">Circle Name</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border-left: 3px solid #00f3ff;">
                        <div style="color: #00f3ff; font-size: 0.7rem; text-transform: uppercase; margin-bottom: 5px;">MCC Code</div>
                        <div id="mc_mcc" style="font-size: 1.5rem; font-family: monospace; font-weight: bold;">404</div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border-left: 3px solid #eab308;">
                        <div style="color: #eab308; font-size: 0.7rem; text-transform: uppercase; margin-bottom: 5px;">MNC Code</div>
                        <div id="mc_mnc" style="font-size: 1.5rem; font-family: monospace; font-weight: bold;">10</div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer-branding" style="background: rgba(255, 255, 255, 0.03); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; color: rgba(255, 255, 255, 0.3); letter-spacing: 2px;">
                <span>DIGITAL INVESTIGATION PORTAL</span>
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
        </div>
    </div>
</div>
<script>
function openMncCard(data) {
    document.getElementById('mc_operator').innerText = data.operator;
    document.getElementById('mc_circle').innerText = data.circle;
    document.getElementById('mc_mcc').innerText = data.mcc;
    document.getElementById('mc_mnc').innerText = data.mnc;
    document.getElementById('mncCardModal').style.display = 'flex';
}
function closeMncCard() {
    document.getElementById('mncCardModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target.id === 'mncCardModal') closeMncCard();
    if (event.target.classList.contains('cyber-modal')) {
        let modals = document.getElementsByClassName('cyber-modal');
        for (let i = 0; i < modals.length; i++) {
            modals[i].style.display = 'none';
        }
    }
}
</script>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>