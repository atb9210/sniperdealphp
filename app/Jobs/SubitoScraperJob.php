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
                $this->campaign->max_pages,
                $this->campaign->use_proxy
            );

            // Include proxy info in logs if proxy usage was enabled
            if ($this->campaign->use_proxy) {
                $proxyInfo = $scraper->getProxyInfo();
                $proxyMessage = $proxyInfo['using_proxy'] 
                    ? "Used proxy: {$proxyInfo['proxy']}, IP: {$proxyInfo['proxy_ip']}" 
                    : "Proxy was requested but not used";
                Log::info($proxyMessage);
            }

            if (empty($ads)) {
                Log::warning("No ads found for campaign: {$this->campaign->name}");
                $jobLog->markAsCompleted(0, 0, "No ads found");
                return;
            }

            Log::info("Found " . count($ads) . " ads for campaign: {$this->campaign->name}");
            
            // Process the results
            $newResults = $this->processResults($ads);
            
            // Check for any unnotified results, regardless of whether they are new
            $unnotifiedCount = $this->campaign->results()
                ->where('notified', false)
                ->count();
                
            // Mark job as completed
            $jobLog->markAsCompleted(
                count($ads),
                $newResults,
                "Successfully processed " . count($ads) . " ads, " . $newResults . " new, " . $unnotifiedCount . " unnotified"
            );

            // Send notifications if there are any unnotified results (new or not)
            if ($unnotifiedCount > 0) {
                $this->sendNotifications($unnotifiedCount);
                Log::info("Notifications queued for {$unnotifiedCount} unnotified results");
            } else {
                Log::info("No unnotified results to send notifications for");
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
                    'is_new' => $existingResult->notified ? false : true,
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
        // Get user settings directly dalla tabella per evitare problemi di relazione
        $userId = $this->campaign->user_id;
        $settings = \App\Models\UserSetting::where('user_id', $userId)->first();
        
        if (!$settings) {
            Log::error("No UserSettings found for user ID {$userId}");
            return;
        }
        
        if (empty($settings->telegram_chat_id) || empty($settings->telegram_token)) {
            Log::info("Skipping notifications for campaign {$this->campaign->name}: no valid Telegram settings");
            return;
        }

        try {
            // Get new results - MODIFIED to check only notified status, not is_new status
            $results = $this->campaign->results()
                ->where('notified', false)
                ->get();

            if ($results->isEmpty()) {
                Log::info("No unnotified results for campaign {$this->campaign->name}");
                return;
            }

            Log::info("Found {$results->count()} unnotified results for campaign {$this->campaign->name}");
            
            // Send initial summary message
            $summaryMessage = "🔍 *Campagna: {$this->campaign->name}*\n";
            $summaryMessage .= "🆕 Trovati {$results->count()} nuovi annunci per: *{$this->campaign->keyword}*\n";
            $summaryMessage .= "⏱ " . now()->format('d/m/Y H:i:s') . "\n\n";
            $summaryMessage .= "📲 _Ti invierò un messaggio per ogni annuncio..._";
            
            $success = $this->sendTelegramMessage($settings, $summaryMessage);
            
            if (!$success) {
                Log::error("Failed to send summary message to Telegram for campaign {$this->campaign->name}");
                return;
            }
            
            // Sleep briefly to avoid rate limiting
            sleep(1);
            
            $sentCount = 0;
            
            // Send individual message for each result
            foreach ($results as $result) {
                // Prepare detailed message for this ad
                $message = $this->formatAdMessage($result);
                
                // Send message
                $success = $this->sendTelegramMessage($settings, $message);
                
                if ($success) {
                    // Mark as notified
                    $result->notified = true;
                    $result->is_new = false;
                    $result->save();
                    $sentCount++;
                    
                    // Avoid Telegram rate limiting
                    usleep(300000); // 300ms delay between messages
                } else {
                    Log::error("Failed to send notification for result ID: {$result->id}, title: {$result->title}");
                }
            }
            
            Log::info("Telegram notifications sent for campaign {$this->campaign->name}: {$sentCount} of {$results->count()} messages");
        } catch (\Exception $e) {
            Log::error("Error sending notifications: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
    
    /**
     * Format a single ad message with emoji and rich formatting.
     */
    protected function formatAdMessage(CampaignResult $result): string
    {
        $message = "📢 *{$result->title}*\n\n";
        
        // Price with emoji based on type
        if ($result->price) {
            $message .= "💰 *Prezzo:* {$result->price}\n";
        }
        
        // Location with emoji
        if ($result->location) {
            $message .= "📍 *Luogo:* {$result->location}\n";
        }
        
        // Date with emoji
        if ($result->date) {
            $message .= "📅 *Data:* {$result->date}\n";
        }
        
        // Status (Available/Sold) with emoji
        $statusEmoji = ($result->stato == 'Disponibile') ? '✅' : '❌';
        $message .= "{$statusEmoji} *Stato:* {$result->stato}\n";
        
        // Shipping info with emoji
        $shippingEmoji = $result->spedizione ? '🚚' : '🏪';
        $shippingText = $result->spedizione ? 'Disponibile' : 'Ritiro in zona';
        $message .= "{$shippingEmoji} *Spedizione:* {$shippingText}\n";
        
        // Add link with call to action
        $message .= "\n🔗 [Vedi Annuncio]({$result->link})\n";
        
        // Add campaign info
        $message .= "\n📊 _Dalla campagna \"{$this->campaign->name}\"_";
        
        return $message;
    }
    
    /**
     * Send a message to Telegram.
     * 
     * @return bool Success status
     */
    protected function sendTelegramMessage($settings, string $message): bool
    {
        $telegramApiUrl = "https://api.telegram.org/bot{$settings->telegram_token}/sendMessage";
        Log::info("Sending Telegram message to URL: " . str_replace($settings->telegram_token, '[HIDDEN]', $telegramApiUrl));
        
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
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode != 200) {
            Log::error("Failed to send Telegram notification: HTTP $httpCode, Error: $error");
            Log::error("Response: $response");
            return false;
        }
        
        Log::info("Telegram API response: HTTP $httpCode - Success");
        return true;
    }
}
