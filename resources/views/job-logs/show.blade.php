<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dettaglio Log Job
            </h2>
            <a href="{{ route('job-logs.campaign', $campaign) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                Torna ai log della campagna
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informazioni Job</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Campagna</h4>
                            <p class="mt-1">
                                <a href="{{ route('campaigns.show', $campaign) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $campaign->name }}
                                </a>
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Stato</h4>
                            <p class="mt-1">
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
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Inizio</h4>
                            <p class="mt-1">{{ $jobLog->started_at ? $jobLog->started_at->format('d/m/Y H:i:s') : 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Fine</h4>
                            <p class="mt-1">{{ $jobLog->completed_at ? $jobLog->completed_at->format('d/m/Y H:i:s') : 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Durata</h4>
                            <p class="mt-1">{{ $jobLog->formatted_duration }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Risultati</h4>
                            <p class="mt-1">
                                @if($jobLog->status === 'success')
                                    {{ $jobLog->results_count }} ({{ $jobLog->new_results_count }} nuovi)
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Messaggio</h3>
                    
                    <div class="mt-2 p-4 bg-gray-50 rounded-md">
                        <p class="whitespace-pre-wrap">{{ $jobLog->message }}</p>
                    </div>
                    
                    @if($jobLog->error)
                        <h3 class="text-lg font-medium text-gray-900 mt-6 mb-4">Errore</h3>
                        
                        <div class="mt-2 p-4 bg-red-50 text-red-800 rounded-md">
                            <p class="whitespace-pre-wrap">{{ $jobLog->error }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 