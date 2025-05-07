<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckNotificationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:status {campaign_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check notification status for campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaignId = $this->argument('campaign_id');
        
        if ($campaignId) {
            $campaigns = [Campaign::find($campaignId)];
            if (!$campaigns[0]) {
                $this->error("Campaign not found with ID: {$campaignId}");
                return 1;
            }
        } else {
            $campaigns = Campaign::all();
            if ($campaigns->isEmpty()) {
                $this->error("No campaigns found in the database");
                return 1;
            }
        }
        
        $this->info("");
        $this->info("📊 Notification Status Report");
        $this->info("=============================");
        
        $headers = ['ID', 'Campaign', 'User', 'Total Results', 'Notified', 'Unnotified', 'New', 'Last Run'];
        $rows = [];
        
        foreach ($campaigns as $campaign) {
            $user = $campaign->user;
            $settings = $user->settings;
            
            $totalResults = $campaign->results()->count();
            $notifiedResults = $campaign->results()->where('notified', true)->count();
            $unnotifiedResults = $campaign->results()->where('notified', false)->count();
            $newResults = $campaign->results()->where('is_new', true)->count();
            
            $telegramStatus = ($settings && !empty($settings->telegram_chat_id) && !empty($settings->telegram_token)) 
                ? "✅" 
                : "❌";
            
            $rows[] = [
                $campaign->id,
                $campaign->name . " " . $telegramStatus,
                $user->name,
                $totalResults,
                $notifiedResults,
                $unnotifiedResults,
                $newResults,
                $campaign->last_run_at ?? 'Never'
            ];
        }
        
        $this->table($headers, $rows);
        
        $this->info("");
        $this->info("Telegram Settings Status");
        $this->info("=======================");
        
        $userHeaders = ['User', 'Telegram Settings'];
        $userRows = [];
        
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $settings = $user->settings;
            
            $userRows[] = [
                $user->name,
                ($settings && !empty($settings->telegram_chat_id) && !empty($settings->telegram_token)) 
                    ? "✅ Configured (Chat ID: {$settings->telegram_chat_id})" 
                    : "❌ Not configured"
            ];
        }
        
        $this->table($userHeaders, $userRows);
        
        $this->info("");
        $this->info("Queue Worker Status");
        $this->info("==================");
        
        $queueWorkerPids = shell_exec("pgrep -f 'php artisan queue:work'");
        if ($queueWorkerPids) {
            $pids = explode("\n", trim($queueWorkerPids));
            $this->info("✅ Queue worker is running with PID(s): " . implode(", ", $pids));
        } else {
            $this->error("❌ No queue worker is running! Run ./start-queue-worker.sh to start one.");
        }
        
        return 0;
    }
} 