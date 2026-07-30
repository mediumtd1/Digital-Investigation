let cy;
let currentData = null;
let centralId = ''; // Can be address or txid
let searchType = 'address'; // 'address' or 'transaction'
let btcPrice = 0;

async function fetchBtcPrice() {
    try {
        const response = await fetch('https://api.coindesk.com/v1/bpi/currentprice.json');
        const data = await response.json();
        btcPrice = data.bpi.USD.rate_float;
    } catch (e) {
        console.error('Failed to fetch BTC price', e);
        btcPrice = 65000; // Fallback
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // UI Elements
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel = document.getElementById('filter-panel');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const closeDetails = document.getElementById('close-details');
    const detailsPanel = document.getElementById('details-panel');
    const loader = document.getElementById('loader');

    // Initialize
    fetchBtcPrice();
    initCytoscape();

    // Event Listeners
    searchBtn.addEventListener('click', () => performSearch(searchInput.value));
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch(searchInput.value);
    });

    filterToggle.addEventListener('click', () => {
        filterPanel.classList.toggle('hidden');
    });

    closeDetails.addEventListener('click', () => {
        detailsPanel.classList.remove('open');
    });

    applyFiltersBtn.addEventListener('click', () => {
        if (currentData) renderVisualization(currentData);
    });

    // Export Listeners
    document.getElementById('export-csv').addEventListener('click', exportCSV);
    document.getElementById('export-json').addEventListener('click', exportJSON);
    document.getElementById('export-pdf').addEventListener('click', exportPDF);
});

async function performSearch(query) {
    if (!query) return;
    
    showLoader(true);
    centralId = query;
    
    // Detect type: TxID is exactly 64 chars hex. Taproot addresses can be ~62 chars.
    searchType = (query.length === 64 && /^[0-9a-fA-F]+$/.test(query)) ? 'transaction' : 'address';

    try {
        const response = await fetch(`api.php?action=${searchType}&id=${query}`);
        const data = await response.json();

        if (data.error) {
            alert(data.error);
        } else {
            currentData = data;
            renderVisualization(data);
            updateHistory(data);
        }
    } catch (error) {
        console.error('Search failed:', error);
        alert('Failed to fetch data. Please check your connection.');
    } finally {
        showLoader(false);
    }
}

function initCytoscape() {
    cy = cytoscape({
        container: document.getElementById('cy-container'),
        style: [
            {
                selector: 'node',
                style: {
                    'shape': 'rectangle',
                    'background-color': '#1e293b',
                    'label': 'data(label)',
                    'color': '#f8fafc',
                    'font-size': '10px',
                    'text-valign': 'center',
                    'text-halign': 'center',
                    'width': '160px',
                    'height': '60px',
                    'text-wrap': 'wrap',
                    'text-max-width': '140px',
                    'border-width': 1,
                    'border-color': '#475569',
                    'border-opacity': 0.5,
                    'transition-property': 'background-color, border-color, width, height',
                    'transition-duration': '0.3s'
                }
            },
            {
                selector: 'node[type="central"]',
                style: {
                    'background-color': '#10b981',
                    'border-color': '#fff',
                    'border-opacity': 0.8,
                    'width': '180px',
                    'height': '70px',
                    'font-weight': 'bold',
                    'font-size': '11px'
                }
            },
            {
                selector: 'node[direction="inbound"]',
                style: { 'background-color': '#a855f7' }
            },
            {
                selector: 'node[direction="outbound"]',
                style: { 'background-color': '#ec4899' }
            },
            {
                selector: 'edge',
                style: {
                    'width': 2,
                    'line-color': '#475569',
                    'target-arrow-color': '#475569',
                    'target-arrow-shape': 'triangle',
                    'curve-style': 'taxi',
                    'taxi-direction': 'horizontal',
                    'opacity': 0.6,
                    'label': 'data(amount)',
                    'font-size': '8px',
                    'color': '#94a3b8',
                    'text-background-opacity': 1,
                    'text-background-color': '#0f172a',
                    'text-background-padding': '2px'
                }
            },
            {
                selector: 'edge[direction="inbound"]',
                style: { 'line-color': '#a855f7', 'target-arrow-color': '#a855f7' }
            },
            {
                selector: 'edge[direction="outbound"]',
                style: { 'line-color': '#ec4899', 'target-arrow-color': '#ec4899' }
            }
        ],
        layout: { name: 'preset' }
    });

    // Tooltip Logic
    const tooltip = document.getElementById('graph-tooltip');

    cy.on('mouseover', 'node', (evt) => {
        const node = evt.target;
        const data = node.data();
        if (!data.tooltipData) return;

        let html = `<h4>${data.nodeType === 'transaction' ? 'Transaction Details' : 'Wallet Details'}</h4>`;
        Object.entries(data.tooltipData).forEach(([key, val]) => {
            html += `<div class="tooltip-row"><label>${key}</label><span>${val}</span></div>`;
        });

        tooltip.innerHTML = html;
        tooltip.style.display = 'block';
    });

    cy.on('mousemove', 'node', (evt) => {
        tooltip.style.left = (evt.renderedPosition.x + 20) + 'px';
        tooltip.style.top = (evt.renderedPosition.y + 20) + 'px';
    });

    cy.on('mouseout', 'node', () => {
        tooltip.style.display = 'none';
    });

    cy.on('tap', 'node', (evt) => {
        const node = evt.target;
        const data = node.data();
        
        if (data.fullId && data.id !== centralId) {
            document.getElementById('search-input').value = data.fullId;
            performSearch(data.fullId);
        }
        showDetails(data);
    });

    cy.on('tap', 'edge', (evt) => {
        showDetails(evt.target.data());
    });
}

