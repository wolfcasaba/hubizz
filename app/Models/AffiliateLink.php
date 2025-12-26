<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'product_id',
        'short_code',
        'original_url',
        'cloaked_url',
        'clicks',
        'conversions',
        'revenue',
        'utm_parameters',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
        'utm_parameters' => 'array',
    ];

    /**
     * Boot the model and generate short code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($link) {
            if (empty($link->short_code)) {
                $link->short_code = static::generateShortCode();
            }
            if (empty($link->cloaked_url)) {
                $link->cloaked_url = '/go/' . $link->short_code;
            }
        });
    }

    /**
     * Get the post that owns the affiliate link.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the product that owns the affiliate link.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class, 'product_id');
    }

    /**
     * Get all clicks for this affiliate link.
     */
    public function affiliateClicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class, 'link_id');
    }

    /**
     * Generate a unique short code.
     */
    protected static function generateShortCode(): string
    {
        do {
            $code = Str::random(8);
        } while (static::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Increment click count.
     */
    public function recordClick(User $user = null, string $ipAddress = null, string $userAgent = null): AffiliateClick
    {
        $this->increment('clicks');

        return $this->affiliateClicks()->create([
            'user_id' => $user?->id,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer' => request()->header('referer'),
            'clicked_at' => now(),
        ]);
    }

    /**
     * Record a conversion.
     */
    public function recordConversion(float $amount): void
    {
        $this->increment('conversions');
        $this->increment('revenue', $amount);
    }

    /**
     * Get conversion rate percentage.
     */
    public function getConversionRateAttribute(): float
    {
        return $this->clicks > 0 ? ($this->conversions / $this->clicks) * 100 : 0;
    }
}
