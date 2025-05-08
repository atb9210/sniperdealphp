#!/bin/bash

echo "SnipeDeal - Supervisor Installer"
echo "================================"
echo ""

# Funzione per rilevare il sistema operativo
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if [ -f /etc/debian_version ]; then
            echo "debian"
        elif [ -f /etc/redhat-release ]; then
            echo "redhat"
        else
            echo "linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "unknown"
    fi
}

# Installa supervisor in base al sistema operativo
install_supervisor() {
    OS=$(detect_os)
    echo "Detected OS: $OS"
    
    case $OS in
        debian)
            echo "Installing Supervisor via apt..."
            sudo apt-get update
            sudo apt-get install -y supervisor
            ;;
        redhat)
            echo "Installing Supervisor via yum..."
            sudo yum install -y epel-release
            sudo yum install -y supervisor
            ;;
        macos)
            echo "Installing Supervisor via Homebrew..."
            if ! command -v brew &> /dev/null; then
                echo "Homebrew not found. Please install Homebrew first: https://brew.sh/"
                exit 1
            fi
            brew install supervisor
            ;;
        *)
            echo "Unsupported operating system. Please install Supervisor manually."
            exit 1
            ;;
    esac
}

# Determina il percorso assoluto del progetto
get_project_path() {
    echo $(cd "$(dirname "$0")" && pwd)
}

# Configura i file di supervisor
configure_supervisor() {
    OS=$(detect_os)
    PROJECT_PATH=$(get_project_path)
    USER=$(whoami)
    
    echo "Project path: $PROJECT_PATH"
    echo "Current user: $USER"
    
    # Sostituisci i percorsi nei file di configurazione
    sed -i.bak "s|/Users/atb/Documents/SnipeDealPhp|$PROJECT_PATH|g" $PROJECT_PATH/supervisor/*.conf
    sed -i.bak "s|user=atb|user=$USER|g" $PROJECT_PATH/supervisor/*.conf
    
    # Copia i file di configurazione nella posizione corretta
    case $OS in
        debian|redhat|linux)
            echo "Copying configuration files to /etc/supervisor/conf.d/"
            sudo mkdir -p /etc/supervisor/conf.d/
            sudo cp $PROJECT_PATH/supervisor/*.conf /etc/supervisor/conf.d/
            ;;
        macos)
            echo "Copying configuration files to /usr/local/etc/supervisor.d/"
            sudo mkdir -p /usr/local/etc/supervisor.d/
            sudo cp $PROJECT_PATH/supervisor/*.conf /usr/local/etc/supervisor.d/
            ;;
    esac
}

# Riavvia Supervisor
restart_supervisor() {
    OS=$(detect_os)
    
    case $OS in
        debian|redhat|linux)
            echo "Restarting Supervisor service..."
            sudo supervisorctl reread
            sudo supervisorctl update
            sudo supervisorctl status
            ;;
        macos)
            echo "Starting Supervisor..."
            brew services restart supervisor
            sleep 2
            supervisorctl status
            ;;
    esac
}

# Main script
echo "Step 1: Installing Supervisor..."
install_supervisor

echo ""
echo "Step 2: Configuring Supervisor for SnipeDeal..."
configure_supervisor

echo ""
echo "Step 3: Starting Supervisor services..."
restart_supervisor

echo ""
echo "Installation completed!"
echo "You can monitor the processes with: supervisorctl status"
echo "Logs are available in:"
echo "  - Scheduler: ${PROJECT_PATH}/storage/logs/scheduler.log"
echo "  - Worker: ${PROJECT_PATH}/storage/logs/worker.log" 