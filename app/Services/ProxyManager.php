<?php

namespace App\Services;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProxyManager
{
    /**
     * Get a random proxy from the user's configured proxies
     *
     * @param int|null $userId
     * @return string|null
     */
    public function getRandomProxy(?int $userId = null): ?string
    {
        $settings = $this->getUserSettings($userId);
        if (!$settings || !$settings->hasActiveProxies()) {
            return null;
        }

        $proxies = $settings->active_proxies;
        if (empty($proxies)) {
            return null;
        }

        return $proxies[array_rand($proxies)];
    }

    /**
     * Get a proxy using a round-robin approach
     *
     * @param int|null $userId
     * @return string|null
     */
    public function getRoundRobinProxy(?int $userId = null): ?string
    {
        $settings = $this->getUserSettings($userId);
        if (!$settings) {
            Log::info("getRoundRobinProxy: No settings found for user");
            return null;
        }
        
        if (!$settings->hasActiveProxies()) {
            Log::info("getRoundRobinProxy: User has no active proxies");
            return null;
        }

        $proxies = $settings->active_proxies;
        if (empty($proxies)) {
            Log::info("getRoundRobinProxy: Active proxies array is empty");
            return null;
        }

        Log::info("getRoundRobinProxy: Found " . count($proxies) . " active proxies");
        
        // Usa un file per memorizzare l'indice invece della sessione
        $indexFile = storage_path('app/proxy_index.txt');
        $lastIndex = -1;
        
        if (file_exists($indexFile)) {
            $lastIndex = (int) file_get_contents($indexFile);
            Log::info("getRoundRobinProxy: Last index from file: " . $lastIndex);
        } else {
            Log::info("getRoundRobinProxy: No index file found, starting from -1");
        }
        
        $newIndex = ($lastIndex + 1) % count($proxies);
        Log::info("getRoundRobinProxy: New index: " . $newIndex);
        
        // Salva il nuovo indice nel file
        file_put_contents($indexFile, $newIndex);
        Log::info("getRoundRobinProxy: Stored new index in file: " . $newIndex);
        
        $selectedProxy = $proxies[$newIndex];
        Log::info("getRoundRobinProxy: Selected proxy: " . $selectedProxy);
        
        return $selectedProxy;
    }

