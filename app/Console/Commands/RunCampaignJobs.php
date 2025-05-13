<?php

namespace App\Console\Commands;

use App\Jobs\SubitoScraperJob;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCampaignJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:run {--campaign=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run campaign jobs that are due to be executed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaignId = $this->option('campaign');
        $force = $this->option('force');

        if ($campaignId) {
            // Run specific campaign
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                $this->error("Campaign with ID {$campaignId} not found");
                return 1;
            }

            if (!$campaign->is_active && !$force) {
                $this->warn("Campaign '{$campaign->name}' is not active. Use --force to run anyway.");
                return 1;
            }

            $this->runCampaign($campaign);
            return 0;
        }

        // Run all due campaigns
        $this->info("Checking for campaigns due to run...");
        
        $campaigns = Campaign::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info("No campaigns due to run");
            return 0;
        }

        $this->info("Found " . $campaigns->count() . " campaigns to run");
        
        foreach ($campaigns as $campaign) {
            $this->runCampaign($campaign);
        }

        $this->info("All campaigns dispatched");
        return 0;
    }

    /**
     * Run a specific campaign.
     */
    protected function runCampaign(Campaign $campaign)
    {
        $this->info("Dispatching job for campaign: {$campaign->name} (ID: {$campaign->id})");
        
        try {
            // Esegue il job immediatamente in modo sincrono
            // invece di metterlo in coda
            SubitoScraperJob::dispatchSync($campaign);
            $this->info("Job executed successfully");
        } catch (\Exception $e) {
            $this->error("Failed to execute job: " . $e->getMessage());
            Log::error("Failed to execute job for campaign {$campaign->id}: " . $e->getMessage());
        }
    }
}
