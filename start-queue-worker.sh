#!/bin/bash

# Colors for terminal output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting Laravel Queue Worker in the background...${NC}"

# Check if a worker is already running
if pgrep -f "php artisan queue:work" > /dev/null; then
    echo -e "${YELLOW}A queue worker is already running.${NC}"
    echo -e "To stop it, run: ${RED}./stop-queue-worker.sh${NC}"
    exit 0
fi

# Start the queue worker and send it to the background
# The worker will process all jobs from the queue continuously
nohup php artisan queue:work --tries=3 --sleep=3 > storage/logs/queue-worker.log 2>&1 &

# Get and display worker PID
WORKER_PID=$!
echo -e "${GREEN}Queue worker started with PID: ${WORKER_PID}${NC}"
echo -e "Log is being written to: storage/logs/queue-worker.log"
echo -e "To stop the worker, run: ${YELLOW}./stop-queue-worker.sh${NC}"

# Create a PID file to track the worker
echo $WORKER_PID > storage/queue-worker.pid

# Make the file executable
chmod +x start-queue-worker.sh 