function renderVisualization(data) {
    cy.elements().remove();
    const elements = [];
    const centerX = 0;
    const centerY = 0;
    const xOffset = 600;
    const ySpacing = 120;

    if (searchType === 'address') {
        // WALLET CENTRIC MODE
        // Central Node: Wallet
        elements.push({
            data: {
                id: centralId,
                label: `WALLET\n${centralId.substring(0, 12)}...`,
                fullId: centralId,
                type: 'central',
                nodeType: 'wallet',
                tooltipData: {
                    'Address': centralId,
                    'Total Received': (data.total_received / 100000000).toFixed(4) + ' BTC',
                    'Total Sent': (data.total_sent / 100000000).toFixed(4) + ' BTC',
                    'Final Balance': (data.final_balance / 100000000).toFixed(4) + ' BTC',
                    'Transactions': data.n_tx
                }
            },
            position: { x: centerX, y: centerY }
        });

        // Split transactions into Inbound and Outbound
        const inboundTXs = [];
        const outboundTXs = [];

        data.txs.forEach(tx => {
            const isIncoming = tx.out.some(o => o.addr === centralId);
            const amountBTC = Math.abs(calculateBalanceChange(tx, centralId)) / 100000000;
            const amountUSD = (amountBTC * btcPrice).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
            
            const nodeData = {
                id: tx.hash,
                fullId: tx.hash,
                nodeType: 'transaction',
                amount: amountBTC.toFixed(4) + ' BTC',
                label: `TX: ${tx.hash.substring(0, 8)}...\n${amountBTC.toFixed(4)} BTC\n${amountUSD}`,
                tooltipData: {
                    'TXID': tx.hash,
                    'Time': new Date(tx.time * 1000).toLocaleString(),
                    'Inputs': tx.inputs.length,
                    'Outputs': tx.out.length,
                    'Amount': amountBTC.toFixed(6) + ' BTC',
                    'USD Value': amountUSD,
                    'Fee': (calculateFee(tx) / 100000000).toFixed(6) + ' BTC'
                }
            };

            if (isIncoming) inboundTXs.push(nodeData);
            else outboundTXs.push(nodeData);
        });

        // Render Inbound (Left)
        inboundTXs.slice(0, 10).forEach((tx, i, arr) => {
            const startY = -((arr.length - 1) * ySpacing) / 2;
            elements.push({
                data: { ...tx, direction: 'inbound' },
                position: { x: centerX - xOffset, y: startY + (i * ySpacing) }
            });
            elements.push({ data: { id: `edge-${tx.id}`, source: tx.id, target: centralId, direction: 'inbound', amount: tx.amount } });
        });

        // Render Outbound (Right)
        outboundTXs.slice(0, 10).forEach((tx, i, arr) => {
            const startY = -((arr.length - 1) * ySpacing) / 2;
            elements.push({
                data: { ...tx, direction: 'outbound' },
                position: { x: centerX + xOffset, y: startY + (i * ySpacing) }
            });
            elements.push({ data: { id: `edge-${tx.id}`, source: centralId, target: tx.id, direction: 'outbound', amount: tx.amount } });
        });

    } else {
        // TRANSACTION CENTRIC MODE
        // Central Node: Transaction
        const amountBTC = data.out.reduce((s, o) => s + o.value, 0) / 100000000;
        const amountUSD = (amountBTC * btcPrice).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        const fee = calculateFee(data);

        elements.push({
            data: {
                id: centralId,
                label: `TRANSACTION\n${centralId.substring(0, 12)}...\n${amountBTC.toFixed(4)} BTC`,
                fullId: centralId,
                type: 'central',
                nodeType: 'transaction',
                tooltipData: {
                    'TXID': centralId,
                    'Time': new Date(data.time * 1000).toLocaleString(),
                    'Amount': amountBTC.toFixed(6) + ' BTC',
                    'USD': amountUSD,
                    'Fee': (fee / 100000000).toFixed(6) + ' BTC',
                    'Fee USD': ((fee / 100000000) * btcPrice).toFixed(2) + ' USD'
                }
            },
            position: { x: centerX, y: centerY }
        });

        // Inputs (Left)
        data.inputs.slice(0, 10).forEach((input, i, arr) => {
            const addr = input.prev_out ? input.prev_out.addr : 'Unknown';
            const val = input.prev_out ? (input.prev_out.value / 100000000).toFixed(4) : '0';
            const startY = -((arr.length - 1) * ySpacing) / 2;
            const nodeId = `in-${addr}-${i}`;
            
            elements.push({
                data: {
                    id: nodeId,
                    fullId: addr,
                    label: `INPUT\n${addr.substring(0, 10)}...\n${val} BTC`,
                    direction: 'inbound',
                    nodeType: 'wallet',
                    tooltipData: { 'Address': addr, 'Value': val + ' BTC' }
                },
                position: { x: centerX - xOffset, y: startY + (i * ySpacing) }
            });
            elements.push({ data: { id: `edge-${nodeId}`, source: nodeId, target: centralId, direction: 'inbound', amount: val + ' BTC' } });
        });

        // Outputs (Right)
        data.out.slice(0, 10).forEach((output, i, arr) => {
            const addr = output.addr || 'Unknown';
            const val = (output.value / 100000000).toFixed(4);
            const startY = -((arr.length - 1) * ySpacing) / 2;
            const nodeId = `out-${addr}-${i}`;

            elements.push({
                data: {
                    id: nodeId,
                    fullId: addr,
                    label: `OUTPUT\n${addr.substring(0, 10)}...\n${val} BTC`,
                    direction: 'outbound',
                    nodeType: 'wallet',
                    tooltipData: { 'Address': addr, 'Value': val + ' BTC' }
                },
                position: { x: centerX + xOffset, y: startY + (i * ySpacing) }
            });
            elements.push({ data: { id: `edge-${nodeId}`, source: centralId, target: nodeId, direction: 'outbound', amount: val + ' BTC' } });
        });
    }

    cy.add(elements);
    cy.animate({
        fit: { eles: cy.elements(), padding: 50 },
        duration: 800,
        easing: 'cubic-bezier(0.42, 0, 0.58, 1)'
    });
}

