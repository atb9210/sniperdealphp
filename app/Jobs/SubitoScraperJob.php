<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignResult;
use App\Models\JobLog;
use App\Services\SubitoScraper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SubitoScraperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The campaign instance.
     *
     * @var \App\Models\Campaign
     */
    protected $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Create a job log entry
        $jobLog = JobLog::createRunning($this->campaign);
        
        try {
            Log::info("Starting campaign job: {$this->campaign->name} (ID: {$this->campaign->id})");

            // Update campaign last run time
            $this->campaign->updateNextRunTime();

            // Run the scraper
            $scraper = new SubitoScraper();
            $ads = $scraper->scrape(
                $this->campaign->keyword, 
                $this->campaign->qso, 
                $this->campaign->max_pages
            );

            if (empty($ads)) {
                Log::warning("No ads found for campaign: {$this->campaign->name}");
                $jobLog->markAsCompleted(0, 0, "No ads found");
                return;
            }

            Log::info("Found " . count($ads) . " ads for campaign: {$this->campaign->name}");
            
            // Process the results
            $newResults = $this->processResults($ads);
            
            // Mark job as completed
            $jobLog->markAsCompleted(
                count($ads),
                $newResults,
                "Successfully processed " . count($ads) . " ads, " . $newResults . " new"
            );

            // Send notifications for new results if needed
            if ($newResults > 0) {
                $this->sendNotifications($newResults);
            }
        } catch (Exception $e) {
            Log::error("Error in campaign job: " . $e->getMessage());
            $jobLog->markAsFailed($e->getMessage());
        }
    }

    /**
     * Process the scraped results.
     */
    protected function processResults(array $ads): int
    {
        $newResultsCount = 0;

        foreach ($ads as $ad) {
            // Skip ads that don't match price criteria
            $price = $this->extractNumericPrice($ad['price'] ?? null);
            if (!$this->matchesPriceCriteria($price)) {
                continue;
            }

            // Check if this ad already exists (by link)
            $existingResult = CampaignResult::where('campaign_id', $this->campaign->id)
                ->where('link', $ad['link'])
                ->first();

            if ($existingResult) {
                // Update existing result if needed
                $existingResult->update([
                    'price' => $ad['price'] ?? $existingResult->price,
                    'stato' => $ad['stato'] ?? $existingResult->stato,
                    'spedizione' => $ad['spedizione'] ?? $existingResult->spedizione,
                    'date' => $ad['date'] ?? $existingResult->date,
                    'is_new' => false,
                ]);
            } else {
                // Create new result
                CampaignResult::create([
                    'campaign_id' => $this->campaign->id,
                    'title' => $ad['title'],
                    'price' => $ad['price'] ?? null,
                    'location' => $ad['location'] ?? null,
                    'date' => $ad['date'] ?? null,
                    'link' => $ad['link'] ?? null,
                    'image' => $ad['image'] ?? null,
                    'stato' => $ad['stato'] ?? 'Disponibile',
                    'spedizione' => $ad['spedizione'] ?? false,
                    'notified' => false,
                    'is_new' => true,
                ]);

                $newResultsCount++;
            }
        }

        return $newResultsCount;
    }

    /**
     * Extract numeric price from price string.
     */
    protected function extractNumericPrice(?string $price): ?float
    {
        if (empty($price)) {
            return null;
        }

        $price = preg_replace('/[^\d,.]/', '', $price);
        $price = str_replace(',', '.', $price);
        
        return (float) $price;
    }

    /**
     * Check if price matches campaign criteria.
     */
    protected function matchesPriceCriteria(?float $price): bool
    {
        if ($price === null) {
            return true; // If no price, we can't filter it out
        }

        $minPrice = $this->campaign->min_price;
        $maxPrice = $this->campaign->max_price;

        if ($minPrice && $price < $minPrice) {
            return false;
        }

        if ($maxPrice && $price > $maxPrice) {
            return false;
        }

        return true;
    }

    /**
     * Send notifications for new results.
     */
    protected function sendNotifications(int $newResults): void
    {
        // Get user settings
        $user = $this->campaign->user;
        $settings = $user->settings ?? null;

        if (!$settings || empty($settings->telegram_chat_id) || empty($settings->telegram_token)) {
            Log::info("Skipping notifications for campaign {$this->campaign->name}: no valid Telegram settings");
            return;
        }

        try {
            // Get new results
            $results = $this->campaign->results()
                ->where('is_new', true)
                ->where('notified', false)
                ->get();

            if ($results->isEmpty()) {
                return;
            }

            // Prepare message
            $message = "🔔 *Nuovi risultati per campagna: {$this->campaign->name}*\n\n";
            $message .= "Trovati {$newResults} nuovi annunci per la keyword: *{$this->campaign->keyword}*\n\n";

            // Add first 5 results
            foreach ($results->take(5) as $index => $result) {
                $message .= ($index + 1) . ". [{$result->title}]({$result->link})\n";
                $message .= "💰 {$result->price}\n";
                if ($result->location) {
                    $message .= "📍 {$result->location}\n";
                }
                $message .= "\n";

                // Mark as notified
                $result->markAsNotified();
            }

            if ($results->count() > 5) {
                $message .= "... e altri " . ($results->count() - 5) . " annunci\n";
            }

            // Send Telegram message
            $telegramApiUrl = "https://api.telegram.org/bot{$settings->telegram_token}/sendMessage";
            $ch = curl_init($telegramApiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'chat_id' => $settings->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => false,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode != 200) {
                Log::error("Failed to send Telegram notification: HTTP $httpCode");
            } else {
                Log::info("Telegram notification sent for campaign {$this->campaign->name}");
            }
        } catch (Exception $e) {
            Log::error("Error sending notifications: " . $e->getMessage());
        }
    }
}
