# Guida al Deployment - SnipeDeal

## Configurazione RunCloud

### 1. Deployment Script
Nel pannello RunCloud, configurare il deployment script con:
```bash
git pull origin staging
./deployment/scripts/fix-permissions.sh
./deployment/scripts/deploy-runcloud.sh
```

### 2. Configurazione Web App
- **PHP Version**: 8.3
- **Document Root**: `/public`
- **Web Server**: Nginx
- **Environment**: Production

### 3. Variabili d'Ambiente
Assicurarsi che il file `.env` contenga:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tuo-dominio.com

DB_CONNECTION=sqlite
```

## Struttura Deployment

```
deployment/
├── docs/                    # Documentazione
│   └── GUIDA_DEPLOYMENT.md  # Questa guida
├── logs/                    # Log di deployment
│   └── deploy-debug.log     # Log dettagliato
└── scripts/                 # Script di deployment
    ├── deploy-runcloud.sh   # Script principale
    └── fix-permissions.sh   # Script permessi
```

## Processo di Deployment

1. **Pre-deployment**
   - Verifica che tutti i cambiamenti siano committati
   - Push su branch staging
   - Verifica che i test passino

2. **Durante il Deployment**
   - RunCloud esegue il pull dal branch staging
   - Vengono sistemati i permessi
   - Viene eseguito lo script di deployment
   - I log vengono salvati in `deployment/logs/`

3. **Post-deployment**
   - Verifica che l'applicazione sia accessibile
   - Controlla i log per eventuali errori
   - Verifica che le migrazioni siano state eseguite

## Troubleshooting

### Errori Comuni

1. **Permission Denied**
   - Eseguire `fix-permissions.sh`
   - Verificare i permessi di storage e bootstrap/cache

2. **Composer/NPM Errors**
   - Verificare la versione di PHP (8.3)
   - Controllare i log in `deployment/logs/`

3. **Database Errors**
   - Verificare la configurazione SQLite
   - Controllare i permessi del database

### Log e Monitoraggio

- I log di deployment sono in `deployment/logs/deploy-debug.log`
- I log dell'applicazione sono in `storage/logs/laravel.log`
- RunCloud Activity Log nel pannello di controllo

## Manutenzione

### Pulizia Periodica
```bash
# Pulizia cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ottimizzazione
php artisan optimize
```

### Backup
- Database SQLite: `/database/database.sqlite`
- File .env
- Log importanti

## Contatti e Supporto

Per problemi di deployment:
1. Controllare i log in `deployment/logs/`
2. Verificare RunCloud Activity Log
3. Contattare il team di sviluppo 