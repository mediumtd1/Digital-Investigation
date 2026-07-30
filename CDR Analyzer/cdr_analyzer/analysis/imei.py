from __future__ import annotations

import pandas as pd


def imei_breakdown(df: pd.DataFrame) -> pd.DataFrame:
    if "imei" not in df.columns:
        return pd.DataFrame(columns=["imei", "records", "pct", "first_seen", "last_seen"])

    d = df.dropna(subset=["imei"]).copy()
    if d.empty:
        return pd.DataFrame(columns=["imei", "records", "pct", "first_seen", "last_seen"])

    d["call_datetime"] = pd.to_datetime(d.get("call_datetime"), errors="coerce")
    out = (
        d.groupby("imei")
        .agg(records=("imei", "size"), first_seen=("call_datetime", "min"), last_seen=("call_datetime", "max"))
        .reset_index()
        .sort_values("records", ascending=False)
    )
    out["pct"] = (out["records"] / max(len(df), 1) * 100).round(2)
    return out


def imei_timeline(df: pd.DataFrame) -> pd.DataFrame:
    return imei_breakdown(df)


def imsi_check(df: pd.DataFrame) -> pd.DataFrame:
    if "imsi" not in df.columns:
        return pd.DataFrame(columns=["imsi", "records", "first_seen", "last_seen"])

    d = df.dropna(subset=["imsi"]).copy()
    if d.empty:
        return pd.DataFrame(columns=["imsi", "records", "first_seen", "last_seen"])

    d["call_datetime"] = pd.to_datetime(d.get("call_datetime"), errors="coerce")
    return (
        d.groupby("imsi")
        .agg(records=("imsi", "size"), first_seen=("call_datetime", "min"), last_seen=("call_datetime", "max"))
        .reset_index()
        .sort_values("records", ascending=False)
    )
