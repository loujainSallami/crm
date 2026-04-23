import os
from dotenv import load_dotenv
from sqlalchemy import create_engine

load_dotenv()

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_USER = os.getenv("DB_USER", "root")
DB_PASS = os.getenv("DB_PASS", "root")
SOURCE_DB = os.getenv("SOURCE_DB", "asterisk")
TARGET_DB = os.getenv("TARGET_DB", "datamart")
CAMPAIGN_ID = os.getenv("CAMPAIGN_ID", "TEST1")

SOURCE_URL = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{SOURCE_DB}"
TARGET_URL = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{TARGET_DB}"

source_engine = create_engine(SOURCE_URL, pool_pre_ping=True, future=True)
target_engine = create_engine(TARGET_URL, pool_pre_ping=True, future=True)