<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubitoScraper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestScraperProxy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:test-proxy 
                            {keyword : La keyword da cercare} 
                            {--pages=1 : Numero di pagine da scansionare} 
                            {--qso : Usa la ricerca specifica} 
                            {--proxy= : Usa un proxy specifico} 
                            {--proxy-file= : Carica proxy da file} 
                            {--debug : Abilita modalità debug} 
                            {--save-html : Salva HTML per debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa lo scraper con supporto proxy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keyword = $this->argument('keyword');
        $pages = $this->option('pages');
        $qso = $this->option('qso');
        $proxyString = $this->option('proxy');
        $proxyFile = $this->option('proxy-file');
        $debug = $this->option('debug');
        $saveHtml = $this->option('save-html');
        
        $this->info("Avvio test scraper per keyword: $keyword");
        $this->info("Opzioni: " . ($qso ? "QSO, " : "") . "$pages pagine, " . ($debug ? "Debug, " : "") . ($saveHtml ? "Salva HTML, " : "") . ($proxyString ? "Proxy: $proxyString" : ""));
        
        // Inizializza lo scraper
        $scraper = new SubitoScraper();
        
        $this->info("Avvio scraping...");
        $startTime = microtime(true);
        
        try {
            // Esegui lo scraping
            $useProxy = $proxyString || $proxyFile;
            $forcedProxy = $proxyString ?: null;
            Log::info("Starting scraping for keyword: '$keyword'" . 
                     ($qso ? ' (qso)' : '') . 
                     ", pages: $pages" . 
                     ($useProxy ? ", using proxy" : ", without proxy"));
            
            $results = $scraper->scrape($keyword, $qso, $pages, $useProxy, $forcedProxy);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            Log::info("Scraping completed for '$keyword'. Found " . count($results) . " ads");
            
            $this->info("Scraping completato in $executionTime secondi");
            $this->info("Trovati " . count($results) . " annunci");
            
            // Mostra i risultati
            if (count($results) > 0) {
                $this->table(
                    ['Titolo', 'Prezzo', 'Località', 'Data', 'Stato', 'Spedizione'],
                    array_map(function($ad) {
                        return [
                            substr($ad['title'] ?? 'N/A', 0, 30),
                            $ad['price'] ?? 'N/A',
                            substr($ad['location'] ?? 'N/A', 0, 20),
                            $ad['date'] ?? 'N/A',
                            $ad['stato'] ?? 'N/A',
                            $ad['spedizione'] ? 'Sì' : 'No'
                        ];
                    }, $results)
                );
            }
            
            // Se in modalità debug, salva i risultati completi
            if ($debug) {
                $jsonResults = json_encode($results, JSON_PRETTY_PRINT);
                Storage::put('scraper_results.json', $jsonResults);
                $this->info("Risultati completi salvati in storage/app/scraper_results.json");
            }
            
            // Mostra informazioni sul proxy
            if ($useProxy) {
                $proxyInfo = $scraper->getProxyInfo();
                $this->info("Informazioni proxy:");
                $this->line("- Proxy attuale: " . ($proxyInfo['proxy'] ?? 'Nessuno'));
                $this->line("- Proxy funzionante: " . ($proxyInfo['proxy_working'] ? 'Sì' : 'No'));
                $this->line("- IP proxy: " . ($proxyInfo['proxy_ip'] ?? 'N/A'));
                $this->line("- IP locale: " . ($proxyInfo['local_ip'] ?? 'N/A'));
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Scraping error for '$keyword': " . $e->getMessage());
            $this->error("Errore durante lo scraping: " . $e->getMessage());
            
            if ($debug) {
                $this->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
} 