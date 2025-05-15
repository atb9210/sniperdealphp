<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Monitoraggio Worker') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Worker Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Stato Worker</h3>
                    @if(count($workerStatus) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($workerStatus as $worker)
                                <div class="border rounded p-4 {{ $worker['state'] === 'RUNNING' ? 'bg-green-50' : 'bg-red-50' }}">
                                    <p class="font-medium">{{ $worker['name'] }}</p>
                                    <p>Stato: {{ $worker['state'] }}</p>
                                    <p>PID: {{ $worker['pid'] }}</p>
                                    <p>Uptime: {{ $worker['uptime'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p>Nessun worker attivo</p>
                    @endif
                </div>
            </div>

            <!-- System Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Informazioni Sistema</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="font-medium">Memoria</p>
                            <p>{{ $systemInfo['memory'] }}</p>
                        </div>
                        <div>
                            <p class="font-medium">CPU</p>
                            <p>{{ $systemInfo['cpu'] }}</p>
                        </div>
                        <div>
                            <p class="font-medium">Disco</p>
                            <p>{{ $systemInfo['disk'] }}</p>
                        </div>
                        <div>
                            <p class="font-medium">Coda</p>
                            <p>{{ $systemInfo['queue_size'] }} job in coda</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Log Recenti</h3>
                    @if(count($recentLogs) > 0)
                        <div class="bg-gray-50 rounded p-4">
                            <pre class="text-sm">{{ implode("\n", $recentLogs) }}</pre>
                        </div>
                    @else
                        <p>Nessun log disponibile</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh ogni 30 secondi
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
    @endpush
</x-app-layout> 