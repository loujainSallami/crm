import pandas as pd
from sqlalchemy import text
from config import target_engine


def create_datamart_tables():
    sql_statements = [
        """
        CREATE TABLE IF NOT EXISTS dim_agent (
            agent_id INT PRIMARY KEY,
            user VARCHAR(50) UNIQUE
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS dim_campaign (
            campaign_id VARCHAR(20) PRIMARY KEY,
            campaign_name VARCHAR(100) NULL
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS dim_status (
            status_id INT PRIMARY KEY,
            status VARCHAR(20) UNIQUE
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS dim_date (
            date_id INT PRIMARY KEY,
            date DATE UNIQUE,
            day INT,
            month INT,
            year INT
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS fact_calls (
            fact_id BIGINT AUTO_INCREMENT PRIMARY KEY,
            agent_id INT NOT NULL,
            campaign_id VARCHAR(20) NOT NULL,
            status_id INT NOT NULL,
            date_id INT NOT NULL,
            lead_id BIGINT NULL,
            call_type ENUM('OUTBOUND','INBOUND') NOT NULL,
            call_count INT NOT NULL DEFAULT 1,
            total_duration INT NOT NULL DEFAULT 0,
            queue_seconds DECIMAL(10,2) DEFAULT 0,
            queue_position INT DEFAULT 0,
            source_call_datetime DATETIME NULL
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS fact_agent_activity (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            agent_id INT,
            campaign_id VARCHAR(20),
            date_id INT,
            talk_sec INT DEFAULT 0,
            wait_sec INT DEFAULT 0,
            pause_sec INT DEFAULT 0,
            dispo_sec INT DEFAULT 0,
            dead_sec INT DEFAULT 0,
            status VARCHAR(20),
            event_time DATETIME
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS fact_recordings (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            agent_id INT,
            lead_id BIGINT,
            start_time DATETIME,
            end_time DATETIME,
            duration_sec INT,
            filename VARCHAR(255)
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS fact_live_snapshot (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            agent_id INT,
            campaign_id VARCHAR(20),
            status VARCHAR(20),
            calls_today INT,
            last_update DATETIME
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS fact_live_metrics (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            campaign_id VARCHAR(20),
            snapshot_time DATETIME,
            leads_in_hopper INT DEFAULT 0,
            auto_calls_count INT DEFAULT 0,
            avg_queue_position DECIMAL(10,2) DEFAULT 0
        )
        """,
        """
        CREATE TABLE IF NOT EXISTS etl_job_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            run_at DATETIME NOT NULL,
            total_outbound INT DEFAULT 0,
            total_inbound INT DEFAULT 0,
            total_loaded INT DEFAULT 0,
            status VARCHAR(20) NOT NULL,
            message TEXT
        )
        """
    ]

    with target_engine.begin() as conn:
        for stmt in sql_statements:
            conn.execute(text(stmt))


def truncate_tables():
    tables = [
        "fact_calls",
        "fact_agent_activity",
        "fact_recordings",
        "fact_live_snapshot",
        "fact_live_metrics",
        "dim_agent",
        "dim_campaign",
        "dim_status",
        "dim_date",
    ]

    with target_engine.begin() as conn:
        conn.execute(text("SET FOREIGN_KEY_CHECKS = 0"))
        for table in tables:
            conn.execute(text(f"TRUNCATE TABLE {table}"))
        conn.execute(text("SET FOREIGN_KEY_CHECKS = 1"))


def load_dimensions(dim_agent, dim_status, dim_campaign, dim_date):
    dim_agent[["agent_id", "user"]].to_sql(
        "dim_agent",
        target_engine,
        if_exists="append",
        index=False
    )

    dim_status[["status_id", "status"]].to_sql(
        "dim_status",
        target_engine,
        if_exists="append",
        index=False
    )

    dim_campaign[["campaign_id", "campaign_name"]].to_sql(
        "dim_campaign",
        target_engine,
        if_exists="append",
        index=False
    )

    dim_date[["date_id", "date", "day", "month", "year"]].to_sql(
        "dim_date",
        target_engine,
        if_exists="append",
        index=False
    )


def load_fact_calls(fact_calls):
    fact_calls[[
        "agent_id",
        "campaign_id",
        "status_id",
        "date_id",
        "lead_id",
        "call_type",
        "call_count",
        "total_duration",
        "queue_seconds",
        "queue_position",
        "source_call_datetime"
    ]].to_sql(
        "fact_calls",
        target_engine,
        if_exists="append",
        index=False
    )


def load_fact_agent_activity(df):
    df[[
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
    ]].to_sql(
        "fact_agent_activity",
        target_engine,
        if_exists="append",
        index=False
    )


def load_fact_recordings(df):
    df[[
        "agent_id",
        "lead_id",
        "start_time",
        "end_time",
        "duration_sec",
        "filename"
    ]].to_sql(
        "fact_recordings",
        target_engine,
        if_exists="append",
        index=False
    )


def load_fact_live(df):
    df[[
        "agent_id",
        "campaign_id",
        "status",
        "calls_today",
        "last_update"
    ]].to_sql(
        "fact_live_snapshot",
        target_engine,
        if_exists="append",
        index=False
    )


def load_fact_live_metrics(df):
    df[[
        "campaign_id",
        "snapshot_time",
        "leads_in_hopper",
        "auto_calls_count",
        "avg_queue_position"
    ]].to_sql(
        "fact_live_metrics",
        target_engine,
        if_exists="append",
        index=False
    )


def write_etl_log(total_outbound: int, total_inbound: int, total_loaded: int, status: str, message: str):
    with target_engine.begin() as conn:
        conn.execute(
            text("""
                INSERT INTO etl_job_log (
                    run_at,
                    total_outbound,
                    total_inbound,
                    total_loaded,
                    status,
                    message
                )
                VALUES (
                    NOW(),
                    :outbound,
                    :inbound,
                    :loaded,
                    :status,
                    :message
                )
            """),
            {
                "outbound": total_outbound,
                "inbound": total_inbound,
                "loaded": total_loaded,
                "status": status,
                "message": message,
            }
        )


def load_all(data: dict):
    create_datamart_tables()
    truncate_tables()

    load_dimensions(
        data["dim_agent"],
        data["dim_status"],
        data["dim_campaign"],
        data["dim_date"]
    )

    load_fact_calls(data["fact_calls"])
    load_fact_agent_activity(data["fact_agent_activity"])
    load_fact_recordings(data["fact_recordings"])
    load_fact_live(data["fact_live_snapshot"])
    load_fact_live_metrics(data["fact_live_metrics"])