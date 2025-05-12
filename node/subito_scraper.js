#!/usr/bin/env node

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2);
const params = {};

// Parse arguments (format: --key=value)
args.forEach(arg => {
  if (arg.startsWith('--')) {
    const [key, value] = arg.slice(2).split('=');
    params[key] = value;
  }
});

// Required parameters
const keyword = params.keyword || '';
const qso = params.qso === 'true';
const pages = parseInt(params.pages || '3', 10);
const useProxy = params.proxy || null;
const outputFile = params.output || path.join(__dirname, '../storage/app/temp/subito_results.json');

// Timeout settings - increase timeouts when using proxy
const timeouts = {
  // Timeout for page navigation
  navigation: useProxy ? 120000 : 60000, // 120 sec with proxy, 60 sec without
  // Timeout for waiting for selectors
  selector: useProxy ? 60000 : 30000,    // 60 sec with proxy, 30 sec without
  // Timeout between actions to simulate human behavior
  humanAction: {
    min: 500,
    max: useProxy ? 2000 : 1000          // Longer delays with proxy
  },
  // Timeout between page requests
  pageRequest: {
    min: useProxy ? 5000 : 2000,
    max: useProxy ? 8000 : 5000
  }
};

// Ensure output directory exists
const outputDir = path.dirname(outputFile);
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

// Log parameters for debugging
console.log('Starting Puppeteer scraper with parameters:');
console.log(JSON.stringify({
  keyword,
  qso,
  pages,
  useProxy: useProxy ? '[PROXY CONFIGURED]' : false,
  outputFile
}, null, 2));

// Helper function to log with timestamps
function log(message) {
  console.log(`[${new Date().toISOString()}] ${message}`);
}

// Function to simulate human-like mouse movement
async function humanMouseMovement(page) {
  const viewportWidth = page.viewport().width;
  const viewportHeight = page.viewport().height;
  
  // Generate random number of movements (between 5 and 15)
  const movementsCount = Math.floor(Math.random() * 10) + 5;
  
  for (let i = 0; i < movementsCount; i++) {
    const x = Math.floor(Math.random() * viewportWidth);
    const y = Math.floor(Math.random() * viewportHeight);
    
    // Move mouse with random duration
    await page.mouse.move(x, y, { steps: Math.floor(Math.random() * 10) + 5 });
    
    // Random pause between movements
    await page.waitForTimeout(Math.random() * 500 + 100);
  }
}

// Function to add random delays for realistic typing
async function typeWithDelays(page, selector, text) {
  await page.focus(selector);
  for (let i = 0; i < text.length; i++) {
    await page.keyboard.type(text[i]);
    await page.waitForTimeout(Math.random() * 100 + 30); // Random delay between keypresses
  }
}

// Function to extract ad details
async function extractAdDetails(card) {
  return await card.evaluate(el => {
    // Helper function to safely query selector text
    const getTextContent = (selector) => {
      const element = el.querySelector(selector);
      return element ? element.textContent.trim() : null;
    };
    
    // Helper function to safely get attribute
    const getAttribute = (selector, attr) => {
      const element = el.querySelector(selector);
      return element ? element.getAttribute(attr) : null;
    };
    
    // Extract data
    const title = getTextContent('h2');
    const priceRaw = getTextContent('p.SmallCard-module_price__yERv7');
    const location = getTextContent('span.index-module_town__2H3jy');
    const province = getTextContent('span.city');
    const date = getTextContent('span.index-module_date__Fmf-4');
    const link = getAttribute('a.SmallCard-module_link__hOkzY', 'href');
    const img = getAttribute('img.CardImage-module_photo__WMsiO', 'src');
    
    // Process information
    const prezzo = priceRaw;
    let stato = 'Disponibile';
    let spedizione = false;
    
    if (priceRaw) {
      if (priceRaw.toLowerCase().includes('venduto')) {
        stato = 'Venduto';
      }
      if (priceRaw.toLowerCase().includes('spedizione disponibile')) {
        spedizione = true;
      }
    }
    
    return {
      title,
      price: prezzo,
      location: [location, province].filter(Boolean).join(' ').trim(),
      date,
      link,
      image: img,
      stato,
      spedizione
    };
  });
}

// Main scraping function
async function scrapePage(page, pageNum) {
  log(`Scraping page ${pageNum}...`);
  
  // Wait for ad cards to load with increased timeout
  await page.waitForSelector('div.item-card--small', { timeout: timeouts.selector });
  
  // Add a random pause to simulate human behavior - adjust based on proxy use
  const pauseDelay = Math.random() * (timeouts.humanAction.max - timeouts.humanAction.min) + timeouts.humanAction.min;
  await page.waitForTimeout(pauseDelay);
  
  // Perform some random scrolling to mimic human behavior
  await page.evaluate(() => {
    const scrollAmount = Math.floor(Math.random() * 800) + 200;
    window.scrollBy(0, scrollAmount);
  });
  
  // Another pause after scrolling
  await page.waitForTimeout(Math.random() * (timeouts.humanAction.max/2) + timeouts.humanAction.min);
  
  // Simulate human mouse movement
  await humanMouseMovement(page);
  
  // Extract ads
  const cards = await page.$$('div.item-card--small');
  log(`Found ${cards.length} ads on page ${pageNum}`);
  
  const results = [];
  for (const card of cards) {
    try {
      const adDetails = await extractAdDetails(card);
      results.push(adDetails);
    } catch (error) {
      log(`Error extracting ad details: ${error.message}`);
    }
  }
  
  return results;
}

