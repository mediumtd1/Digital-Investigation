from __future__ import annotations

import pandas as pd


CALL_TYPE_LABELS = {
    "a_in": "Voice Incoming",
    "a_out": "Voice Outgoing",
    "v_in": "VoWiFi Incoming",
    "v_out": "VoWiFi Outgoing",
    "p2p_smsin": "Personal SMS Received",
    "p2pout": "Personal SMS Sent",
    "a2p_smsin": "Commercial SMS Received",
    "in": "Voice Incoming",
    "out": "Voice Outgoing",
    "smt": "SMS Received",
    "smo": "SMS Sent",
    "dsm": "Data Session",
    "incoming": "Voice Incoming",
    "outgoing": "Voice Outgoing",
}


def kpis(df: pd.DataFrame) -> dict[str, str | int]:
    total = len(df)
    ctype = df.get("call_type", pd.Series([], dtype="object")).astype(str).str.lower()
    service = df.get("service_type", pd.Series([], dtype="object")).astype(str).str.lower()

    voice_mask = ctype.str.contains("in|out|voice|a_in|a_out|v_in|v_out", na=False) | service.str.contains("voice", na=False)
    sms_mask = ctype.str.contains("sms|smo|smt|p2p|a2p", na=False) | service.str.contains("sms", na=False)
    data_mask = ctype.str.contains("dsm|data", na=False) | service.str.contains("data", na=False)

    date_range = "N/A"
    if "call_datetime" in df.columns and not df["call_datetime"].dropna().empty:
        dt = pd.to_datetime(df["call_datetime"], errors="coerce").dropna()
        if not dt.empty:
            date_range = f"{dt.min():%d-%b-%Y} to {dt.max():%d-%b-%Y}"

    return {
        "Total Records": total,
        "Total Voice Calls": int(voice_mask.sum()),
        "Total SMS": int(sms_mask.sum()),
        "Total Data Sessions": int(data_mask.sum()),
        "Unique B-Party": int(df["receiver_number"].nunique()) if "receiver_number" in df.columns else 0,
        "IMEI Count": int(df["imei"].dropna().nunique()) if "imei" in df.columns else 0,
        "Date Range": date_range,
    }


def activity_daily(df: pd.DataFrame) -> pd.DataFrame:
    if "call_datetime" not in df.columns:
        return pd.DataFrame(columns=["date", "total", "sms"])

    d = df.copy()
    d["call_datetime"] = pd.to_datetime(d["call_datetime"], errors="coerce")
    d = d.dropna(subset=["call_datetime"])
    if d.empty:
        return pd.DataFrame(columns=["date", "total", "sms"])

    d["date"] = d["call_datetime"].dt.date
    d["is_sms"] = d.get("call_type", pd.Series(index=d.index, dtype="object")).astype(str).str.lower().str.contains("sms|smo|smt|p2p|a2p", na=False)
    out = d.groupby("date").agg(total=("date", "size"), sms=("is_sms", "sum")).reset_index()
    return out


def activity_hourly(df: pd.DataFrame) -> pd.DataFrame:
    if "hour" not in df.columns:
        return pd.DataFrame(columns=["hour", "count"])
    return df.groupby("hour").size().reset_index(name="count")


def activity_weekday(df: pd.DataFrame) -> pd.DataFrame:
    if "day_of_week" not in df.columns:
        return pd.DataFrame(columns=["day_of_week", "count"])
    order = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
    out = df.groupby("day_of_week").size().reset_index(name="count")
    out["day_of_week"] = pd.Categorical(out["day_of_week"], categories=order, ordered=True)
    return out.sort_values("day_of_week")


def call_type_breakdown(df: pd.DataFrame) -> pd.DataFrame:
    if "call_type" not in df.columns:
        return pd.DataFrame(columns=["raw_type", "label", "count"])

    out = df["call_type"].astype(str).str.strip().str.lower().value_counts().reset_index()
    out.columns = ["raw_type", "count"]
    out["label"] = out["raw_type"].map(CALL_TYPE_LABELS).fillna(out["raw_type"].str.upper())
    return out


def roaming_indicator(df: pd.DataFrame) -> tuple[bool, int]:
    if "roaming_circle" not in df.columns:
        return False, 0

    roaming = (
        df["roaming_circle"]
        .astype(str)
        .str.strip()
        .str.lower()
        .replace({"": pd.NA, "nan": pd.NA, "none": pd.NA})
        .dropna()
    )
    return (not roaming.empty), int(roaming.shape[0])


