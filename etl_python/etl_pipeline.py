import os
import pandas as pd
from sqlalchemy import create_engine
from sqlalchemy.sql import text

# Informations de connexion MySQL
MYSQL_USER = "root"
MYSQL_PASSWORD = "root"
MYSQL_HOST = "localhost"
MYSQL_PORT = 3306
MYSQL_DATABASE = "datamart_crm"

# Chemins des fichiers d'entrée
input_dir = "./input/"
performance_file = os.path.join(input_dir, "agent_performance.csv")

# Fonction pour convertir le temps en secondes
def time_to_seconds(time_str):
    try:
        parts = list(map(int, time_str.split(":")))
        if len(parts) == 2:  # Format mm:ss
            mins, secs = parts
            return mins * 60 + secs
        elif len(parts) == 3:  # Format hh:mm:ss
            hours, mins, secs = parts
            return hours * 3600 + mins * 60 + secs
        else:
            return 0
    except ValueError:
        return 0

# Fonction principale pour insérer les données
def load_data_to_datamart():
    # Lire le fichier CSV
    if not os.path.exists(performance_file):
        raise FileNotFoundError(f"Le fichier {performance_file} est introuvable.")

    performance_df = pd.read_csv(performance_file, skiprows=4)

    # Nettoyer les données
    performance_df = performance_df[performance_df['USER NAME'] != "TOTALS"]  # Supprimer les totaux
    performance_df = performance_df.drop_duplicates(subset=['ID'])  # Supprimer les doublons

    # Convertir les colonnes de temps en secondes
    time_columns = ['PAUSE', 'WAIT', 'TALK', 'DISPO', 'DEAD', 'CUSTOMER']
    for col in time_columns:
        if col in performance_df.columns:
            performance_df[col] = performance_df[col].apply(time_to_seconds)

    # Connexion à la base de données
    engine = create_engine(
        f"mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DATABASE}"
    )

    # Charger les données dans la base de données
    with engine.connect() as conn:
        conn.execute(text("SET FOREIGN_KEY_CHECKS = 0;"))

        # Charger les agents dans une table dim_agent
        dim_agent = performance_df[['ID', 'USER NAME']].drop_duplicates()
        dim_agent.columns = ['agent_id', 'agent_name']
        dim_agent.to_sql("dim_agent", engine, if_exists="replace", index=False)
        print("Dimension 'dim_agent' chargée avec succès.")

        # Charger les performances dans la table fact_agent_performance
        fact_agent_performance = performance_df.rename(columns={
            'ID': 'agent_id',
            'CALLS': 'total_calls',
            'PAUSE': 'total_pause_time',
            'WAIT': 'total_wait_time',
            'TALK': 'total_talk_time',
            'DISPO': 'total_dispo_time',
            'CUSTOMER': 'total_customer_time',
            'SALE': 'total_sales'
        })
        fact_agent_performance['date_id'] = "2025-01-13"  # Date statique basée sur le fichier
        fact_agent_performance.to_sql("fact_agent_performance", engine, if_exists="replace", index=False)
        print("Table de faits 'fact_agent_performance' chargée avec succès.")

        conn.execute(text("SET FOREIGN_KEY_CHECKS = 1;"))

if __name__ == "__main__":
    load_data_to_datamart()
