<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 'campaign_id', 'campaign_result_id', 'date', 'product',
        'sku', 'link', 'contact', 'sale_amount', 'product_cost', 
        'advertising_cost', 'shipping_cost', 'other_costs', 'status', 'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'sale_amount' => 'decimal:2',
        'product_cost' => 'decimal:2',
        'advertising_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Get the user that owns the deal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign that owns the deal.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the campaign result that the deal is based on.
     */
    public function campaignResult()
    {
        return $this->belongsTo(CampaignResult::class);
    }

    /**
     * Get the profit of the deal.
     */
    public function getProfitAttribute()
    {
        if ($this->status !== 'sold' || !$this->sale_amount) {
            return null;
        }
        
        return $this->sale_amount - (
            $this->product_cost + 
            $this->advertising_cost + 
            $this->shipping_cost + 
            $this->other_costs
        );
    }

    /**
     * Get the margin percentage of the deal.
     */
    public function getMarginPercentageAttribute()
    {
        if (!$this->profit || !$this->sale_amount) {
            return null;
        }
        
        return ($this->profit / $this->sale_amount) * 100;
    }

    /**
     * Get the total cost of the deal.
     */
    public function getTotalCostAttribute()
    {
        return $this->product_cost + 
               $this->advertising_cost + 
               $this->shipping_cost + 
               $this->other_costs;
    }
}
