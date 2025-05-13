# Architettura Tecnica - SnipeDeal

Questo documento descrive l'architettura tecnica e lo stack tecnologico utilizzato in SnipeDeal.

## Stack Tecnologico

### Backend
- **Framework**: Laravel PHP
- **PHP Version**: 8.1+
- **Database**: SQLite (attualmente), con piano di migrazione a MySQL/PostgreSQL
- **Caching**: Redis (opzionale)
- **Queue System**: Laravel Queue con database driver (SQLite)
- **Task Scheduling**: Laravel Scheduler

### Frontend
- **View Engine**: Laravel Blade
- **CSS Framework**: Tailwind CSS
- **JavaScript**: Alpine.js
- **Icons**: Heroicons
- **Responsive Design**: Tailwind breakpoints

### Infrastruttura
- **Web Server**: Nginx/Apache
- **Deployment**: Git-based deployment
- **Process Manager**: Supervisor (per gestione code)
- **Monitoring**: Laravel Telescope (opzionale)

### Integrazioni Esterne
- **Notifiche**: API Telegram
- **Scraping**: Symfony DomCrawler, HTTP Client

## Architettura dell'Applicazione

### Struttura MVC
L'applicazione segue il pattern Model-View-Controller tipico di Laravel:
- **Models**: Definizione delle entità e relazioni (Campaign, CampaignResult, JobLog, etc.)
- **Views**: Template Blade per l'interfaccia utente
- **Controllers**: Logica di business e gestione delle richieste

### Componenti Principali

```
app/
├── Console/         # Comandi CLI
│   └── Commands/    # RunCampaignJobs, TestScraper, etc.
├── Http/
│   └── Controllers/ # CampaignController, UserSettingsController, etc.
├── Jobs/            # SubitoScraperJob
├── Models/          # Campaign, CampaignResult, JobLog, etc.
├── Policies/        # CampaignPolicy
├── Providers/       # AuthServiceProvider, etc.
└── Services/        # SubitoScraper
```

### Flusso dei Dati

1. **Creazione Campagna**:
   - Utente crea una campagna tramite interfaccia
   - Controller salva la campagna nel database
   - Viene pianificata la prima esecuzione

2. **Esecuzione Campagna**:
   - Scheduler avvia RunCampaignJobs o utente clicca "Esegui ora"
   - SubitoScraperJob viene dispatched alla coda
   - JobLog viene creato con stato "running"

3. **Processo di Scraping**:
   - Queue worker esegue SubitoScraperJob
   - SubitoScraper estrae dati da Subito.it
   - Risultati vengono filtrati in base ai criteri della campagna
   - Nuovi risultati vengono salvati come CampaignResult
   - JobLog viene aggiornato con stato "success" e statistiche

4. **Notifiche**:
   - Se ci sono nuovi risultati, vengono preparate le notifiche
   - Viene inviata una richiesta all'API Telegram
   - I risultati vengono marcati come "notificati"

5. **Visualizzazione**:
   - Utente accede al dashboard o alla pagina della campagna
   - Controller recupera dati da Campaign, CampaignResult e JobLog
   - View mostra i risultati all'utente

### Sistema di Code

L'applicazione utilizza un approccio misto per la gestione dei job:
- **Esecuzione Sincrona**: I job di scraping vengono eseguiti in modo sincrono tramite `dispatchSync()` in modo che le notifiche vengano inviate immediatamente
- **Queue Worker**: Un worker può essere attivato opzionalmente per operazioni asincrone quando necessario
- **Tabella Queue**: Tabella `jobs` nel database SQLite configurata in `config/queue.php`
- **Job Class**: SubitoScraperJob implementa l'interfaccia ShouldQueue
- **Retry Logic**: Configurato per ritentare automaticamente in caso di fallimenti (default: 3 tentativi)

#### Gestione dei Queue Workers

**Ambiente di sviluppo (locale)**:
```bash
# Avviare un worker in background tramite script
./start-queue-worker.sh

# Terminare i worker attivi
./stop-queue-worker.sh

# Avviare un worker manualmente
php artisan queue:work
```

**Ambiente di produzione**:
Il gestore di processi Supervisor mantiene i worker attivi e li riavvia in caso di errori.

### Sistema di Notifiche

