# CDR Analyzer

Operator-aware CDR forensic dashboard for Indian telecom formats (Jio, Airtel, VI, BSNL + generic fallback) built with Streamlit.

## What this build includes

- Multi-file session upload (`.csv` and `.xlsx`)
- Auto operator detection from header signatures
- Header metadata extraction into a Subject Profile Card
- Auto normalization into a common schema:
  - `caller_number`, `receiver_number`, `call_datetime`, `duration_sec`, `call_type`, `service_type`, `imei`, `imsi`, `tower_id`, `tower_lat`, `tower_lon`, `roaming_circle`
- Overview dashboard:
  - KPI cards, daily activity trend, hourly and weekday bars, call-type donut, connection-type breakdown, roaming badge
- Interactive query + structured filters + filtered export
- Contacts intelligence:
  - Top contacts, top-contact profile, longest calls, duration distribution
- SMS intelligence:
  - company decoding and top personal SMS contacts
- Device intelligence:
  - IMEI timeline and cards, IMSI consistency and SIM-swap warning
- Map intelligence:
  - tower plotting where coordinates are available
- Network graph with interaction threshold control
- Dual-CDR comparison:
  - mutual contacts and common IMEI detection
- OSINT launchpad links for number and IMEI lookups
- Excel export with 3 sheets:
  - `CDR Data`, `Summary`, `Contacts`

## Project structure highlights

- `app.py` — main Streamlit app and module rendering
- `parser/operator_parser.py` — operator detection, metadata extraction, parsing + normalization
- `analysis/summary.py` — KPI and analysis aggregations
- `query_engine/osint.py` — prefix/operator estimation, SMS company decoding, lookup URLs
- `export/excel_writer.py` — Excel multi-sheet export helper

## Run

From workspace root (`D:\CDR Analyzer`):

```powershell
.\.venv\Scripts\python.exe -m streamlit run .\cdr_analyzer\app.py
```

## Notes

- VI text BTS locations are shown as text (geocoding integration can be added later).
- Prefix-to-operator estimation is best-effort and intended for quick field triage.
- For very large files, parsing and normalization are cached with `@st.cache_data`.
