import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
import streamlit as st
import streamlit.components.v1 as components
from pathlib import Path
from streamlit_folium import st_folium

from analysis.network import build_graph, top_nodes
from analysis.timeline import heatmap_data, silent_periods
from analysis.imei import imei_breakdown
from analysis.summary import (
    activity_daily,
    activity_hourly,
    activity_weekday,
    call_type_breakdown,
    duration_buckets,
    imei_stats,
    imsi_stats,
    kpis,
    longest_calls,
    mutual_contacts,
    roaming_indicator,
    top_contacts,
)
from export.excel_writer import to_excel_bytes
from parser.operator_parser import OPERATOR_COLORS, parse_uploaded_file
from query_engine.osint import (
    estimate_operator,
    imei_lookup_links,
    number_lookup_links,
    sms_company,
)
from query_engine.parser import run_query
from tree.builder import build_tree_html
from visualizations.charts import network_chart, imei_gantt, tower_scatter, dual_timeline
from visualizations.heatmap import call_heatmap
from visualizations.map_view import tower_map


st.set_page_config(page_title="CDR Analyzer", layout="wide")

UPLOAD_CACHE_DIR = Path(__file__).resolve().parent / ".upload_cache"


def _ensure_upload_cache_dir():
    UPLOAD_CACHE_DIR.mkdir(parents=True, exist_ok=True)


def _cache_uploaded_file(uploaded_file):
    _ensure_upload_cache_dir()
    target = UPLOAD_CACHE_DIR / uploaded_file.name
    target.write_bytes(uploaded_file.getvalue())


def _restore_cached_uploads():
    _ensure_upload_cache_dir()
    restored: dict[str, dict] = {}
    for p in sorted(UPLOAD_CACHE_DIR.glob("*")):
        if p.suffix.lower() not in {".csv", ".xlsx"}:
            continue
        try:
            payload = parse_upload_cached(p.read_bytes(), p.name)
            restored[p.name] = payload
        except Exception:
            continue
    return restored


def _clear_upload_cache():
    if not UPLOAD_CACHE_DIR.exists():
        return
    for p in UPLOAD_CACHE_DIR.glob("*"):
        try:
            p.unlink()
        except Exception:
            pass


def app_key(file_name: str, section: str) -> str:
    return f"{file_name}_{section}".replace(" ", "_").replace(".", "_")


@st.cache_data(show_spinner=False)
def parse_upload_cached(file_bytes: bytes, file_name: str):
    class UploadWrap:
        name = file_name

        def getvalue(self):
            return file_bytes

    parsed = parse_uploaded_file(UploadWrap())
    return {
        "file_name": parsed.file_name,
        "operator": parsed.operator,
        "metadata": parsed.metadata,
        "raw_df": parsed.raw_df,
        "df": parsed.df,
        "date_range": parsed.date_range,
    }


def render_subject_profile(operator: str, metadata: dict):
    color = OPERATOR_COLORS.get(operator, OPERATOR_COLORS["Unknown"])
    st.markdown(
        f"""
        <div style='background:{color};padding:10px 14px;border-radius:8px 8px 0 0;color:white;font-weight:700;'>
            Subject Profile Card • {operator}
        </div>
        """,
        unsafe_allow_html=True,
    )

    if not metadata:
        st.info("No subscriber metadata was found in header rows. Using inferred profile from CDR records.")
        return

    items = list(metadata.items())
    c1, c2 = st.columns(2)
    half = (len(items) + 1) // 2
    with c1:
        for k, v in items[:half]:
            st.write(f"**{k}:** {v}")
    with c2:
        for k, v in items[half:]:
            st.write(f"**{k}:** {v}")


def _extract_subject_display(df: pd.DataFrame, metadata: dict, operator: str):
    def m(*keys: str):
        lower = {str(k).lower(): str(v) for k, v in metadata.items()}
        for key in keys:
            if key.lower() in lower and lower[key.lower()].strip():
                return lower[key.lower()].strip()
        return ""

    number = ""
    name = ""
    if operator == "Jio":
        name = m("Subscriber Name", "Subscriber Name:")
        number = m("MSISDN", "MSISDN:")
    elif operator == "Airtel":
        number = m("Target Mobile Number")
        name = number
    elif operator == "VI":
        number = m("MSISDN", "MSISDN : -")
        name = number

    if not number and "caller_number" in df.columns and not df["caller_number"].dropna().empty:
        number = str(df["caller_number"].dropna().astype(str).mode().iloc[0])
    clean = "".join(ch for ch in str(number) if ch.isdigit())
    clean = clean[-10:] if len(clean) >= 10 else clean
    pretty = f"+91 {clean[:5]} {clean[5:]}" if len(clean) == 10 else "+91 N/A"
    if not name:
        name = pretty
    return name, pretty


