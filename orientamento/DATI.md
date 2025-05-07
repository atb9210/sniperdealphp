# Dati - SnipeDeal

Questo documento descrive la fonte e la struttura dei dati utilizzati all'interno dell'applicazione SnipeDeal.

## Fonte dei Dati

### Subito.it
La fonte primaria dei dati è il sito di annunci Subito.it. L'applicazione estrae i dati direttamente dal sito web mediante tecniche di web scraping.

#### URL Structure
- Base URL: `https://www.subito.it/annunci-italia/vendita/usato/?q=`
- Parametri principali:
  - `q`: keyword di ricerca
  - `qso`: true/false per ricerca specifica
  - `page`: numero pagina (1-based)

#### Selezione Elementi HTML
Il sistema utilizza selettori CSS per estrarre i dati dalle pagine HTML:
- Card annunci: `div.item-card--small`
- Titolo: `h2`
- Prezzo: `p.SmallCard-module_price__yERv7`
- Località: `span.index-module_town__2H3jy`
- Data: `span.index-module_date__Fmf-4`
- Link: `a.SmallCard-module_link__hOkzY`
- Immagine: `img.CardImage-module_photo__WMsiO`

#### Limitazioni
- Rate limiting: Subito.it implementa meccanismi di rate limiting
- Cambiamenti struttura: La struttura HTML potrebbe cambiare, richiedendo aggiornamenti ai selettori
- Termini di servizio: Necessario rispettare i termini di servizio di Subito.it

## Configurazione del Database

### Database Attuale: SQLite
L'applicazione utilizza attualmente SQLite come database principale per facilità di configurazione e sviluppo. 

#### Configurazione in .env
```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

#### Vantaggi nell'ambiente di sviluppo
- Nessuna necessità di server database separato
- Facilità di backup (singolo file)
- Configurazione minima

#### Limitazioni
- Performance limitate con dataset molto grandi
- Supporto limitato per query complesse
- Concorrenza limitata (problemi con accessi simultanei)

#### Piano di migrazione
È previsto il passaggio a PostgreSQL per l'ambiente di produzione quando il volume di dati aumenterà, mantenendo SQLite per gli ambienti di sviluppo.

### Coda dei Job
La gestione delle code è basata sul database SQLite attraverso la tabella `jobs`.

#### Tabelle correlate
- `jobs`: Jobs in attesa di esecuzione
- `failed_jobs`: Jobs falliti
- `job_batches`: Gruppi di jobs correlati (quando usati)

#### Schema della tabella jobs
```sql
CREATE TABLE "jobs" (
  "id" bigint PRIMARY KEY AUTOINCREMENT NOT NULL,
  "queue" varchar NOT NULL,
  "payload" text NOT NULL,
  "attempts" tinyint NOT NULL,
  "reserved_at" integer,
  "available_at" integer NOT NULL,
  "created_at" integer NOT NULL
);

CREATE INDEX "jobs_queue_reserved_at_index" ON "jobs" ("queue", "reserved_at");
```

## Struttura dei Dati

### Entità Principali

#### Campaign
Rappresenta una campagna di monitoraggio configurata dall'utente.

```sql
CREATE TABLE `campaigns` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `min_price` decimal(10,2) DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `max_pages` int NOT NULL DEFAULT '3',
  `interval_minutes` int NOT NULL DEFAULT '60',
  `qso` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaigns_user_id_foreign` (`user_id`),
  CONSTRAINT `campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

#### CampaignResult
Rappresenta un singolo annuncio trovato durante l'esecuzione di una campagna.

```sql
CREATE TABLE `campaign_results` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stato` varchar(255) NOT NULL DEFAULT 'Disponibile',
  `spedizione` tinyint(1) NOT NULL DEFAULT '0',
  `notified` tinyint(1) NOT NULL DEFAULT '0',
  `is_new` tinyint(1) NOT NULL DEFAULT '1',
  `extra_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_results_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `campaign_results_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
);
```

#### JobLog
Registra l'esecuzione di un job di scraping.

```sql
CREATE TABLE `job_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL,
  `results_count` int DEFAULT NULL,
  `new_results_count` int DEFAULT NULL,
  `message` text,
  `error` text,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_logs_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `job_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
);
```

#### UserSetting
Contiene le impostazioni dell'utente, inclusi i dati per le notifiche Telegram.

```sql
CREATE TABLE `user_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `telegram_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

### Relazioni

```
User 1:N Campaign (Un utente può avere molte campagne)
Campaign 1:N CampaignResult (Una campagna può avere molti risultati)
Campaign 1:N JobLog (Una campagna può avere molti log di esecuzione)
User 1:1 UserSetting (Un utente ha una sola configurazione)
```

## Gestione dei Dati

### Ciclo di Vita dei Dati
1. **Acquisizione**: Scraped da Subito.it durante l'esecuzione di una campagna
2. **Elaborazione**: Filtrazione in base ai criteri della campagna (prezzo min/max)
3. **Storage**: Salvataggio nel database come CampaignResult
4. **Notifica**: Invio notifiche per nuovi risultati
5. **Visualizzazione**: Presentazione all'utente tramite interfaccia web
6. **Archiviazione/Eliminazione**: I dati più vecchi possono essere eliminati periodicamente

### Strategia di Deduplicazione
- Per identificare annunci duplicati, viene utilizzato il campo `link` come identificatore unico
- Se un annuncio con lo stesso link esiste già, viene aggiornato invece di creare un nuovo record
- Il campo `is_new` identifica i risultati non ancora visti dall'utente
- Il campo `notified` traccia se una notifica è già stata inviata

### Considerazioni sulla Privacy
- I dati degli annunci sono pubblicamente disponibili su Subito.it
- Le credenziali Telegram degli utenti sono memorizzate in modo sicuro
- Non vengono raccolti dati personali dai venditori degli annunci
- I dati sono accessibili solo all'utente proprietario della campagna 

### Gestione dei Limiti di SQLite

Per gestire le limitazioni di SQLite, soprattutto con l'aumento del volume dei dati:

1. **Pulizia periodica**: Eliminazione automatica dei risultati più vecchi di X giorni
2. **Indicizzazione ottimizzata**: Creazione di indici appropriati per le query più frequenti
3. **Backup regolari**: Backup pianificati del file database.sqlite
4. **Monitoraggio dimensioni**: Alert quando il database supera dimensioni critiche
5. **Strategie di migrazione**: Procedure testate per la migrazione a PostgreSQL quando necessario 