L'applicazione utilizza l'API di Telegram per inviare notifiche sugli annunci trovati:

1. **Configurazione**:
   - Ogni utente ha un token e un chat_id Telegram configurati nelle impostazioni
   - Accessibili tramite la relazione `User->settings` e i campi `telegram_token` e `telegram_chat_id`

2. **Flusso di notifica**:
   - Quando vengono trovati risultati non notificati, viene inviato un messaggio di riepilogo
   - Poi un messaggio dettagliato per ciascun annuncio
   - I messaggi includono emoji, formattazione Markdown e link
   - Viene implementato un delay per rispettare i rate limit dell'API Telegram

3. **Marcatura notifiche**:
   - I risultati vengono marcati come `notified = true` dopo l'invio
   - Lo stato `is_new` viene impostato su `false` dopo la notifica
   - È possibile resettare lo stato con `campaigns:force ID --reset-notified`

4. **Diagnostica**:
   - Il comando `telegram:status` fornisce una panoramica completa dello stato delle notifiche
   - I log dettagliati vengono salvati in `storage/logs/laravel.log`

### Comandi Artisan

L'applicazione include i seguenti comandi personalizzati:

1. **RunCampaignJobs**: Esegue le campagne dovute in base alla programmazione
2. **ForceCampaignScrape**: Forza l'esecuzione immediata di una campagna specifica
3. **CheckNotificationStatus**: Mostra lo stato di tutte le notifiche nel sistema
4. **TestScraper**: Permette di testare lo scraper con una keyword specifica

La documentazione completa dei comandi è disponibile in `orientamento/COMANDI_UTILI.md`.

### Schema del Database

```
+-----------------+       +-------------------+       +-------------+
| users           |       | campaigns         |       | job_logs    |
+-----------------+       +-------------------+       +-------------+
| id              |------>| id                |------>| id          |
| name            |       | user_id           |       | campaign_id |
| email           |       | name              |       | status      |
| password        |       | keyword           |       | message     |
| ...             |       | min_price         |       | error       |
+-----------------+       | max_price         |       | results_count|
        |                 | max_pages         |       | started_at  |
        |                 | interval_minutes  |       | completed_at|
        |                 | qso               |       | ...         |
        |                 | is_active         |       +-------------+
        |                 | last_run_at       |
        |                 | next_run_at       |
        |                 | ...               |
        |                 +-------------------+
        |                         |
        |                         v
+-----------------+       +-------------------+
| user_settings   |       | campaign_results  |
+-----------------+       +-------------------+
| id              |       | id                |
| user_id         |       | campaign_id       |
| telegram_token  |       | title             |
| telegram_chat_id|       | price             |
| ...             |       | location          |
+-----------------+       | date              |
                          | link              |
                          | image             |
                          | stato             |
                          | spedizione        |
                          | notified          |
                          | is_new            |
                          | ...               |
                          +-------------------+
```

## Considerazioni sulla Scalabilità

### Bottlenecks Potenziali
- **Rate Limiting**: Le richieste a Subito.it devono rispettare limiti per evitare blocchi
- **Database Load**: La crescita dei risultati può richiedere migrazione da SQLite a un DBMS più robusto
- **Queue Performance**: Con molte campagne attive, SQLite potrebbe diventare un collo di bottiglia
- **Worker Scalability**: Un singolo worker può diventare insufficiente con molte campagne

### Strategie di Mitigazione
- **Caching**: Implementare caching per ridurre richieste duplicate
- **Pruning dei Dati**: Eliminazione periodica di risultati e log vecchi
- **Queueing**: Distribuzione del carico con multiple queue workers
- **Randomizzazione**: Variare tempi di scraping per evitare pattern prevedibili
- **Migrazione Database**: Preparare la migrazione a PostgreSQL per gestire carichi maggiori

## Sicurezza

- **Autenticazione**: Sistema standard di Laravel Fortify/Breeze
- **Autorizzazione**: Policy Laravel per proteggere le risorse
- **CSRF Protection**: Middleware Laravel per protezione da attacchi CSRF
- **Validation**: Validazione input lato server per tutti i form
- **User Input**: Escaping dell'output per prevenire XSS
- **API Tokens**: Protezione delle credenziali Telegram 