def imei_stats(df: pd.DataFrame) -> pd.DataFrame:
    if "imei" not in df.columns or "call_datetime" not in df.columns:
        return pd.DataFrame(columns=["imei", "records", "first_seen", "last_seen", "pct"])

    d = df.dropna(subset=["imei"]).copy()
    if d.empty:
        return pd.DataFrame(columns=["imei", "records", "first_seen", "last_seen", "pct"])

    d["call_datetime"] = pd.to_datetime(d["call_datetime"], errors="coerce")
    total = len(df)
    stats = (
        d.groupby("imei")
        .agg(records=("imei", "size"), first_seen=("call_datetime", "min"), last_seen=("call_datetime", "max"))
        .reset_index()
        .sort_values("records", ascending=False)
    )
    stats["pct"] = (stats["records"] / max(total, 1) * 100).round(2)
    return stats


def imsi_stats(df: pd.DataFrame) -> pd.DataFrame:
    if "imsi" not in df.columns or "call_datetime" not in df.columns:
        return pd.DataFrame(columns=["imsi", "records", "first_seen", "last_seen"])

    d = df.dropna(subset=["imsi"]).copy()
    d["call_datetime"] = pd.to_datetime(d["call_datetime"], errors="coerce")
    return (
        d.groupby("imsi")
        .agg(records=("imsi", "size"), first_seen=("call_datetime", "min"), last_seen=("call_datetime", "max"))
        .reset_index()
        .sort_values("records", ascending=False)
    )


def top_contacts(df: pd.DataFrame, limit: int = 20) -> pd.DataFrame:
    if "receiver_number" not in df.columns:
        return pd.DataFrame(columns=["number", "interactions"])

    d = df.copy()
    d["service_type"] = d.get("service_type", pd.Series(index=d.index, dtype="object")).astype(str).str.upper()
    out = (
        d.groupby("receiver_number")
        .agg(
            interactions=("receiver_number", "size"),
            voice=("service_type", lambda s: int((s == "VOICE").sum())),
            sms=("service_type", lambda s: int((s == "SMS").sum())),
            total_duration=("duration_sec", "sum"),
            first_contact=("call_datetime", "min"),
            last_contact=("call_datetime", "max"),
        )
        .reset_index()
        .rename(columns={"receiver_number": "number"})
        .sort_values("interactions", ascending=False)
        .head(limit)
    )

    out["interaction_type"] = out.apply(
        lambda r: "Mixed" if r["voice"] > 0 and r["sms"] > 0 else ("Voice" if r["voice"] > 0 else "SMS"),
        axis=1,
    )
    return out


def longest_calls(df: pd.DataFrame, limit: int = 20) -> pd.DataFrame:
    if "duration_sec" not in df.columns:
        return pd.DataFrame()
    return (
        df.sort_values("duration_sec", ascending=False)
        .head(limit)
        [[c for c in ["receiver_number", "call_datetime", "duration_sec", "tower_location", "tower_id"] if c in df.columns]]
    )


def duration_buckets(df: pd.DataFrame) -> pd.DataFrame:
    if "duration_sec" not in df.columns:
        return pd.DataFrame(columns=["bucket", "count"])

    bins = [-1, 30, 60, 120, 300, 900, 1800, 3600, float("inf")]
    labels = ["0-30s", "30-60s", "1-2m", "2-5m", "5-15m", "15-30m", "30-60m", "60m+"]
    d = pd.to_numeric(df["duration_sec"], errors="coerce").fillna(0)
    out = pd.cut(d, bins=bins, labels=labels).value_counts(sort=False).reset_index()
    out.columns = ["bucket", "count"]
    return out


def mutual_contacts(dfs: dict) -> pd.DataFrame:
    """Find contacts common across multiple CDR DataFrames.

    Args:
        dfs: dict mapping label -> pd.DataFrame for each CDR to compare.

    Returns:
        DataFrame with columns: number, found_in (count), CDRs (names),
        total_interactions, significance_score.
    """
    if len(dfs) < 2:
        return pd.DataFrame()

    contact_maps: dict[str, set] = {}
    interaction_totals: dict[str, int] = {}

    for label, df in dfs.items():
        tc = top_contacts(df, limit=100000)
        if tc.empty:
            contact_maps[label] = set()
            continue
        contact_maps[label] = set(tc["number"].astype(str).tolist())
        for _, row in tc.iterrows():
            num = str(row["number"])
            interaction_totals[num] = interaction_totals.get(num, 0) + int(row["interactions"])

    # Find numbers that appear in at least 2 CDRs
    all_numbers: dict[str, list] = {}
    for label, nums in contact_maps.items():
        for num in nums:
            all_numbers.setdefault(num, []).append(label)

    rows = []
    for num, labels in all_numbers.items():
        if len(labels) >= 2:
            rows.append({
                "number": num,
                "found_in": len(labels),
                "CDRs": ", ".join(sorted(labels)),
                "total_interactions": interaction_totals.get(num, 0),
            })

    if not rows:
        return pd.DataFrame()

    out = pd.DataFrame(rows)
    out["significance_score"] = (out["total_interactions"] * out["found_in"] * 0.7).round(2)
    return out.sort_values(["found_in", "significance_score"], ascending=[False, False]).reset_index(drop=True)
