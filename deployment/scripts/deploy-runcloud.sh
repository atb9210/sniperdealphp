#!/bin/bash

set -e

# Assicurati di essere nella directory principale del progetto
if [[ "$(basename $(pwd))" == "scripts" || "$(basename $(pwd))" == "deployment" ]]; then
  echo "[INFO] Cambio directory alla root del progetto..."
  cd $(dirname $(dirname $(pwd)))
fi

# Crea directory logs se non esiste
mkdir -p deployment/logs

LOG="deployment/logs/deploy-debug.log"
exec > >(tee -a "$LOG") 2>&1

echo "==== DEPLOY START $(date) ===="
echo "User: $(whoami)"
echo "Working dir: $(pwd)"
echo "Git branch: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'N/A')"

# 1. Crea .env se mancante
echo "[STEP] Controllo .env..."
if [ ! -f .env ]; then
  cp .env.example .env
  echo "[INFO] File .env creato da .env.example"
else
  echo "[INFO] File .env già presente"
fi

# 2. Warning su APP_URL e DB
echo "[WARNING] Controlla e modifica .env per APP_URL e DB se necessario!"

# 3. Composer install
echo "[STEP] Composer install..."
if [ ! -f composer.lock ]; then
  echo "[WARNING] composer.lock mancante! Potrebbero esserci problemi di versionamento dipendenze."
fi
composer install --no-dev --optimize-autoloader || { echo "[ERROR] composer install fallito"; exit 1; }

# 4. NPM install/build
echo "[STEP] NPM install/build..."
if [ ! -f package-lock.json ]; then
  echo "[WARNING] package-lock.json mancante! Potrebbero esserci problemi di versionamento dipendenze JS."
fi
npm install || { echo "[ERROR] npm install fallito"; exit 1; }
npm run build || { echo "[ERROR] npm run build fallito"; exit 1; }

# 5. Genera chiave se mancante
echo "[STEP] Generazione APP_KEY se mancante..."
if ! grep -q "APP_KEY=base64" .env; then
  php artisan key:generate || { echo "[ERROR] key:generate fallito"; exit 1; }
  echo "[INFO] APP_KEY generata"
else
  echo "[INFO] APP_KEY già presente"
fi

# 6. Crea database sqlite se serve
echo "[STEP] Controllo database sqlite..."
if grep -q "DB_CONNECTION=sqlite" .env; then
  if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "[INFO] Creato database/database.sqlite"
  else
    echo "[INFO] database/database.sqlite già presente"
  fi
fi

# 7. Migrazioni
echo "[STEP] Migrazioni..."
php artisan migrate --force || { echo "[ERROR] migrate fallito"; exit 1; }

# 8. Ottimizzazione cache
echo "[STEP] Ottimizzazione cache Laravel..."
php artisan config:cache || { echo "[ERROR] config:cache fallito"; exit 1; }
php artisan route:cache || { echo "[ERROR] route:cache fallito"; exit 1; }
php artisan view:cache || { echo "[ERROR] view:cache fallito"; exit 1; }
php artisan optimize || { echo "[ERROR] optimize fallito"; exit 1; }

# 9. Permessi
echo "[STEP] Permessi..."
# Crea i file di log se non esistono
mkdir -p storage/logs
sudo touch storage/logs/scheduler.log storage/logs/worker.log || { echo "[WARNING] Impossibile creare file di log"; }

# Imposta i permessi in modo sicuro
sudo chmod -R 775 storage bootstrap/cache || { echo "[WARNING] chmod fallito, proseguo comunque"; }
sudo chown -R $(whoami):$(whoami) storage bootstrap/cache || { echo "[WARNING] chown fallito, proseguo comunque"; }

# 10. Configurazione Supervisor
echo "[STEP] Configurazione Supervisor..."
if [ -d "supervisor" ]; then
  # Aggiorna i percorsi nei file di configurazione
  PROJECT_PATH=$(pwd)
  USER=$(whoami)
  
  echo "[INFO] Aggiornamento configurazioni supervisor..."
  sed -i.bak "s|/Users/atb/Documents/SnipeDealPhp|$PROJECT_PATH|g" supervisor/*.conf
  sed -i.bak "s|user=atb|user=$USER|g" supervisor/*.conf
  
  # Copia i file di configurazione nella posizione corretta
  echo "[INFO] Copiando file di configurazione in /etc/supervisor/conf.d/"
  sudo mkdir -p /etc/supervisor/conf.d/
  sudo cp supervisor/*.conf /etc/supervisor/conf.d/
  
  # Riavvia supervisor
  echo "[INFO] Riavvio supervisor..."
  sudo supervisorctl reread
  sudo supervisorctl update
  
  echo "[INFO] Configurazione Supervisor completata"
else
  echo "[WARNING] Directory supervisor non trovata, configurazione supervisor saltata"
fi

# 11. Riavvio queue worker
echo "[STEP] Riavvio queue worker..."
php artisan queue:restart || { echo "[WARNING] queue:restart fallito, ma proseguo"; }
echo "[INFO] Queue worker riavviato"

# 12. Avvio servizi
echo "[STEP] Avvio servizi..."
# Ferma tutti i servizi
echo "[INFO] Fermando tutti i servizi..."
sudo supervisorctl stop all || { echo "[WARNING] Impossibile fermare i servizi, proseguo comunque"; }

# Avvia tutti i servizi
echo "[INFO] Avviando tutti i servizi..."
sudo supervisorctl start all || { echo "[WARNING] Impossibile avviare i servizi, proseguo comunque"; }

# Mostra lo stato dei servizi
echo "[INFO] Stato dei servizi:"
sudo supervisorctl status

# 13. Fine deploy
echo "==== DEPLOY COMPLETATO con successo $(date) ====" 