<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProduct;
use App\Models\Category;
use App\Models\Post;
use App\Services\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Product Matcher Service
 *
 * Detects product mentions in content and matches them to affiliate products.
 * Uses pattern matching, keyword detection, and category analysis.
 */
class ProductMatcherService extends BaseService
{
    /**
     * Product detection patterns by category.
     *
     * @var array
     */
    protected array $categoryPatterns = [
        'tech' => [
            'keywords' => [
                'iphone', 'ipad', 'macbook', 'airpods', 'apple watch',
                'samsung galaxy', 'pixel', 'android', 'laptop', 'tablet',
                'smartwatch', 'headphones', 'earbuds', 'speaker', 'router',
                'monitor', 'keyboard', 'mouse', 'webcam', 'microphone',
                'camera', 'drone', 'gaming console', 'ps5', 'xbox',
                'nintendo switch', 'gpu', 'processor', 'ram', 'ssd',
            ],
            'patterns' => [
                '/\b(iPhone|iPad|MacBook|AirPods)\s*\d+/i',
                '/\b(Galaxy\s+S\d+|Galaxy\s+Note\d+)/i',
                '/\b(Pixel\s+\d+)/i',
                '/\b(RTX\s+\d{4}|GTX\s+\d{4})/i',
                '/\b(AMD\s+Ryzen\s+\d+)/i',
            ],
        ],
        'life' => [
            'keywords' => [
                'vacuum', 'air purifier', 'humidifier', 'coffee maker',
                'blender', 'toaster', 'microwave', 'dishwasher',
                'washing machine', 'dryer', 'iron', 'hairdryer',
                'electric toothbrush', 'shaver', 'fitness tracker',
                'yoga mat', 'dumbbells', 'resistance bands',
                'water bottle', 'lunch box', 'backpack', 'suitcase',
            ],
            'patterns' => [
                '/\b(Dyson\s+V\d+)/i',
                '/\b(Roomba\s+\d+)/i',
                '/\b(Instant\s+Pot)/i',
                '/\b(Fitbit\s+\w+)/i',
            ],
        ],
        'biz' => [
            'keywords' => [
                'office chair', 'standing desk', 'desk lamp', 'notebook',
                'planner', 'pen', 'marker', 'calculator', 'printer',
                'scanner', 'shredder', 'filing cabinet', 'whiteboard',
                'projector', 'conferencing system', 'desk organizer',
            ],
            'patterns' => [
                '/\b(Herman\s+Miller\s+\w+)/i',
                '/\b(HP\s+LaserJet\s+\w+)/i',
                '/\b(Canon\s+PIXMA\s+\w+)/i',
            ],
        ],
    ];

    /**
     * Common product indicators.
     *
     * @var array
     */
    protected array $productIndicators = [
        'buy', 'purchase', 'price', 'cost', 'deal', 'sale', 'discount',
        'review', 'comparison', 'vs', 'versus', 'best', 'top rated',
        'recommended', 'features', 'specifications', 'specs',
    ];

