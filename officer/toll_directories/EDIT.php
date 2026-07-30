<?php
session_start();
require_once 'db_functions.php';

if (isset($_POST['login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = "Invalid credentials!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: EDIT.php");
    exit();
}

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$notification = "";
if ($is_logged_in) {
    if (isset($_POST['save_toll'])) {
        $toll_data = [
            'plaza_code' => $_POST['plaza_code'],
            'plaza_name' => $_POST['plaza_name'],
            'plaza_type' => $_POST['plaza_type'],
            'state' => $_POST['state'],
            'district' => $_POST['district'],
            'concessionaire' => $_POST['concessionaire'],
            'lat' => $_POST['lat'],
            'lng' => $_POST['lng']
        ];

        if (!empty($_POST['old_code'])) {
            global $pdo;
            $sql = "UPDATE toll SET 
                    `COL 1` = :code, `COL 2` = :name, `COL 3` = :type, 
                    `COL 4` = :state, `COL 5` = :district, `COL 6` = :concessionaire, 
                    `COL 7` = :location WHERE `COL 1` = :old_code";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([
                'code' => $toll_data['plaza_code'],
                'name' => $toll_data['plaza_name'],
                'type' => $toll_data['plaza_type'],
                'state' => $toll_data['state'],
                'district' => $toll_data['district'],
                'concessionaire' => $toll_data['concessionaire'],
                'location' => $toll_data['lat'] . ',' . $toll_data['lng'],
                'old_code' => $_POST['old_code']
            ])) {
                $notification = "Record updated successfully!";
            }
        } else {

            if (addToll($toll_data)) {
                $notification = "New toll record added!";
            }
        }
    }

    if (isset($_GET['delete_id'])) {
        if (deleteToll($_GET['delete_id'])) {
            $notification = "Record deleted successfully!";
        }
    }

    if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if ($_FILES['csv_file']['size'] > 0) {
            $imported_count = importCSV($file);
            if ($imported_count !== false) {
                $notification = "Successfully imported $imported_count records!";
            }
        }
    }
}

$tolls = getAllTolls();

// FETCH DATA FOR EDITING
$edit_toll = null;
if (isset($_GET['edit_id'])) {
    $edit_toll = getTollById($_GET['edit_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Force high visibility for inputs */
        .form-control, .form-control:focus {
            color: #ffffff !important;
            background-color: #233554 !important;
            border: 1px solid var(--accent-gold);
        }
        .form-control::placeholder {
            color: #8892b0 !important;
            opacity: 1;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container text-center">
            <span class="navbar-brand m-auto"><i class="fas fa-lock me-2"></i>SECURE ADMIN PANEL</span>
            <?php if ($is_logged_in): ?><a href="EDIT.php?logout=1" class="btn btn-danger btn-sm">Logout</a><?php endif; ?>
        </div>
    </nav>

    <div class="container py-5">
        <?php if (!$is_logged_in): ?>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card bg-secondary-navy border-gold p-4">
                        <h3 class="text-gold text-center mb-4">Login</h3>
                        <form method="POST">
                            <input type="text" name="username" class="form-control mb-3" placeholder="ID" required>
                            <input type="password" name="password" class="form-control mb-4" placeholder="Pass" required>
                            <button type="submit" name="login" class="btn btn-gold w-100">Access</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($notification): ?><div class="alert alert-success"><?= $notification ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card bg-secondary-navy border-gold p-4 mb-4">
                        <h4 class="text-gold"><?= $edit_toll ? 'Edit Record' : 'Add New Toll' ?></h4>
                        <form method="POST">
                            <input type="hidden" name="old_code" value="<?= $edit_toll['plaza_code'] ?? '' ?>">
                            <div class="mb-2"><input type="text" name="plaza_code" class="form-control" placeholder="Plaza Code" value="<?= $edit_toll['plaza_code'] ?? '' ?>" required></div>
                            <div class="mb-2"><input type="text" name="plaza_name" class="form-control" placeholder="Plaza Name" value="<?= $edit_toll['plaza_name'] ?? '' ?>" required></div>
                            <div class="mb-2"><input type="text" name="plaza_type" class="form-control" placeholder="Type" value="<?= $edit_toll['plaza_type'] ?? '' ?>"></div>
                            <div class="mb-2"><input type="text" name="state" class="form-control" placeholder="State" value="<?= $edit_toll['state'] ?? '' ?>"></div>
                            <div class="mb-2"><input type="text" name="district" class="form-control" placeholder="District" value="<?= $edit_toll['district'] ?? '' ?>"></div>
                            <div class="mb-2"><input type="text" name="concessionaire" class="form-control" placeholder="Concessionaire" value="<?= $edit_toll['concessionaire'] ?? '' ?>"></div>
                            <div class="row g-2">
                                <div class="col"><input type="text" name="lat" class="form-control mb-2" placeholder="Lat" value="<?= $edit_toll['lat'] ?? '' ?>"></div>
                                <div class="col"><input type="text" name="lng" class="form-control mb-2" placeholder="Lng" value="<?= $edit_toll['lng'] ?? '' ?>"></div>
                            </div>
                            <button type="submit" name="save_toll" class="btn btn-gold w-100"><?= $edit_toll ? 'Update Toll' : 'Save Toll' ?></button>
                            <?php if ($edit_toll): ?><a href="EDIT.php" class="btn btn-sm btn-outline-danger w-100 mt-2">Cancel</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="card bg-secondary-navy border-gold p-4">
                        <h4 class="text-gold">Bulk Upload</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" name="csv_file" class="form-control mb-2" required>
                            <button type="submit" name="import_csv" class="btn btn-outline-gold w-100">Import</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="admin-table">
                        <table class="table table-dark table-hover">
                            <thead><tr><th>CODE</th><th>NAME</th><th>ACTIONS</th></tr></thead>
                            <tbody>
                                <?php foreach ($tolls as $t): ?>
                                <tr style="border-bottom: 1px solid rgba(212,175,55,0.1);">
                                    <td><span class="text-gold"><?= $t['plaza_code'] ?></span></td>
                                    <td><?= $t['plaza_name'] ?></td>
                                    <td>
                                        <a href="EDIT.php?edit_id=<?= $t['plaza_code'] ?>" class="text-info me-3" title="Edit"><i class="fas fa-edit fa-lg"></i></a>
                                        <i class="fas fa-trash text-danger fa-lg cursor-pointer" onclick="confirmDelete('<?= $t['plaza_code'] ?>')" title="Delete" style="cursor:pointer"></i>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>function confirmDelete(id){ if(confirm('Are you sure you want to delete this record?')) window.location.href='EDIT.php?delete_id='+id; }</script>
</body>
</html>
