#!/bin/bash

# Colors for terminal output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Stopping Laravel Queue Worker...${NC}"

# Check if PID file exists
if [ -f storage/queue-worker.pid ]; then
    # Get worker PID from file
    WORKER_PID=$(cat storage/queue-worker.pid)
    
    # Check if the process is still running
    if ps -p $WORKER_PID > /dev/null; then
        echo -e "Stopping worker with PID: ${RED}${WORKER_PID}${NC}"
        kill $WORKER_PID
        
        # Wait a moment and check if it's really stopped
        sleep 1
        if ps -p $WORKER_PID > /dev/null; then
            echo -e "${RED}Failed to stop worker gracefully, using force...${NC}"
            kill -9 $WORKER_PID
        fi
        
        echo -e "${GREEN}Queue worker stopped successfully${NC}"
    else
        echo -e "${YELLOW}Queue worker (PID: ${WORKER_PID}) is not running${NC}"
    fi
    
    # Remove PID file
    rm storage/queue-worker.pid
else
    # No PID file, try to find and kill any queue workers
    WORKER_PIDS=$(pgrep -f "php artisan queue:work")
    
    if [ -n "$WORKER_PIDS" ]; then
        echo -e "Found queue workers with PIDs: ${RED}${WORKER_PIDS}${NC}"
        
        for PID in $WORKER_PIDS; do
            echo -e "Stopping worker with PID: ${RED}${PID}${NC}"
            kill $PID
            
            # Wait a moment and check if it's really stopped
            sleep 1
            if ps -p $PID > /dev/null; then
                echo -e "${RED}Failed to stop worker gracefully, using force...${NC}"
                kill -9 $PID
            fi
        done
        
        echo -e "${GREEN}All queue workers stopped successfully${NC}"
    else
        echo -e "${YELLOW}No queue workers found${NC}"
    fi
fi

# Make the file executable
chmod +x stop-queue-worker.sh 