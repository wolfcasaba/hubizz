<?php

namespace App\Services\RSS;

use App\Models\Category;
use App\Models\ContentHash;
use App\Models\Post;
use App\Models\RssFeed;
use App\Models\RssImport;
use App\Services\AI\ContentGeneratorService;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Content Importer Service
 *
 * Imports RSS feed content with AI rewriting, duplicate detection,
 * and automatic categorization.
 */
class ContentImporterService extends BaseService
{
    protected FeedAggregatorService $aggregator;
    protected DuplicateDetectorService $duplicateDetector;
    protected ContentGeneratorService $contentGenerator;

    public function __construct(
        FeedAggregatorService $aggregator,
        DuplicateDetectorService $duplicateDetector,
        ContentGeneratorService $contentGenerator
    ) {
        $this->aggregator = $aggregator;
        $this->duplicateDetector = $duplicateDetector;
        $this->contentGenerator = $contentGenerator;
    }

    /**
     * Import content from RSS feed.
     *
     * @param RssFeed $feed
     * @param array $options
     * @return RssImport
     */
    public function importFromFeed(RssFeed $feed, array $options = []): RssImport
    {
        $this->logInfo('Starting RSS import', ['feed_id' => $feed->id, 'url' => $feed->url]);

        // Create import record
        $import = RssImport::create([
            'rss_feed_id' => $feed->id,
            'status' => 'pending',
        ]);

        try {
            $import->markAsStarted();

            // Fetch feed
            $result = $this->aggregator->fetchFeed($feed->url);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to fetch feed');
            }

            $items = $result['items'];
            $itemsFound = count($items);
            $itemsImported = 0;
            $itemsSkipped = 0;
            $importLog = [];

            $this->logInfo('Feed fetched', ['items_found' => $itemsFound]);

            // Process each item
            foreach ($items as $item) {
                try {
                    $itemResult = $this->importItem($item, $feed, $options);

                    if ($itemResult['imported']) {
                        $itemsImported++;
                        $importLog[] = [
                            'title' => $item['title'],
                            'status' => 'imported',
                            'post_id' => $itemResult['post_id'],
                        ];
                    } else {
                        $itemsSkipped++;
                        $importLog[] = [
                            'title' => $item['title'],
                            'status' => 'skipped',
                            'reason' => $itemResult['reason'],
                        ];
                    }

                } catch (\Exception $e) {
                    $itemsSkipped++;
                    $this->handleException($e, 'Failed to import item', ['title' => $item['title'] ?? 'Unknown']);

                    $importLog[] = [
                        'title' => $item['title'] ?? 'Unknown',
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }

                // Small delay to avoid overwhelming the system
                if (config('hubizz.rss.rewrite_content')) {
                    usleep(500000); // 0.5 seconds if using AI
                }
            }

            // Mark import as completed
            $import->markAsCompleted($itemsFound, $itemsImported, $itemsSkipped);
            $import->update(['import_log' => $importLog]);

            // Mark feed as checked
            $feed->markAsChecked(true);

            $this->logInfo('Import completed successfully', [
                'import_id' => $import->id,
                'found' => $itemsFound,
                'imported' => $itemsImported,
                'skipped' => $itemsSkipped,
            ]);

        } catch (\Exception $e) {
            $this->handleException($e, 'RSS import failed', ['feed_id' => $feed->id]);

            $import->markAsFailed($e->getMessage());
            $feed->markAsChecked(false);
        }

        return $import->fresh();
    }

    /**
     * Import single RSS item.
     *
     * @param array $item
     * @param RssFeed $feed
     * @param array $options
     * @return array{imported: bool, post_id: int|null, reason: string|null}
     */
    protected function importItem(array $item, RssFeed $feed, array $options = []): array
    {
        // Check if URL already imported
        if (!empty($item['link']) && $this->duplicateDetector->isUrlImported($item['link'])) {
            return [
                'imported' => false,
                'post_id' => null,
                'reason' => 'URL already imported',
            ];
        }

        // Check for duplicates
        $title = $item['title'];
        $content = $item['content'] ?: $item['description'];

        $duplicateCheck = $this->duplicateDetector->isDuplicate($title, $content);

        if ($duplicateCheck['is_duplicate']) {
            return [
                'imported' => false,
                'post_id' => $duplicateCheck['existing_post_id'],
                'reason' => "Duplicate detected ({$duplicateCheck['match_type']}, {$duplicateCheck['similarity']}% similar)",
            ];
        }

        // Determine category
        $category = $this->determineCategory($item, $feed);

        // Create post
        $post = $this->createPost($item, $feed, $category, $options);

        return [
            'imported' => true,
            'post_id' => $post->id,
            'reason' => null,
        ];
    }

