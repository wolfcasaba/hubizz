<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'input',
        'output',
        'model',
        'tokens_used',
        'cost',
        'post_id',
        'user_id',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'cost' => 'decimal:4',
    ];

    /**
     * Get the post associated with this generation.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who triggered this generation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to get recent generations.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get total cost for a period.
     */
    public static function getTotalCost(\DateTime $startDate = null, \DateTime $endDate = null): float
    {
        $query = static::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return (float) $query->sum('cost');
    }

    /**
     * Get total tokens used for a period.
     */
    public static function getTotalTokens(\DateTime $startDate = null, \DateTime $endDate = null): int
    {
        $query = static::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return (int) $query->sum('tokens_used');
    }
}
