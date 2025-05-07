# Architettura Tecnica - SnipeDeal

Questo documento descrive l'architettura tecnica e lo stack tecnologico utilizzato in SnipeDeal.

## Stack Tecnologico

### Backend
- **Framework**: Laravel PHP
- **PHP Version**: 8.1+
- **Database**: MySQL/PostgreSQL
- **Caching**: Redis (opzionale)
- **Queue System**: Laravel Queue con database driver
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

L'applicazione utilizza Laravel Queue per la gestione delle operazioni asincrone:
- **Queue Driver**: Database (tabella `jobs`)
- **Job Class**: SubitoScraperJob
- **Worker**: Gestito da Supervisor in produzione
- **Retry Logic**: Configurato per retries automatici in caso di fallimento

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
- **Database Load**: La crescita dei risultati può richiedere strategie di partitioning
- **Queue Performance**: Con molte campagne attive, la coda può diventare un collo di bottiglia

### Strategie di Mitigazione
- **Caching**: Implementare caching per ridurre richieste duplicate
- **Pruning dei Dati**: Eliminazione periodica di risultati e log vecchi
- **Queueing**: Distribuzione del carico con multiple queue workers
- **Randomizzazione**: Variare tempi di scraping per evitare pattern prevedibili

## Sicurezza

- **Autenticazione**: Sistema standard di Laravel Fortify/Breeze
- **Autorizzazione**: Policy Laravel per proteggere le risorse
- **CSRF Protection**: Middleware Laravel per protezione da attacchi CSRF
- **Validation**: Validazione input lato server per tutti i form
- **User Input**: Escaping dell'output per prevenire XSS
- **API Tokens**: Protezione delle credenziali Telegram 