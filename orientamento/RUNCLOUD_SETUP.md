# Configurazione di SnipeDeal su RunCloud

Questo documento descrive come configurare correttamente SnipeDeal in un ambiente di produzione utilizzando RunCloud.

## Requisiti

- Un account RunCloud
- Un server web configurato con RunCloud (Ubuntu consigliato)
- PHP 8.1+ installato
- MySQL/MariaDB (opzionale, il progetto utilizza attualmente SQLite)

## Deployment del Codice

1. Crea una nuova web application in RunCloud
2. Configura Git per il deployment automatico o carica i file manualmente
3. Assicurati che le directory `storage` e `bootstrap/cache` siano scrivibili dal server web

## Configurazione dello Scheduler

Per garantire che le campagne vengano eseguite automaticamente, è necessario configurare un cronjob in RunCloud:

1. Accedi al pannello di controllo di RunCloud
2. Vai su **Cron Jobs** dal menu laterale
3. Clicca su **Add Cron Job**
4. Configura il cron job con i seguenti valori:
   - **Command**: `/home/runcloud/webapps/snipedeal/run-scheduler-production.sh`
   - **User**: (seleziona l'utente della tua web app)
   - **Frequency**: Seleziona "Every minute" o inserisci `* * * * *`
   - **Comment**: Laravel Scheduler for SnipeDeal

![Configurazione Cron Job in RunCloud](https://docs.runcloud.io/images/app-management/cron-jobs.png)

## Verifica del Funzionamento

Dopo aver configurato il cron job, puoi verificare che lo scheduler stia funzionando correttamente:

1. Controlla i log dello scheduler:
   ```
   tail -f /home/runcloud/webapps/snipedeal/storage/logs/scheduler.log
   ```

2. Esegui il comando di monitoraggio delle campagne:
   ```
   cd /home/runcloud/webapps/snipedeal
   php artisan campaigns:monitor
   ```

3. Verifica che il campo `next_run_at` delle campagne venga aggiornato correttamente

## Configurazione delle Notifiche Telegram

Assicurati che le notifiche Telegram funzionino correttamente:

1. Controlla che ogni utente abbia configurato correttamente il token del bot e il chat_id nelle impostazioni
2. Esegui il comando di test delle notifiche:
   ```
   php artisan telegram:status
   ```

## Risoluzione dei Problemi

Se le campagne non vengono eseguite come previsto:

1. Verifica che il cron job sia attivo e funzionante:
   ```
   grep CRON /var/log/syslog
   ```

2. Controlla i permessi dello script run-scheduler-production.sh:
   ```
   chmod +x run-scheduler-production.sh
   ```

3. Verifica che il percorso nel file run-scheduler-production.sh corrisponda alla tua installazione

4. Controlla i log di Laravel per eventuali errori:
   ```
   tail -f /home/runcloud/webapps/snipedeal/storage/logs/laravel.log
   ```

## Considerazioni sulla Scalabilità

Per applicazioni con un alto numero di campagne o utenti, considera:

1. Migrazione da SQLite a MySQL/MariaDB per migliorare le prestazioni
2. Configurazione di un server Redis per la gestione delle code
3. Implementazione di cache per ridurre il carico sul database

## Note sull'Ambiente di Sviluppo

Per testare lo scheduler in ambiente di sviluppo locale:

1. Su Linux/macOS: Configura un cron job personale che esegua `php artisan schedule:run` ogni minuto
2. Su Windows: Utilizza Task Scheduler per eseguire un batch file che contenga lo stesso comando

**Nota**: L'esecuzione dello scheduler in ambiente locale può essere problematica, specialmente su macOS. È consigliabile testare questa funzionalità direttamente in produzione con le dovute precauzioni. 