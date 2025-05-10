<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\UserSetting;
use App\Services\SubitoScraper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KeywordFormController extends Controller
{
    protected $scraper;

    public function __construct(SubitoScraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function index()
    {
        $keywords = Keyword::latest()->get();
        
        // Controlla se l'utente ha proxy configurati
        $userSettings = UserSetting::where('user_id', Auth::id())->first();
        $hasProxies = $userSettings && $userSettings->hasActiveProxies();
        $proxyCount = $userSettings ? count($userSettings->active_proxies) : 0;
        
        return view('keyword-form.index', compact('keywords', 'hasProxies', 'proxyCount'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'keyword' => 'required|string|max:255',
            ]);

            $keyword = Keyword::create($validated);
            
            // Ottieni le opzioni di ricerca
            $pages = (int)($request->input('pages', 3));
            $qso = $request->has('qso') && $request->input('qso') ? true : false;
            $useProxy = $request->has('use_proxy') && $request->input('use_proxy') ? true : false;
            
            // Esegui lo scraping
            $ads = $this->scraper->scrape($keyword->keyword, $qso, $pages, $useProxy);
            
            // Ottieni informazioni sul proxy utilizzato
            $proxyInfo = $this->scraper->getProxyInfo();
            
            if (empty($ads)) {
                return redirect()->route('keyword.index')
                    ->with('warning', 'No ads found for this keyword.')
                    ->with('keyword', $keyword->keyword)
                    ->with('qso', $qso)
                    ->with('pages', $pages)
                    ->with('use_proxy', $useProxy)
                    ->with('proxy_info', $proxyInfo);
            }

            return redirect()->route('keyword.index')
                ->with('success', 'Found ' . count($ads) . ' ads for keyword: ' . $keyword->keyword)
                ->with('ads', $ads)
                ->with('keyword', $keyword->keyword)
                ->with('qso', $qso)
                ->with('pages', $pages)
                ->with('use_proxy', $useProxy)
                ->with('proxy_info', $proxyInfo);

        } catch (\Exception $e) {
            Log::error('Error in KeywordFormController: ' . $e->getMessage());
            return redirect()->route('keyword.index')
                ->with('error', 'An error occurred while searching for ads. Please try again.');
        }
    }
}
