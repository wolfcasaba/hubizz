<?php

namespace App\Services\RSS;

use App\Models\RssFeed;
use App\Services\BaseService;
use SimplePie\SimplePie;

/**
 * Feed Aggregator Service
 *
 * Handles RSS feed fetching, parsing, and aggregation using SimplePie.
 */
class FeedAggregatorService extends BaseService
{
    protected SimplePie $simplePie;

    public function __construct()
    {
        $this->simplePie = new SimplePie();
        $this->configureFeed();
    }

    /**
     * Configure SimplePie settings.
     *
     * @return void
     */
    protected function configureFeed(): void
    {
        $this->simplePie->set_cache_location(storage_path('framework/cache'));
        $this->simplePie->set_cache_duration(config('hubizz.rss.cache_duration', 3600));
        $this->simplePie->enable_cache(true);
        $this->simplePie->force_feed(true);
        $this->simplePie->set_timeout(30);
    }

    /**
     * Fetch and parse RSS feed.
     *
     * @param string $url
     * @return array{success: bool, items: array, error: string|null, feed_info: array}
     */
    public function fetchFeed(string $url): array
    {
        $this->logInfo('Fetching RSS feed', ['url' => $url]);

        try {
            $this->simplePie->set_feed_url($url);
            $success = $this->simplePie->init();

            if (!$success) {
                $error = $this->simplePie->error();
                $this->logError('Failed to fetch feed', ['url' => $url, 'error' => $error]);

                return [
                    'success' => false,
                    'items' => [],
                    'error' => $error,
                    'feed_info' => [],
                ];
            }

            $items = $this->parseFeedItems($this->simplePie);
            $feedInfo = $this->extractFeedInfo($this->simplePie);

            $this->logInfo('Feed fetched successfully', [
                'url' => $url,
                'items_count' => count($items),
            ]);

            return [
                'success' => true,
                'items' => $items,
                'error' => null,
                'feed_info' => $feedInfo,
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Exception while fetching feed', ['url' => $url]);

            return [
                'success' => false,
                'items' => [],
                'error' => $e->getMessage(),
                'feed_info' => [],
            ];
        }
    }

    /**
     * Parse feed items into structured array.
     *
     * @param SimplePie $feed
     * @return array
     */
    protected function parseFeedItems(SimplePie $feed): array
    {
        $items = [];
        $maxItems = config('hubizz.rss.max_items_per_import', 50);

        foreach ($feed->get_items(0, $maxItems) as $item) {
            $parsedItem = $this->parseItem($item);

            // Apply quality filters
            if ($this->passesQualityFilters($parsedItem)) {
                $items[] = $parsedItem;
            }
        }

        return $items;
    }

    /**
     * Parse individual feed item.
     *
     * @param \SimplePie\Item $item
     * @return array
     */
    protected function parseItem($item): array
    {
        return [
            'title' => $this->cleanText($item->get_title()),
            'link' => $item->get_link(),
            'description' => $this->cleanText($item->get_description()),
            'content' => $this->cleanText($item->get_content()),
            'published_at' => $item->get_date('Y-m-d H:i:s'),
            'author' => $item->get_author()?->get_name(),
            'categories' => $this->extractCategories($item),
            'image' => $this->extractImage($item),
            'guid' => $item->get_id(),
        ];
    }

    /**
     * Extract feed metadata.
     *
     * @param SimplePie $feed
     * @return array
     */
    protected function extractFeedInfo(SimplePie $feed): array
    {
        return [
            'title' => $feed->get_title(),
            'description' => $feed->get_description(),
            'link' => $feed->get_link(),
            'language' => $feed->get_language(),
            'image' => $feed->get_image_url(),
            'copyright' => $feed->get_copyright(),
        ];
    }

    /**
     * Extract categories from feed item.
     *
     * @param \SimplePie\Item $item
     * @return array
     */
    protected function extractCategories($item): array
    {
        $categories = [];

        if ($itemCategories = $item->get_categories()) {
            foreach ($itemCategories as $category) {
                $categories[] = $category->get_label();
            }
        }

        return array_filter($categories);
    }

    /**
     * Extract image from feed item.
     *
     * @param \SimplePie\Item $item
     * @return string|null
     */
    protected function extractImage($item): ?string
    {
        // Try enclosure first (most common)
        if ($enclosure = $item->get_enclosure()) {
            if ($enclosure->get_medium() === 'image') {
                return $enclosure->get_link();
            }
        }

        // Try thumbnail
        if ($thumbnail = $item->get_thumbnail()) {
            return $thumbnail;
        }

        // Try to extract from content
        $content = $item->get_content();
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Clean text content.
     *
     * @param string|null $text
     * @return string
     */
    protected function cleanText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Check if item passes quality filters.
     *
     * @param array $item
     * @return bool
     */
    protected function passesQualityFilters(array $item): bool
    {
        // Check minimum content length
        $minLength = config('hubizz.rss.min_content_length', 200);
        $contentLength = strlen($item['content'] ?: $item['description']);

        if ($contentLength < $minLength) {
            $this->logDebug('Item filtered: too short', ['title' => $item['title']]);
            return false;
        }

        // Check maximum content length
        $maxLength = config('hubizz.rss.max_content_length', 10000);
        if ($contentLength > $maxLength) {
            $this->logDebug('Item filtered: too long', ['title' => $item['title']]);
            return false;
        }

        // Check if image is required
        $requireImage = config('hubizz.rss.skip_if_no_image', false);
        if ($requireImage && empty($item['image'])) {
            $this->logDebug('Item filtered: no image', ['title' => $item['title']]);
            return false;
        }

        // Check for required title
        if (empty($item['title'])) {
            $this->logDebug('Item filtered: no title');
            return false;
        }

        return true;
    }

    /**
     * Fetch multiple feeds.
     *
     * @param array $urls
     * @return array
     */
    public function fetchMultipleFeeds(array $urls): array
    {
        $results = [];

        foreach ($urls as $url) {
            $results[$url] = $this->fetchFeed($url);

            // Small delay to be respectful
            usleep(500000); // 0.5 seconds
        }

        return $results;
    }

    /**
     * Process all due RSS feeds.
     *
     * @return array
     */
    public function processDueFeeds(): array
    {
        $dueFeeds = RssFeed::dueForCheck()->get();

        $this->logInfo('Processing due feeds', ['count' => $dueFeeds->count()]);

        $results = [];

        foreach ($dueFeeds as $feed) {
            try {
                $result = $this->fetchFeed($feed->url);

                // Mark as checked
                $feed->markAsChecked($result['success']);

                $results[] = [
                    'feed_id' => $feed->id,
                    'success' => $result['success'],
                    'items_count' => count($result['items']),
                    'error' => $result['error'],
                ];

            } catch (\Exception $e) {
                $this->handleException($e, 'Failed to process feed', ['feed_id' => $feed->id]);

                $feed->markAsChecked(false);

                $results[] = [
                    'feed_id' => $feed->id,
                    'success' => false,
                    'items_count' => 0,
                    'error' => $e->getMessage(),
                ];
            }

            // Delay between feeds
            sleep(1);
        }

        return $results;
    }

    /**
     * Test feed URL validity.
     *
     * @param string $url
     * @return array{valid: bool, error: string|null, feed_title: string|null}
     */
    public function testFeedUrl(string $url): array
    {
        $result = $this->fetchFeed($url);

        if (!$result['success']) {
            return [
                'valid' => false,
                'error' => $result['error'],
                'feed_title' => null,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'feed_title' => $result['feed_info']['title'] ?? 'Unknown',
            'items_count' => count($result['items']),
        ];
    }

    /**
     * Discover RSS feeds from a website URL.
     *
     * @param string $url
     * @return array
     */
    public function discoverFeeds(string $url): array
    {
        $this->logInfo('Discovering feeds', ['url' => $url]);

        try {
            $this->simplePie->set_feed_url($url);
            $this->simplePie->init();

            $feeds = [];

            if ($discoveredFeeds = $this->simplePie->get_all_discovered_feeds()) {
                foreach ($discoveredFeeds as $feed) {
                    $feeds[] = [
                        'url' => $feed->subscribe_url(),
                        'title' => $feed->get_title(),
                        'type' => $feed->get_type(),
                    ];
                }
            }

            return $feeds;

        } catch (\Exception $e) {
            $this->handleException($e, 'Feed discovery failed', ['url' => $url]);
            return [];
        }
    }

    /**
     * Get feed statistics.
     *
     * @param RssFeed $feed
     * @return array
     */
    public function getFeedStatistics(RssFeed $feed): array
    {
        $imports = $feed->imports()->latest()->take(10)->get();

        $totalImported = $feed->imports()->sum('items_imported');
        $totalSkipped = $feed->imports()->sum('items_skipped');
        $failedImports = $feed->imports()->where('status', 'failed')->count();
        $successRate = $feed->imports()->count() > 0
            ? ($feed->imports()->where('status', 'completed')->count() / $feed->imports()->count()) * 100
            : 0;

        return [
            'total_imports' => $feed->imports()->count(),
            'total_items_imported' => $totalImported,
            'total_items_skipped' => $totalSkipped,
            'failed_imports' => $failedImports,
            'success_rate' => round($successRate, 2),
            'last_success' => $feed->last_success_at?->diffForHumans(),
            'recent_imports' => $imports,
            'average_items_per_import' => $feed->imports()->count() > 0
                ? round($totalImported / $feed->imports()->count(), 2)
                : 0,
        ];
    }
}
