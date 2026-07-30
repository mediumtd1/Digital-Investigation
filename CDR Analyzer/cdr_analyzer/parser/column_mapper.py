# parser/column_mapper.py

STANDARD_FIELDS = {
    "caller_number":   "Caller phone number",
    "receiver_number": "Receiver phone number",
    "call_datetime":   "Date and time of call",
    "duration_sec":    "Duration in seconds",
    "call_type":       "Type (voice/sms/data)",
    "tower_id":        "Cell tower ID",
    "tower_lat":       "Tower latitude",
    "tower_lon":       "Tower longitude",
}

def guess_mapping(columns: list[str]) -> dict[str, str]:
    """
    Auto-guess which raw column maps to which standard field.
    Returns {standard_field: raw_column_name}.
    """
    lower = {c.lower(): c for c in columns}
    guesses = {}

    hints = {
        "caller_number":   ["caller", "calling", "a_number", "a_party", "msisdn_a", "from"],
        "receiver_number": ["receiver", "called", "b_number", "b_party", "msisdn_b", "to", "dialed"],
        "call_datetime":   ["datetime", "date", "time", "timestamp", "call_time", "start"],
        "duration_sec":    ["duration", "dur", "seconds", "length", "call_duration"],
        "call_type":       ["type", "call_type", "service", "category"],
        "tower_id":        ["tower", "cell", "bts", "cell_id", "site"],
        "tower_lat":       ["lat", "latitude"],
        "tower_lon":       ["lon", "lng", "longitude"],
    }

    for field, keywords in hints.items():
        for kw in keywords:
            for col_lower, col_original in lower.items():
                if kw in col_lower:
                    guesses[field] = col_original
                    break
            if field in guesses:
                break

    return guesses