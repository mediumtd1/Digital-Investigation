from __future__ import annotations

from dataclasses import dataclass
from io import BytesIO, StringIO
from typing import Any

import pandas as pd


OPERATOR_COLORS = {
    "Jio": "#1f6feb",
    "Airtel": "#d93025",
    "VI": "#6a1b9a",
    "BSNL": "#ef6c00",
    "Unknown": "#6b7280",
}


@dataclass
class ParsedCDR:
    file_name: str
    operator: str
    metadata: dict[str, Any]
    raw_df: pd.DataFrame
    df: pd.DataFrame
    date_range: str


def parse_uploaded_file(uploaded_file) -> ParsedCDR:
    """Parse CSV/XLSX upload and return normalized CDR data and metadata."""
    name = uploaded_file.name
    file_bytes = uploaded_file.getvalue()
    ext = name.lower().split(".")[-1]

    preview = _read_preview(file_bytes=file_bytes, ext=ext, nrows=25)
    operator = detect_operator(preview)

    if operator == "VI":
        metadata = extract_vi_metadata(preview)
        raw_df = _read_operator_table(file_bytes, ext, header_row=10, data_start=12)
    elif operator == "Airtel":
        metadata = extract_airtel_metadata(preview)
        raw_df = _read_operator_table(file_bytes, ext, header_row=6, data_start=7)
    elif operator == "Jio":
        metadata = extract_jio_metadata(preview)
        raw_df = _read_operator_table(file_bytes, ext, header_row=18, data_start=19)
    else:
        metadata = {}
        raw_df = _read_generic(file_bytes=file_bytes, ext=ext)

    raw_df = clean_dataframe(raw_df)
    normalized = normalize_cdr(raw_df, operator, metadata)

    date_range = "N/A"
    if "call_datetime" in normalized.columns and not normalized["call_datetime"].dropna().empty:
        dt = normalized["call_datetime"].dropna()
        date_range = f"{dt.min():%d-%b-%Y} to {dt.max():%d-%b-%Y}"

    metadata.setdefault("Total Records", int(len(normalized)))
    metadata.setdefault("Date Range", date_range)

    return ParsedCDR(
        file_name=name,
        operator=operator,
        metadata=metadata,
        raw_df=raw_df,
        df=normalized,
        date_range=date_range,
    )


def detect_operator(preview_df: pd.DataFrame) -> str:
    flat = " ".join(
        str(v).strip().lower()
        for v in preview_df.fillna("").astype(str).values.flatten().tolist()
    )

    if "vodafone idea" in flat or "msisdn : -" in flat:
        return "VI"
    if "bharti airtel" in flat:
        return "Airtel"
    if "ticket number" in flat and "calling party telephone number" in flat:
        return "Jio"
    if "bsnl" in flat:
        return "BSNL"
    return "Unknown"


def extract_jio_metadata(preview_df: pd.DataFrame) -> dict[str, str]:
    return _metadata_from_key_value_rows(preview_df)


def extract_airtel_metadata(preview_df: pd.DataFrame) -> dict[str, str]:
    metadata: dict[str, str] = {}
    for row in preview_df.fillna("").astype(str).values.tolist():
        joined = " ".join(row)
        if "Call Details of Mobile No" in joined:
            metadata["Header"] = joined
            metadata["Target Mobile Number"] = _extract_between(joined, "Mobile No", "from").strip("' ")
            date_range = _extract_between(joined, "from", "")
            if date_range:
                metadata["Date Range"] = date_range.strip("' ")
            break
    return metadata


def extract_vi_metadata(preview_df: pd.DataFrame) -> dict[str, str]:
    return _metadata_from_key_value_rows(preview_df)


def _metadata_from_key_value_rows(preview_df: pd.DataFrame) -> dict[str, str]:
    data = preview_df.fillna("").astype(str)
    out: dict[str, str] = {}
    for row in data.values.tolist():
        row = [v.strip() for v in row if str(v).strip() and str(v).strip() != "-" * len(str(v).strip())]
        if len(row) >= 2:
            key = row[0].strip(" :")
            val = row[1].strip(" :")
            if key and val:
                out[key] = val
    return out


def _read_preview(file_bytes: bytes, ext: str, nrows: int = 25) -> pd.DataFrame:
    if ext == "xlsx":
        return pd.read_excel(BytesIO(file_bytes), header=None, nrows=nrows)

    for enc in ("utf-8-sig", "latin1", "cp1252"):
        try:
            return pd.read_csv(StringIO(file_bytes.decode(enc, errors="replace")), header=None, nrows=nrows)
        except Exception:
            continue

    return pd.DataFrame()


