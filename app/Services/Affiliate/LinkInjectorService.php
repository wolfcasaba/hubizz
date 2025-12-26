<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateLink;
use App\Models\AffiliateProduct;
use App\Models\Post;
use App\Services\BaseService;
use Illuminate\Support\Str;

/**
 * Link Injector Service
 *
 * Automatically injects affiliate links into content with proper formatting,
 * link cloaking, and comparison boxes.
 */
class LinkInjectorService extends BaseService
{
    protected ProductMatcherService $productMatcher;

    public function __construct(ProductMatcherService $productMatcher)
    {
        $this->productMatcher = $productMatcher;
    }

    /**
     * Inject affiliate links into post content.
     *
     * @param Post $post
     * @param array $options
     * @return array{content: string, links_added: int, products: array}
     */
    public function injectLinks(Post $post, array $options = []): array
    {
        $this->logInfo('Injecting affiliate links', ['post_id' => $post->id]);

        $maxLinks = $options['max_links'] ?? config('hubizz.affiliate.max_links_per_post', 5);
        $linkStyle = $options['link_style'] ?? config('hubizz.affiliate.link_style', 'inline');
        $addComparisonBox = $options['add_comparison_box'] ?? config('hubizz.affiliate.add_comparison_box', true);

        // Find products in content
        $products = $this->productMatcher->findProductsInPost($post, [
            'min_confidence' => 0.7,
            'max_products' => $maxLinks,
        ]);

        if (empty($products)) {
            $this->logInfo('No products found for link injection', ['post_id' => $post->id]);
            return [
                'content' => $post->body,
                'links_added' => 0,
                'products' => [],
            ];
        }

        $content = $post->body;
        $linksAdded = 0;
        $injectedProducts = [];

        // Inject inline links
        foreach ($products as $product) {
            if ($linksAdded >= $maxLinks) {
                break;
            }

            // Get or create affiliate link
            $affiliateLink = $this->getOrCreateAffiliateLink($product, $post);

            if (!$affiliateLink) {
                continue;
            }

            // Inject link into content
            $result = $this->injectInlineLink($content, $product, $affiliateLink, $linkStyle);

            if ($result['injected']) {
                $content = $result['content'];
                $linksAdded++;
                $injectedProducts[] = [
                    'product' => $product,
                    'link' => $affiliateLink,
                ];
            }
        }

        // Add comparison box if multiple products and enabled
        if ($addComparisonBox && count($injectedProducts) >= 2) {
            $comparisonBox = $this->generateComparisonBox($injectedProducts);
            $content = $this->insertComparisonBox($content, $comparisonBox);
        }

        $this->logInfo('Links injected successfully', [
            'post_id' => $post->id,
            'links_added' => $linksAdded,
        ]);

        return [
            'content' => $content,
            'links_added' => $linksAdded,
            'products' => $injectedProducts,
        ];
    }

    /**
     * Get or create affiliate link for product.
     *
     * @param array $product
     * @param Post $post
     * @return AffiliateLink|null
     */
    protected function getOrCreateAffiliateLink(array $product, Post $post): ?AffiliateLink
    {
        // If we have a product ID, use the existing affiliate product
        if (isset($product['product_id'])) {
            $affiliateProduct = AffiliateProduct::find($product['product_id']);

            if (!$affiliateProduct) {
                return null;
            }

            // Check if link already exists
            $existingLink = AffiliateLink::where('post_id', $post->id)
                ->where('affiliate_product_id', $affiliateProduct->id)
                ->first();

            if ($existingLink) {
                return $existingLink;
            }

            // Create new link
            return AffiliateLink::create([
                'affiliate_product_id' => $affiliateProduct->id,
                'affiliate_network_id' => $affiliateProduct->affiliate_network_id,
                'post_id' => $post->id,
                'original_url' => $affiliateProduct->affiliate_url,
                'link_text' => $product['name'],
                'position' => 'inline',
            ]);
        }

        // For products without database match, we can't create a link yet
        // This would require additional product lookup or manual configuration
        $this->logWarning('Cannot create affiliate link without product_id', [
            'product_name' => $product['name'],
        ]);

        return null;
    }

