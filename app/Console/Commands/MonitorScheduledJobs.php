<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MonitorScheduledJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:monitor {--watch : Continua a monitorare in tempo reale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostra lo stato di esecuzione pianificata di tutte le campagne';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $watchMode = $this->option('watch');
        
        if ($watchMode) {
            $this->info("Modalità watch attivata. Premi Ctrl+C per uscire.");
            $this->info("Aggiornamento ogni 5 secondi...");
            
            while (true) {
                $this->displayStatus();
                sleep(5);
                if (function_exists('system')) {
                    system('clear');
                } else {
                    $this->line(str_repeat("\n", 50));
                }
            }
        } else {
            $this->displayStatus();
        }
        
        return 0;
    }
    
    /**
     * Visualizza lo stato di esecuzione di tutte le campagne.
     */
    protected function displayStatus()
    {
        $now = now();
        $this->info("Timestamp attuale: " . $now->format('Y-m-d H:i:s'));
        
        $headers = ['ID', 'Nome', 'Intervallo', 'Ultima Esec.', 'Prossima Esec.', 'Stato', 'Ritardo'];
        $rows = [];
        
        $campaigns = Campaign::all();
        
        foreach ($campaigns as $campaign) {
            $lastRun = $campaign->last_run_at ? Carbon::parse($campaign->last_run_at) : null;
            $nextRun = $campaign->next_run_at ? Carbon::parse($campaign->next_run_at) : null;
            
            // Calcolo ritardo
            $delay = null;
            if ($nextRun && $now->gt($nextRun)) {
                $delay = $now->diff($nextRun)->format('%H:%I:%S');
            }
            
            // Determina lo stato
            $status = 'Non eseguito';
            if ($lastRun) {
                if (!$campaign->is_active) {
                    $status = 'Inattivo';
                } elseif ($nextRun && $now->gt($nextRun)) {
                    $status = 'In ritardo';
                } elseif ($nextRun) {
                    $status = 'In programma';
                }
            }
            
            // Formatta le date
            $lastRunFormatted = $lastRun ? $lastRun->format('Y-m-d H:i:s') : 'Mai';
            $nextRunFormatted = $nextRun ? $nextRun->format('Y-m-d H:i:s') : 'N/D';
            
            $interval = "{$campaign->interval_minutes} min";
            
            $rows[] = [
                $campaign->id,
                $campaign->name,
                $interval,
                $lastRunFormatted,
                $nextRunFormatted,
                $status,
                $delay ?: 'N/D',
            ];
        }
        
        $this->table($headers, $rows);
        
        // Log delle esecuzioni recenti
        $this->info("");
        $this->info("Job log recenti:");
        
        $recentLogs = \App\Models\JobLog::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($recentLogs->isEmpty()) {
            $this->warn("Nessun log di esecuzione trovato.");
        } else {
            $logHeaders = ['ID', 'Campagna', 'Timestamp', 'Stato', 'Risultati', 'Messaggio'];
            $logRows = [];
            
            foreach ($recentLogs as $log) {
                $timestamp = $log->created_at ? Carbon::parse($log->created_at)->format('Y-m-d H:i:s') : 'N/D';
                $campaign = $log->campaign ? $log->campaign->name : 'N/D';
                
                $logRows[] = [
                    $log->id,
                    $campaign,
                    $timestamp,
                    $log->status,
                    $log->results_count ?? 'N/D',
                    \Illuminate\Support\Str::limit($log->message, 40),
                ];
            }
            
            $this->table($logHeaders, $logRows);
        }
    }
} 