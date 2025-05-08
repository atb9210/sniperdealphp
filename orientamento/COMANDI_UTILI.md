# Comandi Utili - SnipeDeal

Questo documento elenca i comandi utili disponibili in SnipeDeal, con spiegazioni su come e quando utilizzarli.

## Comandi per la Gestione delle Campagne

### campaigns:run
Esegue le campagne che sono in programma per l'esecuzione (based on next_run_at)

```bash
# Eseguire tutte le campagne dovute
php artisan campaigns:run

# Eseguire una campagna specifica
php artisan campaigns:run --campaign=ID

# Eseguire una campagna inattiva
php artisan campaigns:run --campaign=ID --force
```

### campaigns:force
Forza l'esecuzione immediata di una campagna specifica, indipendentemente dalla programmazione.

```bash
# Forza l'esecuzione di una campagna
php artisan campaigns:force ID_CAMPAGNA

# Forza l'esecuzione di una campagna e resetta lo stato notificato dei risultati
php artisan campaigns:force ID_CAMPAGNA --reset-notified
```

### campaigns:monitor
Mostra lo stato di esecuzione pianificata di tutte le campagne, evidenziando le esecuzioni previste e i ritardi.

```bash
# Visualizza lo stato attuale
php artisan campaigns:monitor

# Monitora in tempo reale con aggiornamento ogni 5 secondi
php artisan campaigns:monitor --watch
```

## Comandi di Monitoraggio

### telegram:status
Visualizza lo stato delle notifiche Telegram per tutte le campagne.

```bash
# Visualizza stato per tutte le campagne
php artisan telegram:status

# Visualizza stato per una campagna specifica
php artisan telegram:status ID_CAMPAGNA
```

## Comandi di Test

### scraper:test
Testa lo scraper con una keyword specifica e salva i risultati.

```bash
# Testa lo scraper con una keyword (default 3 pagine)
php artisan scraper:test "keyword"

# Testa lo scraper con un numero specifico di pagine
php artisan scraper:test "keyword" --pages=5
```

## Comandi di Sistema

### queue:work
Avvia un worker per elaborare i job in coda.

```bash
# Avviare un worker
php artisan queue:work

# Avviare un worker che processa un solo job
php artisan queue:work --once

# Avviare un worker con retry specifico
php artisan queue:work --tries=3
```

### cache:clear
Pulisce la cache dell'applicazione.

```bash
php artisan cache:clear
```

### optimize:clear
Pulisce le ottimizzazioni della cache e ricrea i file di bootstrap.

```bash
php artisan optimize:clear
```

## Script di Utilità

### start-queue-worker.sh
Avvia un queue worker in background (necessario solo se si utilizzano dispatch asincrone).

```bash
./start-queue-worker.sh
```

### stop-queue-worker.sh
Arresta i queue worker in esecuzione.

```bash
./stop-queue-worker.sh
```

### run-scheduler.sh
Esegue lo scheduler di Laravel in continuo in ambiente locale (utile per lo sviluppo).

```bash
./run-scheduler.sh
```

### install-supervisor.sh
Installa e configura Supervisor automaticamente per gestire lo scheduler e i queue worker.

```bash
./install-supervisor.sh
```

## Gestione Supervisor

Se hai installato Supervisor, puoi utilizzare i seguenti comandi:

```bash
# Controlla lo stato dei processi
supervisorctl status

# Riavvia lo scheduler
supervisorctl restart snipedeal-scheduler

# Riavvia il worker
supervisorctl restart snipedeal-worker

# Ferma tutti i processi
supervisorctl stop all

# Avvia tutti i processi
supervisorctl start all

# Ricarica la configurazione
supervisorctl reread
supervisorctl update
```

## Note Importanti

1. Le campagne vengono eseguite automaticamente ogni minuto tramite il Laravel Scheduler.
2. Le notifiche vengono inviate immediatamente alla fine dell'esecuzione di una campagna.
3. Lo stato delle notifiche può essere monitorato con `telegram:status`.
4. Se le notifiche non vengono inviate, è possibile utilizzare `campaigns:force ID --reset-notified` per forzare l'invio. 
``` 