<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These cron jobs are run in the background by a cron service.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Esegue il controllo delle campagne ogni minuto
        // Ora che usa dispatchSync le notifiche verranno inviate immediatamente
        $schedule->command('campaigns:run')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Aggiungo un log per debug
        $schedule->call(function () {
            Log::info('Scheduler è attivo e funzionante!');
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 