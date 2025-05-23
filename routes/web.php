<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KeywordFormController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\JobLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkerMonitorController;
use App\Http\Controllers\BusinessManagerController;
use App\Http\Controllers\CampaignResultsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Keyword Form Routes
    Route::get('/keyword', [KeywordFormController::class, 'index'])->name('keyword.index');
    Route::post('/keyword', [KeywordFormController::class, 'store'])->name('keyword.store');
    
    // User Settings Routes
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-telegram', [UserSettingsController::class, 'testTelegram'])->name('settings.test-telegram');
    Route::post('/settings/test-proxy', [UserSettingsController::class, 'testProxy'])->name('settings.test-proxy');
    Route::get('/settings/dump-proxies', [UserSettingsController::class, 'dumpProxies'])->name('settings.dump-proxies');

    // Campaign Routes
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/toggle', [CampaignController::class, 'toggle'])->name('campaigns.toggle');
    Route::post('/campaigns/{campaign}/run', [CampaignController::class, 'run'])->name('campaigns.run');

    // Campaign Results Routes
    Route::get('/campaign-results', [CampaignResultsController::class, 'index'])->name('campaign-results.index');

    // Job Log Routes
    Route::get('/job-logs', [JobLogController::class, 'index'])->name('job-logs.index');
    Route::get('/job-logs/{jobLog}', [JobLogController::class, 'show'])->name('job-logs.show');
    Route::get('/campaigns/{campaign}/job-logs', [JobLogController::class, 'forCampaign'])->name('job-logs.campaign');
    Route::post('/campaigns/{campaign}/job-logs/clear', [JobLogController::class, 'clear'])->name('job-logs.clear');

    // Worker Monitor
    Route::get('/worker-monitor', [WorkerMonitorController::class, 'index'])
        ->name('worker-monitor.index');

    // Business Manager Routes
    Route::get('/business', [BusinessManagerController::class, 'index'])
         ->name('business.index');
    
    Route::get('/business/create', [BusinessManagerController::class, 'create'])
         ->name('business.create');
    
    Route::post('/business', [BusinessManagerController::class, 'store'])
         ->name('business.store');
    
    Route::get('/business/{deal}/edit', [BusinessManagerController::class, 'edit'])
         ->name('business.edit');
    
    Route::put('/business/{deal}', [BusinessManagerController::class, 'update'])
         ->name('business.update');
    
    Route::delete('/business/{deal}', [BusinessManagerController::class, 'destroy'])
         ->name('business.destroy');
    
    Route::put('/business/{deal}/mark-as-sold', [BusinessManagerController::class, 'markAsSold'])
         ->name('business.markAsSold');
    
    Route::post('/business/from-campaign-result/{result}', [BusinessManagerController::class, 'createFromResult'])
         ->name('business.createFromResult');
});

require __DIR__.'/auth.php';
