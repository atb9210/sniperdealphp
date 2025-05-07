<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Services\SubitoScraper;
use Illuminate\Http\Request;
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
        return view('keyword-form.index', compact('keywords'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'keyword' => 'required|string|max:255',
            ]);

            $keyword = Keyword::create($validated);
            
            // Esegui lo scraping
            $pages = (int)($request->input('pages', 3));
            $qso = $request->has('qso') && $request->input('qso') ? true : false;
            $ads = $this->scraper->scrape($keyword->keyword, $qso, $pages);

            if (empty($ads)) {
                return redirect()->route('keyword.index')
                    ->with('warning', 'No ads found for this keyword.')
                    ->with('keyword', $keyword->keyword)
                    ->with('qso', $qso)
                    ->with('pages', $pages);
            }

            return redirect()->route('keyword.index')
                ->with('success', 'Found ' . count($ads) . ' ads for keyword: ' . $keyword->keyword)
                ->with('ads', $ads)
                ->with('keyword', $keyword->keyword)
                ->with('qso', $qso)
                ->with('pages', $pages);

        } catch (\Exception $e) {
            Log::error('Error in KeywordFormController: ' . $e->getMessage());
            return redirect()->route('keyword.index')
                ->with('error', 'An error occurred while searching for ads. Please try again.');
        }
    }
}
