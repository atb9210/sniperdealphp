# Configurazione dello Scheduler - SnipeDeal

SnipeDeal utilizza lo scheduler di Laravel per eseguire periodicamente le campagne di scraping. Questo documento spiega come configurare e gestire lo scheduler nei diversi ambienti.

## Come funziona lo scheduler

Lo scheduler è configurato per eseguire il comando `campaigns:run` ogni minuto, che a sua volta:
1. Controlla quali campagne sono pronte per essere eseguite (basandosi sul campo `next_run_at`)
2. Esegue i job di scraping per le campagne dovute
3. Aggiorna il campo `next_run_at` in base all'intervallo configurato

## Configurazione in ambiente di produzione

In un ambiente di produzione, è necessario aggiungere un'entry cron che esegua `php artisan schedule:run` ogni minuto:

```bash
* * * * * cd /percorso/assoluto/al/progetto && php artisan schedule:run >> /dev/null 2>&1
```

### Passi per configurare cron su Linux/Unix:

1. Accedi al server tramite SSH
2. Apri l'editor crontab:

```bash
crontab -e
```

3. Aggiungi la linea seguente (modifica il percorso):

```bash
* * * * * cd /var/www/snipedeal && php artisan schedule:run >> /dev/null 2>&1
```

4. Salva e chiudi l'editor

### Configurazione con Supervisor (consigliata)

Per una gestione più robusta, puoi utilizzare Supervisor per gestire non solo i queue worker ma anche lo scheduler:

#### Installazione automatica (consigliata)

SnipeDeal include uno script di installazione che configura automaticamente Supervisor sia in ambiente locale che in produzione:

```bash
# Rendi eseguibile lo script (se necessario)
chmod +x install-supervisor.sh

# Esegui lo script di installazione
./install-supervisor.sh
```

Lo script rileverà automaticamente il sistema operativo e installerà Supervisor con la configurazione corretta. Funziona su:
- Ubuntu/Debian
- CentOS/RHEL
- macOS (tramite Homebrew)

#### Installazione manuale

Se preferisci installare manualmente Supervisor:

1. Installa Supervisor sul server:

```bash
sudo apt-get install supervisor
```

2. Copia i file di configurazione forniti:

```bash
sudo cp supervisor/snipedeal-scheduler.conf /etc/supervisor/conf.d/
sudo cp supervisor/snipedeal-worker.conf /etc/supervisor/conf.d/
```

3. Modifica i percorsi nei file di configurazione secondo le tue necessità:

```bash
sudo nano /etc/supervisor/conf.d/snipedeal-scheduler.conf
sudo nano /etc/supervisor/conf.d/snipedeal-worker.conf
```

4. Aggiorna Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start snipedeal-scheduler
sudo supervisorctl start snipedeal-worker
```

#### Comandi utili per Supervisor

```bash
# Visualizza lo stato di tutti i processi
supervisorctl status

# Riavvia un processo specifico
supervisorctl restart snipedeal-scheduler

# Ferma un processo
supervisorctl stop snipedeal-worker

# Avvia un processo
supervisorctl start snipedeal-worker

# Rileggi la configurazione dopo modifiche
supervisorctl reread
supervisorctl update
```

## Configurazione in ambiente locale

Per l'ambiente di sviluppo locale, hai diverse opzioni:

### 1. Esecuzione manuale (per test veloci)

```bash
php artisan schedule:run
```

### 2. Script continuo (per sessioni di sviluppo)

Crea uno script `run-scheduler.sh`:

```bash
#!/bin/bash

echo "Avvio scheduler Laravel in modalità continua..."
echo "Premi Ctrl+C per terminare."

while true; do
    php artisan schedule:run
    sleep 60
done
```

Rendi eseguibile lo script e avvialo:

```bash
chmod +x run-scheduler.sh
./run-scheduler.sh
```

### 3. Utilizzo di Laravel Sail (con Docker)

Se utilizzi Laravel Sail, lo scheduler è già incluso. Assicurati che il container sia in esecuzione:

```bash
sail up -d
```

## Monitoraggio dello scheduler

Per verificare che lo scheduler stia funzionando correttamente:

1. Controlla i log:

```bash
tail -f storage/logs/scheduler.log
```

2. Usa il comando di monitoraggio campagne:

```bash
php artisan campaigns:monitor --watch
```

3. Verifica l'ultimo timestamp di esecuzione:

```bash
php artisan campaigns:status
```

## Risoluzione dei problemi

Se le campagne non vengono eseguite come previsto:

1. Verifica che lo scheduler sia attivo:
   - Controlla i log in `storage/logs/laravel.log` e `storage/logs/scheduler.log`
   - Assicurati che ci sia il messaggio "Scheduler: campaigns:run eseguito con successo"

2. Controlla che le campagne siano attive e abbiano un `next_run_at` valido:
   - `php artisan campaigns:monitor`

3. Verifica che il comando `campaigns:run` funzioni manualmente:
   - `php artisan campaigns:run --verbose`

4. Assicurati che il timestamp di sistema sia corretto:
   - `date`

5. Riavvia lo scheduler se necessario:
   - Se usi Supervisor: `sudo supervisorctl restart snipedeal-scheduler`
   - Se usi cron: non è necessario riavviare, ma puoi controllare lo stato con `sudo systemctl status cron` 