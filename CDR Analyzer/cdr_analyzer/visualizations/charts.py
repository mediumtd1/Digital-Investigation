# visualizations/charts.py

import plotly.express as px
import plotly.graph_objects as go
import pandas as pd

def bar_top_callers(df: pd.DataFrame, title="Top callers"):
    if df.empty:
        return go.Figure()
    fig = px.bar(df, x=df.columns[0], y=df.columns[1],
                 title=title, color_discrete_sequence=["#4f8ef7"])
    fig.update_layout(xaxis_title="Number", yaxis_title="Calls")
    return fig

def pie_call_types(df: pd.DataFrame):
    if df.empty:
        return go.Figure()
    fig = px.pie(df, names=df.columns[0], values=df.columns[1],
                 title="Call type distribution")
    return fig

def line_calls_per_day(df: pd.DataFrame):
    if df.empty:
        return go.Figure()
    fig = px.line(df, x="date", y="call_count",
                  title="Call volume over time", markers=True)
    fig.update_layout(xaxis_title="Date", yaxis_title="Calls")
    return fig

def bar_calls_per_hour(df: pd.DataFrame):
    if df.empty:
        return go.Figure()
    fig = px.bar(df, x="hour", y="call_count",
                 title="Active hours distribution",
                 color_discrete_sequence=["#f7874f"])
    fig.update_layout(xaxis_title="Hour of day", yaxis_title="Calls",
                      xaxis=dict(tickmode="linear", tick0=0, dtick=1))
    return fig

def network_chart(G, top_nodes):
    import networkx as nx
    subG = G.subgraph(top_nodes)
    pos = nx.spring_layout(subG, seed=42)

    edge_x, edge_y = [], []
    for u, v in subG.edges():
        x0, y0 = pos[u]
        x1, y1 = pos[v]
        edge_x += [x0, x1, None]
        edge_y += [y0, y1, None]

    node_x = [pos[n][0] for n in subG.nodes()]
    node_y = [pos[n][1] for n in subG.nodes()]
    node_labels = list(subG.nodes())
    node_sizes = [10 + subG.degree(n) * 3 for n in subG.nodes()]

    fig = go.Figure()
    fig.add_trace(go.Scatter(x=edge_x, y=edge_y, mode="lines",
                             line=dict(width=0.8, color="#aaa"), hoverinfo="none"))
    fig.add_trace(go.Scatter(x=node_x, y=node_y, mode="markers+text",
                             text=node_labels, textposition="top center",
                             marker=dict(size=node_sizes, color="#4f8ef7",
                                         line=dict(width=1, color="#fff")),
                             hovertext=node_labels))
    fig.update_layout(title="Call network graph", showlegend=False,
                      xaxis=dict(showgrid=False, zeroline=False, showticklabels=False),
                      yaxis=dict(showgrid=False, zeroline=False, showticklabels=False))
    return fig


def imei_gantt(imei_df: pd.DataFrame):
    if imei_df.empty:
        return go.Figure()
    fig = px.timeline(imei_df, x_start="first_seen", x_end="last_seen", y="imei", color="imei", title="IMEI timeline")
    fig.update_yaxes(autorange="reversed")
    return fig


def tower_scatter(df: pd.DataFrame):
    if "tower_id" not in df.columns or "call_datetime" not in df.columns:
        return go.Figure()
    d = df.dropna(subset=["tower_id", "call_datetime"]).copy()
    if d.empty:
        return go.Figure()
    d["call_datetime"] = pd.to_datetime(d["call_datetime"], errors="coerce")
    d = d.dropna(subset=["call_datetime"])
    fig = px.scatter(d, x="call_datetime", y="tower_id", title="Tower activity timeline", opacity=0.7)
    return fig


def dual_timeline(df_a: pd.DataFrame, df_b: pd.DataFrame, meta_a: dict | None = None, meta_b: dict | None = None):
    fig = go.Figure()
    if "date" in df_a.columns:
        a = df_a.groupby("date").size().reset_index(name="count")
        fig.add_trace(go.Scatter(x=a["date"], y=a["count"], mode="lines+markers", name=(meta_a or {}).get("Subject", "Subject A")))
    if "date" in df_b.columns:
        b = df_b.groupby("date").size().reset_index(name="count")
        fig.add_trace(go.Scatter(x=b["date"], y=-b["count"], mode="lines+markers", name=(meta_b or {}).get("Subject", "Subject B")))
    fig.update_layout(title="Timeline overlap (A above / B below)", yaxis_title="Activity")
    return fig