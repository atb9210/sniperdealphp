<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crea nuova campagna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('campaigns.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Nome campagna')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus value="{{ old('name') }}" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <p class="mt-1 text-sm text-gray-500">Un nome descrittivo per identificare la campagna.</p>
                        </div>

                        <div>
                            <x-input-label for="keyword" :value="__('Keyword di ricerca')" />
                            <x-text-input id="keyword" name="keyword" type="text" class="mt-1 block w-full" required value="{{ old('keyword') }}" />
                            <x-input-error class="mt-2" :messages="$errors->get('keyword')" />
                            <p class="mt-1 text-sm text-gray-500">La parola chiave da cercare su Subito.it.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="min_price" :value="__('Prezzo minimo (€)')" />
                                <x-text-input id="min_price" name="min_price" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('min_price') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('min_price')" />
                                <p class="mt-1 text-sm text-gray-500">Filtra risultati con prezzo maggiore o uguale a questo valore.</p>
                            </div>

                            <div>
                                <x-input-label for="max_price" :value="__('Prezzo massimo (€)')" />
                                <x-text-input id="max_price" name="max_price" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('max_price') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('max_price')" />
                                <p class="mt-1 text-sm text-gray-500">Filtra risultati con prezzo minore o uguale a questo valore.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="max_pages" :value="__('Numero massimo di pagine')" />
                                <x-text-input id="max_pages" name="max_pages" type="number" min="1" max="10" class="mt-1 block w-full" required value="{{ old('max_pages', 3) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('max_pages')" />
                                <p class="mt-1 text-sm text-gray-500">Quante pagine di risultati analizzare (max 10).</p>
                            </div>

                            <div>
                                <x-input-label for="interval_minutes" :value="__('Intervallo di controllo (minuti)')" />
                                <x-text-input id="interval_minutes" name="interval_minutes" type="number" min="1" class="mt-1 block w-full" required value="{{ old('interval_minutes', 60) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('interval_minutes')" />
                                <p class="mt-1 text-sm text-gray-500">Ogni quanti minuti eseguire la ricerca.</p>
                            </div>
                        </div>

                        <div class="block">
                            <label for="qso" class="inline-flex items-center">
                                <input id="qso" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="qso" value="1" {{ old('qso') ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">{{ __('Ricerca specifica (QSO)') }}</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">Abilita la ricerca specifica su Subito.it.</p>
                        </div>

                        @php
                            $userSettings = \App\Models\UserSetting::where('user_id', auth()->id())->first();
                            $hasProxies = $userSettings && $userSettings->hasActiveProxies();
                            $proxyCount = $userSettings ? count($userSettings->active_proxies) : 0;
                        @endphp

                        <div class="block {{ !$hasProxies ? 'opacity-50' : '' }}">
                            <label for="use_proxy" class="inline-flex items-center">
                                <input id="use_proxy" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" 
                                       name="use_proxy" value="1" {{ old('use_proxy') ? 'checked' : '' }} {{ !$hasProxies ? 'disabled' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">{{ __('Utilizza proxy') }}</span>
                                @if($hasProxies)
                                    <span class="ml-2 text-xs text-green-600">({{ $proxyCount }} disponibili)</span>
                                @else
                                    <span class="ml-2 text-xs text-red-600">(nessun proxy configurato)</span>
                                @endif
                            </label>
                            <p class="mt-1 text-sm text-gray-500">
                                Utilizza proxy per mascherare l'IP durante lo scraping. 
                                @if(!$hasProxies)
                                    <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-900">Configura i proxy nelle impostazioni</a>.
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('campaigns.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-3">
                                Annulla
                            </a>
                            <x-primary-button>
                                {{ __('Crea campagna') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 