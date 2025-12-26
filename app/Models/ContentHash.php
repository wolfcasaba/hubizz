<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class ContentHash extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'title_hash',
        'content_hash',
    ];

    /**
     * Get the post that owns the content hash.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Generate hash for title.
     */
    public static function generateTitleHash(string $title): string
    {
        return hash('sha256', mb_strtolower(trim($title)));
    }

    /**
     * Generate hash for content.
     */
    public static function generateContentHash(string $content): string
    {
        // Remove HTML tags and normalize whitespace
        $cleanContent = preg_replace('/\s+/', ' ', strip_tags($content));
        return hash('sha256', mb_strtolower(trim($cleanContent)));
    }

    /**
     * Check if title exists.
     */
    public static function titleExists(string $title): bool
    {
        $hash = static::generateTitleHash($title);
        return static::where('title_hash', $hash)->exists();
    }

    /**
     * Check if content exists.
     */
    public static function contentExists(string $content): bool
    {
        $hash = static::generateContentHash($content);
        return static::where('content_hash', $hash)->exists();
    }

    /**
     * Check if similar content exists (returns similarity percentage).
     */
    public static function checkSimilarity(string $title, string $content): array
    {
        $titleHash = static::generateTitleHash($title);
        $contentHash = static::generateContentHash($content);

        $existingTitle = static::where('title_hash', $titleHash)->exists();
        $existingContent = static::where('content_hash', $contentHash)->exists();

        return [
            'title_duplicate' => $existingTitle,
            'content_duplicate' => $existingContent,
            'is_duplicate' => $existingTitle || $existingContent,
        ];
    }

    /**
     * Create or update hash for a post.
     */
    public static function createForPost(Post $post): self
    {
        return static::updateOrCreate(
            ['post_id' => $post->id],
            [
                'title_hash' => static::generateTitleHash($post->title),
                'content_hash' => static::generateContentHash($post->body ?? ''),
            ]
        );
    }
}
