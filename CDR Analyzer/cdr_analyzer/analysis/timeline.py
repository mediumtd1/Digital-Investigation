# analysis/timeline.py

import pandas as pd

def calls_per_day(df: pd.DataFrame) -> pd.DataFrame:
    if "date" not in df.columns:
        return pd.DataFrame()
    return df.groupby("date").size().reset_index(name="call_count")

def calls_per_hour(df: pd.DataFrame) -> pd.DataFrame:
    if "hour" not in df.columns:
        return pd.DataFrame()
    return df.groupby("hour").size().reset_index(name="call_count")

def heatmap_data(df: pd.DataFrame) -> pd.DataFrame:
    """Returns pivot table: rows = day_of_week, columns = hour."""
    if "hour" not in df.columns or "day_of_week" not in df.columns:
        return pd.DataFrame()
    order = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]
    pivot = df.groupby(["day_of_week","hour"]).size().unstack(fill_value=0)
    pivot = pivot.reindex([d for d in order if d in pivot.index])
    return pivot


def silent_periods(df: pd.DataFrame, min_gap_days: int = 2) -> pd.DataFrame:
    if "date" not in df.columns:
        return pd.DataFrame(columns=["start_date", "end_date", "days"])

    dates = pd.to_datetime(df["date"], errors="coerce").dropna().dt.normalize().sort_values().unique()
    if len(dates) < 2:
        return pd.DataFrame(columns=["start_date", "end_date", "days"])

    date_range = pd.date_range(start=dates[0], end=dates[-1], freq="D")
    active = set(dates)
    missing = [d for d in date_range if d not in active]
    if not missing:
        return pd.DataFrame(columns=["start_date", "end_date", "days"])

    groups = []
    start = missing[0]
    prev = missing[0]
    for d in missing[1:]:
        if (d - prev).days == 1:
            prev = d
        else:
            groups.append((start, prev))
            start = d
            prev = d
    groups.append((start, prev))

    rows = []
    for s, e in groups:
        gap = (e - s).days + 1
        if gap >= min_gap_days:
            rows.append({"start_date": s.date(), "end_date": e.date(), "days": gap})
    return pd.DataFrame(rows)