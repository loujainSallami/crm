from flask import Flask, request, jsonify
import requests
from bs4 import BeautifulSoup
import json
import os
from flask_cors import CORS  # Importer Flask-CORS

app = Flask(__name__)

CORS(app)

@app.route('/fetch-call-status', methods=['POST'])
def fetch_call_status():
    # URL cible
    url = "http://sdch.ophony.com/vicidial/AST_VDADstats.php"

    # Récupérer les données envoyées par le frontend
    try:
        data = request.get_json()  # Récupérer les données JSON
        print("Données brutes reçues :", data)  # Debug
    except Exception as e:
        return jsonify({"error": "Erreur lors de la récupération des données JSON", "details": str(e)}), 400

    # Extraire les paramètres ou définir des valeurs par défaut
    query_date = data.get("query_date", "2025-01-21")
    end_date = data.get("end_date", "2025-01-21")
    group = data.get("group", "--ALL--")
    include_rollover = data.get("include_rollover", "NO")

    # Debug : Afficher les paramètres extraits
    print("Paramètres extraits :", query_date, end_date, group, include_rollover)

    # Préparer les paramètres pour la requête GET
    params = {
        "query_date": query_date,
        "end_date": end_date,
        "group[]": "--ALL--",
        "include_rollover": "NO",
        "bottom_graph": "NO",
        "carrier_stats": "NO",
        "shift": "ALL",
        "SUBMIT": "SUBMIT",
    }

    print("Paramètres envoyés à la requête GET :", params)



    # Paramètres dynamiques
    params = {
        "query_date": query_date,
        "end_date": end_date,
        "group[]": "--ALL--",
        "include_rollover": "NO",

    }

    # Authentification si nécessaire
    auth = ('ophony', '19admsdch20')  # Remplacez par vos identifiants

    try:
        # Effectuer une requête GET
        response = requests.get(url, params=params, auth=auth)
        response.raise_for_status()

        # Parser le contenu avec BeautifulSoup
        soup = BeautifulSoup(response.content, "html.parser")

        # Localiser la section "CALL STATUS STATS"
        table_section = None
        for line in soup.get_text().splitlines():
            if "CALL STATUS STATS" in line:
                table_section = line
                break

        if not table_section:
            return jsonify({"error": "Tableau 'CALL STATUS STATS' introuvable."}), 404

        # Extraire les lignes du tableau
        lines = soup.get_text().split("\n")
        start = False
        table_data = []
        for line in lines:
            if "CALL STATUS STATS" in line:
                start = True
                continue
            if start and "LIST ID STATS" in line:  # Fin du tableau
                break
            if start and line.strip():  # Capturer les lignes du tableau
                table_data.append(line.strip())

        # Nettoyer et structurer les données
        columns = ["STATUS", "DESCRIPTION", "CATEGORY", "CALLS", "TOTAL TIME", "AVG TIME", "CALLS/HOUR", "AGENT TIME"]
        data = []

        for row in table_data[2:]:  # Ignorer les en-têtes répétées
            if "|" in row:
                values = [value.strip() for value in row.split("|") if value.strip()]
                if len(values) == len(columns):  # Vérifier que la ligne correspond à la structure
                    data.append(dict(zip(columns, values)))

        # Convertir en JSON
        json_data = json.dumps(data, indent=4)

        # Sauvegarder dans un fichier JSON

        file_path = os.path.join("call_status_stats.json")
        with open(file_path, "w") as json_file:
            json_file.write(json_data)

        print("Tableau 'CALL STATUS STATS' sauvegardé dans call_status_stats.json")

                # Sauvegarder une copie dans `assets`
        assets_dir = os.path.join(os.getcwd(),"/home/krch/crm1/frontend/src/assets")
        os.makedirs(assets_dir, exist_ok=True)
        assets_file_path = os.path.join(assets_dir, "call_status_stats.json")
        with open(assets_file_path, "w") as json_file:
            json_file.write(json_data)
        print("Tableau 'CALL STATUS STATS' sauvegardé dans src/assets/call_status_stats.json")


        # Retourner les données JSON
        return jsonify({"message": "Tableau 'CALL STATUS STATS' extrait avec succès.", "data": data})

    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    app.run(debug=True)
