const translations = {
    en: {
        app_title: "DIGITAL INVESTIGATION",
        export_report: "Export Report",
        total_lookups: "Total Lookups",
        spoofed_detected: "Spoofed Detected",
        watchlist_hits: "Watchlist Hits",
        active_investigations: "Active Sessions",
        mac_lookup_tool: "MAC Lookup",
        analyze: "Analyze",
        batch_upload: "Batch Upload (CSV)",
        upload: "Upload",
        analysis_result: "Analysis Result",
        vendor: "Vendor Identity",
        spoof_status: "Spoof Status",
        watchlist_status: "Watchlist",
        network_correlation: "Origin Country / Address",
        add_blacklist: "Blacklist",
        add_whitelist: "Whitelist",
        batch_results: "Batch Results",
        vendor_distribution: "Vendor Intelligence",
        recent_activity: "Recent Activity Log",
        vendor_info: "Vendor Details",
        timeline: "Timeline Info",
        forensic_notes: "Forensic Records",
        address_prefix: "MAC Prefix",
        is_private: "Scope (Private/Public)",
        block_type: "Block Size",
        last_updated: "API Registry Date",
        year_of_detection: "Detection Year",
        historical_records: "Device Lookup History",
        private: "Private (Restricted)",
        public: "Public (Registered)",
        historical_ip: "Historical IP Assignments"
    },
    hi: {
        app_title: "डिजिटल इन्वेस्टिगेशन (Digital Investigation)",
        export_report: "रिपोर्ट निर्यात करें",
        total_lookups: "कुल लुकअप",
        spoofed_detected: "स्पूफिंग का पता चला",
        watchlist_hits: "वॉचलिस्ट हिट्स",
        active_investigations: "सक्रिय सत्र",
        mac_lookup_tool: "मैक लुकअप",
        analyze: "विश्लेषण करें",
        batch_upload: "बैच अपलोड (CSV)",
        upload: "अपलोड करें",
        analysis_result: "विश्लेषण परिणाम",
        vendor: "विक्रेता पहचान",
        spoof_status: "स्पूफ स्थिति",
        watchlist_status: "वॉचलिस्ट",
        network_correlation: "मूल देश और स्थान",
        add_blacklist: "ब्लैकलिस्ट",
        add_whitelist: "व्हाइटलिस्ट",
        batch_results: "बैच परिणाम",
        vendor_distribution: "विक्रेता वितरण",
        recent_activity: "हाल की गतिविधि लॉग",
        vendor_info: "विक्रेता विवरण",
        timeline: "समय रेखा",
        forensic_notes: "फोरेंसिक रिकॉर्ड",
        address_prefix: "मैक प्रीफिक्स",
        is_private: "निजी / सार्वजनिक",
        block_type: "ब्लॉक आकार",
        last_updated: "पंजीकरण तिथि",
        year_of_detection: "पता लगाने का वर्ष",
        historical_records: "डिवाइस लुकअप इतिहास",
        private: "निजी",
        public: "सार्वजनिक",
        historical_ip: "ऐतिहासिक आईपी आवंटन"
    }
};

let currentLang = 'en';

function toggleLanguage() {
    currentLang = currentLang === 'en' ? 'hi' : 'en';
    document.getElementById('current-lang').innerText = currentLang === 'en' ? 'हिंदी' : 'English';
    applyTranslations();
}

function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[currentLang][key]) {
            el.innerText = translations[currentLang][key];
        }
    });
}

// Initial translation check
document.addEventListener('DOMContentLoaded', () => {
    applyTranslations();
});