function calculateFee(tx) {
    const inputSum = tx.inputs.reduce((s, i) => s + (i.prev_out ? i.prev_out.value : 0), 0);
    const outputSum = tx.out.reduce((s, o) => s + o.value, 0);
    return Math.max(0, inputSum - outputSum);
}


function updateHistory(data) {
    const receivedList = document.getElementById('received-tx-list');
    const sentList = document.getElementById('sent-tx-list');
    const totalReceivedVal = document.getElementById('total-received-val');
    const totalSentVal = document.getElementById('total-sent-val');
    const totalReceivedCount = document.getElementById('total-received-count');
    const totalSentCount = document.getElementById('total-sent-count');
    const targetAddressDisplay = document.getElementById('target-address-display');
    const targetBalanceDisplay = document.getElementById('target-balance-display');
    
    receivedList.innerHTML = '';
    sentList.innerHTML = '';

    let totalReceived = 0;
    let totalSent = 0;
    let countReceived = 0;
    let countSent = 0;

    const dateOptions = {
        hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short', year: 'numeric'
    };

    if (searchType === 'address') {
        // WALLET CENTRIC VIEW
        targetAddressDisplay.textContent = centralId;
        targetBalanceDisplay.textContent = (data.final_balance / 100000000).toFixed(4) + ' BTC';
        targetBalanceDisplay.style.display = 'block';

        if (data.txs) {
            data.txs.forEach((tx) => {
                const change = calculateBalanceChange(tx, centralId);
                const amountBTC = Math.abs(change / 100000000);
                const date = new Date(tx.time * 1000).toLocaleString('en-GB', dateOptions);

                let displayAddr = 'Unknown';
                if (change >= 0) {
                    if (tx.inputs.length > 0 && tx.inputs[0].prev_out) displayAddr = tx.inputs[0].prev_out.addr;
                } else {
                    const otherOut = tx.out.find(o => o.addr !== centralId);
                    if (otherOut) displayAddr = otherOut.addr;
                }

                const txHtml = createTxCardHtml(tx.hash, amountBTC, date, displayAddr);

                if (change >= 0) {
                    receivedList.innerHTML += txHtml;
                    totalReceived += Math.abs(change);
                    countReceived++;
                } else {
                    sentList.innerHTML += txHtml;
                    totalSent += Math.abs(change);
                    countSent++;
                }
            });
        }
    } else {
        // TRANSACTION CENTRIC VIEW
        targetAddressDisplay.textContent = 'Transaction: ' + centralId.substring(0, 24) + '...';
        targetBalanceDisplay.style.display = 'none';
        const date = new Date(data.time * 1000).toLocaleString('en-GB', dateOptions);

        // Inputs are "Received" into this transaction
        data.inputs.forEach((input, i) => {
            if (input.prev_out) {
                const amountBTC = input.prev_out.value / 100000000;
                const addr = input.prev_out.addr || 'Unknown';
                receivedList.innerHTML += createTxCardHtml(data.hash, amountBTC, date, addr);
                totalReceived += input.prev_out.value;
                countReceived++;
            }
        });

        // Outputs are "Sent" from this transaction
        data.out.forEach((output, i) => {
            const amountBTC = output.value / 100000000;
            const addr = output.addr || 'Unknown';
            sentList.innerHTML += createTxCardHtml(data.hash, amountBTC, date, addr);
            totalSent += output.value;
            countSent++;
        });
    }

    // Update Totals
    totalReceivedVal.textContent = (totalReceived / 100000000).toFixed(4) + ' BTC';
    totalSentVal.textContent = (totalSent / 100000000).toFixed(4) + ' BTC';
    totalReceivedCount.textContent = `${countReceived} ${searchType === 'address' ? 'Transactions' : 'Inputs'}`;
    totalSentCount.textContent = `${countSent} ${searchType === 'address' ? 'Transactions' : 'Outputs'}`;

    if (countReceived === 0) receivedList.innerHTML = '<div class="empty-state">No incoming data</div>';
    if (countSent === 0) sentList.innerHTML = '<div class="empty-state">No outgoing data</div>';
}

