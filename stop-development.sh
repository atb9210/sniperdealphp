#!/bin/bash
echo "Arresto ambiente di sviluppo SnipeDeal..."

if [ -f .dev-pids ]; then
    read SERVER_PID WORKER_PID VITE_PID < .dev-pids
    
    # Termina i processi
    kill $SERVER_PID 2>/dev/null
    kill $WORKER_PID 2>/dev/null
    kill $VITE_PID 2>/dev/null
    
    echo "Processi terminati."
    rm .dev-pids
else
    echo "File .dev-pids non trovato. Nessun processo da terminare."
fi 