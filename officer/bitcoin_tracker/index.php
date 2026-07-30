<?php
// Get investigator IP address
$investigator_ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1 (Local)' : $_SERVER['REMOTE_ADDR'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin Tracer | Forensic Transaction Visualization</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Fonts: Outfit for a modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Cytoscape.js for graph visualization -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.26.0/cytoscape.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="dark-theme">
    <header class="top-bar">
        <div class="logo">
            <i data-lucide="shield-check"></i>
            <span>BitcoinTracer <small>Forensic Suite</small></span>
        </div>
        <div class="search-container">
            <div class="input-wrapper">
                <i data-lucide="search"></i>
                <input type="text" id="search-input" placeholder="Enter Bitcoin Address or TxID..." autocomplete="off">
                <button id="search-btn">Analyze</button>
            </div>
        </div>
        <div class="user-info">
            <span class="badge">Investigator IP: <?php echo htmlspecialchars($investigator_ip); ?></span>
        </div>
    </header>

    <main class="dashboard-layout">
        <!-- Left Panel: Transaction History -->
        <aside class="left-panel">
            <div class="panel-header">
                <h3><i data-lucide="history"></i> Ledger History</h3>
                <div class="filter-toggle" id="filter-toggle" title="Filter by Amount/Date">
                    <i data-lucide="filter"></i>
                </div>
            </div>

            <!-- Filter Controls (Collapsible) -->
            <div class="filter-container hidden" id="filter-panel">
                <div class="filter-group">
                    <label>Min Amount (BTC)</label>
                    <input type="number" id="filter-amount" step="0.0001" placeholder="0.00">
                </div>
                <div class="filter-group">
                    <label>Date Range</label>
                    <input type="date" id="filter-date-start">
                    <input type="date" id="filter-date-end">
                </div>
                <button class="btn-secondary" id="apply-filters">Apply Filters</button>
            </div>

            <div class="target-address-banner">
                <div class="banner-top">
                    <span class="label">Target Address</span>
                    <span class="balance" id="target-balance-display">0.0000 BTC</span>
                </div>
                <div class="address" id="target-address-display">Analyze an address to begin</div>
            </div>

            <div class="summary-dashboard">
                <div class="summary-card received">
                    <div class="summary-label">Total Received</div>
                    <div class="summary-value" id="total-received-val">0 BTC</div>
                    <div class="summary-subtext" id="total-received-count">0 Transactions</div>
                </div>
                <div class="summary-card sent">
                    <div class="summary-label">Total Sent</div>
                    <div class="summary-value" id="total-sent-val">0 BTC</div>
                    <div class="summary-subtext" id="total-sent-count">0 Transactions</div>
                </div>
            </div>

            <div class="history-divider">
                <span>Transaction History</span>
            </div>

            <div class="split-history-container">
                <div class="history-column">
                    <div class="column-header received">Received</div>
                    <div class="tx-list" id="received-tx-list">
                        <div class="empty-state">No transactions</div>
                    </div>
                </div>
                <div class="history-column">
                    <div class="column-header sent">Sent</div>
                    <div class="tx-list" id="sent-tx-list">
                        <div class="empty-state">No transactions</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Panel: Graph Visualization -->
        <section class="graph-panel">
            <div id="cy-container"></div>
            
            <div class="graph-controls">
                <button title="Zoom In" onclick="cy.zoom(cy.zoom() * 1.2)"><i data-lucide="zoom-in"></i></button>
                <button title="Zoom Out" onclick="cy.zoom(cy.zoom() * 0.8)"><i data-lucide="zoom-out"></i></button>
                <button title="Reset View" onclick="cy.fit()"><i data-lucide="maximize"></i></button>
                <div class="divider"></div>
                <button title="Export PDF" id="export-pdf"><i data-lucide="file-text"></i></button>
                <button title="Export CSV" id="export-csv"><i data-lucide="table"></i></button>
                <button title="Export JSON" id="export-json"><i data-lucide="code"></i></button>
            </div>

            <div class="graph-legend">
                <div class="legend-item"><span class="node central"></span> Central Address</div>
                <div class="legend-item"><span class="node inbound"></span> Incoming Wallets</div>
                <div class="legend-item"><span class="node outbound"></span> Outgoing Wallets</div>
            </div>
        </section>

        <!-- Right Side Panel: Details -->
        <aside class="details-panel" id="details-panel">
            <div class="panel-header">
                <h3>Transaction Details</h3>
                <button class="close-btn" id="close-details">&times;</button>
            </div>
            <div class="details-content" id="details-content">
                <p class="empty-state">Select a node or edge to view details.</p>
            </div>
        </aside>
    </main>

    <div id="loader" class="loader-overlay hidden">
        <div class="spinner"></div>
        <p>Fetching Blockchain Data...</p>
    </div>

    <div id="graph-tooltip" class="graph-tooltip"></div>

    <script src="app.js"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
