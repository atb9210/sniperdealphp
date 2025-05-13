<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(): View
    {
        $settings = UserSetting::firstOrCreate(
            ['user_id' => Auth::id()],
            ['telegram_chat_id' => '', 'telegram_token' => '']
        );
        
        return view('settings.index', compact('settings'));
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request)
    {
        Log::info('Settings update request received', ['request' => $request->all()]);
        
        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string|max:255',
            'telegram_token' => 'nullable|string|max:255',
            'proxy_list' => 'nullable|string',
            'action' => 'nullable|string',
        ]);

        $settings = UserSetting::firstOrCreate(
            ['user_id' => Auth::id()]
        );
        
        // Handle Telegram settings
        if (isset($validated['telegram_chat_id'])) {
            $settings->telegram_chat_id = $validated['telegram_chat_id'];
        }
        
        if (isset($validated['telegram_token'])) {
            $settings->telegram_token = $validated['telegram_token'];
        }
        
        // Handle proxy actions
        if (isset($validated['action']) && $validated['action'] === 'clear_proxies') {
            Log::info('Clearing all proxies');
            $settings->proxies = [];
            $settings->save();
            return redirect()->route('settings.index')
                ->with('success', 'Tutti i proxy sono stati rimossi.');
        }
        
        if (isset($validated['action']) && $validated['action'] === 'test_proxy') {
            Log::info('Testing proxy');
            if (empty($settings->proxies)) {
                return redirect()->route('settings.index')
                    ->with('error', 'Nessun proxy da testare.');
            }
            
            $firstProxy = $settings->proxies[0] ?? null;
            if (!$firstProxy) {
                return redirect()->route('settings.index')
                    ->with('error', 'Proxy non valido.');
            }
            
            try {
                $testUrl = 'https://subito.it';
                $ch = curl_init($testUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_PROXY, $firstProxy);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($response === false) {
                    return redirect()->route('settings.index')
                        ->with('proxy_test_result', 'Errore di connessione: ' . $error)
                        ->with('proxy_test_success', false);
                }
                
                return redirect()->route('settings.index')
                    ->with('proxy_test_result', "Connessione riuscita! Codice HTTP: {$httpCode}")
                    ->with('proxy_test_success', true);
                
            } catch (\Exception $e) {
                return redirect()->route('settings.index')
                    ->with('proxy_test_result', 'Errore: ' . $e->getMessage())
                    ->with('proxy_test_success', false);
            }
        }
        
        // Handle proxy list import
        if (!empty($validated['proxy_list'])) {
            Log::info('Processing proxy list', ['list_length' => strlen($validated['proxy_list'])]);
            $proxyLines = explode("\n", $validated['proxy_list']);
            $formattedProxies = [];
            
            // Get existing proxies
            $existingProxies = is_array($settings->proxies) ? $settings->proxies : [];
            Log::info('Existing proxies', ['count' => count($existingProxies)]);
            
            foreach ($proxyLines as $index => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Handle different proxy formats
                if (strpos($line, '@') !== false && substr_count($line, ':') >= 2) {
                    // Format already contains @ symbol (likely username@password:host:port or similar)
                    // Just add http:// prefix if not already present
                    if (strpos($line, 'http://') !== 0 && strpos($line, 'https://') !== 0) {
                        $formattedProxy = "http://{$line}";
                    } else {
                        $formattedProxy = $line;
                    }
                    $formattedProxies[] = $formattedProxy;
                    Log::info('Formatted proxy with @ symbol', ['proxy' => $formattedProxy]);
                } else {
                    // Standard format: username:password:host:port
                    $parts = explode(':', $line);
                    Log::info('Processing proxy line', ['line' => $line, 'parts_count' => count($parts)]);
                    
                    if (count($parts) === 4) {
                        $formattedProxy = "http://{$parts[0]}:{$parts[1]}@{$parts[2]}:{$parts[3]}";
                        $formattedProxies[] = $formattedProxy;
                        Log::info('Formatted proxy', ['proxy' => $formattedProxy]);
                    } else {
                        Log::warning('Invalid proxy format', ['line' => $line]);
                    }
                }
            }
            
            Log::info('Formatted proxies', ['count' => count($formattedProxies)]);
            
            // Replace existing proxies instead of merging
            $settings->proxies = $formattedProxies;
            $settings->save();
            
            Log::info('Proxies saved', ['total_count' => count($settings->proxies)]);
            
            return redirect()->route('settings.index')
                ->with('success', 'Importati ' . count($formattedProxies) . ' nuovi proxy.');
        }
        
        $settings->save();
        
        return redirect()->route('settings.index')
            ->with('success', 'Impostazioni aggiornate con successo.');
    }

    /**
     * Test the Telegram notification.
     */
    public function testTelegram(Request $request)
    {
        $settings = UserSetting::where('user_id', Auth::id())->first();
        
        if (!$settings || empty($settings->telegram_token) || empty($settings->telegram_chat_id)) {
            return redirect()->route('settings.index')
                ->with('error', 'Configura prima le impostazioni di Telegram.');
        }

        try {
            $user = Auth::user();
            $date = now()->format('d/m/Y H:i:s');
            
            $message = "🔔 *Test Notifica SnipeDealPhp*\n\n";
            $message .= "Questo è un messaggio di test inviato da SnipeDealPhp.\n";
            $message .= "Se stai ricevendo questo messaggio, le tue notifiche Telegram sono configurate correttamente!\n\n";
            $message .= "📱 *Dettagli*\n";
            $message .= "👤 Utente: {$user->name}\n";
            $message .= "📅 Data: {$date}\n";
            $message .= "🆔 Chat ID: {$settings->telegram_chat_id}\n\n";
            $message .= "Ora puoi ricevere notifiche per gli annunci che ti interessano.";
            
            $telegramApiUrl = "https://api.telegram.org/bot{$settings->telegram_token}/sendMessage";
            
            $ch = curl_init($telegramApiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'chat_id' => $settings->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);
                if ($responseData && isset($responseData['ok']) && $responseData['ok'] === true) {
                    return redirect()->route('settings.index')
                        ->with('success', 'Messaggio di test inviato con successo! Controlla la tua app Telegram.');
                } else {
                    return redirect()->route('settings.index')
                        ->with('error', 'Errore API Telegram: ' . ($responseData['description'] ?? 'Risposta non valida'));
                }
            } else {
                return redirect()->route('settings.index')
                    ->with('error', "Errore HTTP {$httpCode}: Verifica che il token e il chat ID siano corretti.");
            }
        } catch (\Exception $e) {
            return redirect()->route('settings.index')
                ->with('error', 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Test a proxy connection.
     */
    public function testProxy(Request $request)
    {
        try {
            $validated = $request->validate([
                'test_url' => 'required|url',
            ]);
            
            $testUrl = $validated['test_url'];
            
            // Usa il ProxyManager per testare un proxy casuale
            $proxyManager = new \App\Services\ProxyManager();
            $proxy = $proxyManager->getRandomProxy();
            
            if (!$proxy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessun proxy disponibile'
                ]);
            }
            
            // Prima ottieni l'IP locale
            $localIp = $proxyManager->getLocalIpAddress();
            
            // Testa il proxy
            $result = $proxyManager->testProxy($proxy, $testUrl);
            
            // Se il test ha successo ma l'IP non è cambiato, il proxy non sta funzionando
            if ($result['success'] && $result['ip_address'] === $localIp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il proxy non sta mascherando l\'IP (IP locale e proxy coincidono)',
                    'http_code' => $result['http_code'],
                    'ip_address' => $result['ip_address'],
                    'local_ip' => $localIp
                ]);
            }
            
            // Se il codice è 403 ma l'IP è cambiato, consideriamo il test riuscito
            if ($result['http_code'] === 403 && $result['ip_address'] !== $localIp) {
                return response()->json([
                    'success' => true,
                    'message' => 'Proxy funzionante (IP mascherato correttamente)',
                    'http_code' => $result['http_code'],
                    'ip_address' => $result['ip_address'],
                    'local_ip' => $localIp
                ]);
            }
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'http_code' => $result['http_code'],
                'ip_address' => $result['ip_address'],
                'local_ip' => $localIp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the current proxy settings (for debugging)
     */
    public function dumpProxies()
    {
        $settings = UserSetting::where('user_id', Auth::id())->first();
        
        if (!$settings) {
            return response()->json([
                'error' => 'No settings found'
            ], 404);
        }
        
        return response()->json([
            'proxies_type' => gettype($settings->proxies),
            'proxies_count' => is_array($settings->proxies) ? count($settings->proxies) : 0,
            'proxies' => $settings->proxies
        ]);
    }
}
