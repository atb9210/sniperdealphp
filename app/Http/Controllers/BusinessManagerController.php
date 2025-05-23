<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Campaign;
use App\Models\CampaignResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Deal::where('user_id', Auth::id());

        // Filtri
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $deals = $query->latest('date')->paginate(15);
        
        // Metriche per la dashboard
        $stockValue = Deal::where('user_id', Auth::id())
            ->where('status', 'in_stock')
            ->sum('product_cost');
            
        $soldValue = Deal::where('user_id', Auth::id())
            ->where('status', 'sold')
            ->sum('sale_amount');
            
        $soldDeals = Deal::where('user_id', Auth::id())
            ->where('status', 'sold')
            ->get();
            
        $totalProfit = $soldDeals->sum(function ($deal) {
            return $deal->profit ?? 0;
        });
        
        // Campagne per il filtro
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        
        return view('business.index', compact('deals', 'stockValue', 'soldValue', 'totalProfit', 'campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        return view('business.create', compact('campaigns'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'nullable|exists:campaigns,id',
            'campaign_result_id' => 'nullable|exists:campaign_results,id',
            'date' => 'required|date|before_or_equal:today',
            'product' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:100',
            'sale_amount' => 'nullable|numeric|min:0',
            'product_cost' => 'required|numeric|min:0',
            'advertising_cost' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'status' => 'required|in:in_stock,sold',
            'notes' => 'nullable|string',
        ]);
        
        // Validazioni condizionali
        if ($validated['status'] === 'sold' && empty($validated['sale_amount'])) {
            return back()->withErrors(['sale_amount' => 'L\'importo di vendita è obbligatorio per i deal venduti.'])
                         ->withInput();
        }
        
        // Verifica consistenza tra campaign_id e campaign_result_id
        if (!empty($validated['campaign_result_id'])) {
            $result = CampaignResult::find($validated['campaign_result_id']);
            if ($result && (!empty($validated['campaign_id']) && $result->campaign_id != $validated['campaign_id'])) {
                return back()->withErrors(['campaign_result_id' => 'Il risultato selezionato non appartiene alla campagna specificata.'])
                             ->withInput();
            }
        }
        
        // Aggiungi user_id
        $validated['user_id'] = Auth::id();
        
        Deal::create($validated);
        
        return redirect()->route('business.index')
                         ->with('success', 'Deal creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deal $deal)
    {
        // Controllo autorizzazione
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }
        
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        
        // Carica i risultati della campagna associata se presente
        $campaignResults = [];
        if ($deal->campaign_id) {
            $campaignResults = CampaignResult::where('campaign_id', $deal->campaign_id)->get();
        }
        
        return view('business.edit', compact('deal', 'campaigns', 'campaignResults'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Deal $deal)
    {
        // Controllo autorizzazione
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }
        
        $validated = $request->validate([
            'campaign_id' => 'nullable|exists:campaigns,id',
            'campaign_result_id' => 'nullable|exists:campaign_results,id',
            'date' => 'required|date|before_or_equal:today',
            'product' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:100',
            'sale_amount' => 'nullable|numeric|min:0',
            'product_cost' => 'required|numeric|min:0',
            'advertising_cost' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'status' => 'required|in:in_stock,sold',
            'notes' => 'nullable|string',
        ]);
        
        // Validazioni condizionali
        if ($validated['status'] === 'sold' && empty($validated['sale_amount'])) {
            return back()->withErrors(['sale_amount' => 'L\'importo di vendita è obbligatorio per i deal venduti.'])
                         ->withInput();
        }
        
        // Verifica consistenza tra campaign_id e campaign_result_id
        if (!empty($validated['campaign_result_id'])) {
            $result = CampaignResult::find($validated['campaign_result_id']);
            if ($result && (!empty($validated['campaign_id']) && $result->campaign_id != $validated['campaign_id'])) {
                return back()->withErrors(['campaign_result_id' => 'Il risultato selezionato non appartiene alla campagna specificata.'])
                             ->withInput();
            }
        }
        
        $deal->update($validated);
        
        return redirect()->route('business.index')
                         ->with('success', 'Deal aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal)
    {
        // Controllo autorizzazione
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }
        
        $deal->delete();
        
        return redirect()->route('business.index')
                         ->with('success', 'Deal eliminato con successo.');
    }
    
    /**
     * Mark a deal as sold.
     */
    public function markAsSold(Request $request, Deal $deal)
    {
        // Controllo autorizzazione
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }
        
        $validated = $request->validate([
            'sale_amount' => 'required|numeric|min:0',
        ]);
        
        $deal->update([
            'status' => 'sold',
            'sale_amount' => $validated['sale_amount'],
        ]);
        
        return redirect()->route('business.index')
                         ->with('success', 'Deal segnato come venduto.');
    }
    
    /**
     * Create a deal from a campaign result.
     */
    public function createFromResult(CampaignResult $result)
    {
        // Controllo autorizzazione
        $campaign = Campaign::find($result->campaign_id);
        if (!$campaign || $campaign->user_id !== Auth::id()) {
            abort(403);
        }
        
        // Ottiene le campagne dell'utente per il form
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        
        // Pre-popola il form con i dati del risultato
        $deal = new Deal([
            'campaign_id' => $result->campaign_id,
            'campaign_result_id' => $result->id,
            'date' => now(),
            'product' => $result->title,
            'link' => $result->link,
            'product_cost' => 0, // Deve essere impostato dall'utente
        ]);
        
        return view('business.create', compact('deal', 'campaigns', 'result'));
    }
}
