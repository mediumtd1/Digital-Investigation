<?php
require_once 'db_functions.php';
$tolls = getAllTolls();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toll Plaza Directory | Premium Search</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        #resultSection {
            display: none;
        }

        .detail-item {
            border-left: 3px solid var(--accent-gold);
            padding-left: 15px;
            margin-bottom: 25px;
        }

        .map-frame {
            border: 2px solid var(--accent-gold);
            border-radius: 20px;
            overflow: hidden;
            margin-top: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-road me-2"></i>TOLL<span>PLAZA</span> DIRECTORY
            </a>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Find Any <span>Toll Plaza</span> Instantly</h1>
            <!-- index.php line 34 -->
            <p class="mb-5" style="color: var(--accent-gold);">Search by Plaza Name or Code to view full details.</p>

            <div class="search-container mb-4">
                <form id="searchForm" onsubmit="event.preventDefault(); triggerSearch();">
                    <input type="text" id="tollSearch" class="search-input"
                        placeholder="Type Plaza Code/Name & Press Enter..." autocomplete="off">
                    <button type="submit"
                        style="background:none; border:none; position:absolute; top: 18px; right: 25px;">
                        <i class="fas fa-search" style="color: var(--accent-gold); font-size: 1.2rem;"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Detailed Result Section (Hidden initially) -->
    <div id="resultSection" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card bg-secondary-navy border-gold p-5" style="border-radius: 30px;">
                    <div class="row">
                        <div class="col-md-7">
                            <h2 id="resName" class="hero-title text-start mb-4"
                                style="font-size: 2.2rem; color: var(--accent-gold);"></h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <small class="text-muted d-block">PLAZA CODE</small>
                                        <strong id="resCode" class="fs-5"></strong>
                                    </div>
                                    <div class="detail-item">
                                        <small class="text-muted d-block">TYPE</small>
                                        <strong id="resType" class="fs-5"></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <small class="text-muted d-block">STATE & DISTRICT</small>
                                        <strong id="resLocation" class="fs-5"></strong>
                                    </div>
                                    <div class="detail-item">
                                        <small class="text-muted d-block">CONCESSIONAIRE</small>
                                        <strong id="resConcessionaire" class="fs-5"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-center d-flex align-items-center justify-content-center">
                            <div class="p-4" style="border: 2px dashed var(--accent-gold); border-radius: 20px;">
                                <i class="fas fa-shield-alt fa-4x text-gold mb-3"></i>
                                <h5 class="text-gold">Official Data</h5>
                                <p class="small text-muted">Authentic information verified by NHAI Directory Services.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map at the bottom (Scroll Down) -->
                <div class="map-frame">
                    <iframe id="resMap" width="100%" height="450" frameborder="0" style="border:0"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <div id="noResult" class="container py-5 text-center d-none">
        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
        <h3>Toll Plaza not found. Please check the code or name.</h3>
    </div>

    <!-- Hidden Grid for JS matching only -->
    <div id="hiddenGrid" class="d-none">
        <?php foreach ($tolls as $toll): ?>
            <div class="toll-data" data-code="<?= htmlspecialchars($toll['plaza_code']) ?>"
                data-name="<?= htmlspecialchars($toll['plaza_name']) ?>"
                data-type="<?= htmlspecialchars($toll['plaza_type']) ?>"
                data-state="<?= htmlspecialchars($toll['state']) ?>"
                data-district="<?= htmlspecialchars($toll['district']) ?>"
                data-concessionaire="<?= htmlspecialchars($toll['concessionaire']) ?>"
                data-lat="<?= htmlspecialchars($toll['lat']) ?>" data-lng="<?= htmlspecialchars($toll['lng']) ?>">
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>

</html>