    /**
     * Inject inline affiliate link into content.
     *
     * @param string $content
     * @param array $product
     * @param AffiliateLink $link
     * @param string $style
     * @return array{injected: bool, content: string}
     */
    protected function injectInlineLink(string $content, array $product, AffiliateLink $link, string $style): array
    {
        $productName = $product['name'];

        // Find first mention of product name (case-insensitive)
        $pattern = '/\b' . preg_quote($productName, '/') . '\b/i';

        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [
                'injected' => false,
                'content' => $content,
            ];
        }

        $matchText = $matches[0][0];
        $matchPos = $matches[0][1];

        // Check if already linked
        $beforeMatch = substr($content, max(0, $matchPos - 10), 10);
        if (str_contains($beforeMatch, '<a ') || str_contains($beforeMatch, 'href=')) {
            return [
                'injected' => false,
                'content' => $content,
            ];
        }

        // Generate link HTML
        $linkHtml = $this->generateLinkHtml($matchText, $link, $style);

        // Replace first mention with link
        $content = substr_replace($content, $linkHtml, $matchPos, strlen($matchText));

        return [
            'injected' => true,
            'content' => $content,
        ];
    }

    /**
     * Generate HTML for affiliate link.
     *
     * @param string $text
     * @param AffiliateLink $link
     * @param string $style
     * @return string
     */
    protected function generateLinkHtml(string $text, AffiliateLink $link, string $style): string
    {
        $url = url('/go/' . $link->short_code);

        switch ($style) {
            case 'button':
                return sprintf(
                    '<a href="%s" class="affiliate-link affiliate-button" target="_blank" rel="nofollow noopener sponsored">%s →</a>',
                    $url,
                    htmlspecialchars($text)
                );

            case 'badge':
                return sprintf(
                    '<a href="%s" class="affiliate-link affiliate-badge" target="_blank" rel="nofollow noopener sponsored"><span class="badge">%s</span></a>',
                    $url,
                    htmlspecialchars($text)
                );

            case 'inline':
            default:
                return sprintf(
                    '<a href="%s" class="affiliate-link" target="_blank" rel="nofollow noopener sponsored">%s</a>',
                    $url,
                    htmlspecialchars($text)
                );
        }
    }

    /**
     * Generate comparison box HTML.
     *
     * @param array $products
     * @return string
     */
    protected function generateComparisonBox(array $products): string
    {
        $html = '<div class="hubizz-comparison-box">';
        $html .= '<h3 class="comparison-title">Compare Products</h3>';
        $html .= '<div class="comparison-grid">';

        foreach ($products as $item) {
            $product = $item['product'];
            $link = $item['link'];
            $affiliateProduct = $link->product;

            $html .= '<div class="comparison-item">';

            // Product image
            if ($affiliateProduct && $affiliateProduct->image) {
                $html .= sprintf(
                    '<div class="comparison-image"><img src="%s" alt="%s" loading="lazy"></div>',
                    htmlspecialchars($affiliateProduct->image),
                    htmlspecialchars($product['name'])
                );
            }

            // Product name
            $html .= sprintf(
                '<div class="comparison-name">%s</div>',
                htmlspecialchars($product['name'])
            );

            // Price
            if ($affiliateProduct && $affiliateProduct->price > 0) {
                $html .= sprintf(
                    '<div class="comparison-price">$%.2f</div>',
                    $affiliateProduct->price
                );
            }

            // Rating
            if ($affiliateProduct && $affiliateProduct->rating > 0) {
                $html .= sprintf(
                    '<div class="comparison-rating">⭐ %.1f</div>',
                    $affiliateProduct->rating
                );
            }

            // Buy button
            $html .= sprintf(
                '<a href="%s" class="comparison-button" target="_blank" rel="nofollow noopener sponsored">Check Price</a>',
                url('/go/' . $link->short_code)
            );

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<p class="comparison-disclosure">As an Amazon Associate, we earn from qualifying purchases.</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Insert comparison box into content.
     *
     * @param string $content
     * @param string $comparisonBox
     * @return string
     */
    protected function insertComparisonBox(string $content, string $comparisonBox): string
    {
        // Try to insert after first paragraph
        $paragraphs = explode('</p>', $content, 3);

        if (count($paragraphs) >= 2) {
            // Insert after first paragraph
            return $paragraphs[0] . '</p>' . "\n\n" . $comparisonBox . "\n\n" . $paragraphs[1] . (isset($paragraphs[2]) ? '</p>' . $paragraphs[2] : '');
        }

        // If no paragraphs found, append to end
        return $content . "\n\n" . $comparisonBox;
    }

    /**
     * Remove affiliate links from content.
     *
     * @param Post $post
     * @return string
     */
    public function removeLinks(Post $post): string
    {
        $content = $post->body;

        // Remove comparison boxes
        $content = preg_replace('/<div class="hubizz-comparison-box">.*?<\/div>/s', '', $content);

        // Convert affiliate links back to plain text
        $content = preg_replace(
            '/<a[^>]*class="[^"]*affiliate-link[^"]*"[^>]*>(.*?)<\/a>/i',
            '$1',
            $content
        );

        return $content;
    }

    /**
     * Update existing links in post.
     *
     * @param Post $post
     * @param array $options
     * @return array
     */
    public function updateLinks(Post $post, array $options = []): array
    {
        // Remove existing links
        $cleanContent = $this->removeLinks($post);

        // Update post body
        $post->update(['body' => $cleanContent]);

        // Re-inject with new settings
        return $this->injectLinks($post, $options);
    }

    /**
     * Batch inject links into multiple posts.
     *
     * @param array $postIds
     * @param array $options
     * @return array
     */
    public function batchInjectLinks(array $postIds, array $options = []): array
    {
        $results = [];

        foreach ($postIds as $postId) {
            try {
                $post = Post::find($postId);

                if (!$post) {
                    $this->logWarning('Post not found', ['post_id' => $postId]);
                    continue;
                }

                $result = $this->injectLinks($post, $options);

                // Update post if links were added
                if ($result['links_added'] > 0) {
                    $post->update(['body' => $result['content']]);
                }

                $results[$postId] = [
                    'success' => true,
                    'links_added' => $result['links_added'],
                    'products' => count($result['products']),
                ];

            } catch (\Exception $e) {
                $this->handleException($e, 'Failed to inject links', ['post_id' => $postId]);

                $results[$postId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }

            // Delay to avoid overwhelming the system
            usleep(100000); // 0.1 seconds
        }

        return $results;
    }

    /**
     * Get link injection statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getInjectionStatistics(?\\DateTime $startDate = null, ?\\DateTime $endDate = null): array
    {
        $query = AffiliateLink::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalLinks = $query->count();
        $totalClicks = (clone $query)->sum('clicks');
        $totalConversions = (clone $query)->sum('conversions');
        $totalRevenue = (clone $query)->sum('revenue');

        $postsWithLinks = (clone $query)->distinct('post_id')->count();
        $avgLinksPerPost = $postsWithLinks > 0 ? round($totalLinks / $postsWithLinks, 2) : 0;

        $ctr = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;

        return [
            'total_links' => $totalLinks,
            'posts_with_links' => $postsWithLinks,
            'avg_links_per_post' => $avgLinksPerPost,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenue,
            'click_through_rate' => $ctr,
            'avg_revenue_per_link' => $totalLinks > 0 ? round($totalRevenue / $totalLinks, 2) : 0,
            'avg_revenue_per_click' => $totalClicks > 0 ? round($totalRevenue / $totalClicks, 2) : 0,
        ];
    }

    /**
     * Generate affiliate disclosure text.
     *
     * @param string $type
     * @return string
     */
    public function generateDisclosure(string $type = 'standard'): string
    {
        $disclosures = [
            'standard' => 'As an Amazon Associate, we earn from qualifying purchases. This means we may receive a small commission if you purchase through our affiliate links at no extra cost to you.',
            'short' => 'We may earn a commission from purchases made through affiliate links.',
            'footer' => 'Some links in this article are affiliate links. If you purchase through these links, we may earn a commission at no extra cost to you. This helps support our work and allows us to continue creating valuable content.',
        ];

        return $disclosures[$type] ?? $disclosures['standard'];
    }
}