def _read_operator_table(file_bytes: bytes, ext: str, header_row: int, data_start: int) -> pd.DataFrame:
    if ext == "xlsx":
        df = pd.read_excel(BytesIO(file_bytes), header=header_row)
        return df.iloc[max(data_start - header_row - 1, 0):].reset_index(drop=True)

    for enc in ("utf-8-sig", "latin1", "cp1252"):
        try:
            text = file_bytes.decode(enc, errors="replace")
            table = pd.read_csv(StringIO(text), header=header_row)
            return table.iloc[max(data_start - header_row - 1, 0):].reset_index(drop=True)
        except Exception:
            continue

    return pd.DataFrame()


def _read_generic(file_bytes: bytes, ext: str) -> pd.DataFrame:
    if ext == "xlsx":
        return pd.read_excel(BytesIO(file_bytes))

    for enc in ("utf-8-sig", "latin1", "cp1252"):
        try:
            return pd.read_csv(StringIO(file_bytes.decode(enc, errors="replace")))
        except Exception:
            continue

    return pd.DataFrame()


def clean_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    cleaned = df.copy()
    cleaned.columns = [str(c).strip().replace("\n", " ") for c in cleaned.columns]

    for col in cleaned.columns:
        if cleaned[col].dtype == object:
            cleaned[col] = (
                cleaned[col]
                .astype(str)
                .str.strip()
                .str.strip("'")
                .replace({"nan": pd.NA, "None": pd.NA, "": pd.NA})
            )

    cleaned = cleaned.dropna(how="all")
    return cleaned


def normalize_cdr(df: pd.DataFrame, operator: str, metadata: dict[str, Any] | None = None) -> pd.DataFrame:
    metadata = metadata or {}
    lower_map = {str(c).lower().strip(): c for c in df.columns}

    def pick(*candidates: str) -> str | None:
        for c in candidates:
            for low, original in lower_map.items():
                if c in low:
                    return original
        return None

    norm = pd.DataFrame()

    if operator == "Jio":
        norm["caller_number"] = _col(df, pick("calling party telephone number"))
        norm["receiver_number"] = _col(df, pick("called party telephone number"))
        call_date = _col(df, pick("call date"))
        call_time = _col(df, pick("call time"))
        norm["call_datetime"] = pd.to_datetime(call_date.fillna("") + " " + call_time.fillna(""), errors="coerce", dayfirst=True)
        norm["duration_sec"] = pd.to_numeric(_col(df, pick("call duration")), errors="coerce").fillna(0)
        norm["call_type"] = _col(df, pick("call type"))
        norm["imei"] = _normalize_imei(_col(df, pick("imei")))
        norm["imsi"] = _col(df, pick("imsi"))
        norm["tower_id"] = _col(df, pick("first cell id"))
        norm["tower_last_id"] = _col(df, pick("last cell id"))
        norm["roaming_circle"] = _col(df, pick("roaming circle"))
        norm["service_type"] = norm["call_type"].map(_service_type_from_call_type)
        norm["connection_type"] = metadata.get("Connection Type")

    elif operator == "Airtel":
        norm["caller_number"] = _col(df, pick("target no"))
        norm["receiver_number"] = _col(df, pick("b party no"))
        call_date = _col(df, pick("date"))
        call_time = _col(df, pick("time"))
        norm["call_datetime"] = pd.to_datetime(call_date.fillna("") + " " + call_time.fillna(""), errors="coerce", dayfirst=True)
        norm["duration_sec"] = pd.to_numeric(_col(df, pick("dur(s)", "dur")), errors="coerce").fillna(0)
        norm["call_type"] = _col(df, pick("call type")).str.upper()
        norm["service_type"] = _col(df, pick("service type")).str.upper()
        norm["imei"] = _normalize_imei(_col(df, pick("imei")))
        norm["imsi"] = _col(df, pick("imsi"))
        norm["tower_id"] = _col(df, pick("first cgi"))
        norm["tower_last_id"] = _col(df, pick("last cgi"))
        latlon = _col(df, pick("first cgi lat/long"))
        norm[["tower_lat", "tower_lon"]] = _split_latlon(latlon)
        norm["roaming_circle"] = _col(df, pick("roam nw"))

    elif operator == "VI":
        norm["caller_number"] = _col(df, pick("target /a party number", "a party number"))
        norm["receiver_number"] = _col(df, pick("b party number"))
        call_date = _col(df, pick("call date"))
        call_time = _col(df, pick("call initiation time"))
        norm["call_datetime"] = pd.to_datetime(call_date.fillna("") + " " + call_time.fillna(""), errors="coerce", dayfirst=True)
        norm["duration_sec"] = pd.to_numeric(_col(df, pick("call duration")), errors="coerce").fillna(0)
        norm["call_type"] = _col(df, pick("call_type")).str.title()
        norm["service_type"] = _col(df, pick("service type")).str.upper()
        norm["connection_type"] = _col(df, pick("type of connection")).str.upper()
        norm["imei"] = _normalize_imei(_col(df, pick("imei")))
        norm["imsi"] = _col(df, pick("imsi"))
        norm["tower_location"] = _col(df, pick("first bts location"))
        norm["tower_id"] = _col(df, pick("first cell global id"))
        norm["tower_last_location"] = _col(df, pick("last bts location"))
        norm["tower_last_id"] = _col(df, pick("last cell global id"))
        norm["roaming_circle"] = _col(df, pick("roaming network/circle"))

    else:
        # Generic fallback with fuzzy matching
        norm["caller_number"] = _col(df, pick("caller", "a party", "from", "target"))
        norm["receiver_number"] = _col(df, pick("receiver", "b party", "to", "called"))
        date_col = _col(df, pick("date"))
        time_col = _col(df, pick("time"))
        if not date_col.empty:
            norm["call_datetime"] = pd.to_datetime(date_col.fillna("") + " " + time_col.fillna(""), errors="coerce", dayfirst=True)
        else:
            norm["call_datetime"] = pd.to_datetime(_col(df, pick("datetime", "timestamp")), errors="coerce")
        norm["duration_sec"] = pd.to_numeric(_col(df, pick("duration", "dur")), errors="coerce").fillna(0)
        norm["call_type"] = _col(df, pick("call type", "type"))
        norm["imei"] = _normalize_imei(_col(df, pick("imei")))
        norm["imsi"] = _col(df, pick("imsi"))
        norm["tower_id"] = _col(df, pick("cell", "cgi", "tower"))

    if "call_datetime" in norm.columns:
        norm["date"] = pd.to_datetime(norm["call_datetime"], errors="coerce").dt.date
        norm["hour"] = pd.to_datetime(norm["call_datetime"], errors="coerce").dt.hour
        norm["day_of_week"] = pd.to_datetime(norm["call_datetime"], errors="coerce").dt.day_name()

    # Normalize phone-like fields
    for col in ("caller_number", "receiver_number"):
        if col in norm.columns:
            norm[col] = norm[col].astype(str).str.replace(r"\D", "", regex=True)

    return norm.dropna(how="all")


