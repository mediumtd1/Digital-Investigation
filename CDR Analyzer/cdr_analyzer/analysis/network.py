# analysis/network.py

import pandas as pd
import networkx as nx

def build_graph(df: pd.DataFrame) -> nx.Graph:
    G = nx.Graph()
    if "caller_number" not in df.columns or "receiver_number" not in df.columns:
        return G
    for _, row in df.iterrows():
        a = str(row["caller_number"])
        b = str(row["receiver_number"])
        if G.has_edge(a, b):
            G[a][b]["weight"] += 1
        else:
            G.add_edge(a, b, weight=1)
    return G

def top_nodes(G: nx.Graph, n: int = 20):
    """Return top N nodes by degree for display."""
    sorted_nodes = sorted(G.degree, key=lambda x: x[1], reverse=True)[:n]
    return [n for n, _ in sorted_nodes]


def detect_clusters(G: nx.Graph):
    if G.number_of_nodes() == 0:
        return []
    try:
        communities = nx.algorithms.community.greedy_modularity_communities(G)
        return [list(c) for c in communities]
    except Exception:
        return []