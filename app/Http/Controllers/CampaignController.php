<?php

namespace App\Http\Controllers;

use App\Jobs\SubitoScraperJob;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display a listing of the campaigns.
     */
    public function index(): View
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): View
    {
        return view('campaigns.create');
    }

    /**
     * Store a newly created campaign in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'max_pages' => 'required|integer|min:1|max:10',
            'interval_minutes' => 'required|integer|min:1',
            'qso' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;
        $validated['qso'] = $request->has('qso');

        $campaign = Campaign::create($validated);

        // Dispatch job immediately for first run
        SubitoScraperJob::dispatch($campaign);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campagna creata con successo. Il primo job è stato avviato.');
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        // Get campaign results
        $results = $campaign->results()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('campaigns.show', compact('campaign', 'results'));
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        return view('campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified campaign in storage.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'max_pages' => 'required|integer|min:1|max:10',
            'interval_minutes' => 'required|integer|min:1',
            'qso' => 'boolean',
        ]);

        $validated['qso'] = $request->has('qso');

        $campaign->update($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campagna aggiornata con successo.');
    }

    /**
     * Remove the specified campaign from storage.
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campagna eliminata con successo.');
    }

    /**
     * Toggle the active status of the campaign.
     */
    public function toggle(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->is_active = !$campaign->is_active;
        $campaign->save();

        $status = $campaign->is_active ? 'attivata' : 'disattivata';

        return redirect()->route('campaigns.index')
            ->with('success', "Campagna {$status} con successo.");
    }

    /**
     * Run the campaign job manually.
     */
    public function run(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        SubitoScraperJob::dispatch($campaign);

        return redirect()->route('campaigns.index')
            ->with('success', 'Job avviato manualmente per la campagna.');
    }
}
