#!/bin/bash

# Set up logs
LOG_FILE="../logs/puppeteer-setup.log"
mkdir -p "../logs"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "==== PUPPETEER SETUP START $(date) ===="
echo "User: $(whoami)"
echo "Working dir: $(pwd)"

# Ensure node and npm are installed
echo "[STEP] Check Node.js installation..."
if ! command -v node &> /dev/null; then
    echo "[ERROR] Node.js not found. Installing..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
else
    echo "[INFO] Node.js $(node -v) is installed"
fi

echo "[STEP] Check NPM installation..."
if ! command -v npm &> /dev/null; then
    echo "[ERROR] NPM not found. Installing..."
    sudo apt-get install -y npm
else
    echo "[INFO] NPM $(npm -v) is installed"
fi

# Install Puppeteer dependencies
echo "[STEP] Installing Puppeteer dependencies..."
sudo apt-get update -y

# Install Chromium and dependencies
echo "[STEP] Installing Chrome dependencies..."
sudo apt-get install -y \
    dconf-service \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgcc-s1 \
    libgdk-pixbuf2.0-0 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    ca-certificates \
    fonts-liberation \
    libnss3 \
    lsb-release \
    xdg-utils \
    wget \
    libgbm1

# Verify Puppeteer installation
echo "[STEP] Verify Puppeteer in package.json..."
if grep -q '"puppeteer"' "../../package.json"; then
    echo "[INFO] Puppeteer is already in package.json"
else
    echo "[WARNING] Puppeteer not found in package.json. Make sure to add it."
fi

# If needed, install puppeteer via npm
echo "[STEP] Verifying Puppeteer installation..."
if [ ! -d "../../node_modules/puppeteer" ]; then
    echo "[INFO] Puppeteer not found in node_modules. It will be installed during the main deployment."
fi

# Create node directory if it doesn't exist
echo "[STEP] Setting up node script directory..."
if [ ! -d "../../node" ]; then
    mkdir -p "../../node"
    echo "[INFO] Created node directory"
else
    echo "[INFO] node directory already exists"
fi

# Ensure Puppeteer script has proper permissions
echo "[STEP] Setting permissions for Puppeteer script..."
if [ -f "../../node/subito_scraper.js" ]; then
    chmod +x "../../node/subito_scraper.js"
    echo "[INFO] Set execute permissions for subito_scraper.js"
else
    echo "[WARNING] subito_scraper.js not found. Will be created during deployment."
fi

echo "==== PUPPETEER SETUP COMPLETED $(date) ====" 