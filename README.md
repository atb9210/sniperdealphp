# SnipeDeal - Scraper e Monitoraggio Annunci Subito.it

Un'applicazione Laravel per il monitoraggio automatico e l'analisi degli annunci su Subito.it, con notifiche Telegram e gestione di campagne personalizzate.

## Funzionalità Principali

### 1. Gestione Campagne
- Creazione e gestione campagne di monitoraggio
- Configurazione parametri:
  - Keyword di ricerca
  - Intervallo di prezzo (min/max)
  - Numero massimo di pagine da scansionare (1-10)
  - Intervallo di tempo tra le esecuzioni (in minuti)
  - Opzione ricerca specifica (qso)
- Attivazione/disattivazione campagne
- Esecuzione manuale immediata ("Esegui ora")
- Pianificazione automatica delle esecuzioni

### 2. Scraping degli Annunci
- Ricerca annunci per keyword
- Supporto per ricerca specifica (qso=true)
- Paginazione automatica (fino a 10 pagine)
- Estrazione dettagliata per ogni annuncio:
  - Titolo
  - Prezzo
  - Località
  - Data
  - Link
  - Immagine
  - Stato (Disponibile/Venduto)
  - Disponibilità spedizione

### 3. Notifiche e Monitoraggio
- Notifiche Telegram per nuovi annunci
- Configurazione personalizzata dei parametri Telegram
- Logging dettagliato delle esecuzioni
- Statistiche di esecuzione (annunci trovati, nuovi, tempo di esecuzione)

### 4. Dashboard e Interfaccia Web
- Dashboard con panoramica delle campagne
- Visualizzazione risultati per ogni campagna
- Gestione utenti e impostazioni personali
- Storico delle esecuzioni con log dettagliati

## Struttura del Progetto

### Controllers
- `CampaignController`: Gestione delle campagne di monitoraggio
  - `index()`: Lista delle campagne
  - `create()`, `store()`: Creazione nuove campagne
  - `show()`: Dettaglio campagna con risultati
  - `edit()`, `update()`: Modifica campagne
  - `destroy()`: Eliminazione campagne
  - `toggle()`: Attivazione/disattivazione
  - `run()`: Esecuzione manuale immediata

- `JobLogController`: Gestione dei log di esecuzione
- `UserSettingsController`: Gestione impostazioni utente (inclusi parametri Telegram)
- `DashboardController`: Visualizzazione dashboard principale
- `KeywordFormController`: Gestione form di ricerca manuale

### Models
- `Campaign`: Definizione campagne di monitoraggio
- `CampaignResult`: Risultati delle campagne (annunci trovati)
- `JobLog`: Log delle esecuzioni
- `UserSetting`: Impostazioni utente (inclusi parametri Telegram)
- `User`: Gestione utenti

### Jobs
- `SubitoScraperJob`: Job per l'esecuzione delle campagne
  - Gestione del processo di scraping
  - Elaborazione dei risultati
  - Invio notifiche
  - Logging delle esecuzioni

### Services
- `SubitoScraper`: Servizio principale per lo scraping
  - `scrape($keyword, $qso, $pages)`: Metodo principale che coordina lo scraping
  - Funzioni di supporto per l'estrazione dei dati

## Sistema di Code e Job

L'applicazione utilizza il sistema di code di Laravel per gestire l'esecuzione asincrona delle campagne:

- Quando viene creata una nuova campagna, viene avviato un job iniziale
- Quando si clicca su "Esegui ora", viene accodato un job immediato
- La pianificazione automatica gestisce l'esecuzione periodica in base all'intervallo configurato

### Configurazione Queue Worker

#### Development Environment
Per processare i job nell'ambiente di sviluppo, esegui:

```bash
php artisan queue:work
```

Mantieni questo comando in esecuzione in una finestra di terminale separata mentre lavori sull'applicazione.

#### Production Environment
Per l'ambiente di produzione, è consigliato utilizzare Supervisor per gestire il processo del queue worker:

1. Installa Supervisor:
   ```bash
   # Su Ubuntu/Debian
   sudo apt-get install supervisor
   
   # Su macOS
   brew install supervisor
   ```

2. Crea un file di configurazione per il queue worker Laravel:
   ```bash
   sudo nano /etc/supervisor/conf.d/snipedeal-worker.conf
   ```

3. Aggiungi la seguente configurazione:
   ```
   [program:snipedeal-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/your/project/storage/logs/worker.log
   stopwaitsecs=3600
   ```

4. Aggiorna la configurazione:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start snipedeal-worker:*
   ```

5. Per verificare lo stato dei worker:
   ```bash
   sudo supervisorctl status
   ```

## Note Tecniche

### URL Structure
- Base URL: `https://www.subito.it/annunci-italia/vendita/usato/?q=`
- Parametri:
  - `q`: keyword di ricerca
  - `qso`: true/false per ricerca specifica
  - `page`: numero pagina (1-based)

### Database
L'applicazione utilizza tabelle per:
- `campaigns`: Definizione delle campagne di monitoraggio
- `campaign_results`: Risultati delle campagne (annunci trovati)
- `job_logs`: Log delle esecuzioni
- `user_settings`: Impostazioni utente
- `users`: Gestione utenti

### Notifiche Telegram
Per utilizzare le notifiche Telegram:
1. Crea un bot Telegram tramite BotFather
2. Configura il token del bot nelle impostazioni utente
3. Configura il chat ID nelle impostazioni utente
4. Attiva le campagne per ricevere notifiche sui nuovi annunci

## Troubleshooting

### Il job non viene eseguito quando clicco su "Esegui ora"
- Verifica che il queue worker sia in esecuzione con `php artisan queue:work`
- In produzione, verifica che Supervisor sia configurato correttamente
- Controlla i log in `storage/logs` per eventuali errori

### Non ricevo notifiche Telegram
- Verifica che il token del bot sia corretto
- Verifica che il chat ID sia corretto
- Assicurati di aver avviato una conversazione con il bot
- Controlla i log per eventuali errori di invio delle notifiche

## Prossimi Sviluppi Possibili
1. Supporto per altri siti di annunci (oltre a Subito.it)
2. Analisi dei prezzi nel tempo con grafici
3. Filtri avanzati per la ricerca
4. App mobile per la gestione delle campagne
5. Sistema di alert basato su regole personalizzate
6. Integrazione con altri sistemi di notifica (email, SMS, etc.)