    /**
     * Test if a proxy is working by making a request to a test URL
     *
     * @param string $proxy
     * @param string $testUrl
     * @return array
     */
    public function testProxy(string $proxy, string $testUrl = 'https://subito.it'): array
    {
        try {
            // Estrai i componenti del proxy
            $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
            if (preg_match($pattern, $proxy, $matches)) {
                $username = $matches[1] ?? null;
                $password = $matches[2] ?? null;
                $host = $matches[3];
                $port = $matches[4];
                
                $ch = curl_init($testUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_PROXY, "$host:$port");
                if ($username && $password) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$username:$password");
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                }
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                // Sostituisco il vecchio User-Agent con gli header completi dello scraper
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding: gzip, deflate, br',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Sec-Ch-Ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
                    'Sec-Ch-Ua-Mobile: ?0',
                    'Sec-Ch-Ua-Platform: "Windows"',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Sec-Fetch-User: ?1',
                    'Upgrade-Insecure-Requests: 1',
                    'Connection: keep-alive',
                    'DNT: 1'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($response === false) {
                    Log::error("Proxy test fallito: $error");
                    return [
                        'success' => false,
                        'message' => 'Errore di connessione: ' . $error,
                        'http_code' => 0,
                        'ip_address' => null
                    ];
                }
                
                // Ottieni l'IP usando un servizio dedicato per avere la certezza
                $ipInfo = $this->getDetailedIpInfo($proxy);
                $ipAddress = $ipInfo['success'] ? $ipInfo['data']['query'] ?? null : null;
                
                // Se non siamo riusciti a ottenere l'IP dal servizio dedicato, proviamo a estrarlo dalla risposta
                if (!$ipAddress) {
                    $ipAddress = $this->extractIpAddress($response, $proxy);
                }
                
                Log::info("Test proxy completato - HTTP: $httpCode, IP: " . ($ipAddress ?? 'sconosciuto'));
                
                return [
                    'success' => ($httpCode >= 200 && $httpCode < 300),
                    'message' => "Connessione riuscita! Codice HTTP: {$httpCode}",
                    'http_code' => $httpCode,
                    'ip_address' => $ipAddress
                ];
            } else {
                Log::warning("Formato proxy non valido: $proxy");
                return [
                    'success' => false,
                    'message' => 'Formato proxy non valido',
                    'http_code' => 0,
                    'ip_address' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error("Eccezione nel test proxy: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
                'http_code' => 0,
                'ip_address' => null
            ];
        }
    }

    /**
     * Find a working proxy from the user's configured proxies
     *
     * @param int|null $userId
     * @return string|null
     */
    public function findWorkingProxy(?int $userId = null): ?string
    {
        $settings = $this->getUserSettings($userId);
        if (!$settings || !$settings->hasActiveProxies()) {
            return null;
        }

        $proxies = $settings->active_proxies;
        if (empty($proxies)) {
            return null;
        }

        // Shuffle the proxies to test them in random order
        shuffle($proxies);
        
        foreach ($proxies as $proxy) {
            $result = $this->testProxy($proxy);
            if ($result['success']) {
                return $proxy;
            }
            // Small delay between tests to avoid rate limiting
            usleep(200000); // 200ms
        }
        
        return null;
    }
    
    /**
     * Try to extract the IP address from the response
     *
     * @param string $response
     * @param string|null $currentProxy Il proxy da utilizzare per la richiesta
     * @return string|null
     */
    protected function extractIpAddress(string $response, ?string $currentProxy = null): ?string
    {
        // Primo tentativo: usa il servizio ipify per ottenere l'IP attraverso il proxy
        if ($currentProxy) {
            try {
                // Estrai i componenti del proxy
                $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
                if (preg_match($pattern, $currentProxy, $matches)) {
                    $username = $matches[1] ?? null;
                    $password = $matches[2] ?? null;
                    $host = $matches[3];
                    $port = $matches[4];
                    
                    $ch = curl_init('https://api.ipify.org');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_PROXY, "$host:$port");
                    if ($username && $password) {
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$username:$password");
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    }
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    $ip = curl_exec($ch);
                    $error = curl_error($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                        Log::info("Rilevato IP del proxy tramite ipify: $ip (HTTP code: $httpCode)");
                        return $ip;
                    }
                    
                    if ($error) {
                        Log::error("Errore rilevamento IP tramite ipify: $error");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Eccezione nel rilevamento IP tramite ipify: " . $e->getMessage());
            }
        }
        
        // Secondo tentativo: usa un servizio alternativo (ipinfo.io)
        if ($currentProxy) {
            try {
                // Estrai i componenti del proxy
                $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
                if (preg_match($pattern, $currentProxy, $matches)) {
                    $username = $matches[1] ?? null;
                    $password = $matches[2] ?? null;
                    $host = $matches[3];
                    $port = $matches[4];
                    
                    $ch = curl_init('https://ipinfo.io/ip');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_PROXY, "$host:$port");
                    if ($username && $password) {
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$username:$password");
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    }
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    $ip = curl_exec($ch);
                    $error = curl_error($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                        Log::info("Rilevato IP del proxy tramite ipinfo.io: $ip (HTTP code: $httpCode)");
                        return $ip;
                    }
                    
                    if ($error) {
                        Log::error("Errore rilevamento IP tramite ipinfo.io: $error");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Eccezione nel rilevamento IP tramite ipinfo.io: " . $e->getMessage());
            }
        }
        
        // Terzo tentativo: cerca pattern di IP nel corpo della risposta
        if (preg_match('/(?:IP Address|Your IP is|Client IP|IP: )[\s:]*([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $response, $matches)) {
            Log::info("Rilevato IP dal contenuto della risposta: " . $matches[1]);
            return $matches[1];
        }
        
        Log::warning("Impossibile rilevare IP del proxy");
        return null;
    }
    
    /**
     * Get the user's IP address without using a proxy
     *
     * @return string|null
     */
    public function getLocalIpAddress(): ?string
    {
        try {
            $ch = curl_init('https://api.ipify.org');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $ip = curl_exec($ch);
            curl_close($ch);
            
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        } catch (\Exception $e) {
            Log::error('Error getting local IP: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get the user settings for the given user ID or the authenticated user
     *
     * @param int|null $userId
     * @return UserSetting|null
     */
    protected function getUserSettings(?int $userId = null): ?UserSetting
    {
        $userId = $userId ?? Auth::id() ?? 1; // Fallback to user ID 1 if not authenticated
        if (!$userId) {
            Log::warning("getUserSettings: No user ID provided and no authenticated user");
            return null;
        }
        
        Log::info("getUserSettings: Getting settings for user ID: " . $userId);
        $settings = UserSetting::where('user_id', $userId)->first();
        
        if (!$settings) {
            Log::warning("getUserSettings: No settings found for user ID: " . $userId);
        }
        
        return $settings;
    }

    /**
     * Ottiene informazioni dettagliate sull'IP utilizzando un servizio esterno
     * 
     * @param string|null $proxy Il proxy da utilizzare
     * @return array Informazioni dettagliate sull'IP
     */
    public function getDetailedIpInfo(?string $proxy = null): array
    {
        try {
            $ch = curl_init('http://ip-api.com/json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            // Se specificato, usa il proxy
            if ($proxy) {
                // Estrai i componenti del proxy
                $pattern = '/^(?:http(?:s?):\/\/)?(?:([^:@]+):([^@]+)@)?([^:]+):(\d+)$/i';
                if (preg_match($pattern, $proxy, $matches)) {
                    $username = $matches[1] ?? null;
                    $password = $matches[2] ?? null;
                    $host = $matches[3];
                    $port = $matches[4];
                    
                    curl_setopt($ch, CURLOPT_PROXY, "$host:$port");
                    if ($username && $password) {
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$username:$password");
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    }
                    Log::info("Richiesta IP info tramite proxy: $host:$port");
                } else {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy);
                    Log::info("Richiesta IP info tramite proxy (formato originale): $proxy");
                }
            }
            
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($error) {
                Log::warning("Errore nella richiesta IP info: $error");
                return [
                    'success' => false,
                    'message' => "Errore: $error",
                    'data' => null
                ];
            }
            
            $data = json_decode($response, true);
            if ($data && isset($data['query'])) {
                Log::info("IP rilevato: " . $data['query'] . ", ISP: " . ($data['isp'] ?? 'sconosciuto'));
                return [
                    'success' => true,
                    'message' => "Successo",
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => "Risposta non valida",
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error("Eccezione nella richiesta IP info: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Eccezione: " . $e->getMessage(),
                'data' => null
            ];
        }
    }
} 