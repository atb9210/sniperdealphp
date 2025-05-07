# Comandi Utili per SnipeDeal

Questo documento contiene una lista di comandi utili per gestire l'applicazione SnipeDeal in ambiente locale.

## Avvio e Gestione Server di Sviluppo

### Avviare l'applicazione
```bash
# Avviare il server Laravel di sviluppo (porta default 8000)
php artisan serve

# Avviare il server su una porta specifica
php artisan serve --port=8888
```

### Arrestare l'applicazione
```bash
# Premere CTRL+C nel terminale dove è attivo il server
# In alternativa, se è in background, trovare e terminare il processo
ps aux | grep artisan
kill <PID>  # Sostituire <PID> con l'ID del processo
```

## Frontend e Asset Development (Vite)

### Avviare server Vite per sviluppo frontend
```bash
# Installare le dipendenze npm (necessario solo la prima volta o dopo aggiornamenti)
npm install

# Avviare server Vite in modalità sviluppo (hot reload)
npm run dev

# Avviare con host specifico (accessibile da altri dispositivi in rete)
npm run dev -- --host
```

### Compilare asset per produzione
```bash
# Compilare e minimizzare asset per produzione
npm run build
```

### Arrestare server Vite
```bash
# Premere CTRL+C nel terminale dove è attivo Vite
# Oppure trovare e terminare il processo
ps aux | grep vite
kill <PID>  # Sostituire <PID> con l'ID del processo
```

## Gestione Code e Worker

### Avviare un Queue Worker
```bash
# Avviare il worker in foreground (mantiene il terminale occupato)
php artisan queue:work

# Avviare un worker che processa un solo job e poi termina
php artisan queue:work --once

# Avviare un worker con timeout di 60 secondi per job
php artisan queue:work --timeout=60

# Avviare un worker che rimane in ascolto anche dopo errori
php artisan queue:work --tries=3
```

### Controllare lo stato delle code
```bash
# Verificare i job in attesa di esecuzione
php artisan queue:monitor

# Listare i job falliti
php artisan queue:failed

# Ritentare tutti i job falliti
php artisan queue:retry all

# Cancellare tutti i job falliti
php artisan queue:flush
```

### Forzare l'esecuzione di campagne
```bash
# Eseguire tutte le campagne in scadenza
php artisan campaigns:run

# Eseguire una campagna specifica (anche se non in scadenza)
php artisan campaigns:run --campaign=<ID> --force
```

## Database e Migrazioni

### Gestione del database
```bash
# Creare il file SQLite (se non esiste)
touch database/database.sqlite

# Eseguire tutte le migrazioni
php artisan migrate

# Fare rollback dell'ultima migrazione
php artisan migrate:rollback

# Ricreare il database da zero (perde tutti i dati!)
php artisan migrate:fresh

# Ricreare il database e aggiungere dati di test
php artisan migrate:fresh --seed
```

### Accesso diretto al database
```bash
# Aprire shell SQLite per query dirette
sqlite3 database/database.sqlite

# Alcuni comandi SQLite utili:
# .tables - mostra tutte le tabelle
# .schema tablename - mostra schema della tabella
# .quit - esce da SQLite
```

## Gestione Cache e Configurazione

### Cache e Configurazione
```bash
# Cancellare la cache delle viste
php artisan view:clear

# Cancellare la cache delle route
php artisan route:clear

# Cancellare la cache della configurazione
php artisan config:clear

# Ottimizzare l'applicazione per produzione
php artisan optimize

# Ripristinare dopo ottimizzazione
php artisan optimize:clear
```

### Generazione chiavi e risorse
```bash
# Generare chiave di applicazione
php artisan key:generate

# Creare link simbolico per lo storage
php artisan storage:link
```

## Testing e Debugging

### Comandi per testing e debug
```bash
# Testare lo scraper con una keyword
php artisan scraper:test "nome prodotto" --pages=3

# Aprire Tinker (REPL di Laravel)
php artisan tinker

# Esempi di comandi Tinker:
# \App\Models\Campaign::count() - conta le campagne
# \App\Models\User::all() - lista tutti gli utenti
# \App\Jobs\SubitoScraperJob::dispatch($campaign) - dispatcha un job manualmente
```

### Controllo log
```bash
# Visualizzare gli ultimi log in tempo reale
tail -f storage/logs/laravel.log

# Cercare errori nel log
grep -i "error" storage/logs/laravel.log

# Pulire file di log (se troppo grande)
> storage/logs/laravel.log
```

## Creazione Script di Utilità

### Script per avvio rapido dell'ambiente
Puoi creare uno script `start-development.sh` nella root del progetto:

```bash
#!/bin/bash
echo "Avvio ambiente di sviluppo SnipeDeal..."

# Avvia il server in background
php artisan serve &
SERVER_PID=$!
echo "Server avviato con PID: $SERVER_PID"

# Avvia il queue worker in background
php artisan queue:work &
WORKER_PID=$!
echo "Queue worker avviato con PID: $WORKER_PID"

# Avvia il server Vite per frontend in background
npm run dev &
VITE_PID=$!
echo "Server Vite avviato con PID: $VITE_PID"

echo "Ambiente di sviluppo avviato! Per terminare, esegui: ./stop-development.sh"
# Salva i PID in un file temporaneo
echo "$SERVER_PID $WORKER_PID $VITE_PID" > .dev-pids
```

### Script per arresto ambiente
E uno script `stop-development.sh`:

```bash
#!/bin/bash
echo "Arresto ambiente di sviluppo SnipeDeal..."

if [ -f .dev-pids ]; then
    read SERVER_PID WORKER_PID VITE_PID < .dev-pids
    
    # Termina i processi
    kill $SERVER_PID 2>/dev/null
    kill $WORKER_PID 2>/dev/null
    kill $VITE_PID 2>/dev/null
    
    echo "Processi terminati."
    rm .dev-pids
else
    echo "File .dev-pids non trovato. Nessun processo da terminare."
fi
```

Rendi questi script eseguibili:
```bash
chmod +x start-development.sh stop-development.sh
```

## Ambiente di Produzione

### Comandi per preparare il deployment
```bash
# Ottimizzare per produzione
php artisan optimize

# Compilare asset (se si usa Laravel Mix/Vite)
npm run build

# Configurare il queue worker con Supervisor
# (vedi documentazione specifica)
``` 