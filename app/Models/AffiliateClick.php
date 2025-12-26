<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'country_code',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the affiliate link that owns the click.
     */
    public function affiliateLink(): BelongsTo
    {
        return $this->belongsTo(AffiliateLink::class, 'link_id');
    }

    /**
     * Get the user that made the click.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('clicked_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get today's clicks.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('clicked_at', today());
    }
}
