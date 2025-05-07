<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'keyword',
        'min_price',
        'max_price',
        'max_pages',
        'interval_minutes',
        'qso',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'min_price' => 'float',
        'max_price' => 'float',
        'max_pages' => 'integer',
        'interval_minutes' => 'integer',
        'qso' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the user that owns the campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the results for the campaign.
     */
    public function results(): HasMany
    {
        return $this->hasMany(CampaignResult::class);
    }

    /**
     * Get the job logs for the campaign.
     */
    public function jobLogs(): HasMany
    {
        return $this->hasMany(JobLog::class);
    }

    /**
     * Get the latest job log for the campaign.
     */
    public function latestJobLog()
    {
        return $this->jobLogs()->latest()->first();
    }

    /**
     * Get the latest successful job log for the campaign.
     */
    public function latestSuccessfulJobLog()
    {
        return $this->jobLogs()->where('status', 'success')->latest()->first();
    }

    /**
     * Check if the campaign is due to run.
     */
    public function isDueToRun(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->next_run_at) {
            return true;
        }

        return $this->next_run_at->isPast();
    }

    /**
     * Update the next run time based on the interval.
     */
    public function updateNextRunTime(): void
    {
        $this->last_run_at = now();
        $this->next_run_at = now()->addMinutes($this->interval_minutes);
        $this->save();
    }
}
