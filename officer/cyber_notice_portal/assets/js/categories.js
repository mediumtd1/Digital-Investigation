function populateCompanies(category) {
    const companySelect = document.getElementById('company_name');
    companySelect.innerHTML = '<option value="">-- Select Company --</option>';
    
    if(companyDB[category]) {
        companyDB[category].forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.name;
            opt.innerHTML = item.name;
            companySelect.appendChild(opt);
        });
    }
}

function autoPopulateDetails() {
    const category = document.getElementById('selected_category').value;
    const companyName = document.getElementById('company_name').value;
    const recipientInput = document.getElementById('recipient_name');
    const addressInput = document.getElementById('recipient_address');
    
    if(companyDB[category]) {
        const company = companyDB[category].find(c => c.name === companyName);
        if(company) {
            recipientInput.value = company.nodal_officer;
            addressInput.value = company.address;
        }
    }
}

const crimeData = {
    "Bank": ["Financial Fraud", "UPI Fraud", "Net Banking Hacking", "Unauthorized Transaction", "KYC Scam"],
    "Social Media": ["Fake Account", "Identity Theft", "Cyber Stalking", "Hacking", "Objectionable Content", "Defamation"],
    "Shopping Platform": ["Order Fraud", "Refund Scam", "Account Hacking", "Seller Fraud"],
    "TSP/ISP": ["SIM Swap Fraud", "Phishing SMS", "Threatening Calls", "IP Tracking"],
    "Others": ["Other Cyber Crime"]
};

function populateCrimes(category) {
    const crimeSelect = document.getElementById('crime_type');
    crimeSelect.innerHTML = '<option value="">-- Select Crime Type --</option>';
    
    let matchedKey = null;
    if (category) {
        const catLower = category.toLowerCase();
        for (let key in crimeData) {
            if (catLower.includes(key.toLowerCase()) || key.toLowerCase().includes(catLower)) {
                matchedKey = key;
                break;
            }
        }
    }
    
    const list = matchedKey ? crimeData[matchedKey] : crimeData["Others"];
    
    if(list) {
        list.forEach(crime => {
            const opt = document.createElement('option');
            opt.value = crime;
            opt.innerHTML = crime;
            crimeSelect.appendChild(opt);
        });
    }
}
