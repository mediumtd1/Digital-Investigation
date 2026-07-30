from __future__ import annotations

import pandas as pd


def common_numbers(df_a: pd.DataFrame, df_b: pd.DataFrame) -> pd.DataFrame:
    if "receiver_number" not in df_a.columns or "receiver_number" not in df_b.columns:
        return pd.DataFrame()

    a = df_a.groupby("receiver_number").size().reset_index(name="interactions_a")
    b = df_b.groupby("receiver_number").size().reset_index(name="interactions_b")
    out = a.merge(b, on="receiver_number", how="inner")
    out = out.rename(columns={"receiver_number": "number"})
    return out.sort_values(["interactions_a", "interactions_b"], ascending=False)


def common_imei(df_a: pd.DataFrame, df_b: pd.DataFrame) -> pd.DataFrame:
    if "imei" not in df_a.columns or "imei" not in df_b.columns:
        return pd.DataFrame(columns=["imei"])
    common = sorted(set(df_a["imei"].dropna().astype(str)) & set(df_b["imei"].dropna().astype(str)))
    return pd.DataFrame({"imei": common})


def common_tower(df_a: pd.DataFrame, df_b: pd.DataFrame) -> pd.DataFrame:
    if "tower_id" not in df_a.columns or "tower_id" not in df_b.columns:
        return pd.DataFrame()
    a = df_a[["tower_id", "date"]].dropna().copy() if "date" in df_a.columns else pd.DataFrame(columns=["tower_id", "date"])
    b = df_b[["tower_id", "date"]].dropna().copy() if "date" in df_b.columns else pd.DataFrame(columns=["tower_id", "date"])
    if a.empty or b.empty:
        return pd.DataFrame()
    return a.merge(b, on=["tower_id", "date"], how="inner").drop_duplicates().sort_values("date")


def direct_comms(df_a: pd.DataFrame, df_b: pd.DataFrame) -> pd.DataFrame:
    if "caller_number" not in df_a.columns or "receiver_number" not in df_a.columns:
        return pd.DataFrame()
    if "caller_number" not in df_b.columns or "receiver_number" not in df_b.columns:
        return pd.DataFrame()

    num_a = str(df_a["caller_number"].dropna().astype(str).mode().iloc[0]) if not df_a["caller_number"].dropna().empty else ""
    num_b = str(df_b["caller_number"].dropna().astype(str).mode().iloc[0]) if not df_b["caller_number"].dropna().empty else ""
    if not num_a or not num_b:
        return pd.DataFrame()

    a_to_b = df_a[df_a["receiver_number"].astype(str) == num_b]
    b_to_a = df_b[df_b["receiver_number"].astype(str) == num_a]
    out = pd.concat([a_to_b, b_to_a], ignore_index=True)
    return out.sort_values("call_datetime") if "call_datetime" in out.columns else out
