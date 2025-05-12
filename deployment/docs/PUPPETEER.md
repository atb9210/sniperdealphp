# Documentazione Puppeteer - SnipeDeal

## Panoramica

Il progetto SnipeDeal utilizza Puppeteer per lo scraping di annunci da Subito.it, con tecniche avanzate anti-bot che permettono una raccolta dati affidabile anche in presenza di misure anti-scraping.

## Requisiti di sistema

Per eseguire Puppeteer su un server di produzione, sono necessari:

- Node.js ≥ 20.x
- NPM ≥ 10.x
- Ubuntu ≥ 20.04 (consigliato) o altra distribuzione Linux con supporto per le dipendenze di Chrome
- Almeno 2GB di RAM disponibile
- Almeno 1GB di spazio su disco per Chrome e dipendenze

## Dipendenze

Le dipendenze di sistema necessarie sono installate automaticamente dallo script `deploy-puppeteer.sh` durante il deployment. Queste includono:

```bash
dconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 
libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc-s1 libgdk-pixbuf2.0-0 
libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 
libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 
libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates 
fonts-liberation libnss3 lsb-release xdg-utils wget libgbm1
```

## Installazione e Deployment

Il setup di Puppeteer viene gestito automaticamente durante il processo di deployment attraverso lo script `deploy-runcloud.sh`, che a sua volta chiama `deploy-puppeteer.sh`.

Passi eseguiti durante l'installazione:
1. Verifica di Node.js e NPM
2. Installazione delle dipendenze di sistema per Chrome
3. Creazione delle directory necessarie
4. Impostazione dei permessi corretti
5. Esecuzione di un test per verificare il funzionamento

## Struttura dei file

```
SnipeDealPhp/
├── node/
│   └── subito_scraper.js  # Script Node.js per lo scraping con Puppeteer
├── app/
│   └── Services/
│       └── SubitoScraper.php  # Classe PHP che interfaccia con lo script Node.js
├── storage/
│   └── app/
│       └── temp/  # Directory per file temporanei e output dello scraping
└── deployment/
    ├── scripts/
    │   ├── deploy-puppeteer.sh  # Script di setup per Puppeteer
    │   └── test-puppeteer.sh  # Script di test per Puppeteer
    └── logs/
        ├── puppeteer-setup.log  # Log di installazione
        └── puppeteer-test.log  # Log di test
```

## Utilizzo in PHP

La classe `SubitoScraper` gestisce la comunicazione con lo script Node.js attraverso `Process`:

```php
// Esempio di utilizzo
$scraper = new SubitoScraper();
$results = $scraper->scrape('iphone', false, 3, false);
```

Parametri di `scrape()`:
- `$keyword`: Parola chiave da cercare
- `$qso`: Flag per "quick sell only"
- `$pages`: Numero di pagine da scrapare
- `$useProxy`: Flag per l'utilizzo di un proxy

## Test del funzionamento

Per verificare che Puppeteer funzioni correttamente dopo il deployment:

```bash
cd deployment/scripts
./test-puppeteer.sh
```

Questo script esegue un test basilare che naviga su un sito, effettua uno screenshot e verifica che tutto funzioni correttamente.

## Troubleshooting

### Problemi comuni

1. **Errore "Failed to launch the browser process"**
   - Causa: Dipendenze mancanti o permessi insufficienti
   - Soluzione: Rilanciare `deploy-puppeteer.sh` e verificare gli errori nel log

2. **Errore di memoria**
   - Causa: RAM insufficiente per Chrome
   - Soluzione: Assicurarsi che il server abbia almeno 2GB di RAM disponibile

3. **Errore "No such file or directory"**
   - Causa: Path non corretti o permessi file inadeguati
   - Soluzione: Verificare i permessi con `chmod +x` per i file eseguibili

### Logs

I log relativi a Puppeteer si trovano in:
- `deployment/logs/puppeteer-setup.log`: Log di installazione
- `deployment/logs/puppeteer-test.log`: Log di test
- `storage/logs/laravel-*.log`: Log generali dell'applicazione

## Performance e ottimizzazione

- Aumentare `--max-old-space-size` per Node.js se necessario per gestire grandi volumi di dati
- Utilizzare il flag `headless: 'new'` per il nuovo motore headless più efficiente
- Configurare timeout adeguati per evitare scraping bloccati
- Utilizzare proxy rotanti per evitare il rate limiting 