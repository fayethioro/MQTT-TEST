# Gestion de la position des conducteurs - README
> **NB** : Ceci est un projet test.

Ce projet permet de gérer et de traiter les positions GPS des conducteurs, en utilisant Laravel.  
Les positions sont stockées dans la base de données et publiées en temps réel via MQTT pour une visualisation sur une carte (par exemple avec Leaflet).

## Fonctionnement

1. **Réception des données GPS**
   - Chaque nouvelle position reçue d'un conducteur (latitude, longitude, précision, vitesse, etc.) est traitée par un Job `ProcessDriverLocation`.

2. **Mise à jour de la position courante**
   - La position actuelle du conducteur est enregistrée ou mise à jour dans la table `driver_locations`.

3. **Historisation des positions**
   - Un point d'historique est ajouté dans `driver_location_historiques` si la dernière position remonte à plus de 20 secondes, ou selon une règle de distance si besoin (voir la classe `ProcessDriverLocation`).

4. **Publication en temps réel (MQTT)**
   - Les positions sont publiées en MQTT sur le topic `izycab/drivers/{driverId}/location` pour permettre un affichage live côté front (ex : Leaflet + MQTT.js).

## Exemple de payload attendu

```json
{
  "latitude": 14.7015,
  "longitude": -17.4580,
  "accuracy": 4.2,
  "speed": 40.2,
  "bearing": 230,
  "provider": "gps",
  "timestamp": "2025-10-08 15:10:20"
}
```

## Exemple de logs

Voir `storage/logs/laravel.log` pour l'état détaillé des traitements (mise à jour, insertion d'historique, publication MQTT).

## Structure principale

- `app/Jobs/ProcessDriverLocation.php` : Traitement métier de la position.
- `app/Models/DriverLocation.php` : Modèle de la position courante.
- `app/Models/DriverLocationHistorique.php` : Historique des positions.
- `app/Services/MqttService.php` : Publication MQTT.

## Démarrage rapide

1. Installer les dépendances :
   ```bash
   composer install
   ```
2. Configurer l'accès à la base de données (`.env`).
3. Lancer les migrations :
   ```bash
   php artisan migrate
   ```
4. S'assurer que le broker MQTT est accessible et que la configuration est correcte.
5. Gérer les queues Laravel (ex : `php artisan queue:work`).

---

Pour toute question sur l'implémentation ou l'utilisation, voir le code source et les commentaires dans `ProcessDriverLocation`.


