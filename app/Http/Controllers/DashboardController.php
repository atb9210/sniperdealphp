<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignResult;
use App\Models\JobLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with campaign statistics.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Get campaign statistics
        $totalCampaigns = Campaign::where('user_id', $user->id)->count();
        $activeCampaigns = Campaign::where('user_id', $user->id)->where('is_active', true)->count();
        
        // Get recent campaigns
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get recent results
        $recentResults = CampaignResult::whereHas('campaign', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('is_new', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get recent job logs
        $recentJobLogs = JobLog::whereHas('campaign', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Calculate statistics
        $totalResults = CampaignResult::whereHas('campaign', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();
        
        $soldResults = CampaignResult::whereHas('campaign', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('stato', 'Venduto')
            ->count();
        
        $sellThroughRate = $totalResults > 0 ? round(($soldResults / $totalResults) * 100, 1) : 0;
        
        return view('dashboard', compact(
            'totalCampaigns', 
            'activeCampaigns', 
            'recentCampaigns', 
            'recentResults', 
            'recentJobLogs',
            'totalResults',
            'soldResults',
            'sellThroughRate'
        ));
    }
} 