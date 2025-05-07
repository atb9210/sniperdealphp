# Backlog delle Attività - SnipeDeal

Questo documento elenca le attività pianificate per lo sviluppo futuro di SnipeDeal.

## Priorità Alta

### Stabilità e Robustezza
- [ ] **Risolvere issue con queue worker e job esecuzione**
  - Assicurarsi che il queue worker rimanga attivo
  - Migliorare la gestione dei job in coda
  - Aggiungere monitoraggio per i worker

- [ ] **Migliorare la gestione degli errori nel processo di scraping**
  - Implementare retry automatici con backoff esponenziale
  - Migliorare i messaggi di errore specifici
  - Notificare amministratori in caso di errori critici

- [ ] **Ottimizzazione delle performance**
  - Analizzare e ottimizzare le query al database
  - Implementare caching per le pagine più visitate
  - Migliorare il tempo di caricamento delle pagine con molti risultati

### Miglioramenti UX/UI
- [ ] **Filtri avanzati per i risultati delle campagne**
  - Aggiungere filtri per prezzo, data, stato
  - Implementare ricerca full-text nei risultati
  - Salvare i filtri preferiti dell'utente

- [ ] **Migliorare il design responsive**
  - Ottimizzare layout per dispositivi mobili
  - Implementare versione mobile-first delle pagine principali

## Priorità Media

### Nuove Funzionalità
- [ ] **Dashboard avanzato con grafici**
  - Implementare grafici per trend di prezzo
  - Visualizzare statistiche di esecuzione nel tempo
  - Creare una vista di analytics personalizzabile

- [ ] **Sistema di tag per i risultati**
  - Aggiungere possibilità di taggare annunci
  - Implementare filtri per tag
  - Aggiungere tag automatici basati su regole

- [ ] **Notifiche avanzate**
  - Aggiungere supporto per notifiche email
  - Implementare notifiche push nel browser
  - Consentire personalizzazione delle notifiche per campagna

### Integrazione e Estensione
- [ ] **Supporto per altri marketplace**
  - Preparare struttura modulare per nuovi scraper
  - Implementare scraper per eBay Italia
  - Uniformare il formato dei risultati tra le piattaforme

## Priorità Bassa

### Miglioramenti Tecnici
- [ ] **Migrazione a Laravel 10**
  - Aggiornamento delle dipendenze
  - Refactoring del codice obsoleto
  - Test di regressione

- [ ] **Containerizzazione con Docker**
  - Creare Dockerfile e docker-compose.yml
  - Documentare il setup con Docker
  - Semplificare deployment

### Funzionalità Aggiuntive
- [ ] **Esportazione dati**
  - Aggiungere esportazione in CSV
  - Implementare esportazione in JSON
  - Creare API per accesso ai dati

- [ ] **Modalità collaborativa**
  - Implementare condivisione campagne tra utenti
  - Aggiungere ruoli e permessi
  - Creare dashboard di team

## Bug Noti

### Critici
- [ ] **Fix: Il job non aggiorna correttamente last_run_at quando eseguito manualmente**
  - Investigare perché l'aggiornamento non funziona correttamente
  - Verificare che la funzione updateNextRunTime() sia chiamata

- [ ] **Fix: Alcuni selettori CSS non funzionano dopo aggiornamento Subito.it**
  - Aggiornare i selettori in SubitoScraper
  - Implementare test automatici per validare i selettori

### Non Critici
- [ ] **Fix: Pagination non mantiene i filtri attivi**
  - Aggiungere parametri di query per filtri durante la navigazione pagine
  - Implementare sessione per ricordare filtri

- [ ] **Fix: Form di ricerca non ricorda valori precedenti**
  - Salvare temporaneamente i valori in sessione
  - Implementare remember me per campi principali

## Documentazione e Testing
- [ ] **Migliorare documentazione per sviluppatori**
  - Documentare API e struttura del codice
  - Creare guide per contribuire al progetto

- [ ] **Aumentare la copertura dei test**
  - Implementare test unitari per componenti core
  - Aggiungere test di integrazione per flussi principali
  - Creare test end-to-end per scenari principali 