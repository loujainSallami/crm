import csv

# Lire le fichier CSV
with open('personnes.csv', mode='r', encoding='utf-8') as fichier:
    lecteur = csv.DictReader(fichier)
    personnes = list(lecteur)

plus30 = [
    personne for personne in personnes
    if 'age' in personne and personne['age'] and int(personne['age']) > 30
]

for personne in plus30:
    print(personne)


