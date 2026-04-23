import pandas as pd


# ================= CLEAN =================
def clean_calls(df: pd.DataFrame, call_type: str) -> pd.DataFrame:
    df = df.copy()

    df["user"] = df["user"].fillna("UNKNOWN").astype(str).str.strip()
    df["status"] = df["status"].fillna("UNKNOWN").astype(str).str.strip().str.upper()
    df["campaign_id"] = df["campaign_id"].fillna("").astype(str).str.strip()

    df["lead_id"] = pd.to_numeric(df.get("lead_id"), errors="coerce")

    df["call_date"] = pd.to_datetime(df["call_date"], errors="coerce")
    df["length_in_sec"] = pd.to_numeric(df["length_in_sec"], errors="coerce").fillna(0)

    # inbound only fields
    if "queue_seconds" not in df.columns:
        df["queue_seconds"] = 0
    if "queue_position" not in df.columns:
        df["queue_position"] = 0

    df["queue_seconds"] = pd.to_numeric(df["queue_seconds"], errors="coerce").fillna(0)
    df["queue_position"] = pd.to_numeric(df["queue_position"], errors="coerce").fillna(0)

    df.loc[df["length_in_sec"] < 0, "length_in_sec"] = 0

    df = df.dropna(subset=["call_date"])
    df = df[df["campaign_id"] != ""]

    df["call_type"] = call_type
    df["call_count"] = 1
    df["date"] = df["call_date"].dt.date

    df = df.drop_duplicates()

    return df.reset_index(drop=True)


# ================= DIMENSIONS =================
def build_dimensions(calls: pd.DataFrame, campaigns: pd.DataFrame):
    dim_agent = calls[["user"]].drop_duplicates().reset_index(drop=True)
    dim_agent["agent_id"] = range(1, len(dim_agent) + 1)

    dim_status = calls[["status"]].drop_duplicates().reset_index(drop=True)
    dim_status["status_id"] = range(1, len(dim_status) + 1)

    dim_campaign = campaigns[["campaign_id", "campaign_name"]].drop_duplicates().reset_index(drop=True)

    dim_date = calls[["date"]].drop_duplicates().reset_index(drop=True)
    dim_date["date_id"] = range(1, len(dim_date) + 1)
    dim_date["day"] = pd.to_datetime(dim_date["date"]).dt.day
    dim_date["month"] = pd.to_datetime(dim_date["date"]).dt.month
    dim_date["year"] = pd.to_datetime(dim_date["date"]).dt.year

    return dim_agent, dim_status, dim_campaign, dim_date


# ================= FACT CALLS =================
def build_fact_calls(calls, dim_agent, dim_status, dim_date):
    fact = calls.merge(dim_agent, on="user", how="left")
    fact = fact.merge(dim_status, on="status", how="left")
    fact = fact.merge(dim_date[["date_id", "date"]], on="date", how="left")

    fact_calls = fact[[
        "agent_id",
        "campaign_id",
        "status_id",
        "date_id",
        "lead_id",
        "call_type",
        "call_count",
        "length_in_sec",
        "queue_seconds",
        "queue_position",
        "call_date"
    ]].copy()

    fact_calls = fact_calls.rename(columns={
        "length_in_sec": "total_duration",
        "call_date": "source_call_datetime"
    })

    return fact_calls


# ================= FACT AGENT =================
def build_fact_agent_activity(df, dim_agent, dim_date):
    df = df.copy()

    df["user"] = df["user"].fillna("UNKNOWN").astype(str).str.strip()
    df["campaign_id"] = df["campaign_id"].fillna("").astype(str).str.strip()
    df["status"] = df["status"].fillna("UNKNOWN").astype(str).str.strip().str.upper()

    df["event_time"] = pd.to_datetime(df["event_time"], errors="coerce")
    df = df.dropna(subset=["event_time"])

    df["date"] = df["event_time"].dt.date

    for col in ["talk_sec", "wait_sec", "pause_sec", "dispo_sec", "dead_sec"]:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0)

    fact = df.merge(dim_agent, on="user", how="left")
    fact = fact.merge(dim_date[["date_id", "date"]], on="date", how="left")

    return fact[[
        "agent_id",
        "campaign_id",
        "date_id",
        "talk_sec",
        "wait_sec",
        "pause_sec",
        "dispo_sec",
        "dead_sec",
        "status",
        "event_time"
    ]]


