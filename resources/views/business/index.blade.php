<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Business Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Valore in Stock</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($stockValue, 2, ',', '.') }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Valore Venduto</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($soldValue, 2, ',', '.') }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Profitto</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($totalProfit, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Filtri e Azioni -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex flex-wrap items-center space-x-4 mb-4 md:mb-0">
                        <form action="{{ route('business.index') }}" method="GET" class="flex flex-wrap gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Stato</label>
                                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Tutti</option>
                                    <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                    <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Venduto</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="campaign_id" class="block text-sm font-medium text-gray-700">Campagna</label>
                                <select id="campaign_id" name="campaign_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Tutte</option>
                                    @foreach($campaigns as $campaign)
                                        <option value="{{ $campaign->id }}" {{ request('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                            {{ $campaign->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700">Da</label>
                                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700">A</label>
                                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div class="self-end">
                                <button type="submit" class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300">
                                    Filtra
                                </button>
                            </div>
                        </form>
                    </div>
                    <a href="{{ route('business.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                        Nuovo Deal
                    </a>
                </div>
            </div>
            
            <!-- Tabella Deal -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Prodotto
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        SKU
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Campagna
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Costo
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vendita
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Profitto
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Margine
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stato
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Azioni
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($deals as $deal)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $deal->date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $deal->product }}
                                            @if($deal->link)
                                                <a href="{{ $deal->link }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 ml-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $deal->sku ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $deal->campaign->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            € {{ number_format($deal->total_cost, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $deal->sale_amount ? '€ ' . number_format($deal->sale_amount, 2, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($deal->profit !== null)
                                                <span class="{{ $deal->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    € {{ number_format($deal->profit, 2, ',', '.') }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($deal->margin_percentage !== null)
                                                <span class="{{ $deal->margin_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ number_format($deal->margin_percentage, 2, ',', '.') }}%
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $deal->status === 'sold' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $deal->status === 'sold' ? 'Venduto' : 'In Stock' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('business.edit', $deal) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    Modifica
                                                </a>
                                                
                                                @if($deal->status === 'in_stock')
                                                    <button type="button" 
                                                            onclick="document.getElementById('mark-as-sold-{{ $deal->id }}').classList.remove('hidden')"
                                                            class="text-green-600 hover:text-green-900">
                                                        Venduto
                                                    </button>
                                                @endif
                                                
                                                <form action="{{ route('business.destroy', $deal) }}" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo deal?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Elimina
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Modal per marcare come venduto -->
                                            <div id="mark-as-sold-{{ $deal->id }}" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
                                                <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Segna come Venduto</h3>
                                                    
                                                    <form action="{{ route('business.markAsSold', $deal) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        
                                                        <div class="mb-4">
                                                            <label for="sale_amount" class="block text-sm font-medium text-gray-700">Importo Vendita</label>
                                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                                    <span class="text-gray-500 sm:text-sm">€</span>
                                                                </div>
                                                                <input type="number" step="0.01" min="0" name="sale_amount" id="sale_amount" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00" required>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="flex justify-end space-x-3">
                                                            <button type="button" 
                                                                    onclick="document.getElementById('mark-as-sold-{{ $deal->id }}').classList.add('hidden')"
                                                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">
                                                                Annulla
                                                            </button>
                                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">
                                                                Conferma
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            Nessun deal trovato.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $deals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 