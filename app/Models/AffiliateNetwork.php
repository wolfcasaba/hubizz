<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'api_key',
        'api_secret',
        'tracking_id',
        'commission_rate',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'config' => 'array',
    ];

    /**
     * Get all products for this network.
     */
    public function products(): HasMany
    {
        return $this->hasMany(AffiliateProduct::class, 'network_id');
    }

    /**
     * Scope a query to only include active networks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get active products for this network.
     */
    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }
}
