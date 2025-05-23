<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ProxyManager;
use Illuminate\Http\Client\PendingRequest;

class SubitoScraper
{
    protected $baseUrl = 'https://www.subito.it/annunci-italia/vendita/usato/?q=';
    protected $apiUrl = 'https://www.subito.it/api/search';
    protected $proxyManager;
    protected $useProxy = false;
    protected $currentProxy = null;
    protected $proxyIpAddress = null;
    protected $localIpAddress = null;

    public function __construct(ProxyManager $proxyManager = null)
    {
        $this->proxyManager = $proxyManager ?? new ProxyManager();
        $this->localIpAddress = $this->proxyManager->getLocalIpAddress();
    }

    public function scrape($keyword, $qso = false, $pages = 3, $useProxy = false, $forcedProxy = null)
    {
        $this->useProxy = $useProxy;
        if ($forcedProxy) {
            $this->currentProxy = $forcedProxy;
            $this->useProxy = true;
            Log::info("Forzato utilizzo proxy: {$forcedProxy}");
        } else if ($this->useProxy) {
            Log::info("Richiesto utilizzo proxy per keyword: {$keyword}");
            $this->currentProxy = $this->proxyManager->getRoundRobinProxy();
            if (!$this->currentProxy) {
                Log::warning("Proxy usage requested but no proxies found. Continuing without proxy.");
                $this->useProxy = false;
            } else {
                // Test del proxy scelto per ottenere l'IP
                Log::info("Proxy selezionato (round-robin): {$this->currentProxy}");
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
        Log::info("Starting scraping for keyword: " . $keyword . ($qso ? ' (qso)' : '') . ", pagine: $pages" . 
                 ($this->useProxy ? ", using proxy: " . $this->currentProxy : ", without proxy"));
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
        
        // Se non abbiamo trovato annunci e stavamo usando un proxy, riprova senza proxy
        if (empty($allAds) && $this->useProxy) {
            Log::warning("Nessun annuncio trovato con proxy. Riprovo senza proxy.");
            $this->useProxy = false;
            $this->currentProxy = null;
            
            Log::info("Restarting scraping without proxy for keyword: " . $keyword . ($qso ? ' (qso)' : '') . ", pagine: $pages");
            for ($page = 1; $page <= $pages; $page++) {
                Log::info("Scraping pagina $page di $pages (senza proxy)");
                $ads = $this->scrapeViaHtml($keyword, $qso, $page);
                Log::info("Pagina $page: trovati " . count($ads) . " annunci (senza proxy)");
                if (!empty($ads)) {
                    $allAds = array_merge($allAds, $ads);
                    Log::info("Totale annunci dopo pagina $page: " . count($allAds) . " (senza proxy)");
                }
            }
        }
        
        Log::info("Scraping completato. Totale annunci trovati: " . count($allAds));
        return $allAds;
    }

    protected function scrapeViaApi($keyword)
    {
        try {
            Log::info("Attempting to scrape via API for keyword: " . $keyword);
            
            $request = $this->getHttpClient();
            
            $response = $request->get($this->apiUrl, [
                'q' => $keyword,
                'limit' => 20,
                'offset' => 0
            ]);

            Log::info("API Response status: " . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data === null) {
                    Log::warning("API returned null data");
                    return [];
                }
                
                Log::info("API Response data structure: " . json_encode(array_keys($data)));
                return $this->processApiResponse($data);
            }

            Log::warning("API request failed with status: " . $response->status());
            return [];
        } catch (\Exception $e) {
            Log::error("API scraping error: " . $e->getMessage());
            return [];
        }
    }

    protected function scrapeViaHtml($keyword, $qso = false, $page = 1)
    {
        $url = $this->baseUrl . urlencode($keyword);
        if ($qso) {
            $url .= '&qso=true';
        }
        if ($page > 1) {
            $url .= '&o=' . $page;
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
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Ch-Ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Connection' => 'keep-alive',
            'DNT' => '1'
        ]);

        if ($this->useProxy && $this->currentProxy) {
            try {
            Log::info("Configurazione proxy per HTTP client: {$this->currentProxy}");
            
                // Estrai i componenti del proxy
                    $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
                    if (preg_match($pattern, $this->currentProxy, $matches)) {
                    $username = $matches[1] ?? null;
                    $password = $matches[2] ?? null;
                        $host = $matches[3];
                        $port = $matches[4];
                        
                    $client = $client->withOptions([
                        'proxy' => [
                            'http' => "http://{$host}:{$port}",
                            'https' => "http://{$host}:{$port}"
                        ],
                        'verify' => false,
                        'timeout' => 30,
                        'connect_timeout' => 30
                    ]);

                    if ($username && $password) {
                        $client = $client->withOptions([
                            'proxy' => [
                                'http' => "http://{$username}:{$password}@{$host}:{$port}",
                                'https' => "http://{$username}:{$password}@{$host}:{$port}"
                            ]
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Errore configurazione proxy: " . $e->getMessage());
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

    protected function extractAdsFromHtml($html)
    {
        $ads = [];
        
        try {
            // Try to find JSON data in the HTML using different patterns
            $patterns = [
                '/"props":\s*({.+?})\s*,\s*"page"/',
                '/"items":\s*(\[.+?\])\s*,\s*"rankedList"/',
                '/"decoratedItems":\s*(\[.+?\])\s*,\s*"rankedList"/'
            ];

            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/s', $html, $matches)) {
                    Log::info("Found JSON data using pattern: " . $pattern);
                    
                    $jsonData = json_decode($matches[1], true);
                    if ($jsonData) {
                        // Extract items based on the pattern matched
                        $items = $this->extractItemsFromJson($jsonData);
                        if (!empty($items)) {
                            foreach ($items as $item) {
                                if (isset($item['item'])) {
                                    $ad = $item['item'];
                                    $ads[] = [
                                        'title' => $ad['subject'] ?? null,
                                        'price' => $this->extractPrice($ad),
                                        'description' => $ad['body'] ?? null,
                                        'url' => $ad['urls']['default'] ?? null,
                                        'location' => $this->extractLocation($ad),
                                        'images' => $this->extractImages($ad),
                                        'condition' => $this->extractCondition($ad),
                                        'date' => $ad['date'] ?? null
                                    ];
                                }
                            }
                            break; // Exit loop if we found and processed items
                        }
                    }
                }
            }
            
            Log::info("Successfully extracted " . count($ads) . " ads from JSON");
            return $ads;
            
        } catch (\Exception $e) {
            Log::error("Error extracting ads from HTML: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    protected function extractItemsFromJson($jsonData)
    {
        // Try different paths to find the items
        if (isset($jsonData['pageProps']['dehydratedState']['queries'][0]['state']['data']['decoratedItems'])) {
            return $jsonData['pageProps']['dehydratedState']['queries'][0]['state']['data']['decoratedItems'];
        }
        
        if (isset($jsonData['pageProps']['initialData']['decoratedItems'])) {
            return $jsonData['pageProps']['initialData']['decoratedItems'];
        }
        
        if (isset($jsonData['decoratedItems'])) {
            return $jsonData['decoratedItems'];
        }

        if (is_array($jsonData)) {
            return $jsonData;
        }

        return [];
    }

    protected function extractPrice($ad)
    {
        if (isset($ad['features']['/price']['values'][0]['value'])) {
            return $ad['features']['/price']['values'][0]['value'];
        }
        return null;
    }

    protected function extractLocation($ad)
    {
        if (isset($ad['geo'])) {
            $location = [];
            if (isset($ad['geo']['region']['value'])) {
                $location['region'] = $ad['geo']['region']['value'];
            }
            if (isset($ad['geo']['city']['value'])) {
                $location['city'] = $ad['geo']['city']['value'];
            }
            if (isset($ad['geo']['town']['value'])) {
                $location['town'] = $ad['geo']['town']['value'];
            }
            return $location;
        }
        return null;
    }

    protected function extractImages($ad)
    {
        if (isset($ad['images'])) {
            return array_map(function($image) {
                return $image['cdnBaseUrl'] ?? null;
            }, $ad['images']);
        }
        return [];
    }

    protected function extractCondition($ad)
    {
        if (isset($ad['features']['/item_condition']['values'][0]['value'])) {
            return $ad['features']['/item_condition']['values'][0]['value'];
        }
        return null;
    }

    protected function processApiResponse($data)
    {
        $ads = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $ads[] = [
                    'title' => $item['subject'] ?? null,
                    'description' => $item['body'] ?? null,
                    'price' => $item['price'] ?? null,
                    'condition' => $item['condition'] ?? null,
                    'location' => $item['location'] ?? null,
                    'date' => $item['date'] ?? null,
                    'images' => $item['images'] ?? [],
                    'link' => $item['url'] ?? null
                ];
            }
        }
        return $ads;
    }

    protected function processItems($items)
    {
        $ads = [];
        foreach ($items as $item) {
            try {
                if (isset($item['item']) && $item['item']['kind'] === 'AdItem') {
                    $ad = $item['item'];
                    
                    // Extract price
                    $price = null;
                    if (isset($ad['features']['/price']['values'][0]['value'])) {
                        $price = $ad['features']['/price']['values'][0]['value'];
                    }

                    // Extract condition
                    $condition = null;
                    if (isset($ad['features']['/item_condition']['values'][0]['value'])) {
                        $condition = $ad['features']['/item_condition']['values'][0]['value'];
                    }

                    // Extract location
                    $location = [];
                    if (isset($ad['geo'])) {
                        if (isset($ad['geo']['region']['value'])) {
                            $location[] = $ad['geo']['region']['value'];
                        }
                        if (isset($ad['geo']['city']['value'])) {
                            $location[] = $ad['geo']['city']['value'];
                        }
                        if (isset($ad['geo']['town']['value'])) {
                            $location[] = $ad['geo']['town']['value'];
                        }
                    }

                    // Extract images
                    $images = [];
                    if (isset($ad['images'])) {
                        foreach ($ad['images'] as $image) {
                            if (isset($image['cdnBaseUrl'])) {
                                $images[] = $image['cdnBaseUrl'];
                            }
                        }
                    }

                    $ads[] = [
                        'title' => $ad['subject'] ?? null,
                        'description' => $ad['body'] ?? null,
                        'price' => $price,
                        'condition' => $condition,
                        'location' => implode(', ', $location),
                        'date' => $ad['date'] ?? null,
                        'images' => $images,
                        'link' => $ad['urls']['default'] ?? null
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Error processing item: " . $e->getMessage());
                continue;
            }
        }

        Log::info("Processed " . count($ads) . " ads successfully");
        return $ads;
    }
} 