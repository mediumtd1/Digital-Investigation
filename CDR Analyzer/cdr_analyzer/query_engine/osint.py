from __future__ import annotations

import re


PREFIX_OPERATOR = {
    "98": "Jio/Legacy GSM",
    "99": "Airtel/Jio",
    "97": "VI/Airtel",
    "96": "Airtel/BSNL",
    "95": "Airtel/VI",
    "94": "BSNL/VI",
    "93": "BSNL/VI",
    "92": "Airtel/VI",
    "91": "Mixed (India Mobile)",
    "90": "Airtel/Jio/VI",
    "89": "Jio/Airtel/VI",
    "88": "Jio/Airtel",
    "87": "Jio/Airtel",
    "86": "Jio/Airtel",
    "85": "Jio/VI",
    "84": "Jio/VI",
    "83": "Jio/VI",
    "82": "Jio/Airtel",
    "81": "Jio/Airtel",
    "80": "Jio/Airtel",
    "79": "Jio",
    "78": "Airtel/VI",
    "77": "Airtel/VI",
    "76": "BSNL/Jio",
    "75": "Airtel/VI",
    "74": "Jio/Airtel",
    "73": "Airtel/Jio",
    "72": "VI/Jio",
    "70": "Jio/VI",
}

SMS_COMPANY_MAP = {
    "BOBOTP": "Bank of Baroda",
    "BOBTXN": "Bank of Baroda",
    "BOBSMS": "Bank of Baroda",
    "HDFCBK": "HDFC Bank",
    "HDBFSL": "HDB Financial Services",
    "AIRTEL": "Airtel",
    "AIRMCA": "Airtel",
    "ARWINF": "Airtel",
    "CBSSBI": "State Bank of India",
    "KBLBNK": "Karnataka Bank",
    "VICARE": "Vodafone Idea",
    "TATACL": "Tata Cliq / Tata Capital",
    "BCBANK": "Business Correspondent Banking",
    "FEDBWK": "Federal Bank",
    "MAXFSN": "Max Financial / MaxLife",
    "PLFSHN": "Policybazaar",
    "VAAHAN": "Vahan",
    "MAHSEC": "Maharashtra Police/Security",
    "MANIHP": "Manipur Home Police",
    "TRAIND": "Trainman/IRCTC",
}


def normalize_number(number: str) -> str:
    cleaned = re.sub(r"\D", "", str(number))
    if cleaned.startswith("91") and len(cleaned) > 10:
        return cleaned[-10:]
    if len(cleaned) > 10:
        return cleaned[-10:]
    return cleaned


def estimate_operator(number: str) -> str:
    n = normalize_number(number)
    if len(n) < 2:
        return "Unknown"
    return PREFIX_OPERATOR.get(n[:2], "Unknown")


def sms_company(sender: str) -> str:
    s = str(sender).upper().strip()
    if not s:
        return "Unknown"

    for key, value in SMS_COMPANY_MAP.items():
        if key in s:
            return value

    if s.startswith("52263") or s.startswith("56321"):
        return "Banking OTP Shortcode"

    token = re.sub(r"[^A-Z0-9]", "", s)
    if len(token) >= 4 and token[:2].isalpha():
        return token[2:8] if len(token) >= 8 else token

    return "Unknown"


def number_lookup_links(number: str) -> dict[str, str]:
    n = normalize_number(number)
    intl = f"91{n}" if len(n) == 10 else n
    return {
        "Truecaller": f"https://www.truecaller.com/search/in/{intl}",
        "Sync.me": f"https://sync.me/search/?number={intl}",
        "Google": f"https://www.google.com/search?q={intl}+India",
        "WhatsApp": f"https://api.whatsapp.com/send?phone={intl}",
    }


def imei_lookup_links(imei: str) -> dict[str, str]:
    code = re.sub(r"\D", "", str(imei))
    return {
        "IMEI.info": f"https://www.imei.info/?imei={code}",
        "CheckMEND": f"https://www.checkmend.com/us/?imei={code}",
        "Google": f"https://www.google.com/search?q={code}+imei",
    }


def tower_lookup_links(lat: float | None = None, lon: float | None = None, cgi: str | None = None) -> dict[str, str]:
    links = {"CellMapper": "https://www.cellmapper.net/map"}
    if lat is not None and lon is not None:
        links["OpenCelliD"] = f"https://opencellid.org/#zoom=16&lat={lat}&lon={lon}"
        links["Google Maps"] = f"https://maps.google.com/?q={lat},{lon}"
    if cgi:
        links["Google CGI Search"] = f"https://www.google.com/search?q={cgi}+cell+global+id+india"
    return links
