# nimbus_worker — systemd service setup

> **Status**: actif en production.

Ce document décrit la configuration du service systemd `nimbus_worker` sur le serveur de production,
qui gère le traitement des messages asynchrones et le scheduler via Symfony Messenger.

---

## Configuration en production

Fichier : `/etc/systemd/system/nimbus-worker.service`

```ini
[Unit]
Description=Nimbus Messenger Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/nimbus
ExecStart=/usr/bin/php bin/console messenger:consume async scheduler_main --time-limit=3600 --memory-limit=512M
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

---

## Mise en place du service

```bash
# Créer le fichier de service
sudo nano /etc/systemd/system/nimbus-worker.service

# Activer et démarrer
sudo systemctl daemon-reload
sudo systemctl enable nimbus-worker
sudo systemctl start nimbus-worker
sudo systemctl status nimbus-worker
```

---

## Commandes utiles

```bash
# Statut et logs
sudo systemctl status nimbus-worker
sudo journalctl -u nimbus-worker -f        # suivre les logs en direct
sudo journalctl -u nimbus-worker --since "1 hour ago"

# Contrôle du service
sudo systemctl start nimbus-worker
sudo systemctl stop nimbus-worker
sudo systemctl restart nimbus-worker
```

---

## Notes

- **`async`** : transport pour les messages asynchrones (envois de fichiers, notifications…)
- **`scheduler_main`** : transport pour les tâches planifiées (équivalent d'un cron géré par Messenger)
- **`--time-limit=3600`** : le worker se relance proprement toutes les heures (évite les fuites mémoire)
- **`--memory-limit=512M`** : arrêt automatique si le process dépasse 512 Mo
- **`RestartSec=5`** : 5 secondes d'attente avant redémarrage en cas de crash
