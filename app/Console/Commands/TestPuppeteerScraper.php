<?php

namespace App\Console\Commands;

use App\Services\SubitoScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPuppeteerScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:puppeteer 
                            {keyword : Keyword to search for}
                            {--qso : Use quick sell only filter}
                            {--pages=3 : Number of pages to scrape}
                            {--proxy : Use proxy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Puppeteer-based Subito.it scraper';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Puppeteer-based Subito.it scraper');
        
        $keyword = $this->argument('keyword');
        $qso = $this->option('qso');
        $pages = $this->option('pages');
        $useProxy = $this->option('proxy');
        
        $this->info("Parameters:");
        $this->info("- Keyword: {$keyword}");
        $this->info("- Quick Sell Only: " . ($qso ? 'Yes' : 'No'));
        $this->info("- Pages: {$pages}");
        $this->info("- Use Proxy: " . ($useProxy ? 'Yes' : 'No'));
        
        $scraper = new SubitoScraper();
        
        // Get proxy info before scraping
        if ($useProxy) {
            $this->info("Checking proxy status...");
            $proxyInfo = $scraper->getProxyInfo();
            
            if ($proxyInfo['using_proxy']) {
                $this->info("Using proxy: " . $proxyInfo['proxy']);
                $this->info("Proxy IP: " . $proxyInfo['proxy_ip']);
                $this->info("Local IP: " . $proxyInfo['local_ip']);
                
                if (!$proxyInfo['proxy_working']) {
                    $this->warn("Warning: Proxy may not be working correctly!");
                }
            } else {
                $this->warn("No working proxy found, will continue without proxy");
            }
        }
        
        $this->info("Starting scraper...");
        $startTime = microtime(true);
        
        try {
            $results = $scraper->scrape($keyword, $qso, $pages, $useProxy);
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->info("Scraping completed in {$duration} seconds");
            $this->info("Found " . count($results) . " ads");
            
            // Display some sample results
            if (count($results) > 0) {
                $this->info("\nSample results:");
                $sample = array_slice($results, 0, min(5, count($results)));
                
                foreach ($sample as $index => $ad) {
                    $this->info("\n" . ($index + 1) . ". " . ($ad['title'] ?? 'No title'));
                    $this->info("   Price: " . ($ad['price'] ?? 'N/A'));
                    $this->info("   Location: " . ($ad['location'] ?? 'N/A'));
                    $this->info("   Status: " . ($ad['stato'] ?? 'N/A'));
                    $this->info("   Link: " . ($ad['link'] ?? 'N/A'));
                }
                
                // Save results to a JSON file for inspection
                $filename = storage_path('app/subito_results_' . time() . '.json');
                file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
                $this->info("\nComplete results saved to: " . $filename);
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error during scraping: " . $e->getMessage());
            Log::error("Puppeteer scraper error: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return 1;
        }
    }
} 