<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\JobLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JobLogController extends Controller
{
    /**
     * Display a listing of the job logs.
     */
    public function index(): View
    {
        $campaigns = Campaign::where('user_id', Auth::id())->pluck('id');
        
        $jobLogs = JobLog::whereIn('campaign_id', $campaigns)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('job-logs.index', compact('jobLogs'));
    }

    /**
     * Display job logs for a specific campaign.
     */
    public function forCampaign(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $jobLogs = JobLog::where('campaign_id', $campaign->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('job-logs.campaign', compact('campaign', 'jobLogs'));
    }

    /**
     * Display the specified job log.
     */
    public function show(JobLog $jobLog): View
    {
        $campaign = $jobLog->campaign;
        $this->authorize('view', $campaign);

        return view('job-logs.show', compact('jobLog', 'campaign'));
    }

    /**
     * Clear job logs for a specific campaign.
     */
    public function clear(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        // Keep only the last 5 logs
        $keepLogs = JobLog::where('campaign_id', $campaign->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->pluck('id');

        JobLog::where('campaign_id', $campaign->id)
            ->whereNotIn('id', $keepLogs)
            ->delete();

        return redirect()->route('job-logs.campaign', $campaign)
            ->with('success', 'Log dei job eliminati con successo, mantenuti solo gli ultimi 5.');
    }
}
