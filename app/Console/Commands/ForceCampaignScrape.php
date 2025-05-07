<?php

namespace App\Console\Commands;

use App\Jobs\SubitoScraperJob;
use App\Models\Campaign;
use App\Models\CampaignResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceCampaignScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:force {campaign_id} {--reset-notified : Reset notified status on results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force scrape a campaign and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaignId = $this->argument('campaign_id');
        $resetNotified = $this->option('reset-notified');
        
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            $this->error("Campaign not found with ID: {$campaignId}");
            return 1;
        }
        
        // Opzionalmente reset dello stato delle notifiche
        if ($resetNotified) {
            $count = CampaignResult::where('campaign_id', $campaignId)
                ->update(['notified' => false, 'is_new' => true]);
            
            $this->info("Reset notification status for {$count} results");
        }
        
        $this->info("Forcing scrape for campaign: {$campaign->name} (ID: {$campaignId})");
        
        // Dispatch job in sync mode to run immediately
        SubitoScraperJob::dispatchSync($campaign);
        
        $this->info("Scrape completed!");
        
        return 0;
    }
} 