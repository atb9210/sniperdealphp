#!/bin/bash

echo "SnipeDeal - Laravel Scheduler Runner"
echo "===================================="
echo "Avvio scheduler in modalità continua..."
echo "Premi Ctrl+C per terminare."
echo ""
echo "Lo scheduler verrà eseguito ogni minuto."
echo "Log: storage/logs/scheduler.log"
echo ""

# Assicura che la directory dei log esista
mkdir -p storage/logs

# Inizializza il file di log se non esiste
touch storage/logs/scheduler.log

# Funzione per una chiusura pulita
cleanup() {
    echo ""
    echo "Terminazione scheduler..."
    exit 0
}

# Intercetta il segnale SIGINT (Ctrl+C)
trap cleanup SIGINT

# Loop principale
while true; do
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Esecuzione schedule:run"
    php artisan schedule:run
    
    # Attendi 60 secondi ma mostra un countdown
    for i in {60..1}; do
        echo -ne "\rProssima esecuzione tra $i secondi... "
        sleep 1
    done
    echo -ne "\r\033[K" # Pulisci la riga
done 