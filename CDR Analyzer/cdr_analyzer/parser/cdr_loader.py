# parser/cdr_loader.py

import pandas as pd

def load_cdr(file_or_df, mapping: dict[str, str]) -> pd.DataFrame:
    """
    Load a CSV file and return a clean normalized DataFrame.
    mapping = {standard_field: raw_column_name}
    """
    if isinstance(file_or_df, pd.DataFrame):
        df = file_or_df.copy()
    else:
        df = pd.read_csv(file_or_df)

    # Rename columns to standard names
    reverse_map = {v: k for k, v in mapping.items()}
    df = df.rename(columns=reverse_map)

    # Keep only mapped columns
    available = [f for f in mapping.keys() if f in df.columns]
    df = df[available].copy()

    # Parse datetime
    if "call_datetime" in df.columns:
        df["call_datetime"] = pd.to_datetime(df["call_datetime"], infer_datetime_format=True, errors="coerce")
        df["hour"]       = df["call_datetime"].dt.hour
        df["day_of_week"]= df["call_datetime"].dt.day_name()
        df["date"]       = df["call_datetime"].dt.date

    # Parse numerics
    if "duration_sec" in df.columns:
        df["duration_sec"] = pd.to_numeric(df["duration_sec"], errors="coerce").fillna(0)

    if "tower_lat" in df.columns:
        df["tower_lat"] = pd.to_numeric(df["tower_lat"], errors="coerce")
    if "tower_lon" in df.columns:
        df["tower_lon"] = pd.to_numeric(df["tower_lon"], errors="coerce")

    return df