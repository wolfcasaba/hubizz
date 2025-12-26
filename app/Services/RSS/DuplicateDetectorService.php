<?php

namespace App\Services\RSS;

use App\Models\ContentHash;
use App\Models\Post;
use App\Services\BaseService;

/**
 * Duplicate Detector Service
 *
 * Detects duplicate content using hash-based comparison and similarity algorithms.
 */
class DuplicateDetectorService extends BaseService
{
    /**
     * Check if content is duplicate.
     *
     * @param string $title
     * @param string $content
     * @return array{is_duplicate: bool, title_match: bool, content_match: bool, similarity: float, existing_post_id: int|null}
     */
    public function isDuplicate(string $title, string $content): array
    {
        // Generate hashes
        $titleHash = ContentHash::generateTitleHash($title);
        $contentHash = ContentHash::generateContentHash($content);

        // Check exact matches
        $titleMatch = ContentHash::where('title_hash', $titleHash)->first();
        $contentMatch = ContentHash::where('content_hash', $contentHash)->first();

        if ($titleMatch || $contentMatch) {
            $this->logInfo('Exact duplicate detected', [
                'title_match' => (bool) $titleMatch,
                'content_match' => (bool) $contentMatch,
            ]);

            return [
                'is_duplicate' => true,
                'title_match' => (bool) $titleMatch,
                'content_match' => (bool) $contentMatch,
                'similarity' => 100.0,
                'existing_post_id' => $titleMatch?->post_id ?? $contentMatch?->post_id,
                'match_type' => 'exact',
            ];
        }

        // Check similarity with existing content
        $similarityCheck = $this->checkSimilarity($title, $content);

        if ($similarityCheck['is_duplicate']) {
            $this->logInfo('Similar content detected', [
                'similarity' => $similarityCheck['similarity'],
            ]);
        }

        return $similarityCheck;
    }

    /**
     * Check content similarity with existing posts.
     *
     * @param string $title
     * @param string $content
     * @return array
     */
    protected function checkSimilarity(string $title, string $content): array
    {
        $threshold = config('hubizz.rss.duplicate_threshold', 0.85);

        // Get recent posts (last 30 days)
        $recentPosts = Post::where('created_at', '>=', now()->subDays(30))
            ->latest()
            ->take(100)
            ->get();

        $maxSimilarity = 0;
        $mostSimilarPost = null;

        foreach ($recentPosts as $post) {
            $titleSimilarity = $this->calculateSimilarity($title, $post->title);
            $contentSimilarity = $this->calculateSimilarity(
                $this->normalizeContent($content),
                $this->normalizeContent($post->body ?? '')
            );

            // Weighted average (title 40%, content 60%)
            $overallSimilarity = ($titleSimilarity * 0.4) + ($contentSimilarity * 0.6);

            if ($overallSimilarity > $maxSimilarity) {
                $maxSimilarity = $overallSimilarity;
                $mostSimilarPost = $post;
            }

            // Early exit if very similar
            if ($overallSimilarity >= 0.95) {
                break;
            }
        }

        $isDuplicate = $maxSimilarity >= $threshold;

        return [
            'is_duplicate' => $isDuplicate,
            'title_match' => false,
            'content_match' => false,
            'similarity' => round($maxSimilarity * 100, 2),
            'existing_post_id' => $isDuplicate ? $mostSimilarPost?->id : null,
            'match_type' => $isDuplicate ? 'similar' : 'unique',
            'threshold' => $threshold * 100,
        ];
    }

    /**
     * Calculate similarity between two strings using Levenshtein-based similarity.
     *
     * @param string $str1
     * @param string $str2
     * @return float 0.0 to 1.0
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        // Normalize strings
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // Use similar_text for percentage
        similar_text($str1, $str2, $percent);

        return $percent / 100;
    }

    /**
     * Normalize content for comparison.
     *
     * @param string $content
     * @return string
     */
    protected function normalizeContent(string $content): string
    {
        // Remove HTML tags
        $content = strip_tags($content);

        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Remove special characters
        $content = preg_replace('/[^\w\s]/', '', $content);

        // Convert to lowercase
        $content = mb_strtolower($content);

        // Trim
        $content = trim($content);

        return $content;
    }

    /**
     * Check if URL has been imported before.
     *
     * @param string $url
     * @return bool
     */
    public function isUrlImported(string $url): bool
    {
        return Post::where('source_url', $url)->exists();
    }

    /**
     * Check if GUID has been imported before.
     *
     * @param string $guid
     * @return bool
     */
    public function isGuidImported(string $guid): bool
    {
        // Store GUIDs in post metadata or separate table
        return Post::whereJsonContains('metadata->guid', $guid)->exists();
    }

    /**
     * Bulk check duplicates for multiple items.
     *
     * @param array $items
     * @return array
     */
    public function bulkCheckDuplicates(array $items): array
    {
        $results = [];

        foreach ($items as $index => $item) {
            $title = $item['title'] ?? '';
            $content = $item['content'] ?? $item['description'] ?? '';

            $results[$index] = $this->isDuplicate($title, $content);

            // Add original item reference
            $results[$index]['original_item'] = $item;
        }

        return $results;
    }

    /**
     * Get duplicate statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getDuplicateStatistics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = ContentHash::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalHashes = $query->count();

        // Find potential duplicates (same content hash)
        $duplicateContentHashes = ContentHash::selectRaw('content_hash, COUNT(*) as count')
            ->groupBy('content_hash')
            ->having('count', '>', 1)
            ->get();

        $duplicateTitleHashes = ContentHash::selectRaw('title_hash, COUNT(*) as count')
            ->groupBy('title_hash')
            ->having('count', '>', 1)
            ->get();

        return [
            'total_hashes' => $totalHashes,
            'unique_content' => ContentHash::distinct('content_hash')->count(),
            'unique_titles' => ContentHash::distinct('title_hash')->count(),
            'duplicate_content_count' => $duplicateContentHashes->count(),
            'duplicate_title_count' => $duplicateTitleHashes->count(),
            'duplicate_percentage' => $totalHashes > 0
                ? round((($duplicateContentHashes->count() + $duplicateTitleHashes->count()) / $totalHashes) * 100, 2)
                : 0,
        ];
    }

    /**
     * Find similar existing posts.
     *
     * @param string $title
     * @param int $limit
     * @return array
     */
    public function findSimilarPosts(string $title, int $limit = 5): array
    {
        $recentPosts = Post::latest()
            ->take(200)
            ->get();

        $similarPosts = [];

        foreach ($recentPosts as $post) {
            $similarity = $this->calculateSimilarity($title, $post->title);

            if ($similarity > 0.5) { // 50% similarity threshold
                $similarPosts[] = [
                    'post_id' => $post->id,
                    'title' => $post->title,
                    'similarity' => round($similarity * 100, 2),
                    'created_at' => $post->created_at,
                ];
            }
        }

        // Sort by similarity
        usort($similarPosts, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($similarPosts, 0, $limit);
    }

    /**
     * Clean up old content hashes.
     *
     * @param int $daysToKeep
     * @return int Number of deleted hashes
     */
    public function cleanupOldHashes(int $daysToKeep = 90): int
    {
        $this->logInfo('Cleaning up old content hashes', ['days' => $daysToKeep]);

        $deleted = ContentHash::where('created_at', '<', now()->subDays($daysToKeep))
            ->whereDoesntHave('post')
            ->delete();

        $this->logInfo('Old hashes cleaned', ['deleted' => $deleted]);

        return $deleted;
    }
}
