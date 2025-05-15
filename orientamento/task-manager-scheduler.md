# Task Manager - Verifica Scheduler e Job Log

## 1. Verifica Stato Scheduler
- [x] 1.1 Controllo configurazione supervisor per lo scheduler
  - Corretto il percorso della directory in snipedeal-scheduler.conf
  - Aggiornato il percorso del file di log
  - Riavviato lo scheduler con successo
  - Stato attuale: RUNNING (pid 852926)
- [x] 1.2 Verifica log dello scheduler
  - Creata directory storage/logs
  - Creato file scheduler.log
  - Impostati permessi corretti (666)
  - Scheduler riavviato per iniziare a scrivere nei log
- [x] 1.3 Test manuale dello scheduler
  - Eseguito comando campaigns:run
  - Trovata 1 campagna da eseguire
  - Job eseguito con successo
  - Output: "All campaigns dispatched"
- [x] 1.4 Verifica che le campagne vengano eseguite correttamente
  - Campagna "test" eseguita con successo
  - Scraping completato per 33 annunci
  - Notifiche Telegram saltate (configurazione mancante)
  - 8 risultati non notificati in coda

## 2. Monitoraggio Worker Supervisor
- [x] 2.1 Verifica configurazione worker supervisor
  - [x] 2.1.1 Controllo file di configurazione
    - Corretto percorso in snipedeal-worker.conf
    - Aggiornato percorso log file
    - Riavviato worker con successo
    - Stato attuale: 2 worker RUNNING (pid 853817, 853818)
  - [x] 2.1.2 Verifica percorsi e permessi
    - Verificati permessi storage/logs
    - Verificati permessi storage/framework
    - Verificati permessi bootstrap/cache
    - Impostati permessi corretti (775) su tutte le directory
    - Impostato proprietario corretto (runcloud:runcloud)
  - [x] 2.1.3 Controllo log dei worker
    - Verificato stato worker tramite supervisor
    - Entrambi i worker in esecuzione (uptime 0:01:12)
    - File di log creato e pronto per l'uso
    - Permessi corretti impostati
- [x] 2.2 Implementazione vista di monitoraggio
  - [x] 2.2.1 Creazione controller per lo stato dei worker
    - Creato WorkerMonitorController
    - Implementati metodi per stato worker
    - Implementati metodi per info sistema
    - Implementati metodi per log recenti
  - [x] 2.2.2 Vista con indicatori di stato per ogni worker
    - Creata vista worker-monitor.index
    - Implementato layout responsive
    - Aggiunti indicatori di stato
    - Aggiunto auto-refresh ogni 30 secondi
  - [x] 2.2.3 Integrazione con i log esistenti
    - Integrati log worker
    - Aggiunta sezione log recenti
    - Implementato formattazione log
- [ ] 2.3 Aggiunta controlli di salute
  - [ ] 2.3.1 Verifica uptime dei worker
  - [ ] 2.3.2 Monitoraggio memoria e CPU
  - [ ] 2.3.3 Controllo errori nei log
- [ ] 2.4 Implementazione notifiche
  - [ ] 2.4.1 Notifica in caso di worker down
  - [ ] 2.4.2 Alert per errori critici
  - [ ] 2.4.3 Report giornaliero stato worker

## 3. Bug Fix e Risoluzione Errori
- [ ] 3.1 Risoluzione errori di routing
  - [x] 3.1.1 Verifica registrazione route worker-monitor
    - Route [worker-monitor.index] non definita
    - Errore in navigation.blade.php
    - Necessario verificare routes/web.php
  - [x] 3.1.2 Verifica namespace del controller
    - Controllare che il controller sia nel namespace corretto
    - Verificare che il controller sia registrato in RouteServiceProvider
  - [x] 3.1.3 Test della route dopo le correzioni
    - Verificare che la route sia accessibile
    - Testare il link nel menu di navigazione

- [ ] 3.2 Risoluzione errori Symfony Error Handler
  - [ ] 3.2.1 Fix funzione highlight_file mancante
    - Errore: Call to undefined function Symfony\Component\ErrorHandler\ErrorRenderer\highlight_file()
    - Verificare installazione estensione PHP necessaria
    - Controllare configurazione PHP
  - [ ] 3.2.2 Aggiornamento dipendenze Symfony
    - Verificare versione symfony/error-handler
    - Aggiornare se necessario
    - Testare dopo l'aggiornamento
  - [ ] 3.2.3 Configurazione ambiente di sviluppo
    - Verificare APP_DEBUG in .env
    - Configurare correttamente l'ambiente di sviluppo
    - Testare la gestione degli errori

