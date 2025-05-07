# Diario di Sviluppo - SnipeDeal

Questo documento tiene traccia del progresso dello sviluppo di SnipeDeal, documentando le principali milestone, decisioni e sfide incontrate.

## Maggio 2025

### 07/05/2025 - Implementazione Sistema di Campagne e Job Logging

**Completato:**
- Creazione del modello Campaign e relative migrazioni
- Implementazione del controller CampaignController per gestire le campagne
- Sviluppo del sistema di log per tracciare l'esecuzione dei job
- Interfaccia per visualizzare i log di esecuzione
- Aggiunta della funzionalità "Esegui ora" per avviare manualmente le campagne

**Problemi riscontrati:**
- Issue con l'aggiornamento del timestamp last_run_at quando si esegue manualmente un job
- Il job viene correttamente inserito in coda ma non viene eseguito senza un queue worker attivo

**Decisioni prese:**
- Utilizzo di un sistema di code basato su database per garantire persistenza
- Implementazione di JobLog separato per tenere traccia dello stato dei job
- Aggiunta di documentazione in README su come configurare correttamente il queue worker

### 06/05/2025 - Risoluzione problemi di notifica e miglioramenti dell'architettura

### Problemi risolti
- Risolto il problema delle notifiche Telegram che non venivano inviate per nuovi risultati
- Corretto il metodo di recupero delle impostazioni utente per garantire l'accesso alle configurazioni Telegram
- Migliorato il tracking delle notifiche con una gestione più robusta degli stati `is_new` e `notified`

### Miglioramenti implementati
- Modificato l'approccio ai job: da asincrono (queue) a sincrono (dispatchSync) per garantire l'invio immediato delle notifiche
- Creato un sistema di diagnostica per monitorare lo stato delle notifiche (`telegram:status`)
- Aggiunto comando per forzare l'esecuzione di campagne con possibilità di reset delle notifiche
- Creati script di utilità per la gestione dei queue worker (`start-queue-worker.sh`, `stop-queue-worker.sh`)

### Architettura aggiornata
- Semplificata la struttura dei comandi, rimuovendo quelli temporanei e di test
- Migliorata la gestione degli errori con log più dettagliati
- Aggiornata la documentazione per riflettere i cambiamenti nell'architettura

## Aprile 2025

### 28/04/2025 - Integrazione Notifiche Telegram

**Completato:**
- Implementazione del modello UserSetting per salvare le configurazioni dell'utente
- Sviluppo dell'integrazione con l'API Telegram
- Creazione dell'interfaccia per configurare le notifiche
- Funzionalità di test per verificare la configurazione Telegram
- Sistema di notifica per nuovi risultati trovati

**Problemi riscontrati:**
- Gestione sicura dei token Telegram
- Formattazione dei messaggi per rispettare i limiti di Telegram
- Gestione degli errori nelle chiamate API

**Decisioni prese:**
- Salvataggio dei token nel database anziché in file di configurazione
- Limitazione a 5 risultati per messaggio Telegram
- Implementazione di un sistema di retry per gli invii falliti

### 15/04/2025 - Sviluppo Motore di Scraping

**Completato:**
- Implementazione della classe SubitoScraper per estrarre dati da Subito.it
- Creazione del comando Artisan per testare lo scraper
- Sviluppo del sistema per gestire paginazione e parametri di ricerca
- Estrazione dei dati completi degli annunci

**Problemi riscontrati:**
- Struttura HTML di Subito.it complessa e soggetta a cambiamenti
- Rate limiting del sito target
- Gestione delle eccezioni durante lo scraping

**Decisioni prese:**
- Utilizzo di selettori CSS robusti con fallback
- Implementazione di delay tra le richieste per evitare rate limiting
- Logging estensivo per debugging e monitoraggio

## Marzo 2025

### 20/03/2025 - Setup Iniziale del Progetto

**Completato:**
- Creazione repository Git
- Setup progetto Laravel
- Configurazione database
- Implementazione sistema di autenticazione
- Struttura base dell'interfaccia utente con Blade e Tailwind

**Decisioni prese:**
- Utilizzo di Laravel Breeze per l'autenticazione
- Adozione di Tailwind CSS per l'interfaccia utente
- Struttura del progetto basata su moduli funzionali

## Roadmap Futura

### Giugno 2025 (Pianificato)
- Miglioramento della stabilità del sistema di code
- Ottimizzazione delle performance di scraping
- Aggiunta di filtri avanzati per i risultati

### Luglio 2025 (Pianificato)
- Implementazione dashboard con grafici e statistiche
- Supporto per scraping di altri marketplace
- Sistema di tag per i risultati

### Agosto 2025 (Pianificato)
- Esportazione dati in vari formati
- API per accesso programmatico
- Miglioramenti UX/UI basati sul feedback degli utenti 

## 05/05/2025 - Prima versione funzionante del sistema di scraping

### Funzionalità implementate
- Creato il servizio SubitoScraper per estrarre dati da Subito.it
- Implementato il sistema di campagne con parametri configurabili
- Creato il job SubitoScraperJob per l'esecuzione asincrona
- Implementato il sistema di gestione dei risultati con deduplicazione

### Architettura creata
- Modelli per Campaign, CampaignResult e JobLog
- Sistema di code con Laravel Queue
- Scheduler per l'esecuzione periodica delle campagne

### Note
- Il sistema base funziona, ma necessita di miglioramenti nell'interfaccia
- La gestione degli errori è ancora limitata
- Il sistema di notifiche sarà implementato nella prossima fase 