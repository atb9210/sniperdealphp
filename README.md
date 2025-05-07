# SnipeDeal - Scraper Subito.it

Un'applicazione Laravel per il monitoraggio e l'analisi degli annunci su Subito.it.

## Funzionalità Principali

### 1. Scraping degli Annunci
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

### 2. Interfaccia Web
- Form di ricerca con:
  - Campo keyword
  - Selezione numero pagine (1-10)
  - Toggle ricerca specifica
  - Loading spinner durante la ricerca
- Visualizzazione risultati in tabella con:
  - Toggle per mostrare/nascondere immagini
  - Filtri per annunci venduti e spedizione disponibile
  - Statistiche (totale disponibili, venduti, sell-through rate, prezzi medi)
- Storico delle keyword ricercate

### 3. Comandi Artisan
- `scraper:test {keyword} {--pages=3}`: Test dello scraper con una keyword specifica

## Struttura del Progetto

### Controllers
- `KeywordFormController`: Gestisce il form di ricerca e la visualizzazione dei risultati
  - `index()`: Mostra il form e lo storico delle keyword
  - `store()`: Processa la ricerca e mostra i risultati

### Services
- `SubitoScraper`: Servizio principale per lo scraping
  - `scrape($keyword, $qso, $pages)`: Metodo principale che coordina lo scraping
  - `scrapeViaHtml()`: Implementazione dello scraping via HTML
  - Funzioni di supporto per l'estrazione dei dati

### Views
- `keyword-form/index.blade.php`: Vista principale con:
  - Form di ricerca
  - Tabella risultati
  - Statistiche
  - Storico keyword

### Models
- `Keyword`: Model per lo storico delle keyword ricercate

## Come Funziona

1. L'utente inserisce una keyword nel form
2. Il controller processa la richiesta e chiama lo scraper
3. Lo scraper:
   - Costruisce l'URL con i parametri corretti
   - Fa le richieste HTTP per ogni pagina
   - Estrae i dati usando Symfony DomCrawler
   - Unisce i risultati di tutte le pagine
4. I risultati vengono mostrati in una tabella con:
   - Filtri interattivi
   - Statistiche in tempo reale
   - Opzioni di visualizzazione

## Note Tecniche

### URL Structure
- Base URL: `https://www.subito.it/annunci-italia/vendita/usato/?q=`
- Parametri:
  - `q`: keyword di ricerca
  - `qso`: true/false per ricerca specifica
  - `page`: numero pagina (1-based)

### Selezione Elementi HTML
- Card annunci: `div.item-card--small`
- Titolo: `h2`
- Prezzo: `p.SmallCard-module_price__yERv7`
- Località: `span.index-module_town__2H3jy`
- Data: `span.index-module_date__Fmf-4`
- Link: `a.SmallCard-module_link__hOkzY`
- Immagine: `img.CardImage-module_photo__WMsiO`

### Logging
- Log dettagliati per:
  - URL delle richieste
  - Numero di annunci trovati per pagina
  - Errori e warning
  - Statistiche di scraping

## Prossimi Sviluppi Possibili

1. Salvataggio automatico dei risultati nel database
2. Notifiche per nuovi annunci
3. Analisi dei prezzi nel tempo
4. Filtri avanzati (prezzo, località, etc.)
5. Export dei risultati in vari formati
6. Dashboard con grafici e statistiche
7. Sistema di alert per annunci che corrispondono a criteri specifici
