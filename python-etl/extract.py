import pandas as pd
from config import source_engine, CAMPAIGN_ID

def extract_outbound_calls():
    return pd.read_sql("""
        SELECT 
            user,
            lead_id,
            status,
            campaign_id,
            call_date,
            length_in_sec
        FROM vicidial_log
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_inbound_calls():
    return pd.read_sql("""
        SELECT
            user,
            lead_id,
            status,
            campaign_id,
            call_date,
            length_in_sec,
            queue_seconds,
            queue_position
        FROM vicidial_closer_log
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_agent_activity():
    return pd.read_sql("""
        SELECT
            user,
            campaign_id,
            event_time,
            talk_sec,
            wait_sec,
            pause_sec,
            dispo_sec,
            dead_sec,
            status
        FROM vicidial_agent_log
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_recordings():
    return pd.read_sql("""
        SELECT
            user,
            lead_id,
            start_time,
            end_time,
            length_in_sec,
            filename
        FROM recording_log
    """, source_engine)

def extract_live_agents():
    return pd.read_sql("""
        SELECT
            user,
            campaign_id,
            status,
            calls_today,
            last_call_time
        FROM vicidial_live_agents
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_hopper():
    return pd.read_sql("""
        SELECT
            lead_id,
            campaign_id,
            status,
            list_id
        FROM vicidial_hopper
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_auto_calls():
    return pd.read_sql("""
        SELECT
            campaign_id,
            status,
            lead_id,
            queue_position,
            call_time,
            call_type,
            agent_grab
        FROM vicidial_auto_calls
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_campaigns():
    return pd.read_sql("""
        SELECT
            campaign_id,
            campaign_name
        FROM vicidial_campaigns
        WHERE campaign_id = %s
    """, source_engine, params=(CAMPAIGN_ID,))

def extract_all():
    outbound = extract_outbound_calls()
    inbound = extract_inbound_calls()
    agent_activity = extract_agent_activity()
    recordings = extract_recordings()
    live_agents = extract_live_agents()
    hopper = extract_hopper()
    auto_calls = extract_auto_calls()
    campaigns = extract_campaigns()

    return {
        "outbound": outbound,
        "inbound": inbound,
        "agent_activity": agent_activity,
        "recordings": recordings,
        "live_agents": live_agents,
        "hopper": hopper,
        "auto_calls": auto_calls,
        "campaigns": campaigns,
    }