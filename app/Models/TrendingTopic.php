<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendingTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'source',
        'score',
        'region',
        'category_id',
        'is_used',
        'post_id',
        'metadata',
        'trending_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'metadata' => 'array',
        'trending_at' => 'datetime',
    ];

    /**
     * Get the category for this trending topic.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the post created from this topic.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope a query to only include unused topics.
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope a query to order by score.
     */
    public function scopeHighScore($query, int $minScore = 50)
    {
        return $query->where('score', '>=', $minScore)
            ->orderBy('score', 'desc');
    }

    /**
     * Scope a query to filter by source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to get recent trending topics.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('trending_at', '>=', now()->subHours($hours));
    }

    /**
     * Mark topic as used.
     */
    public function markAsUsed(Post $post): void
    {
        $this->update([
            'is_used' => true,
            'post_id' => $post->id,
        ]);
    }
}
