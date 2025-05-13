<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Keyword Form') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                {{-- <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div> --}}
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
                    <!-- Form Section -->
                    <form method="POST" action="{{ route('keyword.store') }}" class="space-y-6" id="search-form">
                        @csrf
                        <div class="flex flex-row items-center gap-4">
                            <div class="flex-1">
                                <x-input-label for="keyword" :value="__('Keyword')" />
                                <x-text-input id="keyword" name="keyword" type="text" class="mt-1 block w-full" required value="{{ session('keyword', old('keyword')) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('keyword')" />
                            </div>
                            <div class="w-32">
                                <x-input-label for="pages" :value="__('Numero pagine')" />
                                <input id="pages" name="pages" type="number" min="1" max="10" value="{{ session('pages', old('pages', 3)) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-primary-button id="search-btn">{{ __('Search Ads') }}</x-primary-button>
                            <span id="loading-spinner" class="hidden ml-2"><svg class="animate-spin h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> <span class="text-gray-600">Cercando...</span></span>
                            <label class="inline-flex items-center cursor-pointer ml-4">
                                <input type="checkbox" id="toggle-qso" name="qso" value="1" class="form-checkbox" @if(session('qso') || old('qso')) checked @endif>
                                <span class="ml-2 text-sm">Ricerca specifica</span>
                            </label>
                            
                            @if($hasProxies)
                            <label class="inline-flex items-center cursor-pointer ml-4">
                                <input type="checkbox" id="toggle-proxy" name="use_proxy" value="1" class="form-checkbox" @if(session('use_proxy') || old('use_proxy')) checked @endif>
                                <span class="ml-2 text-sm">Usa proxy ({{ $proxyCount }} disponibili)</span>
                            </label>
                            <div id="proxy-test-result" class="hidden mt-2 p-2 rounded text-sm"></div>
                            @endif
                        </div>
                    </form>

                    @if (session('ads'))
                        <div class="mt-4 mb-2 flex items-center gap-4">
                            <span class="text-sm text-gray-700 font-semibold">Trovate {{ count(session('ads')) }} inserzioni</span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="toggle-images" name="toggle-images" class="form-checkbox">
                                <span class="ml-2 text-sm">Mostra colonna immagini</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="filter-venduti" class="form-checkbox">
                                <span class="ml-2 text-sm">Solo venduti</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="filter-spedizione" class="form-checkbox">
                                <span class="ml-2 text-sm">Solo spedizione disponibile</span>
                            </label>
                        </div>
                        
                        @if(session('proxy_info'))
                        <div class="mt-4 p-3 bg-gray-100 rounded-md">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Informazioni di connessione:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Utilizzo proxy:</span> 
                                    @if(session('proxy_info.using_proxy'))
                                        <span class="text-green-600">Attivo</span>
                                    @else
                                        <span class="text-gray-600">Non attivo</span>
                                    @endif
                                </div>
                                @if(session('proxy_info.using_proxy') && session('proxy_info.proxy'))
                                <div>
                                    <span class="font-medium">Proxy:</span> 
                                    <span class="font-mono text-xs">{{ session('proxy_info.proxy') }}</span>
                                </div>
                                @endif
                                <div>
                                    <span class="font-medium">IP locale:</span> 
                                    <span class="font-mono">{{ session('proxy_info.local_ip') ?? 'Non disponibile' }}</span>
                                    @if(null !== session('proxy_info.local_details') && null !== session('proxy_info.local_details.isp'))
                                    <span class="text-xs text-gray-600 block">{{ session('proxy_info.local_details.isp') }} ({{ session('proxy_info.local_details.country') ?? '' }})</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-medium">IP utilizzato:</span> 
                                    <span class="font-mono">{{ session('proxy_info.proxy_ip') ?? session('proxy_info.local_ip') ?? 'Non disponibile' }}</span>
                                    @if(session('proxy_info.using_proxy') && null !== session('proxy_info.proxy_details') && null !== session('proxy_info.proxy_details.isp'))
                                    <span class="text-xs text-gray-600 block">{{ session('proxy_info.proxy_details.isp') }} ({{ session('proxy_info.proxy_details.country') ?? '' }})</span>
                                    @endif
                                    @if(session('proxy_info.using_proxy') && session('proxy_info.proxy_working'))
                                        <span class="ml-2 text-green-600 text-xs">(Maschera confermata)</span>
                                    @elseif(session('proxy_info.using_proxy') && !session('proxy_info.proxy_working'))
                                        <span class="ml-2 text-red-600 text-xs">(Maschera NON funzionante!)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 text-xs text-gray-500">
                                <p>
                                    Nota: se l'IP utilizzato coincide con l'IP locale quando il proxy è attivo, significa che il proxy non sta funzionando correttamente.
                                    Prova a modificare i proxy nelle <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:underline">impostazioni</a>.
                                </p>
                            </div>
                        </div>
                        @endif
                    @endif

                    <script>
                        document.getElementById('search-form').addEventListener('submit', function() {
                            document.getElementById('search-btn').disabled = true;
                            document.getElementById('loading-spinner').classList.remove('hidden');
                        });

                        // Gestione test proxy
                        const proxyToggle = document.getElementById('toggle-proxy');
                        const proxyTestResult = document.getElementById('proxy-test-result');
                        
                        if (proxyToggle) {
                            proxyToggle.addEventListener('change', async function() {
                                if (this.checked) {
                                    proxyTestResult.innerHTML = '<div class="flex items-center"><svg class="animate-spin h-4 w-4 text-gray-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Test proxy in corso...</div>';
                                    proxyTestResult.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
                                    proxyTestResult.classList.add('bg-gray-100', 'text-gray-700');
                                    
                                    try {
                                        const response = await fetch('{{ route("settings.test-proxy") }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                test_url: 'https://subito.it'
                                            })
                                        });
                                        
                                        const data = await response.json();
                                        
                                        if (data.success) {
                                            proxyTestResult.innerHTML = `
                                                <div class="text-green-700">
                                                    <div class="font-medium">✓ ${data.message}</div>
                                                    <div class="text-xs mt-1">IP Proxy: ${data.ip_address || 'N/A'}</div>
                                                    <div class="text-xs">IP Locale: ${data.local_ip || 'N/A'}</div>
                                                    <div class="text-xs">HTTP: ${data.http_code}</div>
                                                </div>
                                            `;
                                            proxyTestResult.classList.remove('bg-gray-100', 'text-gray-700', 'bg-red-100', 'text-red-700');
                                            proxyTestResult.classList.add('bg-green-100', 'text-green-700');
                                        } else {
                                            proxyTestResult.innerHTML = `
                                                <div class="text-red-700">
                                                    <div class="font-medium">✗ ${data.message}</div>
                                                    <div class="text-xs mt-1">IP Proxy: ${data.ip_address || 'N/A'}</div>
                                                    <div class="text-xs">IP Locale: ${data.local_ip || 'N/A'}</div>
                                                    <div class="text-xs">HTTP: ${data.http_code}</div>
                                                </div>
                                            `;
                                            proxyTestResult.classList.remove('bg-gray-100', 'text-gray-700', 'bg-green-100', 'text-green-700');
                                            proxyTestResult.classList.add('bg-red-100', 'text-red-700');
                                            
                                            // Disabilita il checkbox se il test fallisce
                                            this.checked = false;
                                        }
                                    } catch (error) {
                                        proxyTestResult.innerHTML = `
                                            <div class="text-red-700">
                                                <div class="font-medium">✗ Errore test proxy</div>
                                                <div class="text-xs mt-1">${error.message}</div>
                                            </div>
                                        `;
                                        proxyTestResult.classList.remove('bg-gray-100', 'text-gray-700', 'bg-green-100', 'text-green-700');
                                        proxyTestResult.classList.add('bg-red-100', 'text-red-700');
                                        
                                        // Disabilita il checkbox se il test fallisce
                                        this.checked = false;
                                    }
                                } else {
                                    proxyTestResult.classList.add('hidden');
                                }
                            });
                            
                            // Esegui il test iniziale se il checkbox è già attivo
                            if (proxyToggle.checked) {
                                proxyToggle.dispatchEvent(new Event('change'));
                            }
                        }
                    </script>

                    <!-- Scraped Ads Section -->
                    @if (session('ads'))
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Found Ads</h3>
                            <div id="ads-stats" class="mb-4 text-sm text-gray-700"></div>
                            <div class="overflow-x-auto">
                                <table id="ads-table" class="min-w-full divide-y divide-gray-200 whitespace-nowrap border border-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Titolo</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Prezzo</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Località</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Data</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200 image-col">Immagine</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Stato</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Spedizione</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Link</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach (session('ads') as $ad)
                                            <tr @if(($ad['stato'] ?? '') === 'Venduto') class="bg-red-50" @endif>
                                                <td class="px-4 py-2 border-b border-gray-100">{{ $ad['title'] }}</td>
                                                <td class="px-4 py-2 border-b border-gray-100">{{ $ad['price'] }}</td>
                                                <td class="px-4 py-2 border-b border-gray-100 whitespace-nowrap max-w-xs truncate">{{ $ad['location'] }}</td>
                                                <td class="px-4 py-2 border-b border-gray-100 whitespace-nowrap max-w-xs truncate">{{ $ad['date'] ?? '' }}</td>
                                                <td class="px-4 py-2 border-b border-gray-100 image-cell image-col">
                                                    @if (!empty($ad['image']))
                                                        <img src="{{ $ad['image'] }}" alt="img" class="h-12 w-12 object-cover rounded" />
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 border-b border-gray-100">
                                                    @if(($ad['stato'] ?? '') === 'Venduto')
                                                        <span class="text-red-500 font-semibold">Venduto</span>
                                                    @else
                                                        {{ $ad['stato'] ?? '' }}
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 border-b border-gray-100">
                                                    @if (isset($ad['spedizione']))
                                                        @if ($ad['spedizione'])
                                                            <span class="text-green-600 font-semibold">Sì</span>
                                                        @else
                                                            <span class="text-gray-500">No</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 border-b border-gray-100">
                                                    <a href="{{ $ad['link'] }}" target="_blank" class="text-blue-600 hover:underline">Vai</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <script>
                            const toggleImages = document.getElementById('toggle-images');
                            const filterVenduti = document.getElementById('filter-venduti');
                            const filterSpedizione = document.getElementById('filter-spedizione');
                            function updateImageColDisplay() {
                                document.querySelectorAll('.image-col').forEach(cell => {
                                    cell.style.display = toggleImages.checked ? '' : 'none';
                                });
                            }
                            // Nascondi di default al primo caricamento
                            updateImageColDisplay();
                            toggleImages.addEventListener('change', updateImageColDisplay);

                            // Filtro venduti e spedizione
                            function parsePrice(val) {
                                if (!val) return null;
                                let n = val.replace(/[^\d,.]/g, '').replace(',', '.');
                                return parseFloat(n) || null;
                            }
                            function updateTableFilters() {
                                const rows = document.querySelectorAll('#ads-table tbody tr');
                                let totDisponibili = 0, totVenduti = 0, sumDisponibili = 0, sumVenduti = 0;
                                let countDisponibili = 0, countVenduti = 0;
                                rows.forEach(row => {
                                    const stato = row.querySelector('td:nth-child(6)')?.innerText.trim();
                                    const spedizione = row.querySelector('td:nth-child(7)')?.innerText.trim();
                                    const prezzo = parsePrice(row.querySelector('td:nth-child(2)')?.innerText.trim());
                                    let show = true;
                                    if (filterVenduti.checked && stato !== 'Venduto') show = false;
                                    if (filterSpedizione.checked && spedizione !== 'Sì') show = false;
                                    row.style.display = show ? '' : 'none';
                                    if (show) {
                                        if (stato === 'Venduto') {
                                            totVenduti++;
                                            if (prezzo) { sumVenduti += prezzo; countVenduti++; }
                                        } else {
                                            totDisponibili++;
                                            if (prezzo) { sumDisponibili += prezzo; countDisponibili++; }
                                        }
                                    }
                                });
                                // Sell through rate
                                const sellThrough = totVenduti + totDisponibili > 0 ? ((totVenduti / (totVenduti + totDisponibili)) * 100).toFixed(1) : '0.0';
                                // Prezzi medi
                                const avgDisponibili = countDisponibili > 0 ? (sumDisponibili / countDisponibili).toFixed(2) : '-';
                                const avgVenduti = countVenduti > 0 ? (sumVenduti / countVenduti).toFixed(2) : '-';
                                document.getElementById('ads-stats').innerHTML = `
                                    <div class="flex flex-wrap gap-4">
                                        <span><b>Totale disponibili:</b> ${totDisponibili}</span>
                                        <span><b>Totale venduti:</b> ${totVenduti}</span>
                                        <span><b>Sell through rate:</b> ${sellThrough}%</span>
                                        <span><b>Prezzo medio disponibili:</b> ${avgDisponibili} €</span>
                                        <span><b>Prezzo medio venduti:</b> ${avgVenduti} €</span>
                                    </div>
                                `;
                            }
                            filterVenduti.addEventListener('change', updateTableFilters);
                            filterSpedizione.addEventListener('change', updateTableFilters);
                            // Aggiorna subito dopo il render
                            updateTableFilters();
                        </script>
                    @endif

                    <!-- Keywords Table Section -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Keywords List</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keyword</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($keywords ?? [] as $keyword)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $keyword->keyword }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <form method="POST" action="{{ route('keyword.store') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="keyword" value="{{ $keyword->keyword }}">
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">Search Again</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No keywords found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 