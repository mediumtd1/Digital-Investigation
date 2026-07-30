from __future__ import annotations

import pandas as pd

from query_engine.osint import sms_company


def decode_sms_sender(sender: str) -> str:
    return sms_company(sender)


def a2p_vs_p2p(df: pd.DataFrame) -> pd.DataFrame:
    if "service_type" not in df.columns:
        return pd.DataFrame(columns=["category", "count"])

    s = df[df["service_type"].astype(str).str.upper().eq("SMS")].copy()
    if s.empty:
        return pd.DataFrame(columns=["category", "count"])

    ctype = s.get("call_type", pd.Series(index=s.index, dtype="object")).astype(str).str.lower()
    out = pd.DataFrame(
        {
            "category": ["A2P", "P2P", "Unknown"],
            "count": [
                int(ctype.str.contains("a2p").sum()),
                int(ctype.str.contains("p2p|smo|smt").sum()),
                int((~ctype.str.contains("a2p|p2p|smo|smt", na=False)).sum()),
            ],
        }
    )
    return out


def company_breakdown(df: pd.DataFrame) -> pd.DataFrame:
    if "receiver_number" not in df.columns:
        return pd.DataFrame(columns=["company", "count", "first_sms", "last_sms"])

    s = df[df.get("service_type", pd.Series(index=df.index, dtype="object")).astype(str).str.upper().eq("SMS")].copy()
    if s.empty:
        return pd.DataFrame(columns=["company", "count", "first_sms", "last_sms"])

    s["company"] = s["receiver_number"].apply(sms_company)
    return (
        s.groupby("company")
        .agg(count=("company", "size"), first_sms=("call_datetime", "min"), last_sms=("call_datetime", "max"))
        .reset_index()
        .sort_values("count", ascending=False)
    )
