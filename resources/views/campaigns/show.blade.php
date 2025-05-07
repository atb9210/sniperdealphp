<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $campaign->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('job-logs.campaign', $campaign) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Visualizza log
                </a>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Modifica campagna
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Campaign Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informazioni campagna</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Keyword</h4>
                            <p class="mt-1">
                                {{ $campaign->keyword }}
                                @if($campaign->qso)
                                    <span class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">QSO</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Filtro prezzo</h4>
                            <p class="mt-1">
                                @if($campaign->min_price && $campaign->max_price)
                                    {{ $campaign->min_price }}€ - {{ $campaign->max_price }}€
                                @elseif($campaign->min_price)
                                    Min: {{ $campaign->min_price }}€
                                @elseif($campaign->max_price)
                                    Max: {{ $campaign->max_price }}€
                                @else
                                    Nessun filtro
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Stato</h4>
                            <p class="mt-1">
                                @if($campaign->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Attiva</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inattiva</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Intervallo</h4>
                            <p class="mt-1">{{ $campaign->interval_minutes }} minuti</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Pagine</h4>
                            <p class="mt-1">{{ $campaign->max_pages }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Ultima esecuzione</h4>
                            <p class="mt-1">{{ $campaign->last_run_at ? $campaign->last_run_at->format('d/m/Y H:i') : 'Mai' }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Prossima esecuzione</h4>
                            <p class="mt-1">{{ $campaign->next_run_at ? $campaign->next_run_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Creata il</h4>
                            <p class="mt-1">{{ $campaign->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex space-x-3">
                        <form action="{{ route('campaigns.toggle', $campaign) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-{{ $campaign->is_active ? 'yellow' : 'green' }}-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-{{ $campaign->is_active ? 'yellow' : 'green' }}-500 focus:bg-{{ $campaign->is_active ? 'yellow' : 'green' }}-700 active:bg-{{ $campaign->is_active ? 'yellow' : 'green' }}-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ $campaign->is_active ? 'Metti in pausa' : 'Attiva' }}
                            </button>
                        </form>
                        
                        <form action="{{ route('campaigns.run', $campaign) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Esegui ora
                            </button>
                        </form>
                        
                        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questa campagna? Questa azione non può essere annullata.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Elimina campagna
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Campaign Results -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Risultati della campagna</h3>
                        
                        <div class="flex items-center">
                            <div class="mr-4">
                                <label for="filter-venduti" class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="filter-venduti" class="form-checkbox">
                                    <span class="ml-2 text-sm">Solo venduti</span>
                                </label>
                            </div>
                            <div>
                                <label for="filter-spedizione" class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="filter-spedizione" class="form-checkbox">
                                    <span class="ml-2 text-sm">Solo spedizione disponibile</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($results->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">Nessun risultato trovato per questa campagna.</p>
                            <form action="{{ route('campaigns.run', $campaign) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">Esegui la campagna ora</button>
                            </form>
                        </div>
                    @else
                        <div id="results-stats" class="mb-4 text-sm text-gray-700"></div>
                        <div class="overflow-x-auto">
                            <table id="results-table" class="min-w-full divide-y divide-gray-200 whitespace-nowrap border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Titolo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Prezzo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Località</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Data</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Stato</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Spedizione</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-200">Link</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr @if(($result->stato ?? '') === 'Venduto') class="bg-red-50" @endif data-stato="{{ $result->stato }}" data-spedizione="{{ $result->spedizione ? '1' : '0' }}">
                                            <td class="px-4 py-2 border-b border-gray-100">{{ $result->title }}</td>
                                            <td class="px-4 py-2 border-b border-gray-100">{{ $result->price }}</td>
                                            <td class="px-4 py-2 border-b border-gray-100 whitespace-nowrap max-w-xs truncate">{{ $result->location }}</td>
                                            <td class="px-4 py-2 border-b border-gray-100 whitespace-nowrap max-w-xs truncate">{{ $result->date ?? '' }}</td>
                                            <td class="px-4 py-2 border-b border-gray-100">
                                                @if(($result->stato ?? '') === 'Venduto')
                                                    <span class="text-red-500 font-semibold">Venduto</span>
                                                @else
                                                    {{ $result->stato ?? '' }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 border-b border-gray-100">
                                                @if ($result->spedizione)
                                                    <span class="text-green-600 font-semibold">Sì</span>
                                                @else
                                                    <span class="text-gray-500">No</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 border-b border-gray-100">
                                                <a href="{{ $result->link }}" target="_blank" class="text-blue-600 hover:underline">Vai</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $results->links() }}
                        </div>

                        <script>
                            // Filtri per la tabella dei risultati
                            const filterVenduti = document.getElementById('filter-venduti');
                            const filterSpedizione = document.getElementById('filter-spedizione');
                            
                            function updateTableFilters() {
                                const rows = document.querySelectorAll('#results-table tbody tr');
                                let totDisponibili = 0, totVenduti = 0, sumDisponibili = 0, sumVenduti = 0;
                                let countDisponibili = 0, countVenduti = 0;
                                
                                rows.forEach(row => {
                                    const stato = row.getAttribute('data-stato');
                                    const spedizione = row.getAttribute('data-spedizione') === '1';
                                    const prezzo = parsePrice(row.querySelector('td:nth-child(2)').innerText.trim());
                                    
                                    let show = true;
                                    if (filterVenduti.checked && stato !== 'Venduto') show = false;
                                    if (filterSpedizione.checked && !spedizione) show = false;
                                    
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
                                
                                document.getElementById('results-stats').innerHTML = `
                                    <div class="flex flex-wrap gap-4">
                                        <span><b>Totale disponibili:</b> ${totDisponibili}</span>
                                        <span><b>Totale venduti:</b> ${totVenduti}</span>
                                        <span><b>Sell through rate:</b> ${sellThrough}%</span>
                                        <span><b>Prezzo medio disponibili:</b> ${avgDisponibili} €</span>
                                        <span><b>Prezzo medio venduti:</b> ${avgVenduti} €</span>
                                    </div>
                                `;
                            }
                            
                            function parsePrice(val) {
                                if (!val) return null;
                                let n = val.replace(/[^\d,.]/g, '').replace(',', '.');
                                return parseFloat(n) || null;
                            }
                            
                            filterVenduti.addEventListener('change', updateTableFilters);
                            filterSpedizione.addEventListener('change', updateTableFilters);
                            
                            // Aggiorna subito dopo il render
                            updateTableFilters();
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 