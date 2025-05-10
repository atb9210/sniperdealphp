<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobLog extends Model
{
    protected $fillable = [
        'campaign_id',
        'status',
        'results_count',
        'new_results_count',
        'message',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'new_results_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns the job log.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the duration of the job in seconds.
     */
    public function getDurationInSecondsAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get the formatted duration of the job.
     */
    public function getFormattedDurationAttribute()
    {
        $seconds = $this->duration_in_seconds;
        
        if ($seconds === null) {
            return 'N/A';
        }

        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return $minutes . ' min ' . $seconds . ' sec';
    }

    /**
     * Create a new job log for a campaign with 'running' status.
     */
    public static function createRunning(Campaign $campaign, ?string $message = null): self
    {
        return self::create([
            'campaign_id' => $campaign->id,
            'status' => 'running',
            'message' => $message ?? 'Job started',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the job log as completed successfully.
     */
    public function markAsCompleted(int $resultsCount, int $newResultsCount, ?string $message = null): void
    {
        $this->update([
            'status' => 'success',
            'results_count' => $resultsCount,
            'new_results_count' => $newResultsCount,
            'message' => $message ?? 'Job completed successfully',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the job log as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'error',
            'error' => $error,
            'message' => 'Job failed: ' . $error,
            'completed_at' => now(),
        ]);
    }
}
