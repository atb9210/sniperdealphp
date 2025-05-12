<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ProxyManager;
use Illuminate\Http\Client\PendingRequest;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\File;

class SubitoScraper
{
    protected $baseUrl = 'https://www.subito.it/annunci-italia/vendita/usato/?q=';
    protected $apiUrl = 'https://www.subito.it/api/search';
    protected $proxyManager;
    protected $useProxy = false;
    protected $currentProxy = null;
    protected $proxyIpAddress = null;
    protected $localIpAddress = null;
    protected $nodeScriptPath;
    protected $tempDir;

    public function __construct(ProxyManager $proxyManager = null)
    {
        $this->proxyManager = $proxyManager ?? new ProxyManager();
        $this->localIpAddress = $this->proxyManager->getLocalIpAddress();
        $this->nodeScriptPath = base_path('node/subito_scraper.js');
        $this->tempDir = storage_path('app/temp');
        
        // Ensure temp directory exists
        if (!File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
    }

    public function scrape($keyword, $qso = false, $pages = 3, $useProxy = false)
    {
        $this->useProxy = $useProxy;
        
        // Prepara il proxy se richiesto
        if ($this->useProxy) {
            Log::info("Richiesto utilizzo proxy per keyword: {$keyword}");
            $this->currentProxy = $this->proxyManager->findWorkingProxy();
            
            if (!$this->currentProxy) {
                Log::warning("Proxy usage requested but no working proxy found. Continuing without proxy.");
                $this->useProxy = false;
            } else {
                // Test del proxy scelto per ottenere l'IP
                Log::info("Proxy trovato, eseguo test: {$this->currentProxy}");
                
                // Ottiene informazioni dettagliate sull'IP del proxy
                $ipInfo = $this->proxyManager->getDetailedIpInfo($this->currentProxy);
                if ($ipInfo['success']) {
                    $this->proxyIpAddress = $ipInfo['data']['query'] ?? null;
                    $proxyIsp = $ipInfo['data']['isp'] ?? 'sconosciuto';
                    $proxyCountry = $ipInfo['data']['country'] ?? 'sconosciuto';
                    Log::info("Proxy IP: {$this->proxyIpAddress}, ISP: {$proxyIsp}, Paese: {$proxyCountry}");
                } else {
                    // Fallback al test standard
                    $proxyTest = $this->proxyManager->testProxy($this->currentProxy);
                    $this->proxyIpAddress = $proxyTest['ip_address'] ?? null;
                    Log::info("Test proxy standard: IP: {$this->proxyIpAddress}");
                }
                
                // Verifica che l'IP ottenuto sia differente dall'IP locale
                if ($this->proxyIpAddress && $this->localIpAddress && 
                    $this->proxyIpAddress === $this->localIpAddress) {
                    Log::warning("Attenzione: l'IP del proxy ({$this->proxyIpAddress}) coincide con l'IP locale. Il proxy potrebbe non funzionare correttamente.");
                }
            }
        }
        
        Log::info("Starting scraping using Puppeteer for keyword: " . $keyword . ($qso ? ' (qso)' : '') . ", pagine: $pages" . 
                 ($this->useProxy ? ", using proxy: " . $this->currentProxy : ", without proxy"));

        try {
            return $this->scrapeViaPuppeteer($keyword, $qso, $pages);
        } catch (\Exception $e) {
            Log::error("Error scraping with Puppeteer: " . $e->getMessage());
            Log::warning("Falling back to HTML scraping method...");
            
            // Fallback al vecchio metodo in caso di errore
            $allAds = [];
            for ($page = 1; $page <= $pages; $page++) {
                Log::info("Scraping pagina $page di $pages");
                $ads = $this->scrapeViaHtml($keyword, $qso, $page);
                Log::info("Pagina $page: trovati " . count($ads) . " annunci");
                if (!empty($ads)) {
                    $allAds = array_merge($allAds, $ads);
                    Log::info("Totale annunci dopo pagina $page: " . count($allAds));
                }
            }
            Log::info("Scraping completato. Totale annunci trovati: " . count($allAds));
            return $allAds;
        }
    }

    /**
     * Scrape Subito.it using Puppeteer with anti-bot techniques
     */
    protected function scrapeViaPuppeteer($keyword, $qso = false, $pages = 3)
    {
        Log::info("Executing Puppeteer scraper script...");
        
        // Prepare output file path
        $outputFile = $this->tempDir . '/subito_results_' . time() . '.json';
        
        // Build command arguments
        $args = [
            'node',
            $this->nodeScriptPath,
            '--keyword=' . escapeshellarg($keyword),
            '--qso=' . ($qso ? 'true' : 'false'),
            '--pages=' . $pages,
            '--output=' . $outputFile
        ];
        
        // Add proxy if enabled
        if ($this->useProxy && $this->currentProxy) {
            $args[] = '--proxy=' . escapeshellarg($this->currentProxy);
        }
        
        // Create and configure the process
        $process = new Process($args);
        $process->setTimeout(300); // 5 minutes timeout
        
        try {
            Log::info("Starting Puppeteer process: " . implode(' ', $args));
            $process->run();
            
            // Check if process was successful
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            // Get process output
            $output = $process->getOutput();
            Log::info("Puppeteer script output: " . $output);
            
            // Read results from output file
            if (File::exists($outputFile)) {
                $jsonData = File::get($outputFile);
                $results = json_decode($jsonData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Error decoding JSON results: " . json_last_error_msg());
                }
                
                Log::info("Successfully parsed " . count($results) . " results from Puppeteer");
                
                // Clean up the output file
                File::delete($outputFile);
                
                return $results;
            } else {
                throw new \Exception("Output file not found: {$outputFile}");
            }
        } catch (\Exception $e) {
            Log::error("Error running Puppeteer script: " . $e->getMessage());
            
            // Check for debugging files
            $debugHtmlPath = $this->tempDir . '/subito_page.html';
            if (File::exists($debugHtmlPath)) {
                Log::info("Debug HTML file available at: {$debugHtmlPath}");
            }
            
            $captchaPath = $this->tempDir . '/captcha.png';
            if (File::exists($captchaPath)) {
                Log::error("CAPTCHA detected! Screenshot saved at: {$captchaPath}");
            }
            
            throw $e;
        }
    }

    // Keeping the existing scrapeViaHtml method as a fallback
    protected function scrapeViaHtml($keyword, $qso = false, $page = 1)
    {
        $url = $this->baseUrl . urlencode($keyword);
        if ($qso) {
            $url .= '&qso=true';
        }
        if ($page > 1) {
            $url .= '&page=' . $page;
        }
        Log::info("Scraping URL: " . $url);
        
        try {
            $request = $this->getHttpClient();
            $response = $request->get($url);

            if ($response->successful()) {
                $html = $response->body();
                Storage::put('debug.html', $html);
                Log::info("HTML saved to storage/debug.html");

                $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
                $ads = [];
                $cards = $crawler->filter('div.item-card--small');
                Log::info('Trovate ' . $cards->count() . ' card annuncio');
                foreach ($cards as $cardNode) {
                    $card = new \Symfony\Component\DomCrawler\Crawler($cardNode);
                    $title = $card->filter('h2')->count() ? $card->filter('h2')->text() : null;
                    $priceRaw = $card->filter('p.SmallCard-module_price__yERv7')->count() ? $card->filter('p.SmallCard-module_price__yERv7')->text() : null;
                    $location = $card->filter('span.index-module_town__2H3jy')->count() ? $card->filter('span.index-module_town__2H3jy')->text() : null;
                    $province = $card->filter('span.city')->count() ? $card->filter('span.city')->text() : null;
                    $date = $card->filter('span.index-module_date__Fmf-4')->count() ? $card->filter('span.index-module_date__Fmf-4')->text() : null;
                    $link = $card->filter('a.SmallCard-module_link__hOkzY')->count() ? $card->filter('a.SmallCard-module_link__hOkzY')->attr('href') : null;
                    $img = $card->filter('img.CardImage-module_photo__WMsiO')->count() ? $card->filter('img.CardImage-module_photo__WMsiO')->attr('src') : null;

                    // Estrazione prezzo, stato e spedizione
                    $prezzo = $priceRaw;
                    $stato = 'Disponibile';
                    $spedizione = false;
                    if ($priceRaw) {
                        // Cerca "Venduto" o "Spedizione disponibile"
                        if (stripos($priceRaw, 'venduto') !== false) {
                            $stato = 'Venduto';
                        }
                        if (stripos($priceRaw, 'spedizione disponibile') !== false) {
                            $spedizione = true;
                        }
                        // Rimuovi testo extra dal prezzo
                        $prezzo = trim(preg_replace('/[^\d,.€]+.*/u', '', $priceRaw));
                    }

                    $ads[] = [
                        'title' => $title,
                        'price' => $prezzo,
                        'location' => trim(($location ?? '') . ' ' . ($province ?? '')),
                        'date' => $date,
                        'link' => $link,
                        'image' => $img,
                        'stato' => $stato,
                        'spedizione' => $spedizione
                    ];
                }
                if (count($ads) === 0) {
                    Log::error('Nessun annuncio trovato, ma HTML contiene card. Controllare selettori.');
                }
                return $ads;
            } else {
                Log::error("Failed to get response from Subito.it: " . $response->status());
                return [];
            }
        } catch (\Exception $e) {
            Log::error("Error scraping Subito.it: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Prepara un client HTTP con le impostazioni corrette e i proxy se necessario
     */
    protected function getHttpClient(): PendingRequest
    {
        $client = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ]);
        
        // Aggiungi proxy se richiesto
        if ($this->useProxy && $this->currentProxy) {
            // Laravel's HTTP client non supporta direttamente proxy auth nel formato URL,
            // quindi dobbiamo usare le curl options
            Log::info("Configurazione proxy per HTTP client: {$this->currentProxy}");
            
            try {
                // Verifica che il proxy abbia il corretto formato
                if (strpos($this->currentProxy, '@') !== false) {
                    // Dividi l'URL del proxy nelle sue componenti
                    $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
                    if (preg_match($pattern, $this->currentProxy, $matches)) {
                        $schema = 'http://';
                        $username = $matches[1];
                        $password = $matches[2];
                        $host = $matches[3];
                        $port = $matches[4];
                        
                        Log::info("Proxy analizzato - Host: {$host}, Port: {$port}, User: {$username}");
                        
                        // Opzione 1: Usa solo curl options (più affidabile)
                        $client->withOptions([
                            'curl' => [
                                CURLOPT_PROXY => "{$host}:{$port}",
                                CURLOPT_PROXYUSERPWD => "{$username}:{$password}",
                                CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
                                CURLOPT_FOLLOWLOCATION => 1,
                                CURLOPT_TIMEOUT => 30
                            ]
                        ]);
                        
                        Log::info("Proxy configurato con curl options");
                    } else {
                        Log::warning("Formato proxy non valido: {$this->currentProxy}");
                        $client->withOptions(['proxy' => $this->currentProxy]);
                    }
                } else {
                    // Proxy semplice senza autenticazione
                    Log::info("Configurazione proxy semplice: {$this->currentProxy}");
                    $client->withOptions(['proxy' => $this->currentProxy]);
                }
            } catch (\Exception $e) {
                Log::error("Errore configurazione proxy: " . $e->getMessage());
                // Prosegui senza proxy in caso di errore
            }
        }
        
        return $client;
    }

    /**
     * Restituisce informazioni sul proxy attualmente utilizzato
     */
    public function getProxyInfo(): array
    {
        // Ottieni informazioni dettagliate per l'IP del proxy
        $proxyIpDetails = [];
        if ($this->useProxy && $this->currentProxy) {
            $ipInfo = $this->proxyManager->getDetailedIpInfo($this->currentProxy);
            if ($ipInfo['success']) {
                $proxyIpDetails = $ipInfo['data'];
            }
        }
        
        // Ottieni informazioni dettagliate per l'IP locale
        $localIpDetails = [];
        $ipInfo = $this->proxyManager->getDetailedIpInfo();
        if ($ipInfo['success']) {
            $localIpDetails = $ipInfo['data'];
            // Aggiorna l'IP locale con quello ottenuto dal servizio
            $this->localIpAddress = $localIpDetails['query'] ?? $this->localIpAddress;
        }
        
        return [
            'using_proxy' => $this->useProxy,
            'proxy' => $this->currentProxy,
            'proxy_ip' => $this->proxyIpAddress,
            'proxy_details' => $proxyIpDetails,
            'local_ip' => $this->localIpAddress,
            'local_details' => $localIpDetails,
            'proxy_working' => ($this->proxyIpAddress && $this->localIpAddress && $this->proxyIpAddress !== $this->localIpAddress)
        ];
    }
} 