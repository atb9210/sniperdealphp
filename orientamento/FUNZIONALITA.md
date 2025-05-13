# Funzionalità di SnipeDeal

Questo documento descrive le funzionalità attualmente implementate e quelle previste per lo sviluppo futuro.

## Funzionalità Implementate

### Gestione Campagne
- ✅ Creazione campagne con parametri personalizzati (keyword, prezzo min/max, pagine, intervallo)
- ✅ Attivazione/disattivazione campagne
- ✅ Esecuzione manuale delle campagne ("Esegui ora")
- ✅ Visualizzazione dei risultati delle campagne
- ✅ Pianificazione automatica delle esecuzioni in base all'intervallo definito

### Scraping
- ✅ Scraping di annunci da Subito.it
- ✅ Supporto per ricerca specifica (qso)
- ✅ Paginazione automatica (fino a 10 pagine)
- ✅ Estrazione dettagli completi degli annunci (titolo, prezzo, località, data, link, immagine, stato, spedizione)
- ✅ Rilevamento degli annunci nuovi vs. quelli già trovati

### Notifiche
- ✅ Configurazione bot Telegram personalizzato
- ✅ Invio notifiche per nuovi annunci trovati
- ✅ Test delle notifiche Telegram
- ✅ Personalizzazione del formato delle notifiche con dettagli degli annunci
- ✅ Tracking degli annunci notificati per evitare duplicati

### Logging e Monitoraggio
- ✅ Log dettagliati delle esecuzioni dei job
- ✅ Visualizzazione dei log con stato, durata, risultati
- ✅ Dashboard con statistiche generali
- ✅ Storico delle esecuzioni per campagna
- ✅ Pulizia automatica dei log più vecchi

### Autenticazione e Sicurezza
- ✅ Registrazione e login utenti
- ✅ Protezione delle campagne (visibili solo al proprietario)
- ✅ Gestione profilo utente
- ✅ Autorizzazioni basate su policy

## Funzionalità in Sviluppo

### Miglioramenti Scraping
- 🔄 Ottimizzazione delle performance di scraping
- 🔄 Gestione più robusta degli errori
- 🔄 Supporto per proxy e rotazione IP
- 🔄 Gestione dei captcha

### Analisi Dati
- 🔄 Grafici di trend dei prezzi nel tempo
- 🔄 Statistiche avanzate per categoria di prodotto
- 🔄 Rilevamento automatico di anomalie nei prezzi
- 🔄 Esportazione dati in diversi formati

### Interfaccia Utente
- 🔄 Miglioramenti responsive del design
- 🔄 Dark mode
- 🔄 Filtri avanzati per la visualizzazione dei risultati
- 🔄 Ordinamento personalizzato dei risultati

### Integrazione con Altri Servizi
- 🔄 Webhook per integrazioni personalizzate
- 🔄 API per accesso programmatico ai dati
- 🔄 Supporto per altre piattaforme di notifica oltre a Telegram

## Funzionalità Pianificate

### Espansione a Nuove Piattaforme
- 📅 Supporto per eBay Italia
- 📅 Supporto per Facebook Marketplace
- 📅 Supporto per Vinted
- 📅 Interfaccia unificata per cercare su più piattaforme

### Intelligenza Artificiale
- 📅 Riconoscimento e categorizzazione automatica degli annunci
- 📅 Analisi delle immagini per valutare la qualità dei prodotti
- 📅 Suggerimenti automatici per campagne basati sugli interessi dell'utente
- 📅 Rilevamento automatico di annunci sospetti o fraudolenti

### App Mobile
- 📅 Versione mobile nativa per iOS
- 📅 Versione mobile nativa per Android
- 📅 Notifiche push
- 📅 Modalità offline per visualizzare risultati salvati

### Social e Collaborazione
- 📅 Condivisione di campagne tra utenti
- 📅 Commenti e note sugli annunci
- 📅 Liste preferiti e wishlist
- 📅 Suggerimenti collaborativi tra utenti con interessi simili

## Legenda
- ✅ Implementato
- 🔄 In sviluppo
- 📅 Pianificato 