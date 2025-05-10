#!/bin/bash

# Questo script è progettato per essere eseguito come cronjob ogni minuto in RunCloud
# Esegue lo scheduler di Laravel, che a sua volta verifica e avvia le campagne dovute

# Vai alla directory del progetto (assicurati di aggiornare questo percorso per il tuo ambiente RunCloud)
cd /home/runcloud/webapps/sniper-deal

# Esegui lo scheduler con output nel log
php artisan schedule:run >> storage/logs/scheduler.log 2>&1 