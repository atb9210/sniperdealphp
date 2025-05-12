#!/bin/bash

set -e

LOG="../logs/deploy-debug.log"
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
# Assicuriamoci che siamo nella directory principale del progetto
cd "$(dirname "$0")/../.." || { echo "[ERROR] Cambio directory fallito"; exit 1; }
if ! grep -q "APP_KEY=base64" .env; then
  php artisan key:generate --force || { echo "[ERROR] key:generate fallito"; exit 1; }
  echo "[INFO] APP_KEY generata"
else
  echo "[INFO] APP_KEY già presente"
  # Verifichiamo che la chiave sia valida
  KEY_VALUE=$(grep APP_KEY .env | cut -d '=' -f 2)
  if [[ -z "$KEY_VALUE" || "$KEY_VALUE" == "base64:" ]]; then
    echo "[WARNING] APP_KEY presente ma sembra non valida, rigeneriamo..."
    php artisan key:generate --force || { echo "[ERROR] key:generate fallito"; exit 1; }
    echo "[INFO] APP_KEY rigenerata"
  fi
fi

# 5.5 Setup Puppeteer (spostato dopo la generazione della chiave)
echo "[STEP] Configurazione Puppeteer..."
bash "$(dirname "$0")/deploy-puppeteer.sh" || { echo "[ERROR] Puppeteer setup fallito"; exit 1; }

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
# Pulisce eventuali cache precedenti
php artisan config:clear || { echo "[WARNING] config:clear warning"; }
php artisan route:clear || { echo "[WARNING] route:clear warning"; }
php artisan view:clear || { echo "[WARNING] view:clear warning"; }
php artisan cache:clear || { echo "[WARNING] cache:clear warning"; }

# Ora rigenera le cache
php artisan config:cache || { echo "[ERROR] config:cache fallito"; exit 1; }
php artisan route:cache || { echo "[ERROR] route:cache fallito"; exit 1; }
php artisan view:cache || { echo "[ERROR] view:cache fallito"; exit 1; }
php artisan optimize || { echo "[ERROR] optimize fallito"; exit 1; }

# 9. Permessi
echo "[STEP] Permessi..."
chmod -R 775 storage bootstrap/cache || { echo "[ERROR] chmod fallito"; exit 1; }
chown -R $(whoami):$(whoami) storage bootstrap/cache || { echo "[ERROR] chown fallito"; exit 1; }

# 9.5 Crea cartelle temporanee per Puppeteer
echo "[STEP] Creazione cartelle temporanee per Puppeteer..."
mkdir -p storage/app/temp
chmod -R 775 storage/app/temp

# 10. Verifica finale
echo "[STEP] Verifica finale configurazione..."
if ! grep -q "APP_KEY=base64" .env || grep -q "APP_KEY=base64:" .env; then
  echo "[WARNING] APP_KEY non trovata o non valida dopo il deploy, tentativo finale..."
  php artisan key:generate --force
fi

# 11. Fine deploy
echo "==== DEPLOY COMPLETATO con successo $(date) ====" 