<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'telegram_token',
        'proxies',
    ];

    protected $casts = [
        'proxies' => 'array',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get active proxies list (non-empty values)
     */
    public function getActiveProxiesAttribute(): array
    {
        if (empty($this->proxies)) {
            return [];
        }
        
        // Filtra solo i proxy non vuoti
        return array_values(array_filter($this->proxies, fn($proxy) => !empty($proxy)));
    }
    
    /**
     * Check if user has active proxies configured
     */
    public function hasActiveProxies(): bool
    {
        return count($this->active_proxies) > 0;
    }
}