# ================= FACT RECORDINGS =================
def build_fact_recordings(df, dim_agent):
    df = df.copy()

    df["user"] = df["user"].fillna("UNKNOWN").astype(str).str.strip()
    df["start_time"] = pd.to_datetime(df["start_time"], errors="coerce")
    df["end_time"] = pd.to_datetime(df["end_time"], errors="coerce")
    df["length_in_sec"] = pd.to_numeric(df["length_in_sec"], errors="coerce").fillna(0)

    fact = df.merge(dim_agent, on="user", how="left")

    return fact[[
        "agent_id",
        "lead_id",
        "start_time",
        "end_time",
        "length_in_sec",
        "filename"
    ]].rename(columns={"length_in_sec": "duration_sec"})


# ================= FACT LIVE =================
def build_fact_live(df, dim_agent):
    df = df.copy()

    df["user"] = df["user"].fillna("UNKNOWN").astype(str).str.strip()

    fact = df.merge(dim_agent, on="user", how="left")

    return fact[[
        "agent_id",
        "campaign_id",
        "status",
        "calls_today",
        "last_call_time"
    ]].rename(columns={"last_call_time": "last_update"})


# ================= LIVE METRICS =================
def build_fact_live_metrics(hopper, auto_calls):
    hopper_count = len(hopper)
    auto_calls_count = len(auto_calls)

    avg_queue_position = 0
    if len(auto_calls) > 0 and "queue_position" in auto_calls.columns:
        avg_queue_position = auto_calls["queue_position"].astype(float).mean()

    campaign_id = None
    if len(hopper) > 0:
        campaign_id = hopper["campaign_id"].iloc[0]

    return pd.DataFrame([{
        "campaign_id": campaign_id,
        "snapshot_time": pd.Timestamp.now(),
        "leads_in_hopper": hopper_count,
        "auto_calls_count": auto_calls_count,
        "avg_queue_position": round(avg_queue_position, 2),
    }])


# ================= KPI =================
def build_kpis(calls: pd.DataFrame):
    total_calls = len(calls)

    sale_calls = len(calls[calls["status"] == "SALE"])
    callbk_calls = len(calls[calls["status"] == "CALLBK"])
    contact_calls = len(calls[calls["status"].isin(["SALE", "CALLBK"])])
    abandon_calls = len(calls[calls["status"].isin(["DROP", "AB", "ABANDON"])])

    avg_duration = calls["length_in_sec"].mean() if total_calls else 0
    avg_wait = calls["queue_seconds"].mean() if total_calls else 0

    return {
        "total_calls": int(total_calls),
        "total_outbound": int(len(calls[calls["call_type"] == "OUTBOUND"])),
        "total_inbound": int(len(calls[calls["call_type"] == "INBOUND"])),
        "total_leads": int(calls["lead_id"].nunique()),
        "avg_duration_sec": float(round(avg_duration, 2)),
        "avg_wait_sec": float(round(avg_wait, 2)),
        "conversion_rate": float(round((sale_calls / total_calls) * 100, 2)) if total_calls else 0.0,
        "contact_rate": float(round((contact_calls / total_calls) * 100, 2)) if total_calls else 0.0,
        "abandon_rate": float(round((abandon_calls / total_calls) * 100, 2)) if total_calls else 0.0,
        "sale_rate": float(round((sale_calls / total_calls) * 100, 2)) if total_calls else 0.0,
        "callbk_rate": float(round((callbk_calls / total_calls) * 100, 2)) if total_calls else 0.0,
    }

# ================= MAIN =================
def transform_all(raw):
    outbound_clean = clean_calls(raw["outbound"], "OUTBOUND")
    inbound_clean = clean_calls(raw["inbound"], "INBOUND")

    calls = pd.concat([outbound_clean, inbound_clean], ignore_index=True)

    dim_agent, dim_status, dim_campaign, dim_date = build_dimensions(
        calls,
        raw["campaigns"]
    )

    fact_calls = build_fact_calls(calls, dim_agent, dim_status, dim_date)
    fact_agent_activity = build_fact_agent_activity(raw["agent_activity"], dim_agent, dim_date)
    fact_recordings = build_fact_recordings(raw["recordings"], dim_agent)
    fact_live = build_fact_live(raw["live_agents"], dim_agent)
    fact_live_metrics = build_fact_live_metrics(raw["hopper"], raw["auto_calls"])

    kpis = build_kpis(calls)

    return {
        "calls": calls,
        "dim_agent": dim_agent,
        "dim_status": dim_status,
        "dim_campaign": dim_campaign,
        "dim_date": dim_date,
        "fact_calls": fact_calls,
        "fact_agent_activity": fact_agent_activity,
        "fact_recordings": fact_recordings,
        "fact_live_snapshot": fact_live,
        "fact_live_metrics": fact_live_metrics,
        "kpis": kpis
    }