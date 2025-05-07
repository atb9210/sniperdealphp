# SnipeDeal - Panoramica del Progetto

SnipeDeal è un'applicazione web basata su Laravel per il monitoraggio automatico degli annunci su Subito.it. Il sistema permette agli utenti di creare campagne personalizzate per monitorare prodotti specifici, ricevere notifiche su nuovi annunci e analizzare i risultati.

## Obiettivo Principale

L'obiettivo principale dell'applicazione è automatizzare la ricerca e il monitoraggio degli annunci di prodotti su Subito.it, permettendo agli utenti di essere informati rapidamente quando vengono pubblicati nuovi annunci che corrispondono ai loro criteri di ricerca.

## Componenti Principali

1. **Sistema di Scraping**: Un motore di scraping che estrae dati da Subito.it in modo efficiente
2. **Gestione Campagne**: Dashboard per creare e gestire diverse campagne di monitoraggio
3. **Sistema di Notifiche**: Invio automatico di notifiche Telegram in tempo reale per nuovi annunci 
4. **Strumenti di Diagnostica**: Comandi per monitorare lo stato del sistema e risolvere problemi
5. **Interfaccia Utente**: UI moderna e reattiva per la gestione delle campagne e la visualizzazione dei risultati

## Tecnologie Utilizzate

- **Backend**: Laravel PHP framework
- **Frontend**: Blade + Alpine.js + Tailwind CSS
- **Database**: SQLite (con piano di migrazione a PostgreSQL)
- **Notifiche**: API Telegram
- **Scraping**: Symfony DomCrawler

## Stato Attuale del Progetto

L'applicazione è funzionale con le seguenti caratteristiche implementate:
- Creazione e gestione campagne
- Scraping automatico di Subito.it
- Notifiche Telegram in tempo reale
- Strumenti di diagnostica e monitoraggio
- Dashboard per la visualizzazione dei risultati

Il progetto è in fase di sviluppo attivo con miglioramenti continui all'interfaccia utente, all'efficienza dello scraping e all'aggiunta di nuove funzionalità.

## Documentazione Correlata

Per informazioni più dettagliate, consulta i seguenti documenti:
- [Visione del Progetto](VISIONE.md)
- [Funzionalità](FUNZIONALITA.md)
- [Architettura Tecnica](ARCHITETTURA.md)
- [Note sullo Sviluppo](DIARIO_SVILUPPO.md) 