- [ ] 3.3 Risoluzione errori Process
  - [x] 3.3.1 Fix proc_open non disponibile
    - Errore: The Process class relies on proc_open, which is not available
    - Modificare il controller per usare metodi alternativi
    - Implementare soluzione senza Process class
  - [x] 3.3.2 Implementazione metodi alternativi
    - Usare shell_exec per i comandi di sistema
    - Rimuovere accesso a file di sistema
    - Gestire restrizioni open_basedir
  - [ ] 3.3.3 Test delle nuove implementazioni
    - Verificare che le info di sistema siano corrette
    - Testare la lettura dei log
    - Verificare le performance

## 4. Testing e Verifica
- [ ] 4.1 Test vista di monitoraggio
- [ ] 4.2 Verifica notifiche
- [ ] 4.3 Test recovery automatico
- [ ] 4.4 Verifica performance

## 4. Diagnostica e Risoluzione Problemi Coda

### 4.1 Diagnostica Conteggio Job in Coda
- [ ] Verificare l'implementazione attuale del conteggio job
  - [ ] Controllare il metodo `getQueueSize()` nel controller
  - [ ] Verificare se il comando `queue:size` è disponibile
  - [ ] Controllare i log per eventuali errori
- [ ] Testare alternative per il conteggio
  - [ ] Usare `Queue::size()` di Laravel
  - [ ] Verificare la tabella `jobs` nel database
  - [ ] Implementare un conteggio manuale dei job
- [ ] Aggiungere logging dettagliato
  - [ ] Log dei job in entrata
  - [ ] Log dei job processati
  - [ ] Log degli errori di processamento

### 4.2 Implementazione Soluzione
- [ ] Scegliere il metodo più affidabile per il conteggio
- [ ] Implementare la soluzione nel controller
- [ ] Aggiungere test unitari
- [ ] Verificare con campagne reali
- [ ] Documentare la soluzione

### 4.3 Monitoraggio e Manutenzione
- [ ] Implementare alert per anomalie
- [ ] Aggiungere metriche di performance
- [ ] Creare dashboard di monitoraggio
- [ ] Documentare procedure di manutenzione

## 5. Documentazione
- [ ] 5.1 Documentazione vista di monitoraggio
- [ ] 5.2 Documentazione sistema di notifiche
- [ ] 5.3 Aggiornamento README

## 5. Analisi e Risoluzione Esecuzione Campagne

### 5.1 Analisi Flusso Attuale
- [ ] Verifica comando campaigns:run
  - [ ] Analisi codice del comando
  - [ ] Verifica creazione job
  - [ ] Controllo log di esecuzione
- [ ] Verifica gestione coda
  - [ ] Controllo configurazione queue
  - [ ] Verifica stato worker
  - [ ] Analisi log worker

### 5.2 Implementazione Soluzione Run vs Work
- [ ] Analisi vantaggi/svantaggi
  - [x] Confronto performance
    - dispatchSync: esecuzione immediata, bloccante
    - dispatch: asincrono, gestito dai worker
  - [ ] Verifica gestione errori
  - [ ] Analisi impatto su risorse
- [ ] Implementazione soluzione
  - [ ] Modifica comando campaigns:run
    - Cambiare da dispatchSync a dispatch
    - Aggiungere logging per tracciare i job
    - Verificare che i job vengano inseriti in coda
  - [ ] Aggiornamento gestione job
    - Implementare retry policy
    - Aggiungere timeout
    - Migliorare gestione errori
  - [ ] Implementazione logging
    - Log creazione job
    - Log esecuzione job
    - Log errori e retry
- [ ] Test e verifica
  - [ ] Test con campagne reali
  - [ ] Verifica performance
  - [ ] Controllo gestione errori

### 5.3 Ottimizzazione Processo
- [ ] Miglioramento gestione job
  - [ ] Implementazione retry policy
  - [ ] Gestione timeout
  - [ ] Logging dettagliato
- [ ] Monitoraggio e alert
  - [ ] Implementazione metriche
  - [ ] Setup alert
  - [ ] Dashboard monitoraggio

### 5.4 Documentazione
- [ ] Documentazione soluzione
  - [ ] Architettura sistema
  - [ ] Flusso di esecuzione
  - [ ] Procedure di manutenzione
- [ ] Aggiornamento README
  - [ ] Istruzioni installazione
  - [ ] Configurazione
  - [ ] Troubleshooting

## Note
- Ogni task verrà aggiornato con [x] quando completato
- Verranno aggiunte note specifiche per ogni step
- In caso di errori, verranno documentati e risolti prima di procedere

## Stato Attuale
- Scheduler funzionante correttamente
- Necessità di monitoraggio worker supervisor
- Sistema di notifiche da implementare
- Errori di routing e Symfony da risolvere 