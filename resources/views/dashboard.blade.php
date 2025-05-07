<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistiche principali -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Campagne</h3>
                        <div class="flex items-center">
                            <span class="text-3xl font-bold">{{ $totalCampaigns }}</span>
                            <span class="ml-2 text-sm text-gray-500">({{ $activeCampaigns }} attive)</span>
                        </div>
                        <a href="{{ route('campaigns.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-900">
                            Visualizza tutte le campagne →
                        </a>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Risultati totali</h3>
                        <div class="flex items-center">
                            <span class="text-3xl font-bold">{{ $totalResults }}</span>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            {{ $soldResults }} venduti ({{ $sellThroughRate }}%)
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Azioni rapide</h3>
                        <div class="space-y-2">
                            <a href="{{ route('campaigns.create') }}" class="block w-full py-2 px-4 bg-indigo-600 text-white text-center rounded-md hover:bg-indigo-700 transition">
                                Nuova campagna
                            </a>
                            <a href="{{ route('keyword.index') }}" class="block w-full py-2 px-4 bg-gray-200 text-gray-700 text-center rounded-md hover:bg-gray-300 transition">
                                Ricerca manuale
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campagne recenti -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Campagne recenti</h3>
                        <a href="{{ route('campaigns.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                            Vedi tutte →
                        </a>
                    </div>

                    @if($recentCampaigns->isEmpty())
                        <p class="text-gray-500">Non hai ancora creato nessuna campagna.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keyword</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ultima esecuzione</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentCampaigns as $campaign)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('campaigns.show', $campaign) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $campaign->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $campaign->keyword }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($campaign->is_active)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Attiva</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inattiva</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $campaign->last_run_at ? $campaign->last_run_at->format('d/m/Y H:i') : 'Mai' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('campaigns.show', $campaign) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    Dettagli
                                                </a>
                                                <form action="{{ route('campaigns.run', $campaign) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                        Esegui
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Risultati recenti -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Risultati recenti</h3>

                    @if($recentResults->isEmpty())
                        <p class="text-gray-500">Non ci sono risultati recenti.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($recentResults as $result)
                                <div class="border rounded-lg overflow-hidden shadow-sm">
                                    @if($result->image)
                                        <img src="{{ $result->image }}" alt="{{ $result->title }}" class="w-full h-48 object-cover">
                                    @else
                                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-400">Nessuna immagine</span>
                                        </div>
                                    @endif
                                    <div class="p-4">
                                        <h4 class="font-medium text-gray-900 mb-1 truncate">{{ $result->title }}</h4>
                                        <p class="text-gray-700 font-bold mb-1">{{ $result->price }}</p>
                                        <p class="text-gray-500 text-sm mb-2">{{ $result->location }}</p>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500">{{ $result->campaign->name }}</span>
                                            <a href="{{ $result->link }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                                Vedi annuncio →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Log recenti -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Log recenti</h3>
                        <a href="{{ route('job-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                            Vedi tutti →
                        </a>
                    </div>

                    @if($recentJobLogs->isEmpty())
                        <p class="text-gray-500">Non ci sono log recenti.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagna</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risultati</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentJobLogs as $jobLog)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('campaigns.show', $jobLog->campaign) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $jobLog->campaign->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($jobLog->status === 'running')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        In esecuzione
                                                    </span>
                                                @elseif($jobLog->status === 'success')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Completato
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Errore
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($jobLog->status === 'success')
                                                    {{ $jobLog->results_count }} ({{ $jobLog->new_results_count }} nuovi)
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $jobLog->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
