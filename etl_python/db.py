from flask import Flask, jsonify
from flask_cors import CORS
import mysql.connector
from mysql.connector import Error
from sshtunnel import SSHTunnelForwarder
from datetime import timedelta

app = Flask(__name__)
CORS(app)  # Autoriser les requêtes CORS depuis le front-end

# Informations de connexion
SSH_CONFIG = {
    "host": "sdch.ophony.com",
    "username": "root",
    "password": "isic2024"
}

MYSQL_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "xarxus",
    "password": "xarixoph2012",
    "database": "xarxdb_200"
}


def execute_query(query):
    try:
        with SSHTunnelForwarder(
            (SSH_CONFIG["host"], 22),
            ssh_username=SSH_CONFIG["username"],
            ssh_password=SSH_CONFIG["password"],
            remote_bind_address=(MYSQL_CONFIG["host"], MYSQL_CONFIG["port"])
        ) as tunnel:
            connection = mysql.connector.connect(
                host="127.0.0.1",
                port=tunnel.local_bind_port,
                user=MYSQL_CONFIG["user"],
                password=MYSQL_CONFIG["password"],
                database=MYSQL_CONFIG["database"]
            )

            cursor = connection.cursor(dictionary=True)
            cursor.execute(query)
            results = cursor.fetchall()

            # Convertir timedelta en format lisible
            for row in results:
                for key, value in row.items():
                    if isinstance(value, timedelta):
                        row[key] = str(value)

            return results
    except Error as e:
        return {"error": str(e)}
    finally:
        if connection and connection.is_connected():
            connection.close()


@app.route('/api/data', methods=['GET'])
def get_real_time_data():
    query = """
    SELECT      
        conf_exten AS SESSIONID,   
        status AS STATUS,
        TIMEDIFF(NOW(), last_state_change) AS `MM:SS`,
        campaign_id AS CAMPAIGN,
        calls_today AS CALLS
    FROM vicidial_live_agents 
    WHERE status IN ('READY', 'INCALL', 'PAUSED', 'CLOSER');
    """
    data = execute_query(query)
    return jsonify({"success": True, "data": data}) if isinstance(data, list) else jsonify({"success": False, "error": str(data)})


@app.route('/api/summary', methods=['GET'])
def get_summary_data():
    query = """
    SELECT 
        SUM(CASE WHEN vac.status IN ('LIVE', 'PLACED') THEN 1 ELSE 0 END) AS calls_being_placed,
        SUM(CASE WHEN vac.status = 'RINGING' THEN 1 ELSE 0 END) AS calls_ringing,
        SUM(CASE WHEN vac.status = 'WAITING' THEN 1 ELSE 0 END) AS calls_waiting_for_agents,
        SUM(CASE WHEN vac.status = 'IVR' THEN 1 ELSE 0 END) AS calls_in_ivr,
        COUNT(DISTINCT vla.user) AS agents_logged_in,
        SUM(CASE WHEN vla.status = 'INCALL' THEN 1 ELSE 0 END) AS agents_in_calls,
        SUM(CASE WHEN vla.status = 'READY' THEN 1 ELSE 0 END) AS agents_waiting,
        SUM(CASE WHEN vla.status = 'PAUSED' THEN 1 ELSE 0 END) AS paused_agents,
        SUM(CASE WHEN vla.status = 'DEAD' THEN 1 ELSE 0 END) AS agents_in_dead_calls,
        SUM(CASE WHEN vla.status = 'DISPO' THEN 1 ELSE 0 END) AS agents_in_dispo
    FROM 
        vicidial_live_agents vla
    LEFT JOIN 
        vicidial_auto_calls vac 
    ON 
        vla.campaign_id = vac.campaign_id;
    """
    data = execute_query(query)
    if isinstance(data, list) and data:
        return jsonify({"success": True, "summary": data[0]})
    return jsonify({"success": False, "error": "No data found"})



if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