function createTxCardHtml(hash, amountBTC, date, displayAddr) {
    return `
        <div class="tx-card" onclick="showDetails(${JSON.stringify({hash: hash, amount: amountBTC.toFixed(6) + ' BTC', timestamp: 0, type: 'tx'}).replace(/"/g, '&quot;')})">
            <div class="tx-card-top">
                <span class="tx-address" title="${displayAddr}">${displayAddr.substring(0, 8)}...${displayAddr.slice(-4)}</span>
                <span class="tx-amount-badge">${amountBTC.toFixed(4)} BTC</span>
            </div>
            <div class="tx-card-bottom">${date}</div>
        </div>
    `;
}

function calculateBalanceChange(tx, addr) {
    let inputSum = 0;
    let outputSum = 0;

    tx.inputs.forEach(input => {
        if (input.prev_out && input.prev_out.addr === addr) {
            inputSum += input.prev_out.value;
        }
    });

    tx.out.forEach(output => {
        if (output.addr === addr) {
            outputSum += output.value;
        }
    });

    return outputSum - inputSum;
}

function showDetails(data) {
    const panel = document.getElementById('details-panel');
    const content = document.getElementById('details-content');
    
    let html = '';
    
    if (data.nodeType === 'wallet') {
        // Wallet Details
        html = `
            <div class="detail-row">
                <label>Wallet Address</label>
                <span class="value-mono">${data.fullId}</span>
            </div>
            <div class="detail-row">
                <label>Role</label>
                <p>${data.type === 'central' ? 'Investigation Target' : 'Related Wallet'}</p>
            </div>
            <div class="detail-row">
                <label>Total Received</label>
                <p>${(currentData.total_received / 100000000).toFixed(6)} BTC</p>
            </div>
            <div class="detail-row">
                <label>Final Balance</label>
                <p style="font-weight: bold; color: var(--success)">${(currentData.final_balance / 100000000).toFixed(6)} BTC</p>
            </div>
            <a href="https://www.blockchain.com/explorer/addresses/btc/${data.fullId}" target="_blank" class="btn-link">
                View in Explorer <i data-lucide="external-link"></i>
            </a>
        `;
    } else if (data.nodeType === 'transaction') {
        // Transaction Details
        html = `
            <div class="detail-row">
                <label>Transaction Hash</label>
                <span class="value-mono">${data.fullId}</span>
            </div>
            <div class="detail-row">
                <label>Amount</label>
                <p style="font-weight: bold; color: ${data.direction === 'inbound' ? '#a855f7' : '#ec4899'}">
                    ${data.amount || 'N/A'}
                </p>
            </div>
            <a href="https://www.blockchain.com/explorer/transactions/btc/${data.fullId}" target="_blank" class="btn-link">
                Full TX Analysis <i data-lucide="external-link"></i>
            </a>
        `;
    }

    content.innerHTML = html;
    panel.classList.add('open');
    if (window.lucide) lucide.createIcons();
}