    /**
     * Create post from RSS item.
     *
     * @param array $item
     * @param RssFeed $feed
     * @param Category|null $category
     * @param array $options
     * @return Post
     */
    protected function createPost(array $item, RssFeed $feed, ?Category $category, array $options = []): Post
    {
        $shouldRewrite = config('hubizz.rss.rewrite_content', true) && $this->isFeatureEnabled('ai_content_generation');

        DB::beginTransaction();

        try {
            if ($shouldRewrite) {
                // Use AI to create unique content
                $post = $this->contentGenerator->generateFromRSSItem($item, $category, [
                    'user_id' => $options['user_id'] ?? 1,
                    'language' => $feed->metadata['language'] ?? 'en',
                ]);
            } else {
                // Direct import without AI
                $post = $this->createDirectPost($item, $feed, $category, $options);
            }

            // Download and save image if configured
            if (config('hubizz.rss.download_images', true) && !empty($item['image'])) {
                $this->downloadAndAttachImage($post, $item['image']);
            }

            // Create content hash for future duplicate detection
            if (!$shouldRewrite) {
                ContentHash::createForPost($post);
            }

            DB::commit();

            $this->logInfo('Post created from RSS', ['post_id' => $post->id, 'title' => $post->title]);

            return $post;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create post directly without AI rewriting.
     *
     * @param array $item
     * @param RssFeed $feed
     * @param Category|null $category
     * @param array $options
     * @return Post
     */
    protected function createDirectPost(array $item, RssFeed $feed, ?Category $category, array $options = []): Post
    {
        $title = $item['title'];
        $content = $item['content'] ?: $item['description'];

        return Post::create([
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $content,
            'excerpt' => Str::limit(strip_tags($item['description']), 200),
            'language' => $feed->metadata['language'] ?? 'en',
            'category_id' => $category?->id,
            'user_id' => $options['user_id'] ?? 1,
            'status' => config('hubizz.rss.auto_publish') ? 'published' : 'draft',
            'published_at' => config('hubizz.rss.auto_publish') ? now() : null,
            'source_url' => $item['link'] ?? null,
            'metadata' => [
                'rss_feed_id' => $feed->id,
                'guid' => $item['guid'] ?? null,
                'author' => $item['author'] ?? null,
                'published_at' => $item['published_at'] ?? null,
            ],
        ]);
    }

    /**
     * Determine category for RSS item.
     *
     * @param array $item
     * @param RssFeed $feed
     * @return Category|null
     */
    protected function determineCategory(array $item, RssFeed $feed): ?Category
    {
        // Use feed's default category if set
        if ($feed->category_id) {
            return $feed->category;
        }

        // Auto-categorize based on item categories
        if (config('hubizz.rss.auto_categorize', true) && !empty($item['categories'])) {
            return $this->matchCategory($item['categories']);
        }

        return null;
    }

    /**
     * Match item categories to existing categories.
     *
     * @param array $itemCategories
     * @return Category|null
     */
    protected function matchCategory(array $itemCategories): ?Category
    {
        foreach ($itemCategories as $categoryName) {
            $category = Category::where('name', 'like', '%' . $categoryName . '%')
                ->orWhere('slug', Str::slug($categoryName))
                ->first();

            if ($category) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Download and attach image to post.
     *
     * @param Post $post
     * @param string $imageUrl
     * @return bool
     */
    protected function downloadAndAttachImage(Post $post, string $imageUrl): bool
    {
        try {
            $this->logInfo('Downloading image', ['url' => $imageUrl]);

            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->logWarning('Failed to download image', ['url' => $imageUrl]);
                return false;
            }

            // Get file extension from URL or content type
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = $this->getExtensionFromMimeType($response->header('Content-Type'));
            }

            $filename = 'rss-' . $post->id . '-' . time() . '.' . $extension;
            $path = 'uploads/posts/' . date('Y/m');
            $fullPath = storage_path('app/public/' . $path);

            // Create directory if not exists
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Save file
            file_put_contents($fullPath . '/' . $filename, $response->body());

            // Update post
            $post->update(['image' => $path . '/' . $filename]);

            $this->logInfo('Image downloaded and attached', ['post_id' => $post->id]);

            return true;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to download image', ['url' => $imageUrl]);
            return false;
        }
    }

    /**
     * Get file extension from MIME type.
     *
     * @param string|null $mimeType
     * @return string
     */
    protected function getExtensionFromMimeType(?string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $mimeMap[$mimeType] ?? 'jpg';
    }

    /**
     * Batch import from multiple feeds.
     *
     * @param array $feedIds
     * @param array $options
     * @return array
     */
    public function batchImport(array $feedIds, array $options = []): array
    {
        $results = [];

        foreach ($feedIds as $feedId) {
            try {
                $feed = RssFeed::findOrFail($feedId);
                $import = $this->importFromFeed($feed, $options);

                $results[] = [
                    'feed_id' => $feedId,
                    'success' => $import->status === 'completed',
                    'import_id' => $import->id,
                    'items_imported' => $import->items_imported,
                    'items_skipped' => $import->items_skipped,
                ];

                // Delay between feeds
                sleep(2);

            } catch (\Exception $e) {
                $this->handleException($e, 'Batch import failed for feed', ['feed_id' => $feedId]);

                $results[] = [
                    'feed_id' => $feedId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get import statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getImportStatistics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = RssImport::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalImports = $query->count();
        $successfulImports = (clone $query)->where('status', 'completed')->count();
        $failedImports = (clone $query)->where('status', 'failed')->count();

        $totalItemsFound = (clone $query)->sum('items_found');
        $totalItemsImported = (clone $query)->sum('items_imported');
        $totalItemsSkipped = (clone $query)->sum('items_skipped');

        return [
            'total_imports' => $totalImports,
            'successful_imports' => $successfulImports,
            'failed_imports' => $failedImports,
            'success_rate' => $totalImports > 0 ? round(($successfulImports / $totalImports) * 100, 2) : 0,
            'total_items_found' => $totalItemsFound,
            'total_items_imported' => $totalItemsImported,
            'total_items_skipped' => $totalItemsSkipped,
            'import_rate' => $totalItemsFound > 0 ? round(($totalItemsImported / $totalItemsFound) * 100, 2) : 0,
            'average_items_per_import' => $totalImports > 0 ? round($totalItemsImported / $totalImports, 2) : 0,
        ];
    }
}
