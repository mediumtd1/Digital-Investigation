# query_engine/parser.py

import pandas as pd
import re

def run_query(df: pd.DataFrame, query: str) -> tuple[pd.DataFrame, str]:
    """
    Parse a plain-English query and return (filtered_df, chart_hint).
    chart_hint tells the UI which chart to auto-show.
    """
    q = query.lower().strip()
    result = df.copy()
    chart_hint = "bar"

    # --- Number-specific queries ---
    number_match = re.search(r"(\+?\d[\d\s\-]{7,14}\d)", q)
    if number_match:
        number = number_match.group(1).replace(" ", "").replace("-", "")
        if "caller_number" in df.columns and "receiver_number" in df.columns:
            result = df[
                (df["caller_number"].astype(str).str.replace(r"\D","",regex=True) == number) |
                (df["receiver_number"].astype(str).str.replace(r"\D","",regex=True) == number)
            ]
        chart_hint = "network"

    # --- Time range queries ---
    hour_match = re.search(r"between\s+(\d{1,2})\s*(am|pm)?\s+and\s+(\d{1,2})\s*(am|pm)?", q)
    if hour_match and "hour" in df.columns:
        h1 = int(hour_match.group(1))
        h2 = int(hour_match.group(3))
        if hour_match.group(2) == "pm" and h1 != 12: h1 += 12
        if hour_match.group(4) == "pm" and h2 != 12: h2 += 12
        result = result[(result["hour"] >= h1) & (result["hour"] <= h2)]
        chart_hint = "hourly"

    # --- Duration filter ---
    dur_match = re.search(r"longer than\s+(\d+)\s*(min|second)", q)
    if dur_match and "duration_sec" in df.columns:
        val = int(dur_match.group(1))
        if "min" in dur_match.group(2):
            val *= 60
        result = result[result["duration_sec"] > val]
        chart_hint = "bar"

    # --- Day-of-week filter ---
    days = ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"]
    for day in days:
        if day in q and "day_of_week" in df.columns:
            result = result[result["day_of_week"].str.lower() == day]
            chart_hint = "hourly"
            break

    # --- Call type filter ---
    for ctype in ["voice","sms","data","missed"]:
        if ctype in q and "call_type" in df.columns:
            result = result[result["call_type"].str.lower().str.contains(ctype, na=False)]
            chart_hint = "pie"
            break

    # --- Keyword hints for chart type ---
    if any(w in q for w in ["trend","over time","daily","per day"]):
        chart_hint = "timeline"
    if any(w in q for w in ["frequent","top","most"]):
        chart_hint = "bar"
    if any(w in q for w in ["heatmap","pattern","hour","active"]):
        chart_hint = "heatmap"
    if any(w in q for w in ["location","tower","map","where"]):
        chart_hint = "map"

    return result, chart_hint