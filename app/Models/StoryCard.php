<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'type',
        'content',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get the post that owns the story card.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get all reactions for this story card.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(StoryReaction::class, 'card_id');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get reaction counts.
     */
    public function getReactionCountsAttribute(): array
    {
        return [
            'hot' => $this->reactions()->where('reaction_type', 'hot')->count(),
            'not' => $this->reactions()->where('reaction_type', 'not')->count(),
            'hmm' => $this->reactions()->where('reaction_type', 'hmm')->count(),
        ];
    }

    /**
     * Get total reactions.
     */
    public function getTotalReactionsAttribute(): int
    {
        return $this->reactions()->count();
    }

    /**
     * Check if user has reacted.
     */
    public function hasUserReacted(User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $this->reactions()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get user's reaction.
     */
    public function getUserReaction(User $user): ?StoryReaction
    {
        return $this->reactions()
            ->where('user_id', $user->id)
            ->first();
    }
}