def _col(df: pd.DataFrame, col_name: str | None) -> pd.Series:
    if col_name is None or col_name not in df.columns:
        return pd.Series([pd.NA] * len(df), index=df.index, dtype="object")
    return df[col_name]


def _split_latlon(series: pd.Series) -> pd.DataFrame:
    if series.empty:
        return pd.DataFrame({"tower_lat": [], "tower_lon": []})

    parts = series.astype(str).str.split("/", n=1, expand=True)
    lat = pd.to_numeric(parts[0], errors="coerce")
    lon = pd.to_numeric(parts[1] if 1 in parts.columns else pd.Series([pd.NA] * len(series), index=series.index), errors="coerce")
    return pd.DataFrame({"tower_lat": lat, "tower_lon": lon}, index=series.index)


def _normalize_imei(series: pd.Series) -> pd.Series:
    def fix(v: Any) -> str | None:
        if pd.isna(v):
            return pd.NA
        s = str(v).strip().strip("'")
        if not s:
            return pd.NA
        if "e+" in s.lower():
            try:
                return str(int(float(s)))
            except Exception:
                return s
        if s.replace(".", "", 1).isdigit() and "." in s:
            try:
                return str(int(float(s)))
            except Exception:
                return s
        return s

    return series.apply(fix)


def _service_type_from_call_type(v: Any) -> str:
    s = str(v).lower()
    if "sms" in s:
        return "SMS"
    if "data" in s:
        return "DATA"
    return "VOICE"


def _extract_between(text: str, left: str, right: str) -> str:
    try:
        start = text.index(left) + len(left)
        if right:
            end = text.index(right, start)
            return text[start:end]
        return text[start:]
    except ValueError:
        return ""
