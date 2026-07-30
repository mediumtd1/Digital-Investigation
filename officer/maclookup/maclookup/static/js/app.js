document.addEventListener('DOMContentLoaded', () => {
    updateClock();
    setInterval(updateClock, 1000);
    fetchDashboardStats();

    // Single Lookup
    document.getElementById('lookup-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button');
        const mac = document.getElementById('mac-input').value.trim();
        if (!mac) return;

        // Loading state
        const originalBtnText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ANALYZING...';
        btn.disabled = true;

        try {
            const res = await fetch('mac_gateway.php?action=lookup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mac_address: mac })
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                showToast(data.error || 'Lookup failed', 'danger');
                return;
            }

            displayResult(data);
            fetchDashboardStats();
            // document.getElementById('mac-input').value = ''; // Keep it for reference
        } catch (error) {
            console.error('Lookup error:', error);
            showToast('Network error or server unavailable', 'danger');
        } finally {
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
        }
    });

    // Batch Upload
    document.getElementById('batch-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = document.getElementById('csv-file').files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('mac_gateway.php?action=batch', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (!res.ok) {
                showToast(data.error || 'Batch upload failed', 'danger');
                return;
            }

            displayBatchResults(data.results);
            fetchDashboardStats();
            document.getElementById('csv-file').value = '';
        } catch (error) {
            showToast('Network error during batch upload', 'danger');
        }
    });


    // Search Filtering for History Log
    document.getElementById('history-search').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#recent-activity-body tr:not(.date-group-header)');
        rows.forEach(row => {
            const mac = row.cells[1].innerText.toLowerCase();
            row.style.display = mac.includes(term) ? '' : 'none';
        });
    });

    // Select All History
    const selectAllBtn = document.getElementById('select-all-history');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('.history-checkbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            // Also update group checkboxes
            document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }

    // Date Group Selection
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('group-checkbox')) {
            const date = e.target.dataset.date;
            const checkboxes = document.querySelectorAll(`.history-checkbox.group-${date}`);
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        }
    });
});

let currentMac = null;

function displayResult(data) {
    document.getElementById('result-card').classList.remove('d-none');
    document.getElementById('batch-result-card').classList.add('d-none');
    
    document.getElementById('res-mac').innerText = data.mac_address;
    currentMac = data.mac_address;
    
    // Vendor Info
    document.getElementById('res-vendor').innerText = data.vendor || 'Unknown Hardware Vendor';
    document.getElementById('res-prefix').innerText = data.mac_prefix || 'Not Available';
    document.getElementById('res-location').innerText = data.country || 'N/A';
    document.getElementById('res-scope').innerText = data.is_private ? 'Private / Internal' : 'Public / External';
    document.getElementById('res-address').innerText = data.address || 'No registered corporate address found in IEEE records';
    
    // Timeline
    document.getElementById('res-updated').innerText = data.last_api_update;
    document.getElementById('res-year').innerText = data.last_api_update.split('-')[0];
    
    // Forensic
    const spoofEl = document.getElementById('res-spoof');
    if (data.is_spoofed) {
        spoofEl.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Likely Spoofed / Randomized</span>';
    } else {
        spoofEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Factory Standard</span>';
    }
    
    // History
    const historyContainer = document.getElementById('res-history');
    if (data.history && data.history.length > 0) {
        let html = '<ul class="list-unstyled mb-0" style="font-size: 0.75rem;">';
        data.history.forEach(h => {
            html += `<li class="border-bottom border-secondary py-1 d-flex justify-content-between">
                <span>${h.search_timestamp}</span>
                <span class="badge ${h.is_spoofed ? 'bg-danger' : 'bg-success'}" style="font-size: 0.5rem;">${h.is_spoofed ? 'Spoofed' : 'Clean'}</span>
            </li>`;
        });
        html += '</ul>';
        historyContainer.innerHTML = html;
    } else {
        historyContainer.innerHTML = '<div class="text-muted small">No previous records</div>';
    }

    // IP Info Integration
    const ipRow = document.getElementById('res-ip-row');
    if (data.resolved_ip) {
        ipRow.classList.remove('d-none');
        document.getElementById('res-ip').innerText = data.resolved_ip;
        if (data.ip_details) {
            const geo = data.ip_details;
            document.getElementById('res-ip-geo').innerHTML = `
                <i class="bi bi-geo-alt"></i> ${geo.city}, ${geo.country} (${geo.isp})
            `;
        }
    } else {
        ipRow.classList.add('d-none');
    }
    
    applyTranslations();
}