function showLoader(show) {
    const loader = document.getElementById('loader');
    if (show) loader.classList.remove('hidden');
    else loader.classList.add('hidden');
}

// Export Functions
function exportCSV() {
    if (!currentData) return;
    let csv = 'Hash,Date,Amount (BTC),Status\n';
    currentData.txs.forEach(tx => {
        const change = calculateBalanceChange(tx, centralId) / 100000000;
        const date = new Date(tx.time * 1000).toISOString();
        csv += `${tx.hash},${date},${change},${change >= 0 ? 'Incoming' : 'Outgoing'}\n`;
    });
    downloadFile(csv, `btc_report_${centralId}.csv`, 'text/csv');
}

function exportJSON() {
    if (!currentData) return;
    downloadFile(JSON.stringify(currentData, null, 2), `btc_report_${centralId}.json`, 'application/json');
}

async function exportPDF() {
    if (!currentData) return;
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text('Bitcoin Forensic Investigation Report', 20, 20);
    
    doc.setFontSize(10);
    doc.text(`Target Address: ${centralId}`, 20, 30);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 20, 35);
    
    doc.setFontSize(14);
    doc.text('Transaction Summary', 20, 50);
    
    let y = 60;
    currentData.txs.slice(0, 15).forEach((tx, i) => {
        if (y > 270) { doc.addPage(); y = 20; }
        const change = calculateBalanceChange(tx, centralId) / 100000000;
        doc.setFontSize(8);
        doc.text(`${tx.hash.substring(0, 32)}... | ${change.toFixed(6)} BTC`, 20, y);
        y += 10;
    });

    doc.save(`btc_report_${centralId}.pdf`);
}

function downloadFile(content, fileName, contentType) {
    const a = document.createElement('a');
    const file = new Blob([content], { type: contentType });
    a.href = URL.createObjectURL(file);
    a.download = fileName;
    a.click();
}
