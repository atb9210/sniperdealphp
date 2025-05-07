<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impostazioni') }}
        </h2>
    </x-slot>

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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
        </div>
    </div>
</x-app-layout> 