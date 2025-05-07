<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignResult extends Model
{
    protected $fillable = [
        'campaign_id',
        'title',
        'price',
        'location',
        'date',
        'link',
        'image',
        'stato',
        'spedizione',
        'notified',
        'is_new',
        'extra_data',
    ];

    protected $casts = [
        'spedizione' => 'boolean',
        'notified' => 'boolean',
        'is_new' => 'boolean',
        'extra_data' => 'array',
    ];

    /**
     * Get the campaign that owns the result.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the numeric price value.
     */
    public function getNumericPriceAttribute()
    {
        if (empty($this->price)) {
            return null;
        }

        $price = preg_replace('/[^\d,.]/', '', $this->price);
        $price = str_replace(',', '.', $price);
        
        return (float) $price;
    }

    /**
     * Check if the result matches the campaign price criteria.
     */
    public function matchesPriceCriteria(): bool
    {
        $numericPrice = $this->numeric_price;
        
        if ($numericPrice === null) {
            return true; // If no price, we can't filter it out
        }

        $campaign = $this->campaign;
        $minPrice = $campaign->min_price;
        $maxPrice = $campaign->max_price;

        if ($minPrice && $numericPrice < $minPrice) {
            return false;
        }

        if ($maxPrice && $numericPrice > $maxPrice) {
            return false;
        }

        return true;
    }

    /**
     * Mark the result as notified.
     */
    public function markAsNotified(): void
    {
        $this->notified = true;
        $this->save();
    }

    /**
     * Mark the result as not new.
     */
    public function markAsNotNew(): void
    {
        $this->is_new = false;
        $this->save();
    }
}