function displayBatchResults(results) {
    document.getElementById('batch-result-card').classList.remove('d-none');
    document.getElementById('result-card').classList.add('d-none');
    
    const tbody = document.getElementById('batch-results-body');
    tbody.innerHTML = '';
    
    results.forEach(res => {
        const tr = document.createElement('tr');
        if (res.watchlist_status === 'BLACKLIST') tr.classList.add('table-danger');
        
        tr.innerHTML = `
            <td>${res.mac_address}</td>
            <td>${res.vendor}</td>
            <td>${res.is_spoofed ? '<span class="badge bg-danger">Spoofed</span>' : '<span class="badge bg-success">Valid</span>'}</td>
        `;
        tbody.appendChild(tr);
    });
    
    showToast(`Processed ${results.length} MAC addresses`, 'success');
}

async function fetchDashboardStats() {
    try {
        const res = await fetch('mac_gateway.php?action=stats');
        const data = await res.json();
        
        document.getElementById('metric-total').innerText = data.total_searches;
        document.getElementById('metric-spoofed').innerText = data.spoofed_count;
        document.getElementById('metric-watchlist').innerText = data.watchlist_hits;
        
        // Render Recent Activity with Grouping
        const tbody = document.getElementById('recent-activity-body');
        tbody.innerHTML = '';
        
        // Group by date
        const groups = {};
        data.recent_searches.forEach(search => {
            const date = search.search_timestamp.split(' ')[0];
            if (!groups[date]) groups[date] = [];
            groups[date].push(search);
        });

        // Sort dates descending
        const sortedDates = Object.keys(groups).sort().reverse();

        sortedDates.forEach(date => {
            // Add Header Row
            const headerTr = document.createElement('tr');
            headerTr.classList.add('date-group-header', 'table-secondary');
            headerTr.innerHTML = `
                <td colspan="3" class="small fw-bold text-dark py-1">
                    <input type="checkbox" class="form-check-input group-checkbox me-2" data-date="${date}">
                    <i class="bi bi-calendar3 me-1"></i> ${formatGroupDate(date)}
                </td>
            `;
            tbody.appendChild(headerTr);

            // Add Data Rows
            groups[date].forEach(search => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="checkbox" class="form-check-input history-checkbox group-${date}" value="${search.id}"></td>
                    <td class="font-monospace">${search.mac_address}</td>
                    <td><small>${search.search_timestamp.split(' ')[1]}</small></td>
                `;
                tbody.appendChild(tr);
            });
        });

    } catch (error) {
        console.error('Failed to fetch stats', error);
    }
}

// renderVendorChart removed as requested

async function addToWatchlist(listType) {
    if (!currentMac) return;
    
    try {
        const res = await fetch('mac_gateway.php?action=watchlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mac_address: currentMac, list_type: listType })
        });
        
        if (res.ok) {
            showToast(`${currentMac} added to ${listType}`, 'success');
            // Re-trigger lookup to refresh UI
            document.getElementById('mac-input').value = currentMac;
            document.getElementById('lookup-form').dispatchEvent(new Event('submit'));
        }
    } catch (error) {
        showToast('Failed to update watchlist', 'danger');
    }
}

function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    const toastHTML = `
        <div class="toast align-items-center text-bg-${type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const newToast = toastContainer.lastElementChild;
    setTimeout(() => {
        newToast.classList.remove('show');
        setTimeout(() => newToast.remove(), 300);
    }, 3000);
}

function formatGroupDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date().toISOString().split('T')[0];
    const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];

    if (dateStr === today) return "Today";
    if (dateStr === yesterday) return "Yesterday";
    
    return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

function exportSelected(format) {
    const selected = Array.from(document.querySelectorAll('.history-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        showToast('Please select at least one record to export', 'warning');
        return;
    }
    
    const ids = selected.join(',');
    window.location.href = `mac_gateway.php?action=export&format=${format}&ids=${ids}`;
}

function exportResult(format) {
    const mac = document.getElementById('res-mac').innerText.trim();
    if (!mac || mac === '--') {
        showToast('No active result to export', 'warning');
        return;
    }
    window.location.href = `mac_gateway.php?action=export&format=${format}&mac=${encodeURIComponent(mac)}`;
}

function updateClock() {
    const now = new Date();
    document.getElementById('system-time').innerText = now.toLocaleString();
}