    /**
     * Find products in content.
     *
     * @param string $content
     * @param Category|null $category
     * @param array $options
     * @return array
     */
    public function findProducts(string $content, ?Category $category = null, array $options = []): array
    {
        $minConfidence = $options['min_confidence'] ?? 0.6;
        $maxProducts = $options['max_products'] ?? 10;

        $this->logInfo('Finding products in content', [
            'content_length' => strlen($content),
            'category' => $category?->slug,
        ]);

        // Normalize content
        $normalizedContent = $this->normalizeContent($content);

        // Detect products
        $matches = [];

        // Pattern-based detection
        $patternMatches = $this->detectByPatterns($normalizedContent, $category);
        $matches = array_merge($matches, $patternMatches);

        // Keyword-based detection
        $keywordMatches = $this->detectByKeywords($normalizedContent, $category);
        $matches = array_merge($matches, $keywordMatches);

        // Database product matching
        $dbMatches = $this->matchExistingProducts($normalizedContent);
        $matches = array_merge($matches, $dbMatches);

        // Merge duplicates and calculate confidence
        $products = $this->mergeAndScoreMatches($matches, $normalizedContent);

        // Filter by confidence
        $products = array_filter($products, fn($p) => $p['confidence'] >= $minConfidence);

        // Sort by confidence
        usort($products, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        // Limit results
        $products = array_slice($products, 0, $maxProducts);

        $this->logInfo('Products found', ['count' => count($products)]);

        return $products;
    }

    /**
     * Detect products using regex patterns.
     *
     * @param string $content
     * @param Category|null $category
     * @return array
     */
    protected function detectByPatterns(string $content, ?Category $category): array
    {
        $matches = [];
        $categorySlug = $category?->slug ?? 'all';

        // Get patterns for category
        $patterns = [];
        if ($categorySlug !== 'all' && isset($this->categoryPatterns[$categorySlug]['patterns'])) {
            $patterns = $this->categoryPatterns[$categorySlug]['patterns'];
        } else {
            // Use all patterns
            foreach ($this->categoryPatterns as $cat => $data) {
                if (isset($data['patterns'])) {
                    $patterns = array_merge($patterns, $data['patterns']);
                }
            }
        }

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $patternMatches)) {
                foreach ($patternMatches[0] as $match) {
                    $matches[] = [
                        'name' => trim($match),
                        'source' => 'pattern',
                        'category' => $categorySlug,
                        'base_confidence' => 0.8,
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Detect products using keywords.
     *
     * @param string $content
     * @param Category|null $category
     * @return array
     */
    protected function detectByKeywords(string $content, ?Category $category): array
    {
        $matches = [];
        $categorySlug = $category?->slug ?? 'all';

        // Get keywords for category
        $keywords = [];
        if ($categorySlug !== 'all' && isset($this->categoryPatterns[$categorySlug]['keywords'])) {
            $keywords = $this->categoryPatterns[$categorySlug]['keywords'];
        } else {
            // Use all keywords
            foreach ($this->categoryPatterns as $cat => $data) {
                if (isset($data['keywords'])) {
                    $keywords = array_merge($keywords, $data['keywords']);
                }
            }
        }

        $contentLower = mb_strtolower($content);

        foreach ($keywords as $keyword) {
            if (str_contains($contentLower, mb_strtolower($keyword))) {
                $matches[] = [
                    'name' => $keyword,
                    'source' => 'keyword',
                    'category' => $categorySlug,
                    'base_confidence' => 0.7,
                ];
            }
        }

        return $matches;
    }

    /**
     * Match against existing affiliate products in database.
     *
     * @param string $content
     * @return array
     */
    protected function matchExistingProducts(string $content): array
    {
        $matches = [];
        $contentLower = mb_strtolower($content);

        // Get all active affiliate products
        $products = AffiliateProduct::active()->get();

        foreach ($products as $product) {
            // Check if product name is mentioned
            if (str_contains($contentLower, mb_strtolower($product->name))) {
                $matches[] = [
                    'name' => $product->name,
                    'source' => 'database',
                    'category' => $product->category?->slug ?? 'unknown',
                    'base_confidence' => 0.9,
                    'product_id' => $product->id,
                    'asin' => $product->asin,
                    'network_id' => $product->affiliate_network_id,
                ];
            }

            // Check keywords from product metadata
            if (!empty($product->metadata['keywords'])) {
                foreach ($product->metadata['keywords'] as $keyword) {
                    if (str_contains($contentLower, mb_strtolower($keyword))) {
                        $matches[] = [
                            'name' => $product->name,
                            'source' => 'database_keyword',
                            'category' => $product->category?->slug ?? 'unknown',
                            'base_confidence' => 0.75,
                            'product_id' => $product->id,
                            'asin' => $product->asin,
                            'network_id' => $product->affiliate_network_id,
                        ];
                        break; // Only match once per product
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Merge duplicate matches and calculate confidence scores.
     *
     * @param array $matches
     * @param string $content
     * @return array
     */
    protected function mergeAndScoreMatches(array $matches, string $content): array
    {
        $merged = [];

        foreach ($matches as $match) {
            $key = mb_strtolower(trim($match['name']));

            if (!isset($merged[$key])) {
                $merged[$key] = $match;
                $merged[$key]['mentions'] = 1;
            } else {
                // Merge data
                $merged[$key]['mentions']++;

                // Use highest base confidence
                if ($match['base_confidence'] > $merged[$key]['base_confidence']) {
                    $merged[$key]['base_confidence'] = $match['base_confidence'];
                }

                // Prefer database matches
                if ($match['source'] === 'database' && !isset($merged[$key]['product_id'])) {
                    $merged[$key]['product_id'] = $match['product_id'] ?? null;
                    $merged[$key]['asin'] = $match['asin'] ?? null;
                    $merged[$key]['network_id'] = $match['network_id'] ?? null;
                }
            }
        }

        // Calculate final confidence scores
        foreach ($merged as $key => &$match) {
            $match['confidence'] = $this->calculateConfidence($match, $content);
        }

        return array_values($merged);
    }

    /**
     * Calculate confidence score for a product match.
     *
     * @param array $match
     * @param string $content
     * @return float 0.0 to 1.0
     */
    protected function calculateConfidence(array $match, string $content): float
    {
        $score = $match['base_confidence'];

        // Boost for multiple mentions
        if ($match['mentions'] > 1) {
            $score += min(0.1 * ($match['mentions'] - 1), 0.15);
        }

        // Boost for product indicators nearby
        $hasIndicators = $this->hasProductIndicators($match['name'], $content);
        if ($hasIndicators) {
            $score += 0.1;
        }

        // Boost for database matches
        if (isset($match['product_id'])) {
            $score += 0.05;
        }

        // Cap at 1.0
        return min($score, 1.0);
    }

    /**
     * Check if product indicators appear near the product name.
     *
     * @param string $productName
     * @param string $content
     * @return bool
     */
    protected function hasProductIndicators(string $productName, string $content): bool
    {
        $contentLower = mb_strtolower($content);
        $productPos = mb_strpos($contentLower, mb_strtolower($productName));

        if ($productPos === false) {
            return false;
        }

        // Check 100 characters before and after
        $contextStart = max(0, $productPos - 100);
        $contextLength = min(200, mb_strlen($content) - $contextStart);
        $context = mb_substr($content, $contextStart, $contextLength);
        $contextLower = mb_strtolower($context);

        foreach ($this->productIndicators as $indicator) {
            if (str_contains($contextLower, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find products in a post.
     *
     * @param Post $post
     * @param array $options
     * @return array
     */
    public function findProductsInPost(Post $post, array $options = []): array
    {
        $content = $post->title . "\n\n" . $post->body . "\n\n" . ($post->excerpt ?? '');

        return $this->findProducts($content, $post->category, $options);
    }

    /**
     * Normalize content for processing.
     *
     * @param string $content
     * @return string
     */
    protected function normalizeContent(string $content): string
    {
        // Remove HTML tags
        $content = strip_tags($content);

        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Trim
        $content = trim($content);

        return $content;
    }

    /**
     * Get product suggestions for a category.
     *
     * @param Category $category
     * @param int $limit
     * @return Collection
     */
    public function getSuggestedProducts(Category $category, int $limit = 10): Collection
    {
        return AffiliateProduct::active()
            ->where('category_id', $category->id)
            ->orderBy('conversion_rate', 'desc')
            ->orderBy('commission_amount', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Batch process posts to find products.
     *
     * @param array $postIds
     * @param array $options
     * @return array
     */
    public function batchFindProducts(array $postIds, array $options = []): array
    {
        $results = [];

        foreach ($postIds as $postId) {
            try {
                $post = Post::find($postId);

                if (!$post) {
                    $this->logWarning('Post not found', ['post_id' => $postId]);
                    continue;
                }

                $products = $this->findProductsInPost($post, $options);

                $results[$postId] = [
                    'success' => true,
                    'products' => $products,
                    'count' => count($products),
                ];

            } catch (\Exception $e) {
                $this->handleException($e, 'Failed to find products in post', ['post_id' => $postId]);

                $results[$postId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get product detection statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getDetectionStatistics(?\\DateTime $startDate = null, ?\\DateTime $endDate = null): array
    {
        $query = Post::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalPosts = $query->count();
        $postsWithProducts = 0;
        $totalProducts = 0;
        $productsByCategory = [];

        // This would be more efficient with a products_detected column on posts
        // For now, we'll estimate based on a sample
        $sampleSize = min(100, $totalPosts);
        $sample = (clone $query)->inRandomOrder()->take($sampleSize)->get();

        foreach ($sample as $post) {
            $products = $this->findProductsInPost($post);

            if (!empty($products)) {
                $postsWithProducts++;
                $totalProducts += count($products);

                $category = $post->category?->slug ?? 'uncategorized';
                $productsByCategory[$category] = ($productsByCategory[$category] ?? 0) + count($products);
            }
        }

        // Extrapolate from sample
        $detectionRate = $sampleSize > 0 ? ($postsWithProducts / $sampleSize) : 0;
        $avgProductsPerPost = $postsWithProducts > 0 ? ($totalProducts / $postsWithProducts) : 0;

        return [
            'total_posts' => $totalPosts,
            'sample_size' => $sampleSize,
            'estimated_posts_with_products' => round($totalPosts * $detectionRate),
            'detection_rate' => round($detectionRate * 100, 2),
            'avg_products_per_post' => round($avgProductsPerPost, 2),
            'products_by_category' => $productsByCategory,
        ];
    }
}
