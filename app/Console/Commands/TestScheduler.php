<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TestScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:scheduler {--minutes=5 : Numero di minuti di test} {--interval=30 : Intervallo in secondi tra le esecuzioni}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa lo scheduler eseguendo commands:run ad intervalli regolari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int) $this->option('minutes');
        $interval = (int) $this->option('interval');
        
        if ($minutes < 1) {
            $this->error('Il numero di minuti deve essere almeno 1');
            return 1;
        }
        
        if ($interval < 1) {
            $this->error('L\'intervallo deve essere almeno 1 secondo');
            return 1;
        }
        
        $end = Carbon::now()->addMinutes($minutes);
        $iteration = 1;
        
        $this->info("Avvio test dello scheduler per $minutes minuti (fino a " . $end->format('H:i:s') . ")");
        $this->info("Esecuzione ogni $interval secondi");
        $this->info("");
        
        $progressBar = $this->output->createProgressBar($minutes * (60 / $interval));
        $progressBar->start();
        
        while (Carbon::now()->lt($end)) {
            $this->line("");
            $this->info("Iterazione $iteration - " . Carbon::now()->format('H:i:s'));
            
            // Esegui il comando campaigns:run
            $this->info("Esecuzione di campaigns:run...");
            Artisan::call('campaigns:run');
            $output = Artisan::output();
            $this->line(trim($output));
            
            // Attendi l'intervallo specificato
            sleep($interval);
            
            $progressBar->advance();
            $iteration++;
        }
        
        $progressBar->finish();
        $this->line("");
        $this->info("Test dello scheduler completato! Eseguite $iteration iterazioni.");
        
        // Mostra lo stato finale
        $this->line("");
        $this->info("Stato finale delle campagne:");
        Artisan::call('campaigns:monitor');
        $this->line(Artisan::output());
        
        return 0;
    }
} 