<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string|max:255',
            'telegram_token' => 'nullable|string|max:255',
        ]);

        $settings = UserSetting::firstOrCreate(
            ['user_id' => Auth::id()]
        );
        
        $settings->update($validated);

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
}
