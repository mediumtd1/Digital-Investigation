<?php
require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$statement = $pdo->query('SELECT * FROM police_stations ORDER BY id DESC');
$stations = $statement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Police Stations</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0F172A; color: #E2E8F0; margin: 0; }
        .page { max-width: 1100px; margin: 0 auto; padding: 32px; }
        header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        header h1 { margin: 0; font-size: 2rem; color: #E0F2FE; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 12px 18px; background: #2563EB; color: #FFFFFF; text-decoration: none; border-radius: 8px; transition: background 0.2s ease; }
        .button:hover { background: #1D4ED8; }
        .card { background: #111827; border: 1px solid #1E293B; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 16px 14px; text-align: left; border-bottom: 1px solid #1E293B; }
        th { color: #A5B4FC; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        tr:hover { background: rgba(59, 130, 246, 0.08); }
        .status-empty { color: #94A3B8; }
        a.action-link { color: #93C5FD; text-decoration: none; }
        a.action-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div>
                <h1>Police Stations</h1>
                <p style="color:#CBD5E1; margin: 8px 0 0;">Browse station records with a clean dark blue interface.</p>
            </div>
            <div class="top-actions">
                <a class="button" href="create.php">Add Station</a>
                <a class="button" href="import.php">Import CSV</a>
                <a class="button" href="search.php">Search</a>
                <a class="button" href="atm.php">ATM Lookup</a>
            </div>
        </header>
        <?php if (count($stations) === 0): ?>
        <p>No police station records found. Use the button above to add one.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>State</th><th>District</th><th>Police Station</th><th>Contact</th><th>Location</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stations as $station): ?>
                    <tr>
                        <td><?= htmlspecialchars($station['id']) ?></td>
                        <td><?= htmlspecialchars($station['state']) ?></td>
                        <td><?= htmlspecialchars($station['district']) ?></td>
                        <td><?= htmlspecialchars($station['police_station']) ?></td>
                        <td><?= htmlspecialchars($station['contact']) ?></td>
                        <td><?= htmlspecialchars($station['location']) ?></td>
                        <td>
                            <a href="update.php?id=<?= urlencode($station['id']) ?>">Edit</a> |
                            <a href="delete.php?id=<?= urlencode($station['id']) ?>" onclick="return confirm('Delete this station?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>
