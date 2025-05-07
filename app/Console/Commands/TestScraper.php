<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubitoScraper;

class TestScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:test {keyword} {--pages=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Subito.it scraper with a given keyword';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keyword = $this->argument('keyword');
        $pages = $this->option('pages');
        $this->info("Testing scraper with keyword: {$keyword}, pages: {$pages}");

        $scraper = new SubitoScraper();
        $results = $scraper->scrape($keyword, false, $pages);

        $jsonPath = storage_path('app/scraper_results.json');
        file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Risultati salvati in: $jsonPath");

        // Mostra un estratto dei primi 2 risultati
        $this->info("Esempio risultati:");
        foreach (array_slice($results, 0, 2) as $index => $ad) {
            $this->line("\nAd #" . ($index + 1));
            $this->line("Titolo: " . ($ad['title'] ?? 'N/A'));
            $this->line("Prezzo: " . ($ad['price'] ?? 'N/A'));
            $this->line("Località: " . ($ad['location'] ?? 'N/A'));
            $this->line("Link: " . ($ad['link'] ?? 'N/A'));
            $this->line("Immagine: " . ($ad['image'] ?? 'N/A'));
        }
    }
}