// Main function
async function main() {
  let browser;
  try {
    log('Launching browser...');
    
    // Setup browser launch options
    const launchOptions = {
      headless: 'new',  // Use the new headless mode
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--disable-gpu',
        '--window-size=1920,1080',
        '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
      ]
    };
    
    // Add proxy if specified
    if (useProxy) {
      launchOptions.args.push(`--proxy-server=${useProxy}`);
      log(`Using proxy: ${useProxy}`);
    }
    
    browser = await puppeteer.launch(launchOptions);
    
    // Create a new page
    const page = await browser.newPage();
    
    // Set viewport to simulate a real browser
    await page.setViewport({ width: 1920, height: 1080 });
    
    // Setup anti-detection measures
    await page.evaluateOnNewDocument(() => {
      // Overwrite the 'plugins' property to use the regular Chrome plugins
      Object.defineProperty(navigator, 'plugins', {
        get: () => [1, 2, 3, 4, 5],
        enumerable: true,
        configurable: true
      });
      
      // Overwrite the 'languages' property
      Object.defineProperty(navigator, 'languages', {
        get: () => ['it-IT', 'it', 'en-US', 'en'],
        enumerable: true,
        configurable: true
      });
      
      // Overwrite the WebDriver property
      Object.defineProperty(navigator, 'webdriver', {
        get: () => false,
        enumerable: true,
        configurable: true
      });
      
      // Overwrite the Chrome property
      const originalHasOwnProperty = Object.prototype.hasOwnProperty;
      window.chrome = {
        runtime: {}
      };
      
      // Add permission functions - missing in headless
      if (!('permissions' in navigator)) {
        navigator.permissions = {
          query: async (params) => ({
            state: 'granted',
            onchange: null
          })
        };
      }
    });
    
    // Set extra HTTP headers
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
      'Cache-Control': 'no-cache',
      'Pragma': 'no-cache'
    });
    
    // Build URL for Subito.it
    let url = `https://www.subito.it/annunci-italia/vendita/usato/?q=${encodeURIComponent(keyword)}`;
    if (qso) {
      url += '&qso=true';
    }
    
    // Store for all results across pages
    let allAds = [];
    
    // Scrape the requested number of pages
    for (let pageNum = 1; pageNum <= pages; pageNum++) {
      let pageUrl = url;
      if (pageNum > 1) {
        pageUrl += `&page=${pageNum}`;
      }
      
      log(`Navigating to page ${pageNum}: ${pageUrl}`);
      
      // Navigate to the URL with increased timeout when using proxy
      const response = await page.goto(pageUrl, {
        waitUntil: 'networkidle2',
        timeout: timeouts.navigation
      });
      
      if (!response || !response.ok()) {
        log(`Error loading page ${pageNum}: ${response ? response.status() : 'Unknown error'}`);
        continue;
      }
      
      // Check for CAPTCHA or other challenge pages
      const pageContent = await page.content();
      if (pageContent.includes('captcha') || pageContent.includes('challenge') || pageContent.includes('security check')) {
        log('CAPTCHA or security challenge detected. Taking screenshot...');
        await page.screenshot({ path: path.join(outputDir, 'captcha.png') });
        throw new Error('CAPTCHA or security challenge detected');
      }
      
      // Save page html for debugging
      if (pageNum === 1) {
        fs.writeFileSync(path.join(outputDir, 'subito_page.html'), pageContent);
        log('Saved first page HTML for debugging');
      }
      
      // Scrape this page
      const pageAds = await scrapePage(page, pageNum);
      allAds = [...allAds, ...pageAds];
      
      log(`Total ads after page ${pageNum}: ${allAds.length}`);
      
      // Random delay between page requests - longer when using proxy
      if (pageNum < pages) {
        const delay = Math.random() * (timeouts.pageRequest.max - timeouts.pageRequest.min) + timeouts.pageRequest.min;
        log(`Waiting ${Math.round(delay)}ms before loading next page...`);
        await page.waitForTimeout(delay);
      }
    }
    
    // Write results to output file
    fs.writeFileSync(outputFile, JSON.stringify(allAds, null, 2));
    log(`Successfully scraped ${allAds.length} ads. Results saved to ${outputFile}`);
    
    return { success: true, count: allAds.length, outputFile };
    
  } catch (error) {
    log(`Error during scraping: ${error.message}`);
    log(error.stack);
    
    // Write error to output file
    fs.writeFileSync(outputFile, JSON.stringify({
      success: false,
      error: error.message,
      timestamp: new Date().toISOString()
    }, null, 2));
    
    return { success: false, error: error.message };
  } finally {
    if (browser) {
      log('Closing browser...');
      await browser.close();
    }
  }
}

// Execute the main function
main()
  .then(result => {
    console.log(JSON.stringify(result));
    process.exit(result.success ? 0 : 1);
  })
  .catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
  }); 