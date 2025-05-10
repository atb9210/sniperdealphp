<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impostazioni') }}
        </h2>
    </x-slot>

    <!-- Include debugging script -->
    <script src="{{ asset('js/console.js') }}"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notifiche Telegram</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Configura le notifiche Telegram per ricevere aggiornamenti sugli annunci interessanti.
                    </p>

                    <!-- Form Section -->
                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="telegram_token" :value="__('Token Bot Telegram')" />
                                <x-text-input id="telegram_token" name="telegram_token" type="text" class="mt-1 block w-full" value="{{ $settings->telegram_token }}" />
                                <p class="mt-1 text-xs text-gray-500">Crea un bot su Telegram con @BotFather e inserisci qui il token.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('telegram_token')" />
                            </div>

                            <div>
                                <x-input-label for="telegram_chat_id" :value="__('Chat ID Telegram')" />
                                <x-text-input id="telegram_chat_id" name="telegram_chat_id" type="text" class="mt-1 block w-full" value="{{ $settings->telegram_chat_id }}" />
                                <p class="mt-1 text-xs text-gray-500">Usa @userinfobot su Telegram per ottenere il tuo Chat ID.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('telegram_chat_id')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Salva Impostazioni') }}</x-primary-button>
                        </div>
                    </form>
                    
                    @if ($settings->telegram_token && $settings->telegram_chat_id)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-base font-medium mb-2">Test Notifiche</h4>
                            <p class="text-sm text-gray-600 mb-3">
                                Verifica che le tue impostazioni siano corrette inviando un messaggio di test.
                            </p>
                            <form method="POST" action="{{ route('settings.test-telegram') }}">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Invia Messaggio di Test') }}</x-secondary-button>
                            </form>
                        </div>
                    @endif

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Guida alla Configurazione</h3>
                        
                        <div class="prose prose-sm max-w-none">
                            <h4 class="text-base font-medium">Come configurare un bot Telegram:</h4>
                            <ol class="list-decimal pl-5 space-y-2">
                                <li>Apri Telegram e cerca @BotFather</li>
                                <li>Invia il comando /newbot</li>
                                <li>Segui le istruzioni per creare il tuo bot</li>
                                <li>Copia il token fornito e incollalo nel campo "Token Bot Telegram"</li>
                            </ol>

                            <h4 class="text-base font-medium mt-4">Come ottenere il tuo Chat ID:</h4>
                            <ol class="list-decimal pl-5 space-y-2">
                                <li>Cerca @userinfobot su Telegram</li>
                                <li>Invia un messaggio qualsiasi al bot</li>
                                <li>Il bot risponderà con le tue informazioni, incluso il tuo ID</li>
                                <li>Copia l'ID e incollalo nel campo "Chat ID Telegram"</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Proxy Settings Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Gestione Proxy</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Configura e gestisci i proxy per migliorare l'affidabilità dello scraping e prevenire blocchi IP.
                    </p>

                    <!-- Debug Info -->
                    <div class="mb-4 p-3 bg-gray-100 border border-gray-300 rounded">
                        <h4 class="text-sm font-bold mb-2">Debug Info:</h4>
                        <p class="text-xs">Proxies type: {{ gettype($settings->proxies) }}</p>
                        <p class="text-xs">Proxies count: {{ is_array($settings->proxies) ? count($settings->proxies) : 'N/A' }}</p>
                        <p class="text-xs">Raw data: <pre class="text-xs overflow-auto max-h-40">{{ var_export($settings->proxies, true) }}</pre></p>
                    </div>

                    <!-- Form Section -->
                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Current Proxies Display -->
                        <div class="mb-4">
                            <x-input-label :value="__('Proxy Configurati')" />
                            
                            <div class="mt-2 border rounded-md p-3 bg-gray-50">
                                @if(empty($settings->proxies) || count($settings->proxies) === 0)
                                    <p class="text-sm text-gray-500 italic">Nessun proxy configurato.</p>
                                @else
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($settings->proxies as $proxy)
                                            <li class="text-sm font-mono break-all">{{ $proxy }}</li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Totale proxy configurati: {{ count($settings->proxies) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Proxy Import Section -->
                        <div>
                            <x-input-label for="proxy_list" :value="__('Importa Lista Proxy')" />
                            <textarea
                                id="proxy_list"
                                name="proxy_list"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                rows="6"
                                placeholder="Incolla qui la tua lista di proxy (uno per riga)&#10;Formato: username:password:host:port"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Formato supportato: username:password:host:port (un proxy per riga)
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('proxy_list')" />
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Salva Proxy') }}</x-primary-button>
                            
                            @if(!empty($settings->proxies) && count($settings->proxies) > 0)
                                <x-secondary-button type="submit" name="action" value="clear_proxies" onclick="return confirm('Sei sicuro di voler rimuovere tutti i proxy?')">
                                    {{ __('Rimuovi Tutti i Proxy') }}
                                </x-secondary-button>
                                
                                <x-secondary-button type="submit" name="action" value="test_proxy">
                                    {{ __('Testa Primo Proxy') }}
                                </x-secondary-button>
                            @endif
                        </div>
                    </form>
                    
                    @if (session('proxy_test_result'))
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-base font-medium mb-2">Risultato Test Proxy</h4>
                            <div class="{{ session('proxy_test_success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' }} px-4 py-3 rounded relative border mb-3" role="alert">
                                <span class="block sm:inline">{{ session('proxy_test_result') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informazioni sui Proxy</h3>
                        
                        <div class="prose prose-sm max-w-none">
                            <p>L'utilizzo di proxy può aiutare a:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Evitare blocchi IP durante lo scraping intensivo</li>
                                <li>Aumentare l'affidabilità delle campagne di monitoraggio</li>
                                <li>Distribuire le richieste su più indirizzi IP</li>
                            </ul>
                            
                            <p class="mt-3">I proxy vengono utilizzati a rotazione durante l'esecuzione delle campagne.</p>
                            
                            <div class="mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400">
                                <p class="text-sm text-yellow-700">
                                    <strong>Nota:</strong> Assicurati di utilizzare proxy affidabili e di rispettare i termini di servizio dei siti monitorati.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 