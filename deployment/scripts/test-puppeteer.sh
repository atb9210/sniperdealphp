#!/bin/bash

# Set up logs
LOG_FILE="../logs/puppeteer-test.log"
mkdir -p "../logs"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "==== PUPPETEER TEST START $(date) ===="
echo "User: $(whoami)"
echo "Working dir: $(pwd)"

# Crea un file JavaScript temporaneo per il test
TEST_FILE="../../temp_puppeteer_test.js"

cat > $TEST_FILE << 'EOF'
const puppeteer = require('puppeteer');

async function runTest() {
  console.log('Starting Puppeteer test...');
  
  try {
    // Launch browser in headless mode with required args
    const browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    console.log('Browser launched successfully');
    
    // Open a new page
    const page = await browser.newPage();
    console.log('New page created');
    
    // Navigate to a website
    await page.goto('https://example.com');
    console.log('Navigated to example.com');
    
    // Take a screenshot
    const tempScreenshot = './temp_screenshot.png';
    await page.screenshot({ path: tempScreenshot });
    console.log(`Screenshot saved to ${tempScreenshot}`);
    
    // Get page title
    const title = await page.title();
    console.log(`Page title: ${title}`);
    
    // Close browser
    await browser.close();
    console.log('Browser closed successfully');
    
    return { success: true };
  } catch (error) {
    console.error('Test failed with error:', error);
    return { success: false, error: error.message };
  }
}

runTest()
  .then(result => {
    console.log('Test result:', result);
    process.exit(result.success ? 0 : 1);
  })
  .catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
  });
EOF

# Esegui il test
echo "[STEP] Esecuzione test Puppeteer..."
cd ../../
node $TEST_FILE

# Controlla il risultato
TEST_RESULT=$?
if [ $TEST_RESULT -eq 0 ]; then
    echo "[SUCCESS] Il test Puppeteer è stato completato con successo!"
else
    echo "[ERROR] Il test Puppeteer è fallito con codice $TEST_RESULT"
fi

# Pulizia
echo "[STEP] Pulizia file temporanei..."
rm -f $TEST_FILE
rm -f ./temp_screenshot.png

echo "==== PUPPETEER TEST COMPLETED $(date) ===="

# Restituisci il risultato
exit $TEST_RESULT 