def render_tree_header(df: pd.DataFrame, metadata: dict, operator: str):
    subject_name, subject_number = _extract_subject_display(df, metadata, operator)
    stats = kpis(df)
    date_range = stats.get("Date Range", "N/A")
    connection = "Unknown"
    if "connection_type" in df.columns and df["connection_type"].notna().any():
        connection = str(df["connection_type"].dropna().astype(str).mode().iloc[0]).upper()
    circle = str(metadata.get("Circle", metadata.get("Roaming Circle Name", "Unknown")))

    imei_flag = ""
    if "imei" in df.columns and df["imei"].dropna().nunique() > 1:
        imei_flag = "<span style='background:#7f1d1d;color:#fecaca;padding:4px 8px;border-radius:999px;margin-left:6px'>IMEI SWITCH</span>"
    imsi_flag = ""
    if "imsi" in df.columns and df["imsi"].dropna().nunique() > 1:
        imsi_flag = "<span style='background:#7f1d1d;color:#fecaca;padding:4px 8px;border-radius:999px;margin-left:6px'>SIM SWAP</span>"

    color = OPERATOR_COLORS.get(operator, OPERATOR_COLORS["Unknown"])
    st.markdown(
        f"""
        <div style='display:flex;justify-content:space-between;align-items:center;background:#111827;border:1px solid #30363d;border-radius:10px;padding:12px 14px;margin-bottom:8px'>
          <div style='color:#e5e7eb;font-weight:600'>{subject_name} · {subject_number} · {operator.upper()} · {len(df):,} records · {date_range}</div>
          <div>
            <span style='background:{color};color:white;padding:4px 8px;border-radius:999px'>{operator.upper()}</span>
            <span style='background:#1f2937;color:#e5e7eb;padding:4px 8px;border-radius:999px;margin-left:6px'>{connection}</span>
            <span style='background:#1f2937;color:#e5e7eb;padding:4px 8px;border-radius:999px;margin-left:6px'>{circle}</span>
            {imei_flag}{imsi_flag}
          </div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def render_tree_chart_zone(file_name: str, df: pd.DataFrame, second_df: pd.DataFrame | None, meta: dict, second_meta: dict | None):
    active_chart = st.session_state.get("active_chart")
    if not active_chart:
        return

    st.markdown("### Tree Action Output")
    if active_chart == "daily-activity":
        d = activity_daily(df)
        if not d.empty:
            fig = go.Figure()
            fig.add_trace(go.Scatter(x=d["date"], y=d["total"], mode="lines+markers", name="Total"))
            st.plotly_chart(fig, width="stretch", key=app_key(file_name, "tree_daily"))
    elif active_chart == "hourly-pattern":
        h = activity_hourly(df)
        if not h.empty:
            st.plotly_chart(px.bar(h, x="hour", y="count", color="count", color_continuous_scale="OrRd"), width="stretch", key=app_key(file_name, "tree_hourly"))
    elif active_chart == "weekly-heatmap":
        st.plotly_chart(call_heatmap(heatmap_data(df)), width="stretch", key=app_key(file_name, "tree_heatmap"))
    elif active_chart == "imei-timeline":
        st.plotly_chart(imei_gantt(imei_breakdown(df)), width="stretch", key=app_key(file_name, "tree_imei_timeline"))
    elif active_chart == "tower-timeline":
        st.plotly_chart(tower_scatter(df), width="stretch", key=app_key(file_name, "tree_tower_timeline"))
    elif active_chart == "call-graph":
        n = st.slider("Show top N contacts", 5, 100, 30, key=app_key(file_name, "tree_graph_n"))
        G = build_graph(df)
        st.plotly_chart(network_chart(G, top_nodes(G, n=n)), width="stretch", key=app_key(file_name, "tree_call_graph"))
    elif active_chart == "cluster-detect":
        G = build_graph(df)
        st.plotly_chart(network_chart(G, top_nodes(G, n=80)), width="stretch", key=app_key(file_name, "tree_cluster_graph"))
        st.info("Cluster coloring can be extended using detected community IDs in the graph trace.")
    elif active_chart == "timeline-overlap" and second_df is not None:
        st.plotly_chart(dual_timeline(df, second_df, meta, second_meta or {}), width="stretch", key=app_key(file_name, "tree_dual_timeline"))
    elif active_chart == "movement-map":
        m = tower_map(df)
        if m:
            st_folium(m, width=900, height=480, key=app_key(file_name, "tree_map"))
        else:
            st.info("No mappable tower coordinates in active CDR.")
    elif active_chart == "sms-company-pie":
        sms_df = df[df.get("service_type", pd.Series(index=df.index, dtype="object")).astype(str).str.upper().eq("SMS")].copy()
        if not sms_df.empty and "receiver_number" in sms_df.columns:
            sms_df["company"] = sms_df["receiver_number"].apply(sms_company)
            company = sms_df["company"].value_counts().reset_index()
            company.columns = ["company", "count"]
            st.plotly_chart(px.pie(company, names="company", values="count", title="SMS company decode"), width="stretch", key=app_key(file_name, "tree_sms_pie"))
    elif active_chart == "silent-periods":
        sp = silent_periods(df)
        if sp.empty:
            st.success("No 2+ day silent gaps found.")
        else:
            st.dataframe(sp, use_container_width=True)


def render_analysis_tree_tab(file_name: str, df: pd.DataFrame, metadata: dict, operator: str, second_df: pd.DataFrame | None, second_meta: dict | None):
    render_subject_profile(operator, metadata)
    render_tree_header(df, metadata, operator)

    tree_html = build_tree_html(df, metadata, operator=operator, second_df=second_df)
    components.html(tree_html, height=720, scrolling=False)

    # Fallback controls if browser blocks postMessage -> Streamlit handoff
    with st.expander("Tree chart controls (fallback)", expanded=False):
        choice = st.selectbox(
            "Show chart",
            [None, "daily-activity", "hourly-pattern", "weekly-heatmap", "imei-timeline", "tower-timeline", "call-graph", "cluster-detect", "timeline-overlap", "movement-map", "sms-company-pie", "silent-periods"],
            key=app_key(file_name, "tree_fallback_chart"),
        )
        if choice:
            st.session_state.active_chart = choice

    render_tree_chart_zone(file_name, df, second_df, metadata, second_meta)

    st.markdown("---")
    st.markdown("### CDR Filter Tree")
    st.caption("Root: CDR Filters → choose one branch at a time. Selecting one branch collapses the others.")
    tdf, _ = render_advanced_filters(file_name, df, metadata=metadata, context="tree")
    st.markdown(f"**Tree filter result:** {len(tdf):,} records")
    st.dataframe(tdf.head(200), use_container_width=True, height=320)


def render_overview(file_name: str, df: pd.DataFrame):
    stats = kpis(df)
    cols = st.columns(7)
    keys = ["Total Records", "Total Voice Calls", "Total SMS", "Total Data Sessions", "Unique B-Party", "IMEI Count", "Date Range"]
    for i, k in enumerate(keys):
        cols[i].metric(k, stats[k])

    has_roaming, roaming_count = roaming_indicator(df)
    if has_roaming:
        st.markdown(f":red-badge[ROAMING DETECTED • {roaming_count} records]")
    else:
        st.markdown(":green-badge[No roaming records detected]")

    daily = activity_daily(df)
    if not daily.empty:
        line = go.Figure()
        line.add_trace(go.Scatter(x=daily["date"], y=daily["total"], mode="lines+markers", name="Total Activity"))
        line.add_trace(go.Scatter(x=daily["date"], y=daily["sms"], mode="lines", name="SMS Activity"))
        line.update_layout(title="Call + SMS activity per day", xaxis_title="Date", yaxis_title="Records")
        st.plotly_chart(line, use_container_width=True, key=app_key(file_name, "overview_daily"))
        st.caption("Daily activity helps spot spikes, low-activity travel windows, and suspicious surge days.")

    c1, c2 = st.columns(2)
    with c1:
        hr = activity_hourly(df)
        if not hr.empty:
            fig_hr = px.bar(hr, x="hour", y="count", color="count", color_continuous_scale="OrRd", title="Hourly activity")
            st.plotly_chart(fig_hr, use_container_width=True, key=app_key(file_name, "overview_hourly"))
            st.caption("This chart shows behavior by hour (including unusual late-night activity).")
    with c2:
        wd = activity_weekday(df)
        if not wd.empty:
            fig_wd = px.bar(wd, x="day_of_week", y="count", color="count", color_continuous_scale="Blues", title="Day-of-week activity")
            st.plotly_chart(fig_wd, use_container_width=True, key=app_key(file_name, "overview_weekday"))
            st.caption("Weekday/weekend trends help identify routine and anomaly periods.")

    c3, c4 = st.columns(2)
    with c3:
        ctb = call_type_breakdown(df)
        if not ctb.empty:
            fig_ct = px.pie(ctb, names="label", values="count", hole=0.45, title="Call type breakdown")
            st.plotly_chart(fig_ct, use_container_width=True, key=app_key(file_name, "overview_call_type"))
            st.caption("Shows raw telecom type codes with human-readable labels.")
    with c4:
        if "connection_type" in df.columns and df["connection_type"].notna().any():
            conn = df["connection_type"].astype(str).str.upper().value_counts().reset_index()
            conn.columns = ["connection_type", "count"]
            fig_conn = px.bar(conn, x="count", y="connection_type", orientation="h", title="Connection type")
            st.plotly_chart(fig_conn, use_container_width=True, key=app_key(file_name, "overview_connection_type"))


def _direction_masks(df: pd.DataFrame):
    ctype = df.get("call_type", pd.Series(index=df.index, dtype="object")).astype(str).str.lower()
    incoming = ctype.str.contains(r"incoming|a_in|v_in|\bin\b|smt|smsin", regex=True, na=False)
    outgoing = ctype.str.contains(r"outgoing|a_out|v_out|\bout\b|smo|p2pout", regex=True, na=False)
    return incoming, outgoing


def render_advanced_filters(file_name: str, df: pd.DataFrame, metadata: dict | None = None, context: str = "query"):
    metadata = metadata or {}
    fdf = df.copy()
    key_prefix = app_key(file_name, f"adv_{context}")
    branch_key = f"{key_prefix}_branch"

    categories = [
        "Date & Time",
        "Call Type",
        "B-Party / Contacts",
        "IMEI / Device",
        "Location / Tower",
        "SMS",
        "Duration",
    ]
    pending = st.session_state.get("pending_filter_branch")
    if pending in categories:
        st.session_state[branch_key] = pending
        st.session_state["pending_filter_branch"] = None
        if "filter_branch" in st.query_params:
            del st.query_params["filter_branch"]

    active = st.radio("Filter branches", categories, horizontal=True, key=branch_key)

    if active == "Date & Time":
        preset = st.selectbox("Custom filter preset", ["None", "Night 10PM–5AM", "Weekends only", "Last 30 days"], key=f"{key_prefix}_dt_preset")
        if "date" in fdf.columns and fdf["date"].notna().any():
            min_d = pd.to_datetime(fdf["date"], errors="coerce").min()
            max_d = pd.to_datetime(fdf["date"], errors="coerce").max()
            dr = st.date_input("Date range", (min_d, max_d), key=f"{key_prefix}_date")
            if isinstance(dr, tuple) and len(dr) == 2:
                fdf = fdf[(pd.to_datetime(fdf["date"], errors="coerce") >= pd.Timestamp(dr[0])) & (pd.to_datetime(fdf["date"], errors="coerce") <= pd.Timestamp(dr[1]))]
        hour_range = st.slider("Hour range", 0, 23, (0, 23), key=f"{key_prefix}_hour")
        if "hour" in fdf.columns:
            fdf = fdf[fdf["hour"].between(hour_range[0], hour_range[1], inclusive="both")]
            if preset == "Night 10PM–5AM":
                fdf = fdf[(fdf["hour"] >= 22) | (fdf["hour"] <= 5)]
        dows = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
        sel_dows = st.multiselect("Day of week", dows, default=dows, key=f"{key_prefix}_dow")
        if "day_of_week" in fdf.columns and sel_dows:
            fdf = fdf[fdf["day_of_week"].isin(sel_dows)]
        if "call_datetime" in fdf.columns:
            month_names = sorted(pd.to_datetime(fdf["call_datetime"], errors="coerce").dropna().dt.strftime("%B").unique().tolist())
            sel_months = st.multiselect("Month selector", month_names, default=month_names, key=f"{key_prefix}_months")
            if sel_months:
                fdf = fdf[pd.to_datetime(fdf["call_datetime"], errors="coerce").dt.strftime("%B").isin(sel_months)]
        if preset == "Weekends only" and "day_of_week" in fdf.columns:
            fdf = fdf[fdf["day_of_week"].isin(["Saturday", "Sunday"])]
        if preset == "Last 30 days" and "call_datetime" in fdf.columns and not fdf.empty:
            max_dt = pd.to_datetime(fdf["call_datetime"], errors="coerce").max()
            if pd.notna(max_dt):
                fdf = fdf[pd.to_datetime(fdf["call_datetime"], errors="coerce") >= (max_dt - pd.Timedelta(days=30))]
        if st.toggle("Silent period detector (3+ day gaps)", value=False, key=f"{key_prefix}_silent_toggle"):
            sp = silent_periods(fdf, min_gap_days=3)
            if sp.empty:
                st.success("No silent periods of 3+ days detected.")
            else:
                st.warning("Silent periods detected")
                st.dataframe(sp, use_container_width=True)

    elif active == "Call Type":
        preset = st.selectbox("Custom filter preset", ["None", "Voice only", "SMS only", "VoWiFi only", "Data only"], key=f"{key_prefix}_ct_preset")
        call_types = sorted(fdf.get("call_type", pd.Series([], dtype="object")).dropna().astype(str).unique().tolist())
        selected_types = st.multiselect("Call type multi-select", call_types, default=call_types, key=f"{key_prefix}_types")
        if selected_types and "call_type" in fdf.columns:
            fdf = fdf[fdf["call_type"].astype(str).isin(selected_types)]
        direction = st.selectbox("Direction", ["Both", "Incoming only", "Outgoing only"], key=f"{key_prefix}_dir")
        incoming, outgoing = _direction_masks(fdf)
        if direction == "Incoming only":
            fdf = fdf[incoming]
        elif direction == "Outgoing only":
            fdf = fdf[outgoing]

        jio_codes = ["a_in", "a_out", "A2P_SMSIN", "P2POUT", "P2P_SMSIN", "v_in", "v_out"]
        airtel_codes = ["IN", "OUT", "SMO", "SMT", "DSM"]
        vi_codes = ["Incoming", "Outgoing", "VOICE", "SMS"]
        if st.toggle("Jio-specific call type filter", value=False, key=f"{key_prefix}_jio_on") and "call_type" in fdf.columns:
            sel = st.multiselect("Jio types", jio_codes, default=jio_codes, key=f"{key_prefix}_jio_types")
            fdf = fdf[fdf["call_type"].astype(str).isin(sel)]
        if st.toggle("Airtel-specific call type filter", value=False, key=f"{key_prefix}_airtel_on") and "call_type" in fdf.columns:
            sel = st.multiselect("Airtel types", airtel_codes, default=airtel_codes, key=f"{key_prefix}_airtel_types")
            fdf = fdf[fdf["call_type"].astype(str).isin(sel)]
        if st.toggle("VI-specific call type filter", value=False, key=f"{key_prefix}_vi_on"):
            sel = st.multiselect("VI types", vi_codes, default=vi_codes, key=f"{key_prefix}_vi_types")
            mask = fdf.get("call_type", pd.Series(index=fdf.index, dtype="object")).astype(str).isin(sel) | fdf.get("service_type", pd.Series(index=fdf.index, dtype="object")).astype(str).str.upper().isin(sel)
            fdf = fdf[mask]

        if preset != "None":
            c = fdf.get("call_type", pd.Series(index=fdf.index, dtype="object")).astype(str).str.lower()
            s = fdf.get("service_type", pd.Series(index=fdf.index, dtype="object")).astype(str).str.lower()
            if preset == "Voice only":
                fdf = fdf[s.eq("voice") | c.str.contains("a_in|a_out|v_in|v_out|incoming|outgoing", na=False)]
            elif preset == "SMS only":
                fdf = fdf[s.eq("sms") | c.str.contains("sms|smo|smt|a2p|p2p", na=False)]
            elif preset == "VoWiFi only":
                fdf = fdf[c.str.contains("v_in|v_out|vowifi", na=False)]
            elif preset == "Data only":
                fdf = fdf[s.eq("data") | c.str.contains("dsm|data", na=False)]

    elif active == "B-Party / Contacts":
        preset = st.selectbox("Custom filter preset", ["None", "Night-only contacts", "Incoming-only contacts", "Outgoing-only contacts"], key=f"{key_prefix}_bp_preset")
        bnum = st.text_input("Search specific number", key=f"{key_prefix}_bnum")
        if bnum and "receiver_number" in fdf.columns:
            fdf = fdf[fdf["receiver_number"].astype(str).str.contains(bnum, na=False)]

        top_n = st.slider("Top N contacts", 10, 100, 20, step=10, key=f"{key_prefix}_topn")
        min_calls = st.slider("Min call count threshold", 1, 50, 1, key=f"{key_prefix}_min_calls")
        min_duration = st.slider("Min duration (sec)", 0, 3600, 0, key=f"{key_prefix}_min_dur_contact")

        if "duration_sec" in fdf.columns:
            fdf = fdf[pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0) >= min_duration]

        if "receiver_number" in fdf.columns:
            counts = fdf["receiver_number"].astype(str).value_counts()
            keep = counts[counts >= min_calls].head(top_n).index
            fdf = fdf[fdf["receiver_number"].astype(str).isin(keep)]

        if "receiver_number" in fdf.columns:
            op_opts = ["All", "Jio", "Airtel", "VI", "BSNL", "Unknown"]
            op_sel = st.selectbox("Operator filter (estimated)", op_opts, key=f"{key_prefix}_opsel")
            if op_sel != "All":
                est = fdf["receiver_number"].astype(str).apply(estimate_operator)
                fdf = fdf[est.str.contains(op_sel, case=False, na=False)]

        incoming, outgoing = _direction_masks(fdf)
        if preset == "Night-only contacts" and "hour" in fdf.columns and "receiver_number" in fdf.columns:
            g = fdf.groupby("receiver_number")["hour"].apply(lambda s: bool(((s >= 22) | (s <= 5)).all()))
            keep = g[g].index
            fdf = fdf[fdf["receiver_number"].isin(keep)]
        elif preset == "Incoming-only contacts" and "receiver_number" in fdf.columns:
            g_in = fdf.assign(_in=incoming).groupby("receiver_number")["_in"].sum()
            g_out = fdf.assign(_out=outgoing).groupby("receiver_number")["_out"].sum()
            keep = g_in[(g_in > 0) & (g_out.reindex(g_in.index, fill_value=0) == 0)].index
            fdf = fdf[fdf["receiver_number"].isin(keep)]
        elif preset == "Outgoing-only contacts" and "receiver_number" in fdf.columns:
            g_in = fdf.assign(_in=incoming).groupby("receiver_number")["_in"].sum()
            g_out = fdf.assign(_out=outgoing).groupby("receiver_number")["_out"].sum()
            keep = g_out[(g_out > 0) & (g_in.reindex(g_out.index, fill_value=0) == 0)].index
            fdf = fdf[fdf["receiver_number"].isin(keep)]

    elif active == "IMEI / Device":
        preset = st.selectbox("Custom filter preset", ["None", "Secondary IMEIs only", "Suspicious switches"], key=f"{key_prefix}_imei_preset")
        if "imei" in fdf.columns:
            imeis = sorted([x for x in fdf["imei"].dropna().astype(str).unique().tolist() if x])
            sel_imei = st.selectbox("IMEI selector", ["All"] + imeis, key=f"{key_prefix}_imei")
            if sel_imei != "All":
                fdf = fdf[fdf["imei"].astype(str) == sel_imei]

            if preset in {"Secondary IMEIs only", "Suspicious switches"} and imeis:
                vc = fdf["imei"].astype(str).value_counts()
                primary = vc.index[0] if not vc.empty else None
                if primary:
                    fdf = fdf[fdf["imei"].astype(str) != primary]
            if st.toggle("Show IMEI first/last seen", key=f"{key_prefix}_imei_dates") and "call_datetime" in fdf.columns:
                tbl = fdf.groupby("imei").agg(first_seen=("call_datetime", "min"), last_seen=("call_datetime", "max"), records=("imei", "size")).reset_index()
                st.dataframe(tbl, use_container_width=True)
            if st.toggle("TAC clone detector", key=f"{key_prefix}_tac"):
                tac_tbl = fdf.assign(tac=fdf["imei"].astype(str).str[:8]).groupby("tac").agg(unique_imeis=("imei", lambda s: s.astype(str).nunique()), records=("imei", "size")).reset_index()
                clones = tac_tbl[tac_tbl["unique_imeis"] > 1]
                if clones.empty:
                    st.success("No TAC clone-like pattern detected.")
                else:
                    st.warning("Potential TAC clone pattern detected")
                    st.dataframe(clones, use_container_width=True)

    elif active == "Location / Tower":
        preset = st.selectbox("Custom filter preset", ["None", "Roaming only", "Home circle only"], key=f"{key_prefix}_loc_preset")
        home_circle = str(metadata.get("Circle", metadata.get("Roaming Circle Name", ""))).strip()
        if "roaming_circle" in fdf.columns:
            circles = sorted([c for c in fdf["roaming_circle"].dropna().astype(str).unique().tolist() if c])
            sel_circle = st.selectbox("Circle/State filter", ["All"] + circles, key=f"{key_prefix}_circle")
            if sel_circle != "All":
                fdf = fdf[fdf["roaming_circle"].astype(str) == sel_circle]
            roaming_only = st.toggle("Roaming only toggle", value=(preset == "Roaming only"), key=f"{key_prefix}_roaming_only")
            if roaming_only:
                if home_circle:
                    fdf = fdf[fdf["roaming_circle"].astype(str).str.lower() != home_circle.lower()]
                else:
                    fdf = fdf[fdf["roaming_circle"].notna()]
            elif preset == "Home circle only" and home_circle:
                fdf = fdf[fdf["roaming_circle"].astype(str).str.lower() == home_circle.lower()]
        tower_q = st.text_input("Tower/Cell ID search", key=f"{key_prefix}_tower_search")
        if tower_q and "tower_id" in fdf.columns:
            fdf = fdf[fdf["tower_id"].astype(str).str.contains(tower_q, case=False, na=False)]
        loc_q = st.text_input("Location text search (VI BTS address)", key=f"{key_prefix}_loc_search")
        if loc_q and "tower_location" in fdf.columns:
            fdf = fdf[fdf["tower_location"].astype(str).str.contains(loc_q, case=False, na=False)]

    elif active == "SMS":
        preset = st.selectbox("Custom filter preset", ["None", "A2P only", "P2P only", "Sent SMS only", "OTP likely"], key=f"{key_prefix}_sms_preset")
        sms = fdf[fdf.get("service_type", pd.Series(index=fdf.index, dtype="object")).astype(str).str.upper().eq("SMS")].copy()
        cat = sms.get("call_type", pd.Series(index=sms.index, dtype="object")).astype(str).str.lower()
        sms_category = st.selectbox("SMS category", ["All", "A2P", "P2P", "Sent"], key=f"{key_prefix}_sms_cat")
        if sms_category == "A2P":
            sms = sms[cat.str.contains("a2p", na=False)]
        elif sms_category == "P2P":
            sms = sms[cat.str.contains("p2p|smt|smo", na=False)]
        elif sms_category == "Sent":
            sms = sms[cat.str.contains("smo|p2pout|out", na=False)]

        if "receiver_number" in sms.columns:
            sms["decoded_company"] = sms["receiver_number"].apply(sms_company)
            companies = sorted([x for x in sms["decoded_company"].dropna().astype(str).unique().tolist() if x])
            sel_company = st.selectbox("SMS company filter", ["All"] + companies, key=f"{key_prefix}_sms_company")
            if sel_company != "All":
                sms = sms[sms["decoded_company"] == sel_company]
            sender_q = st.text_input("B-Party SMS sender search", key=f"{key_prefix}_sms_sender")
            if sender_q:
                sms = sms[sms["receiver_number"].astype(str).str.contains(sender_q, case=False, na=False)]
            otp_toggle = st.toggle("OTP SMS filter", value=(preset == "OTP likely"), key=f"{key_prefix}_otp")
            if otp_toggle:
                sms = sms[sms["receiver_number"].astype(str).str.contains(r"52263|56321|OTP", case=False, regex=True, na=False)]

        if preset == "A2P only":
            sms = sms[cat.str.contains("a2p", na=False)]
        elif preset == "P2P only":
            sms = sms[cat.str.contains("p2p|smt|smo", na=False)]
        elif preset == "Sent SMS only":
            sms = sms[cat.str.contains("smo|p2pout|out", na=False)]

        fdf = sms

    elif active == "Duration":
        preset = st.selectbox("Custom filter preset", ["None", "> 5 min", "> 10 min", "> 30 min", "Zero duration"], key=f"{key_prefix}_dur_preset")
        max_dur = int(pd.to_numeric(fdf.get("duration_sec", pd.Series([0])), errors="coerce").fillna(0).max()) if not fdf.empty else 0
        rng = st.slider("Duration range (sec)", 0, max(1, max_dur), (0, max(1, max_dur)), key=f"{key_prefix}_dur_range")
        if "duration_sec" in fdf.columns:
            dur = pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0)
            fdf = fdf[(dur >= rng[0]) & (dur <= rng[1])]
            if st.toggle("Zero duration toggle", value=(preset == "Zero duration"), key=f"{key_prefix}_dur_zero"):
                fdf = fdf[pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0) == 0]
            if preset == "> 5 min":
                fdf = fdf[pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0) > 300]
            elif preset == "> 10 min":
                fdf = fdf[pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0) > 600]
            elif preset == "> 30 min":
                fdf = fdf[pd.to_numeric(fdf["duration_sec"], errors="coerce").fillna(0) > 1800]

    return fdf, active


def render_query_and_filters(file_name: str, df: pd.DataFrame, metadata: dict | None = None):
    st.subheader("Interactive Query & Filters")
    sample_queries = [
        "calls between 11pm and 3am",
        "calls longer than 10 minutes",
        "SMS from HDFC",
        "calls on monday",
        "show heatmap pattern",
    ]
    qcols = st.columns(len(sample_queries))
    for i, sq in enumerate(sample_queries):
        with qcols[i]:
            if st.button(sq, key=app_key(file_name, f"sampleq_{i}")):
                st.session_state[app_key(file_name, "query")] = sq
                st.rerun()

    query = st.text_input(
        "Natural-language query",
        value=st.session_state.get(app_key(file_name, "query"), ""),
        placeholder="e.g. calls from tower Sagar / calls in January / number 9876543210",
        key=app_key(file_name, "query"),
    )

    fdf = df.copy()
    hint = "bar"
    if query.strip():
        fdf, hint = run_query(fdf, query)

    with st.expander("Advanced structured filters", expanded=True):
        fdf, active_branch = render_advanced_filters(file_name, fdf, metadata=metadata, context="query")
        st.caption(f"Active filter branch: {active_branch}")

    st.markdown(f"**{len(fdf):,} records matched**")

    if hint == "timeline":
        d = activity_daily(fdf)
        if not d.empty:
            st.plotly_chart(px.line(d, x="date", y="total", title="Filtered timeline"), use_container_width=True, key=app_key(file_name, "fchart_timeline"))
    elif hint == "heatmap":
        wd = activity_weekday(fdf)
        if not wd.empty:
            st.plotly_chart(px.bar(wd, x="day_of_week", y="count", title="Filtered weekday pattern"), use_container_width=True, key=app_key(file_name, "fchart_heat"))
    elif hint == "map" and "tower_lat" in fdf.columns and "tower_lon" in fdf.columns:
        m = tower_map(fdf)
        if m:
            st_folium(m, height=440, key=app_key(file_name, "fchart_map"))
    else:
        tc = top_contacts(fdf, limit=15)
        if not tc.empty:
            st.plotly_chart(px.bar(tc, x="interactions", y="number", orientation="h", title="Top contacts (filtered)", color="interaction_type"), use_container_width=True, key=app_key(file_name, "fchart_bar"))

    summary_df = pd.DataFrame({"Metric": list(kpis(fdf).keys()), "Value": list(kpis(fdf).values())})
    contacts_df = top_contacts(fdf, limit=100)
    st.download_button(
        "Download Excel for this filter result",
        data=to_excel_bytes(fdf, summary_df=summary_df, contacts_df=contacts_df),
        file_name=f"filtered_{file_name.rsplit('.', 1)[0]}.xlsx",
        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        key=app_key(file_name, "download_filtered"),
    )
    st.dataframe(fdf, use_container_width=True, height=380)


def render_contacts(file_name: str, df: pd.DataFrame):
    top = top_contacts(df, limit=20)
    if top.empty:
        st.info("No contact-level data available.")
        return

    st.plotly_chart(px.bar(top, x="interactions", y="number", orientation="h", color="interaction_type", title="Top 20 contacts"), use_container_width=True, key=app_key(file_name, "contacts_top20"))
    st.caption("This shows the most frequently contacted B-Party numbers and communication mode.")

    lead = top.iloc[0]
    st.markdown("### Top Contact Profile")
    c1, c2, c3, c4 = st.columns(4)
    c1.metric("Number", lead["number"])
    c2.metric("Operator (est)", estimate_operator(lead["number"]))
    c3.metric("Interactions", int(lead["interactions"]))
    c4.metric("Total duration (sec)", int(lead.get("total_duration", 0)))

    with st.expander("OSINT launchpad for top contact"):
        for label, url in number_lookup_links(str(lead["number"])).items():
            st.link_button(label, url)

    long_calls = longest_calls(df, limit=20)
    if not long_calls.empty:
        st.plotly_chart(px.bar(long_calls, x="duration_sec", y="receiver_number", orientation="h", title="Longest duration calls (top 20)"), use_container_width=True, key=app_key(file_name, "contacts_longest"))

    buckets = duration_buckets(df)
    if not buckets.empty:
        st.plotly_chart(px.bar(buckets, x="bucket", y="count", title="Call duration distribution"), use_container_width=True, key=app_key(file_name, "contacts_bucket"))


def render_sms(file_name: str, df: pd.DataFrame):
    sms_df = df[df.get("service_type", pd.Series(index=df.index, dtype="object")).astype(str).str.upper().eq("SMS")].copy()
    if sms_df.empty:
        st.info("No SMS records detected in this CDR.")
        return

    if "receiver_number" in sms_df.columns:
        sms_df["company"] = sms_df["receiver_number"].apply(sms_company)
        company = (
            sms_df.groupby("company")
            .agg(count=("company", "size"), first_sms=("call_datetime", "min"), last_sms=("call_datetime", "max"))
            .reset_index()
            .sort_values("count", ascending=False)
        )
        st.plotly_chart(px.pie(company, names="company", values="count", title="Commercial/P2P SMS sender mix"), use_container_width=True, key=app_key(file_name, "sms_company"))
        st.dataframe(company, use_container_width=True)

    p2p = sms_df[sms_df["receiver_number"].astype(str).str.fullmatch(r"\d{10,13}", na=False)] if "receiver_number" in sms_df.columns else pd.DataFrame()
    if not p2p.empty:
        top_sms = p2p["receiver_number"].value_counts().head(15).reset_index()
        top_sms.columns = ["number", "sms_count"]
        st.plotly_chart(px.bar(top_sms, x="sms_count", y="number", orientation="h", title="Top personal SMS contacts"), use_container_width=True, key=app_key(file_name, "sms_p2p"))


def render_devices(file_name: str, df: pd.DataFrame):
    im = imei_stats(df)
    ims = imsi_stats(df)

    if not im.empty:
        timeline = px.timeline(im, x_start="first_seen", x_end="last_seen", y="imei", color="imei", title="IMEI timeline")
        timeline.update_yaxes(autorange="reversed")
        st.plotly_chart(timeline, use_container_width=True, key=app_key(file_name, "device_imei_timeline"))

        for idx, row in im.iterrows():
            with st.container(border=True):
                c1, c2, c3, c4, c5 = st.columns(5)
                c1.metric("IMEI", row["imei"])
                c2.metric("Records", int(row["records"]))
                c3.metric("First seen", f"{row['first_seen']:%d-%b-%Y}" if pd.notna(row["first_seen"]) else "N/A")
                c4.metric("Last seen", f"{row['last_seen']:%d-%b-%Y}" if pd.notna(row["last_seen"]) else "N/A")
                c5.metric("CDR %", f"{row['pct']}%")
                if int(row["records"]) < 50:
                    st.warning("Secondary / Suspicious Device")
                with st.expander("OSINT lookup"):
                    for label, url in imei_lookup_links(str(row["imei"])).items():
                        st.link_button(label, url, key=app_key(file_name, f"imei_{idx}_{label}"))
                    st.code(f"TAC: {str(row['imei'])[:8]}")

    if not ims.empty:
        st.markdown("### IMSI consistency")
        st.dataframe(ims, use_container_width=True)
        if ims["imsi"].nunique() > 1:
            st.error(f"SIM CARD CHANGE DETECTED — {ims['imsi'].nunique()} unique IMSIs found.")
            imsi_t = px.timeline(ims, x_start="first_seen", x_end="last_seen", y="imsi", color="imsi", title="SIM swap timeline")
            imsi_t.update_yaxes(autorange="reversed")
            st.plotly_chart(imsi_t, use_container_width=True, key=app_key(file_name, "device_imsi_timeline"))


def render_map(file_name: str, df: pd.DataFrame):
    if {"tower_lat", "tower_lon"}.issubset(df.columns):
        m = tower_map(df)
        if m:
            st_folium(m, width=900, height=520, key=app_key(file_name, "map_main"))
            st.caption("Tower map shows frequently used locations with larger markers.")
        else:
            st.info("No valid latitude/longitude tower records found in this file.")
    else:
        st.info("This operator format has no direct tower GPS coordinates. Use BTS/CGI text for lookup.")

    if "tower_id" in df.columns and df["tower_id"].notna().any():
        tower_tbl = df[[c for c in ["tower_id", "tower_location", "call_datetime", "receiver_number"] if c in df.columns]].dropna(subset=["tower_id"]).copy()
        tower_top = tower_tbl["tower_id"].value_counts().head(20).reset_index()
        tower_top.columns = ["tower_id", "records"]
        st.dataframe(tower_top, use_container_width=True)


def render_network(file_name: str, df: pd.DataFrame):
    G = build_graph(df)
    if G.number_of_nodes() == 0:
        st.info("Not enough graph-compatible records.")
        return
    threshold = st.slider("Min interactions to include", 1, 20, 2, key=app_key(file_name, "network_threshold"))
    edges = [(u, v, d.get("weight", 1)) for u, v, d in G.edges(data=True) if d.get("weight", 1) >= threshold]
    sub = G.edge_subgraph([(u, v) for u, v, _ in edges]).copy() if edges else G
    tn = top_nodes(sub, n=100)
    st.plotly_chart(network_chart(sub, tn), use_container_width=True, key=app_key(file_name, "network_main"))


def render_compare(loaded: dict):
    if len(loaded) < 2:
        st.info("Load at least two CDR files to use multi-CDR comparison.")
        return

    names = list(loaded.keys())

    selected = st.multiselect(
        "Select CDRs to compare (choose 2 or more)",
        options=names,
        default=names[:min(len(names), 2)],
        key="cmp_multiselect",
    )

    if len(selected) < 2:
        st.warning("Please select at least 2 CDR files to compare.")
        return

    selected_dfs = {name: loaded[name]["df"] for name in selected}

    # ── Mutual Contacts ──────────────────────────────────────────────────────
    st.markdown("### 👥 Mutual Contacts")
    mutual = mutual_contacts(selected_dfs)
    if mutual.empty:
        st.info("No mutual contacts found across the selected CDR files.")
    else:
        # Colour-code rows found in more CDRs
        def _style_found_in(val):
            if val == len(selected):
                return "background-color:#7f1d1d;color:#fecaca;font-weight:700"
            elif val >= 3:
                return "background-color:#78350f;color:#fef3c7;font-weight:600"
            elif val == 2:
                return "background-color:#1e3a5f;color:#bfdbfe"
            return ""

        styled = mutual.style.applymap(_style_found_in, subset=["found_in"])
        st.dataframe(styled, use_container_width=True)
        st.caption(
            f"🔴 Found in ALL {len(selected)} CDRs  |  🟠 Found in 3+ CDRs  |  🔵 Found in 2 CDRs"
        )

    # ── Common IMEI ──────────────────────────────────────────────────────────
    st.markdown("### 📱 Common IMEI Analysis")
    imei_sets = {
        name: set(
            df.get("imei", pd.Series([], dtype="object")).dropna().astype(str).tolist()
        )
        for name, df in selected_dfs.items()
    }

    # Find which CDRs share each IMEI
    all_imeis: dict[str, list] = {}
    for name, imeis in imei_sets.items():
        for imei in imeis:
            if imei and imei.lower() not in ("nan", "none", ""):
                all_imeis.setdefault(imei, []).append(name)

    shared_imeis = {imei: cdrs for imei, cdrs in all_imeis.items() if len(cdrs) >= 2}
    if shared_imeis:
        imei_rows = [
            {"IMEI": imei, "Found In": len(cdrs), "CDRs": ", ".join(sorted(cdrs))}
            for imei, cdrs in sorted(shared_imeis.items(), key=lambda x: -len(x[1]))
        ]
        imei_df = pd.DataFrame(imei_rows)
        st.error(f"⚠️ COMMON DEVICE DETECTED — {len(shared_imeis)} shared IMEI(s)")
        st.dataframe(imei_df, use_container_width=True)
    else:
        st.success("✅ No shared IMEI detected across selected CDRs.")

    # ── Common Cell / Tower ID ────────────────────────────────────────────────
    st.markdown("### 🗼 Common Cell ID / Tower Analysis")
    tower_sets = {
        name: set(
            df.get("tower_id", pd.Series([], dtype="object")).dropna().astype(str).tolist()
        )
        for name, df in selected_dfs.items()
    }

    all_towers: dict[str, list] = {}
    for name, towers in tower_sets.items():
        for tower in towers:
            if tower and tower.lower() not in ("nan", "none", ""):
                all_towers.setdefault(tower, []).append(name)

    shared_towers = {t: cdrs for t, cdrs in all_towers.items() if len(cdrs) >= 2}
    if shared_towers:
        tower_rows = [
            {"Tower / Cell ID": t, "Found In": len(cdrs), "CDRs": ", ".join(sorted(cdrs))}
            for t, cdrs in sorted(shared_towers.items(), key=lambda x: -len(x[1]))
        ]
        tower_df = pd.DataFrame(tower_rows)
        st.warning(f"⚠️ {len(shared_towers)} shared Cell ID(s) found across selected CDRs")
        st.dataframe(tower_df, use_container_width=True)
    else:
        has_tower_data = any(not df.get("tower_id", pd.Series()).dropna().empty for df in selected_dfs.values())
        if has_tower_data:
            st.success("✅ No shared Cell IDs detected across selected CDRs.")
        else:
            st.info("ℹ️ No tower_id column found in these CDRs — Cell ID comparison not available.")

    # ── Common IMSI ───────────────────────────────────────────────────────────
    st.markdown("### 📟 Common IMSI / SIM Analysis")
    imsi_sets = {
        name: set(
            df.get("imsi", pd.Series([], dtype="object")).dropna().astype(str).tolist()
        )
        for name, df in selected_dfs.items()
    }

    all_imsis: dict[str, list] = {}
    for name, imsis in imsi_sets.items():
        for imsi in imsis:
            if imsi and imsi.lower() not in ("nan", "none", ""):
                all_imsis.setdefault(imsi, []).append(name)

    shared_imsis = {imsi: cdrs for imsi, cdrs in all_imsis.items() if len(cdrs) >= 2}
    if shared_imsis:
        imsi_rows = [
            {"IMSI": imsi, "Found In": len(cdrs), "CDRs": ", ".join(sorted(cdrs))}
            for imsi, cdrs in sorted(shared_imsis.items(), key=lambda x: -len(x[1]))
        ]
        imsi_df = pd.DataFrame(imsi_rows)
        st.error(f"⚠️ COMMON SIM DETECTED — {len(shared_imsis)} shared IMSI(s)")
        st.dataframe(imsi_df, use_container_width=True)
    else:
        st.success("✅ No shared IMSI detected across selected CDRs.")




def apply_theme():
    st.markdown(
        """
        <style>
            .stApp { background-color: #0f0f23; color: #e5e7eb; }
            [data-testid="stSidebar"] { background-color: #111827; }
            h1, h2, h3, h4, h5, h6, p, label, span, div { color: #e5e7eb; }
            [data-testid="stCaptionContainer"] { color: #9ca3af !important; }
            /* Fix white-on-white BaseWeb select/combobox controls (e.g., Compare CDR Subject A/B) */
            div[data-baseweb="select"] > div,
            div[data-baseweb="select"] [role="combobox"] {
                background-color: #0b1220 !important;
                color: #e5e7eb !important;
                border-color: #334155 !important;
            }
            div[data-baseweb="select"] input,
            input[role="combobox"] {
                color: #e5e7eb !important;
                -webkit-text-fill-color: #e5e7eb !important;
                caret-color: #e5e7eb !important;
            }
            div[data-baseweb="select"] svg {
                fill: #cbd5e1 !important;
            }
            /* Dropdown menu options */
            div[data-baseweb="popover"] ul,
            div[data-baseweb="popover"] li {
                background-color: #0b1220 !important;
                color: #e5e7eb !important;
            }
            div[data-baseweb="popover"] li:hover {
                background-color: #1e293b !important;
            }
            /* Global button contrast fix */
            .stButton > button,
            .stDownloadButton > button,
            [data-testid="stBaseButton-secondary"],
            [data-testid="stBaseButton-primary"] {
                background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
                color: #ffffff !important;
                border: 1px solid #3b82f6 !important;
                border-radius: 10px !important;
                font-weight: 600 !important;
            }
            .stButton > button:hover,
            .stDownloadButton > button:hover,
            [data-testid="stBaseButton-secondary"]:hover,
            [data-testid="stBaseButton-primary"]:hover {
                background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
                border-color: #60a5fa !important;
                color: #ffffff !important;
            }
            .stButton > button:disabled,
            .stDownloadButton > button:disabled {
                background: #334155 !important;
                border-color: #475569 !important;
                color: #cbd5e1 !important;
            }
            /* File uploader zone and browse button contrast */
            [data-testid="stFileUploaderDropzone"] {
                background-color: #0b1220 !important;
                border: 1px dashed #3b82f6 !important;
                color: #e5e7eb !important;
            }
            [data-testid="stFileUploaderDropzone"] * {
                color: #e5e7eb !important;
            }
            [data-testid="stFileUploaderDropzone"] button {
                background: #1e40af !important;
                color: #ffffff !important;
                border: 1px solid #3b82f6 !important;
            }
            .small-badge { display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600; }
            /* Hide Streamlit default UI chrome */
            #MainMenu { visibility: hidden !important; display: none !important; }
            [data-testid="stToolbar"] { visibility: hidden !important; display: none !important; }
            [data-testid="stDecoration"] { visibility: hidden !important; display: none !important; }
            footer { visibility: hidden !important; display: none !important; }
            /* Hide Deploy button */
            [data-testid="stAppDeployButton"] { display: none !important; }
            /* Hide running/cycle spinner in toolbar */
            [data-testid="stStatusWidget"] { display: none !important; }
            .stSpinner { display: none !important; }
        </style>
        """,
        unsafe_allow_html=True,
    )


apply_theme()
st.title("📡 CDR Analyzer")
st.caption("Operator-aware forensic dashboard for Indian telecom CDRs (Jio, Airtel, VI, BSNL, Generic).")

if "loaded_cdrs" not in st.session_state:
    st.session_state.loaded_cdrs = {}
if "active_file" not in st.session_state:
    st.session_state.active_file = None
if "active_chart" not in st.session_state:
    st.session_state.active_chart = None
if "cache_restore_checked" not in st.session_state:
    st.session_state.cache_restore_checked = False

chart_from_query = st.query_params.get("chart")
if chart_from_query:
    st.session_state.active_chart = str(chart_from_query)

filter_from_query = st.query_params.get("filter_branch")
if filter_from_query:
    st.session_state["pending_filter_branch"] = str(filter_from_query)

if not st.session_state.loaded_cdrs and not st.session_state.cache_restore_checked:
    restored = _restore_cached_uploads()
    if restored:
        st.session_state.loaded_cdrs.update(restored)
        if st.session_state.active_file is None and restored:
            st.session_state.active_file = next(iter(restored.keys()))
        st.session_state["restored_from_cache_count"] = len(restored)
    st.session_state.cache_restore_checked = True

with st.sidebar:
    st.header("CDR Session")
    if st.session_state.get("restored_from_cache_count", 0):
        st.success(f"Auto-restored {st.session_state['restored_from_cache_count']} cached file(s)")
    uploads = st.file_uploader("Upload CSV/XLSX", type=["csv", "xlsx"], accept_multiple_files=True)

    if uploads:
        for f in uploads:
            if f.name not in st.session_state.loaded_cdrs:
                with st.spinner(f"Parsing {f.name}..."):
                    parsed = parse_upload_cached(f.getvalue(), f.name)
                    st.session_state.loaded_cdrs[f.name] = parsed
                    _cache_uploaded_file(f)

    cclear1, cclear2 = st.columns(2)
    with cclear1:
        if st.button("Reset loaded", key="reset_loaded"):
            st.session_state.loaded_cdrs = {}
            st.session_state.active_file = None
            st.session_state.cache_restore_checked = False
            st.rerun()
    with cclear2:
        if st.button("Clear cache", key="clear_cache"):
            _clear_upload_cache()
            st.session_state.cache_restore_checked = False
            st.success("Upload cache cleared")

    st.markdown("---")
    if st.button("Compare two CDRs", key="go_compare"):
        st.session_state["compare_mode"] = True
    st.subheader("Loaded files")
    if st.session_state.loaded_cdrs:
        for fname, payload in st.session_state.loaded_cdrs.items():
            color = OPERATOR_COLORS.get(payload["operator"], OPERATOR_COLORS["Unknown"])
            info = f"{payload['operator']} • {len(payload['df']):,} rec • {payload.get('date_range', 'N/A')}"
            st.markdown(f"<span class='small-badge' style='background:{color};color:white'>{payload['operator']}</span>  **{fname}**", unsafe_allow_html=True)
            st.caption(info)
        st.session_state.active_file = st.selectbox("Active CDR", list(st.session_state.loaded_cdrs.keys()), index=0 if st.session_state.active_file is None else list(st.session_state.loaded_cdrs.keys()).index(st.session_state.active_file) if st.session_state.active_file in st.session_state.loaded_cdrs else 0)
    else:
        st.info("Upload one or more CDR files to begin.")

if not st.session_state.loaded_cdrs:
    st.info("Upload CDR CSV/XLSX files from the left sidebar to start analysis.")
    st.stop()

active = st.session_state.loaded_cdrs[st.session_state.active_file]
active_name = st.session_state.active_file
active_df = active["df"]
active_operator = active["operator"]

secondary_name = None
secondary_df = None
secondary_meta = None
if len(st.session_state.loaded_cdrs) > 1:
    for fname in st.session_state.loaded_cdrs.keys():
        if fname != active_name:
            secondary_name = fname
            secondary_df = st.session_state.loaded_cdrs[fname]["df"]
            secondary_meta = st.session_state.loaded_cdrs[fname].get("metadata", {})
            break

tabs = st.tabs(["Analysis Tree", "Overview", "Contacts", "SMS", "Devices", "Map", "Network", "Compare CDRs", "Export"])

with tabs[0]:
    render_analysis_tree_tab(active_name, active_df, active.get("metadata", {}), active_operator, secondary_df, secondary_meta)

with tabs[1]:
    render_overview(active_name, active_df)
    st.markdown("---")
    render_query_and_filters(active_name, active_df, active.get("metadata", {}))

with tabs[2]:
    render_contacts(active_name, active_df)

with tabs[3]:
    render_sms(active_name, active_df)

with tabs[4]:
    render_devices(active_name, active_df)

with tabs[5]:
    render_map(active_name, active_df)

with tabs[6]:
    render_network(active_name, active_df)

with tabs[7]:
    render_compare(st.session_state.loaded_cdrs)

with tabs[8]:
    st.subheader("📤 Export CDR")
    st.caption("Enrich and download the active CDR as an Excel workbook.")

    # ── Add-on Columns ─────────────────────────────────────────────────────
    ADDON_OPTIONS = [
        "Contact Frequency",
        "Duration Bucket",
        "Contact Type (Voice/SMS/Mixed)",
        "Hour of Day",
        "Day of Week",
        "Call Direction (Incoming/Outgoing)",
    ]
    addons = st.multiselect(
        "Add-on columns to append to raw CDR rows",
        options=ADDON_OPTIONS,
        default=[],
        key=app_key(active_name, "export_addons"),
        help="These columns are calculated and appended to each CDR row before export.",
    )

    export_df = active_df.copy()

    if "Contact Frequency" in addons and "receiver_number" in export_df.columns:
        freq_map = export_df["receiver_number"].astype(str).value_counts().to_dict()
        export_df["contact_frequency"] = export_df["receiver_number"].astype(str).map(freq_map).fillna(0).astype(int)

    if "Duration Bucket" in addons and "duration_sec" in export_df.columns:
        bins = [-1, 30, 60, 120, 300, 900, 1800, 3600, float("inf")]
        labels = ["0-30s", "30-60s", "1-2m", "2-5m", "5-15m", "15-30m", "30-60m", "60m+"]
        export_df["duration_bucket"] = pd.cut(
            pd.to_numeric(export_df["duration_sec"], errors="coerce").fillna(0),
            bins=bins, labels=labels,
        ).astype(str)

    if "Contact Type (Voice/SMS/Mixed)" in addons and "receiver_number" in export_df.columns:
        tc_map = top_contacts(export_df, limit=100000).set_index("number")["interaction_type"].to_dict()
        export_df["contact_type"] = export_df["receiver_number"].astype(str).map(tc_map).fillna("Unknown")

    if "Hour of Day" in addons:
        if "hour" in export_df.columns:
            export_df["hour_of_day"] = export_df["hour"]
        elif "call_datetime" in export_df.columns:
            export_df["hour_of_day"] = pd.to_datetime(export_df["call_datetime"], errors="coerce").dt.hour

    if "Day of Week" in addons:
        if "day_of_week" in export_df.columns:
            export_df["day_of_week_addon"] = export_df["day_of_week"]
        elif "call_datetime" in export_df.columns:
            export_df["day_of_week_addon"] = pd.to_datetime(export_df["call_datetime"], errors="coerce").dt.day_name()

    if "Call Direction (Incoming/Outgoing)" in addons and "call_type" in export_df.columns:
        ctype = export_df["call_type"].astype(str).str.lower()
        export_df["call_direction"] = "Unknown"
        export_df.loc[ctype.str.contains(r"incoming|a_in|v_in|\bin\b|smt|smsin", regex=True, na=False), "call_direction"] = "Incoming"
        export_df.loc[ctype.str.contains(r"outgoing|a_out|v_out|\bout\b|smo|p2pout", regex=True, na=False), "call_direction"] = "Outgoing"

    # ── Column Selector ────────────────────────────────────────────────────
    with st.expander("🔧 Select columns to include in export", expanded=False):
        all_cols = export_df.columns.tolist()
        selected_cols = st.multiselect(
            "Columns",
            options=all_cols,
            default=all_cols,
            key=app_key(active_name, "export_col_select"),
        )
        if selected_cols:
            export_df = export_df[selected_cols]

    # ── Preview ────────────────────────────────────────────────────────────
    st.markdown(f"**Preview — {len(export_df):,} rows × {len(export_df.columns)} columns**")
    st.dataframe(export_df.head(100), use_container_width=True, height=340)

    # ── Build workbook & download ──────────────────────────────────────────
    summary_df = pd.DataFrame({"Metric": list(kpis(active_df).keys()), "Value": list(kpis(active_df).values())})
    contacts_df = top_contacts(active_df, limit=200)
    st.download_button(
        "⬇️ Download Excel (CDR Data + Summary + Contacts)",
        data=to_excel_bytes(export_df, summary_df=summary_df, contacts_df=contacts_df),
        file_name=f"{active_name.rsplit('.', 1)[0]}_analysis.xlsx",
        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        key=app_key(active_name, "download_full"),
    )