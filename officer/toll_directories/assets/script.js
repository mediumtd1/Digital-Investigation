function triggerSearch() {
    const query = document.getElementById('tollSearch').value.toLowerCase().trim();
    const tollData = document.querySelectorAll('.toll-data');
    const resultSection = document.getElementById('resultSection');
    const noResult = document.getElementById('noResult');
    
    let found = false;

    if (query === "") {
        resultSection.style.display = 'none';
        noResult.classList.add('d-none');
        return;
    }

    tollData.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const code = item.getAttribute('data-code').toLowerCase();

        if (!found && (name.includes(query) || code.includes(query))) {
            // Populate the sections
            document.getElementById('resName').textContent = item.getAttribute('data-name');
            document.getElementById('resCode').textContent = item.getAttribute('data-code');
            document.getElementById('resType').textContent = item.getAttribute('data-type');
            document.getElementById('resLocation').textContent = item.getAttribute('data-state') + ", " + item.getAttribute('data-district');
            document.getElementById('resConcessionaire').textContent = item.getAttribute('data-concessionaire');

            // Update Map with exact Pin Marker
            const lat = item.getAttribute('data-lat');
            const lng = item.getAttribute('data-lng');
            document.getElementById('resMap').src = `https://maps.google.com/maps?q=${lat},${lng}&t=&z=15&ie=UTF8&iwloc=&output=embed`;

            resultSection.style.display = 'block';
            noResult.classList.add('d-none');

            // Scroll to Result
            resultSection.scrollIntoView({ behavior: 'smooth' });
            found = true;
        }
    });

    if (!found) {
        resultSection.style.display = 'none';
        noResult.classList.remove('d-none');
    }
}

// Keep confirmDelete for Admin panel if needed
function confirmDelete(id) {
    if (confirm('Delete?')) window.location.href = `EDIT.php?delete_id=${id}`;
}
