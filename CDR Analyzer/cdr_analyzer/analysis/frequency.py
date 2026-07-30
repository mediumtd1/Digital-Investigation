# analysis/frequency.py

import pandas as pd

def top_callers(df: pd.DataFrame, n: int = 10) -> pd.DataFrame:
    if "caller_number" not in df.columns:
        return pd.DataFrame()
    return (
        df["caller_number"]
        .value_counts()
        .head(n)
        .reset_index()
        .rename(columns={"index": "number", "caller_number": "call_count"})
    )

def top_receivers(df: pd.DataFrame, n: int = 10) -> pd.DataFrame:
    if "receiver_number" not in df.columns:
        return pd.DataFrame()
    return (
        df["receiver_number"]
        .value_counts()
        .head(n)
        .reset_index()
        .rename(columns={"index": "number", "receiver_number": "call_count"})
    )

def call_type_distribution(df: pd.DataFrame) -> pd.DataFrame:
    if "call_type" not in df.columns:
        return pd.DataFrame()
    return df["call_type"].value_counts().reset_index().rename(
        columns={"index": "type", "call_type": "count"}
    )

def contacts_of(df: pd.DataFrame, number: str) -> pd.DataFrame:
    """All numbers that called or were called by a given number."""
    if "caller_number" not in df.columns or "receiver_number" not in df.columns:
        return pd.DataFrame()
    mask = (df["caller_number"] == number) | (df["receiver_number"] == number)
    sub = df[mask].copy()
    sub["other"] = sub.apply(
        lambda r: r["receiver_number"] if r["caller_number"] == number else r["caller_number"],
        axis=1
    )
    return sub["other"].value_counts().reset_index().rename(
        columns={"index": "contact", "other": "interactions"}
    )