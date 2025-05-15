# Configurazione Supervisor

Questo sistema permette di generare configurazioni di supervisor scalabili che funzionano su qualsiasi server.

## Struttura

- `templates/`: Contiene i template delle configurazioni
  - `campaigns-scheduler.conf.template`: Template per lo scheduler delle campagne
  - `queue-worker.conf.template`: Template per i worker delle code
- `generate-configs.sh`: Script per generare le configurazioni

## Come funziona

I template contengono variabili che vengono sostituite con i valori corretti durante la generazione:

- `{{APP_PATH}}`: Percorso assoluto dell'applicazione
- `{{APP_USER}}`: Utente che esegue l'applicazione

## Utilizzo manuale

1. Esegui lo script di generazione:
   ```bash
   cd supervisor
   ./generate-configs.sh
   ```

2. Installa le configurazioni:
   ```bash
   sudo cp *.conf /etc/supervisor/conf.d/
   sudo supervisorctl reread
   sudo supervisorctl update
   ```

## Utilizzo durante il deployment

Lo script di deployment `deploy-runcloud.sh` esegue automaticamente questi passaggi.

## Configurazioni disponibili

### 1. campaigns-scheduler

Esegue il comando `campaigns:run` ogni minuto per processare le campagne in scadenza.

```ini
[program:campaigns-scheduler]
command=bash -c "while true; do php artisan campaigns:run; sleep 60; done"
```

### 2. queue-worker

Processa i job nella coda.

```ini
[program:queue-worker]
command=php artisan queue:work --tries=3 --max-time=3600
numprocs=2
``` 