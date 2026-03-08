from flask import Flask, jsonify
from sqlalchemy import create_engine, text

# Initialisation de l'application Flask
app = Flask(__name__)

# Connexion à SQLite
DATABASE_URI = "./output/agent_data_mart.db"
engine = create_engine(f"sqlite:///{DATABASE_URI}")

# Route : Statistiques globales
@app.route('/api/global-stats', methods=['GET'])
def get_global_stats():
    query = """
        SELECT 
            SUM(CALLS) AS total_calls, 
            AVG(WAIT) AS avg_wait_time, 
            COUNT(DISTINCT "USER NAME") AS total_agents 
        FROM agent_performance
    """
    with engine.connect() as conn:
        result = conn.execute(text(query)).fetchone()
    return jsonify({
        "total_calls": result[0] if result[0] is not None else 0,
        "avg_wait_time": result[1] if result[1] is not None else 0,
        "total_agents": result[2] if result[2] is not None else 0
    })

# Route : Performances des agents
@app.route('/api/agent-performance', methods=['GET'])
def get_agent_performance():
    query = """
        SELECT 
            "USER NAME" AS agent_name, 
            SUM(CALLS) AS total_calls, 
            SUM(WAIT) AS total_wait_time, 
            SUM(TALK) AS total_talk_time 
        FROM agent_performance 
        GROUP BY "USER NAME"
    """
    with engine.connect() as conn:
        result = conn.execute(text(query)).fetchall()
    agents = [
        {
            "agent_name": row[0],
            "total_calls": row[1],
            "total_wait_time": row[2],
            "total_talk_time": row[3]
        }
        for row in result
    ]
    return jsonify(agents)

# Route : Analyse des pauses
@app.route('/api/pause-analysis', methods=['GET'])
def get_pause_analysis():
    query = """
        SELECT 
            "USER NAME" AS agent_name, 
            SUM(PAUSE) AS total_pause_time 
        FROM pause_breakdown 
        GROUP BY "USER NAME"
    """
    with engine.connect() as conn:
        result = conn.execute(text(query)).fetchall()
    pauses = [
        {
            "agent_name": row[0],
            "total_pause_time": row[1]
        }
        for row in result
    ]
    return jsonify(pauses)

# Lancer le serveur Flask
if __name__ == "__main__":
    app.run(debug=True, port=8001)
