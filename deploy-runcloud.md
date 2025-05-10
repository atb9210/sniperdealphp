# SnipeDeal - Script di Deployment per RunCloud

```bash
#!/bin/bash

# === CONFIGURAZIONE ===
APP_PATH="/home/runcloud/webapps/sniper-deal"
USER="runcloud"
PHP="/RunCloud/Packages/php83rc/bin/php"
COMPOSER="/usr/local/bin/composer"
NODE="/usr/bin/npm"

cd $APP_PATH

echo "==[ 1. Pull codice da git ]=="
git pull origin main

echo "==[ 2. Imposta PHP corretto (solo se necessario) ]=="
if [[ "$(readlink -f /usr/bin/php)" != "$PHP" ]]; then
  rm /usr/bin/php
  ln -s $PHP /usr/bin/php
fi

echo "==[ 3. Installa dipendenze PHP ]=="
$COMPOSER install --no-dev --optimize-autoloader

echo "==[ 4. Installa dipendenze Node e build asset ]=="
$NODE install
$NODE run build

echo "==[ 5. Aggiorna chiave se mancante ]=="
if ! grep -q "APP_KEY=base64" .env; then
  $PHP artisan key:generate
fi

echo "==[ 6. Migrazioni database ]=="
$PHP artisan migrate --force

echo "==[ 7. Ottimizza cache Laravel ]=="
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan optimize

echo "==[ 8. Permessi corretti ]=="
chown -R $USER:$USER $APP_PATH
chmod -R 755 $APP_PATH
chmod -R 775 $APP_PATH/storage
chmod -R 775 $APP_PATH/bootstrap/cache
chown -R $USER:$USER $APP_PATH/public/build
chmod -R 755 $APP_PATH/public/build

echo "==[ 9. Scheduler: cronjob ]=="
CRON_CMD="* * * * * cd $APP_PATH && ./run-scheduler-production.sh >> $APP_PATH/storage/logs/scheduler.log 2>&1"
( crontab -l 2>/dev/null | grep -Fv "$APP_PATH/run-scheduler-production.sh" ; echo "$CRON_CMD" ) | crontab -

echo "==[ 10. FINE DEPLOY ]=="
echo "✅ Deploy completato! Controlla il sito e i log per eventuali errori."

# === NOTE IMPORTANTI (da leggere e fare manualmente la prima volta) ===
# 1. Modifica APP_URL nel file .env con il dominio reale o temporaneo del sito.
#    Esempio: sed -i 's|APP_URL=.*|APP_URL=https://sniper-deal.x95jg2qbzq-95m32evxk3rv.p.temp-site.link|' .env
# 2. Assicurati che la root del sito su RunCloud punti a: $APP_PATH/public
# 3. Dopo la prima installazione, elimina eventuali migration di pulizia inutili (es: remove_unused_columns_from_user_settings_table.php)
# 4. Se aggiorni le migration, NON usare migrate:fresh in produzione (perdi tutti i dati!)
# 5. Se la tabella user_settings è corrotta o mancante, droppala manualmente e rilancia 'php artisan migrate'
# 6. Se cambi dominio, aggiorna APP_URL, ricostruisci la cache/config e gli asset
``` 