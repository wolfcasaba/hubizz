<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'viral_score',
        'views',
        'shares',
        'comments',
        'reactions',
        'engagement_rate',
        'ctr',
        'last_calculated_at',
    ];

    protected $casts = [
        'viral_score' => 'integer',
        'views' => 'integer',
        'shares' => 'integer',
        'comments' => 'integer',
        'reactions' => 'integer',
        'engagement_rate' => 'decimal:2',
        'ctr' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the post that owns the content score.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope a query to get high performing content.
     */
    public function scopeViral($query, int $minScore = 70)
    {
        return $query->where('viral_score', '>=', $minScore)
            ->orderBy('viral_score', 'desc');
    }

    /**
     * Scope a query to get most viewed content.
     */
    public function scopeMostViewed($query, int $limit = 10)
    {
        return $query->orderBy('views', 'desc')->limit($limit);
    }

    /**
     * Calculate and update viral score.
     */
    public function calculateViralScore(): void
    {
        // Weighted scoring algorithm
        $viewScore = min(($this->views / 1000) * 20, 30); // Max 30 points
        $shareScore = min($this->shares * 5, 25); // Max 25 points
        $commentScore = min($this->comments * 3, 20); // Max 20 points
        $reactionScore = min($this->reactions * 2, 15); // Max 15 points
        $engagementBonus = $this->engagement_rate > 5 ? 10 : 0; // Bonus 10 points

        $viralScore = (int) ($viewScore + $shareScore + $commentScore + $reactionScore + $engagementBonus);

        $this->update([
            'viral_score' => min($viralScore, 100),
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Calculate engagement rate.
     */
    public function calculateEngagementRate(): void
    {
        if ($this->views > 0) {
            $totalEngagements = $this->shares + $this->comments + $this->reactions;
            $rate = ($totalEngagements / $this->views) * 100;

            $this->update([
                'engagement_rate' => round($rate, 2),
            ]);
        }
    }

    /**
     * Increment views.
     */
    public function incrementViews(int $count = 1): void
    {
        $this->increment('views', $count);
        $this->calculateEngagementRate();
        $this->calculateViralScore();
    }
}
