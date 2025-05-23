<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignResultsController extends Controller
{
    /**
     * Mostra i risultati di tutte le campagne con filtri.
     */
    public function index(Request $request)
    {
        $query = CampaignResult::whereHas('campaign', function ($query) {
            $query->where('user_id', Auth::id());
        });

        // Filtri
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ordinamento
        $query->orderBy('created_at', 'desc');

        $results = $query->paginate(15);
        
        // Campagne per il filtro
        $campaigns = Campaign::where('user_id', Auth::id())->get();
        
        return view('campaign-results.index', compact('results', 'campaigns'));
    }
} 