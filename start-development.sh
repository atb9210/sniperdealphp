#!/bin/bash
echo "Avvio ambiente di sviluppo SnipeDeal..."

# Avvia il server in background
php artisan serve &
SERVER_PID=$!
echo "Server avviato con PID: $SERVER_PID"

# Avvia il queue worker in background
php artisan queue:work &
WORKER_PID=$!
echo "Queue worker avviato con PID: $WORKER_PID"

# Avvia il server Vite per frontend in background
npm run dev &
VITE_PID=$!
echo "Server Vite avviato con PID: $VITE_PID"

echo "Ambiente di sviluppo avviato! Per terminare, esegui: ./stop-development.sh"
# Salva i PID in un file temporaneo
echo "$SERVER_PID $WORKER_PID $VITE_PID" > .dev-pids 