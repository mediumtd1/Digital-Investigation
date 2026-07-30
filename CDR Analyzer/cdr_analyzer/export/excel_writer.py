# export/excel_writer.py

import pandas as pd
from io import BytesIO

def to_excel_bytes(
    df: pd.DataFrame,
    sheet_name: str = "CDR Data",
    summary_df: pd.DataFrame | None = None,
    contacts_df: pd.DataFrame | None = None,
) -> bytes:
    buffer = BytesIO()
    with pd.ExcelWriter(buffer, engine="openpyxl") as writer:
        df.to_excel(writer, index=False, sheet_name=sheet_name)
        if summary_df is not None and not summary_df.empty:
            summary_df.to_excel(writer, index=False, sheet_name="Summary")
        if contacts_df is not None and not contacts_df.empty:
            contacts_df.to_excel(writer, index=False, sheet_name="Contacts")
    return buffer.getvalue()