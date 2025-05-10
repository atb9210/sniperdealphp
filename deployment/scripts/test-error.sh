#!/bin/bash

# Questo script genererà un errore di deployment
echo "Iniziando test di errore..."

# Forza un errore
exit 1

# Questo non verrà mai eseguito
echo "Questo non dovrebbe apparire" 