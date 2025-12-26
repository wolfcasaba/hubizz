<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'rss_feed_id',
        'status',
        'items_found',
        'items_imported',
        'items_skipped',
        'error_message',
        'import_log',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'import_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the RSS feed that owns the import.
     */
    public function rssFeed(): BelongsTo
    {
        return $this->belongsTo(RssFeed::class);
    }

    /**
     * Scope a query to only include completed imports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed imports.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark import as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted(int $found, int $imported, int $skipped): void
    {
        $this->update([
            'status' => 'completed',
            'items_found' => $found,
            'items_imported' => $imported,
            'items_skipped' => $skipped,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }
}
