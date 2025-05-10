# Directory Deployment

Questa directory contiene tutti i file e gli script necessari per il deployment dell'applicazione su RunCloud.

## Struttura

```
deployment/
├── docs/                    # Documentazione dettagliata
├── logs/                    # Log di deployment
└── scripts/                 # Script di deployment
```

## File Principali

- `docs/GUIDA_DEPLOYMENT.md`: Guida completa al processo di deployment
- `scripts/deploy-runcloud.sh`: Script principale di deployment
- `scripts/fix-permissions.sh`: Script per la gestione dei permessi
- `scripts/test-error.sh`: Script per testare gli errori di deployment

## Utilizzo

1. Leggere la guida in `docs/GUIDA_DEPLOYMENT.md`
2. Configurare RunCloud con gli script in `scripts/`
3. Monitorare i log in `logs/`

## Manutenzione

- I log vengono salvati automaticamente in `logs/`
- Gli script possono essere modificati in `scripts/`
- La documentazione può essere aggiornata in `docs/` 