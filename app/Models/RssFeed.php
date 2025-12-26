<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RssFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'title',
        'category_id',
        'fetch_interval',
        'is_active',
        'priority',
        'last_checked_at',
        'last_success_at',
        'fail_count',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_checked_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    /**
     * Get the category that owns the RSS feed.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all imports for this RSS feed.
     */
    public function imports(): HasMany
    {
        return $this->hasMany(RssImport::class);
    }

    /**
     * Scope a query to only include active feeds.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find feeds due for checking.
     */
    public function scopeDueForCheck($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere(function($q2) {
                      // 15min feeds
                      $q2->where('fetch_interval', '15min')
                         ->where('last_checked_at', '<', now()->subMinutes(15));
                  })
                  ->orWhere(function($q2) {
                      // Hourly feeds
                      $q2->where('fetch_interval', 'hourly')
                         ->where('last_checked_at', '<', now()->subHour());
                  })
                  ->orWhere(function($q2) {
                      // Daily feeds
                      $q2->where('fetch_interval', 'daily')
                         ->where('last_checked_at', '<', now()->subDay());
                  });
            })
            ->orderBy('priority', 'desc');
    }

    /**
     * Mark feed as checked.
     */
    public function markAsChecked(bool $success = true): void
    {
        $this->last_checked_at = now();
        if ($success) {
            $this->last_success_at = now();
            $this->fail_count = 0;
        } else {
            $this->fail_count++;
        }
        $this->save();
    }
}
