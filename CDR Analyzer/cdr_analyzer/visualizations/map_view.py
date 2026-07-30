# visualizations/map_view.py

import folium
import pandas as pd
from streamlit_folium import st_folium

def tower_map(df: pd.DataFrame):
    if "tower_lat" not in df.columns or "tower_lon" not in df.columns:
        return None

    valid = df.dropna(subset=["tower_lat", "tower_lon"])
    if valid.empty:
        return None

    center_lat = valid["tower_lat"].mean()
    center_lon = valid["tower_lon"].mean()
    m = folium.Map(location=[center_lat, center_lon], zoom_start=10)

    tower_counts = valid.groupby(["tower_lat","tower_lon"]).size().reset_index(name="calls")
    for _, row in tower_counts.iterrows():
        folium.CircleMarker(
            location=[row["tower_lat"], row["tower_lon"]],
            radius=min(5 + row["calls"] * 0.5, 25),
            color="#e05c1a",
            fill=True,
            fill_color="#e05c1a",
            fill_opacity=0.6,
            popup=f"Calls: {row['calls']}"
        ).add_to(m)

    return m