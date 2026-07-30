# visualizations/heatmap.py

import plotly.graph_objects as go

def call_heatmap(pivot_df):
    if pivot_df.empty:
        return go.Figure()
    fig = go.Figure(data=go.Heatmap(
        z=pivot_df.values,
        x=[str(h) for h in pivot_df.columns],
        y=list(pivot_df.index),
        colorscale="YlOrRd",
        hoverongaps=False
    ))
    fig.update_layout(
        title="Call frequency heatmap (day × hour)",
        xaxis_title="Hour of day",
        yaxis_title="Day of week"
    )
    return fig