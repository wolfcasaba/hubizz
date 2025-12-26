<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_id',
        'external_id',
        'name',
        'description',
        'price',
        'currency',
        'image_url',
        'affiliate_url',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the network that owns the product.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(AffiliateNetwork::class, 'network_id');
    }

    /**
     * Get all affiliate links for this product.
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(AffiliateLink::class, 'product_id');
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Search products by name or description.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->whereFullText(['name', 'description'], $term);
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->price, 2);
    }
}
