#!/bin/bash

# Questo script genera le configurazioni di supervisor a partire dai template

set -e

# Assicurati di essere nella directory supervisor
if [[ "$(basename $(pwd))" != "supervisor" ]]; then
  if [[ -d "supervisor" ]]; then
    cd supervisor
  else
    echo "Errore: Non trovo la directory supervisor"
    exit 1
  fi
fi

# Ottieni il percorso assoluto dell'applicazione
APP_PATH=$(dirname $(pwd))
APP_USER=$(whoami)

echo "Generazione configurazioni supervisor..."
echo "APP_PATH: $APP_PATH"
echo "APP_USER: $APP_USER"

# Genera le configurazioni a partire dai template
for template in templates/*.conf.template; do
  output_file=$(basename "$template" .template)
  echo "Generazione $output_file da $template..."
  
  # Sostituisci le variabili nel template
  sed -e "s|{{APP_PATH}}|$APP_PATH|g" \
      -e "s|{{APP_USER}}|$APP_USER|g" \
      "$template" > "$output_file"
  
  echo "File $output_file generato con successo"
done

echo "Configurazioni generate con successo"
echo "Per installarle, esegui: sudo cp *.conf /etc/supervisor/conf.d/ && sudo supervisorctl reread && sudo supervisorctl update" 