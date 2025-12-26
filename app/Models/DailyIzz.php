<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DailyIzz extends Model
{
    use HasFactory;

    protected $table = 'daily_izz';

    protected $fillable = [
        'date',
        'post_ids',
        'summary',
        'total_views',
        'total_shares',
        'curated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'post_ids' => 'array',
        'total_views' => 'integer',
        'total_shares' => 'integer',
        'curated_at' => 'datetime',
    ];

    /**
     * Get the posts for this Daily Izz.
     */
    public function posts(): Collection
    {
        if (empty($this->post_ids)) {
            return collect([]);
        }

        return Post::whereIn('id', $this->post_ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $this->post_ids) . ')')
            ->get();
    }

    /**
     * Scope a query to get today's Daily Izz.
     */
    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    /**
     * Scope a query to get latest Daily Izz.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }

    /**
     * Get or create today's Daily Izz.
     */
    public static function getOrCreateToday(): self
    {
        return static::firstOrCreate(
            ['date' => today()],
            [
                'post_ids' => [],
                'total_views' => 0,
                'total_shares' => 0,
            ]
        );
    }

    /**
     * Curate top posts for the day.
     */
    public function curateTopPosts(int $count = 5): void
    {
        $topPosts = ContentScore::viral(60)
            ->with('post')
            ->whereHas('post', function ($query) {
                $query->whereDate('created_at', today());
            })
            ->limit($count)
            ->get()
            ->pluck('post_id')
            ->toArray();

        $this->update([
            'post_ids' => $topPosts,
            'curated_at' => now(),
        ]);

        $this->calculateTotals();
    }

    /**
     * Calculate total views and shares.
     */
    public function calculateTotals(): void
    {
        $scores = ContentScore::whereIn('post_id', $this->post_ids)->get();

        $this->update([
            'total_views' => $scores->sum('views'),
            'total_shares' => $scores->sum('shares'),
        ]);
    }
}
