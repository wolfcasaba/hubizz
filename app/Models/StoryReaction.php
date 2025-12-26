<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'user_id',
        'ip_address',
        'reaction_type',
    ];

    /**
     * Get the story card that owns the reaction.
     */
    public function storyCard(): BelongsTo
    {
        return $this->belongsTo(StoryCard::class, 'card_id');
    }

    /**
     * Get the user who made the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by reaction type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('reaction_type', $type);
    }

    /**
     * Scope a query to get hot reactions.
     */
    public function scopeHot($query)
    {
        return $query->where('reaction_type', 'hot');
    }

    /**
     * Scope a query to get not reactions.
     */
    public function scopeNot($query)
    {
        return $query->where('reaction_type', 'not');
    }

    /**
     * Scope a query to get hmm reactions.
     */
    public function scopeHmm($query)
    {
        return $query->where('reaction_type', 'hmm');
    }

    /**
     * Get reaction emoji.
     */
    public function getEmojiAttribute(): string
    {
        return match($this->reaction_type) {
            'hot' => '🔥',
            'not' => '❄️',
            'hmm' => '🤔',
            default => '❓',
        };
    }
}
