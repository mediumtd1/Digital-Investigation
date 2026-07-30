from __future__ import annotations

from pathlib import Path
from typing import Any

import pandas as pd

from analysis.summary import imei_stats, top_contacts


CENTER_COLORS = {
    "Jio": "#388bfd",
    "Airtel": "#f85149",
    "VI": "#a371f7",
    "Unknown": "#8b949e",
    "BSNL": "#d29922",
}


def _clean_number(v: Any) -> str:
    s = "".join(ch for ch in str(v or "") if ch.isdigit())
    if s.startswith("91") and len(s) > 10:
        s = s[-10:]
    elif len(s) > 10:
        s = s[-10:]
    return s


def format_indian_number(v: Any) -> str:
    s = _clean_number(v)
    if len(s) == 10:
        return f"+91 {s[:5]} {s[5:]}"
    return "+91 N/A"


def _meta_get(meta: dict[str, Any], *keys: str) -> str:
    lower = {str(k).lower(): v for k, v in meta.items()}
    for key in keys:
        if key.lower() in lower and str(lower[key.lower()]).strip():
            return str(lower[key.lower()]).strip()
    return ""


def _subject_name_and_number(df: pd.DataFrame, metadata: dict[str, Any], operator: str):
    if operator == "Jio":
        name = _meta_get(metadata, "Subscriber Name", "Subscriber Name:")
        number = _meta_get(metadata, "MSISDN", "MSISDN:")
    elif operator == "Airtel":
        number = _meta_get(metadata, "Target Mobile Number")
        name = number
    elif operator == "VI":
        number = _meta_get(metadata, "MSISDN", "MSISDN : -")
        name = number
    else:
        number = ""
        name = ""

    if not number:
        number = str(df.get("caller_number", pd.Series([], dtype="object")).dropna().astype(str).mode().iloc[0]) if "caller_number" in df.columns and not df["caller_number"].dropna().empty else ""
    if not name:
        name = number if number else "Unknown Subject"

    pretty_number = format_indian_number(number)
    return str(name).strip(), pretty_number, _clean_number(number)


def _top4_rows(df: pd.DataFrame) -> str:
    top = top_contacts(df, limit=4)
    if top.empty:
        return "<p style='color:#9ca3af'>No B-Party data available.</p>"

    mx = max(int(top["interactions"].max()), 1)
    rows = []
    for _, row in top.iterrows():
        w = int((int(row["interactions"]) / mx) * 100)
        rows.append(
            f"<div style='margin:6px 0'><div style='font-size:12px'>{row['number']} <span style='color:#9ca3af'>({int(row['interactions'])})</span></div><div class='bar'><i style='width:{w}%'></i></div></div>"
        )
    return "".join(rows)


def build_tree_html(df: pd.DataFrame, metadata: dict[str, Any], operator: str = "Unknown", second_df: pd.DataFrame | None = None) -> str:
    template_path = Path(__file__).resolve().parents[1] / "assets" / "mindmap_template.html"
    html = template_path.read_text(encoding="utf-8")

    subject_name, subject_number, raw_number = _subject_name_and_number(df, metadata, operator)

    total_records = len(df)
    ctype = df.get("call_type", pd.Series([], dtype="object")).astype(str).str.lower()
    service = df.get("service_type", pd.Series([], dtype="object")).astype(str).str.lower()
    voice_count = int((ctype.str.contains("in|out|voice|a_in|a_out|v_in|v_out", na=False) | service.str.contains("voice", na=False)).sum())
    sms_count = int((ctype.str.contains("sms|smo|smt|p2p|a2p", na=False) | service.str.contains("sms", na=False)).sum())
    unique_bparty = int(df["receiver_number"].nunique()) if "receiver_number" in df.columns else 0

    top = top_contacts(df, limit=1)
    top_contact = str(top.iloc[0]["number"]) if not top.empty else "N/A"
    top_contact_count = int(top.iloc[0]["interactions"]) if not top.empty else 0

    imei_df = imei_stats(df)
    imei_count = int(imei_df["imei"].nunique()) if not imei_df.empty else 0
    imei_primary = str(imei_df.iloc[0]["imei"]) if not imei_df.empty else "N/A"
    imei_primary_pct = float(imei_df.iloc[0]["pct"]) if not imei_df.empty else 0.0
    imei_secondary_count = max(0, imei_count - 1)
    imei_badge = "!" if imei_count > 1 else "1"
    imei_node_bg = "#4a1a1a" if imei_count > 1 else "#1a4228"
    imei_node_color = "#f85149" if imei_count > 1 else "#3fb950"

    mutual_count = 0
    if second_df is not None and not second_df.empty and "receiver_number" in df.columns and "receiver_number" in second_df.columns:
        mutual_count = len(set(df["receiver_number"].dropna().astype(str)) & set(second_df["receiver_number"].dropna().astype(str)))
    mutual_sublabel = f"{mutual_count} mutual contacts found" if second_df is not None else "Load 2nd CDR to activate"

    unique_towers = int(df["tower_id"].nunique()) if "tower_id" in df.columns else 0

    replacements = {
        "__SUBJECT_NAME__": subject_name,
        "__SUBJECT_NUMBER__": subject_number,
        "__TOTAL_RECORDS__": f"{total_records:,} records",
        "__CENTER_COLOR__": CENTER_COLORS.get(operator, CENTER_COLORS["Unknown"]),
        "__OPERATOR_LABEL__": operator.upper() if operator else "UNKNOWN",
        "__VOICE_COUNT__": f"{voice_count:,}",
        "__SMS_COUNT__": f"{sms_count:,}",
        "__UNIQUE_BPARTY__": f"{unique_bparty:,}",
        "__IMEI_PRIMARY__": imei_primary,
        "__IMEI_PRIMARY_PCT__": f"{imei_primary_pct:.2f}",
        "__IMEI_SECONDARY_COUNT__": str(imei_secondary_count),
        "__TOP_CONTACT__": top_contact,
        "__TOP_CONTACT_COUNT__": f"{top_contact_count:,}",
        "__BPARTY_BRANCH_COUNT__": "6",
        "__IMEI_BADGE__": imei_badge,
        "__IMEI_NODE_BG__": imei_node_bg,
        "__IMEI_NODE_COLOR__": imei_node_color,
        "__MUTUAL_SUBLABEL__": mutual_sublabel,
        "__UNIQUE_TOWERS__": f"{unique_towers:,}",
        "__TOP4_ROWS__": _top4_rows(df),
    }

    for token, value in replacements.items():
        html = html.replace(token, str(value))

    return html
