<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateClick;
use App\Models\AffiliateLink;
use App\Models\AffiliateNetwork;
use App\Models\AffiliateProduct;
use App\Models\Post;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Revenue Tracker Service
 *
 * Tracks affiliate revenue, clicks, conversions, and provides detailed analytics.
 */
class RevenueTrackerService extends BaseService
{
    /**
     * Track affiliate click.
     *
     * @param AffiliateLink $link
     * @param array $data
     * @return AffiliateClick
     */
    public function trackClick(AffiliateLink $link, array $data = []): AffiliateClick
    {
        $this->logInfo('Tracking affiliate click', [
            'link_id' => $link->id,
            'short_code' => $link->short_code,
        ]);

        // Create click record
        $click = AffiliateClick::create([
            'affiliate_link_id' => $link->id,
            'ip_address' => $data['ip'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'referer' => $data['referer'] ?? request()->header('referer'),
            'country' => $data['country'] ?? $this->getCountryFromIp($data['ip'] ?? request()->ip()),
            'device_type' => $data['device_type'] ?? $this->detectDeviceType($data['user_agent'] ?? request()->userAgent()),
        ]);

        // Update link click count
        $link->recordClick();

        // Clear relevant caches
        $this->clearRevenueCache($link);

        return $click;
    }

    /**
     * Track affiliate conversion.
     *
     * @param AffiliateLink $link
     * @param float $amount
     * @param array $data
     * @return bool
     */
    public function trackConversion(AffiliateLink $link, float $amount, array $data = []): bool
    {
        $this->logInfo('Tracking affiliate conversion', [
            'link_id' => $link->id,
            'amount' => $amount,
        ]);

        try {
            DB::beginTransaction();

            // Update link with conversion
            $link->recordConversion($amount);

            // Update product conversion stats
            if ($link->product) {
                $link->product->increment('conversions');
                $link->product->increment('revenue', $amount);

                // Update conversion rate
                $conversionRate = $link->product->clicks > 0
                    ? ($link->product->conversions / $link->product->clicks) * 100
                    : 0;
                $link->product->update(['conversion_rate' => $conversionRate]);
            }

            // Update network revenue
            if ($link->network) {
                $link->network->increment('total_revenue', $amount);
            }

            // Find the associated click and mark as converted
            $recentClick = AffiliateClick::where('affiliate_link_id', $link->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNull('converted_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentClick) {
                $recentClick->update([
                    'converted_at' => now(),
                    'conversion_amount' => $amount,
                ]);
            }

            DB::commit();

            // Clear caches
            $this->clearRevenueCache($link);

            $this->logInfo('Conversion tracked successfully', [
                'link_id' => $link->id,
                'amount' => $amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleException($e, 'Failed to track conversion', [
                'link_id' => $link->id,
                'amount' => $amount,
            ]);

            return false;
        }
    }

    /**
     * Get revenue statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param array $filters
     * @return array
     */
    public function getRevenueStatistics(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        array $filters = []
    ): array {
        $cacheKey = $this->generateStatsCacheKey('revenue', $startDate, $endDate, $filters);

        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate, $filters) {
            $clicksQuery = AffiliateClick::query();
            $linksQuery = AffiliateLink::query();

            // Apply date filters
            if ($startDate) {
                $clicksQuery->where('created_at', '>=', $startDate);
                $linksQuery->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $clicksQuery->where('created_at', '<=', $endDate);
                $linksQuery->where('created_at', '<=', $endDate);
            }

            // Apply additional filters
            if (isset($filters['network_id'])) {
                $clicksQuery->whereHas('link', fn($q) => $q->where('affiliate_network_id', $filters['network_id']));
                $linksQuery->where('affiliate_network_id', $filters['network_id']);
            }

            if (isset($filters['post_id'])) {
                $clicksQuery->whereHas('link', fn($q) => $q->where('post_id', $filters['post_id']));
                $linksQuery->where('post_id', $filters['post_id']);
            }

            // Get metrics
            $totalClicks = $clicksQuery->count();
            $totalConversions = (clone $clicksQuery)->whereNotNull('converted_at')->count();
            $totalRevenue = (clone $linksQuery)->sum('revenue');
            $totalLinks = $linksQuery->count();

            // Calculate rates
            $conversionRate = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;
            $avgRevenuePerClick = $totalClicks > 0 ? round($totalRevenue / $totalClicks, 2) : 0;
            $avgRevenuePerConversion = $totalConversions > 0 ? round($totalRevenue / $totalConversions, 2) : 0;

            // Get top performers
            $topLinks = $this->getTopLinks($startDate, $endDate, $filters, 5);
            $topProducts = $this->getTopProducts($startDate, $endDate, $filters, 5);
            $topPosts = $this->getTopPosts($startDate, $endDate, $filters, 5);

            return [
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'total_revenue' => round($totalRevenue, 2),
                'total_links' => $totalLinks,
                'conversion_rate' => $conversionRate,
                'avg_revenue_per_click' => $avgRevenuePerClick,
                'avg_revenue_per_conversion' => $avgRevenuePerConversion,
                'top_links' => $topLinks,
                'top_products' => $topProducts,
                'top_posts' => $topPosts,
            ];
        });
    }

    /**
     * Get top performing links.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param array $filters
     * @param int $limit
     * @return array
     */
    protected function getTopLinks(
        ?\DateTime $startDate,
        ?\DateTime $endDate,
        array $filters,
        int $limit
    ): array {
        $query = AffiliateLink::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if (isset($filters['network_id'])) {
            $query->where('affiliate_network_id', $filters['network_id']);
        }

        return $query->orderBy('revenue', 'desc')
            ->orderBy('conversions', 'desc')
            ->take($limit)
            ->get()
            ->map(fn($link) => [
                'link_id' => $link->id,
                'link_text' => $link->link_text,
                'short_code' => $link->short_code,
                'clicks' => $link->clicks,
                'conversions' => $link->conversions,
                'revenue' => $link->revenue,
                'product' => $link->product?->name,
            ])
            ->toArray();
    }

    /**
     * Get top performing products.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param array $filters
     * @param int $limit
     * @return array
     */
    protected function getTopProducts(
        ?\DateTime $startDate,
        ?\DateTime $endDate,
        array $filters,
        int $limit
    ): array {
        $query = AffiliateProduct::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if (isset($filters['network_id'])) {
            $query->where('affiliate_network_id', $filters['network_id']);
        }

        return $query->where('conversions', '>', 0)
            ->orderBy('revenue', 'desc')
            ->orderBy('conversion_rate', 'desc')
            ->take($limit)
            ->get()
            ->map(fn($product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'asin' => $product->asin,
                'clicks' => $product->clicks,
                'conversions' => $product->conversions,
                'conversion_rate' => $product->conversion_rate,
                'revenue' => $product->revenue,
            ])
            ->toArray();
    }

    /**
     * Get top performing posts.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param array $filters
     * @param int $limit
     * @return array
     */
    protected function getTopPosts(
        ?\DateTime $startDate,
        ?\DateTime $endDate,
        array $filters,
        int $limit
    ): array {
        $query = AffiliateLink::select('post_id')
            ->selectRaw('SUM(clicks) as total_clicks')
            ->selectRaw('SUM(conversions) as total_conversions')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->whereNotNull('post_id')
            ->groupBy('post_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if (isset($filters['network_id'])) {
            $query->where('affiliate_network_id', $filters['network_id']);
        }

        $results = $query->orderBy('total_revenue', 'desc')
            ->take($limit)
            ->get();

        return $results->map(function ($result) {
            $post = Post::find($result->post_id);

            return [
                'post_id' => $result->post_id,
                'title' => $post?->title ?? 'Unknown',
                'slug' => $post?->slug,
                'clicks' => $result->total_clicks,
                'conversions' => $result->total_conversions,
                'revenue' => $result->total_revenue,
            ];
        })->toArray();
    }

    /**
     * Get revenue by network.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getRevenueByNetwork(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = AffiliateLink::select('affiliate_network_id')
            ->selectRaw('COUNT(*) as total_links')
            ->selectRaw('SUM(clicks) as total_clicks')
            ->selectRaw('SUM(conversions) as total_conversions')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->whereNotNull('affiliate_network_id')
            ->groupBy('affiliate_network_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $results = $query->orderBy('total_revenue', 'desc')->get();

        return $results->map(function ($result) {
            $network = AffiliateNetwork::find($result->affiliate_network_id);

            $conversionRate = $result->total_clicks > 0
                ? round(($result->total_conversions / $result->total_clicks) * 100, 2)
                : 0;

            return [
                'network_id' => $result->affiliate_network_id,
                'network_name' => $network?->name ?? 'Unknown',
                'total_links' => $result->total_links,
                'total_clicks' => $result->total_clicks,
                'total_conversions' => $result->total_conversions,
                'total_revenue' => round($result->total_revenue, 2),
                'conversion_rate' => $conversionRate,
            ];
        })->toArray();
    }

    /**
     * Get click analytics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getClickAnalytics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = AffiliateClick::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalClicks = $query->count();

        // Clicks by device
        $byDevice = (clone $query)->select('device_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        // Clicks by country
        $byCountry = (clone $query)->select('country')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->pluck('count', 'country')
            ->toArray();

        // Clicks by hour
        $byHour = (clone $query)->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Clicks by day of week
        $byDayOfWeek = (clone $query)->selectRaw('DAYOFWEEK(created_at) as day')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

        return [
            'total_clicks' => $totalClicks,
            'by_device' => $byDevice,
            'by_country' => $byCountry,
            'by_hour' => $byHour,
            'by_day_of_week' => $byDayOfWeek,
        ];
    }

    /**
     * Get revenue trend data.
     *
     * @param string $period daily|weekly|monthly
     * @param int $periods Number of periods to return
     * @return array
     */
    public function getRevenueTrend(string $period = 'daily', int $periods = 30): array
    {
        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = AffiliateClick::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('COUNT(*) as clicks')
            ->selectRaw('SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as conversions')
            ->selectRaw('SUM(COALESCE(conversion_amount, 0)) as revenue')
            ->where('created_at', '>=', now()->sub($periods, $period))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $results->map(fn($r) => [
            'period' => $r->period,
            'clicks' => $r->clicks,
            'conversions' => $r->conversions,
            'revenue' => round($r->revenue, 2),
            'conversion_rate' => $r->clicks > 0 ? round(($r->conversions / $r->clicks) * 100, 2) : 0,
        ])->toArray();
    }

    /**
     * Detect device type from user agent.
     *
     * @param string|null $userAgent
     * @return string
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Get country from IP address.
     *
     * @param string $ip
     * @return string|null
     */
    protected function getCountryFromIp(string $ip): ?string
    {
        // This would integrate with a GeoIP service
        // For now, return null (to be implemented with MaxMind GeoIP2 or similar)
        return null;
    }

    /**
     * Clear revenue-related caches.
     *
     * @param AffiliateLink $link
     * @return void
     */
    protected function clearRevenueCache(AffiliateLink $link): void
    {
        Cache::tags(['revenue', 'affiliate'])->flush();
    }

    /**
     * Generate cache key for statistics.
     *
     * @param string $type
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param array $filters
     * @return string
     */
    protected function generateStatsCacheKey(
        string $type,
        ?\DateTime $startDate,
        ?\DateTime $endDate,
        array $filters
    ): string {
        $key = "affiliate_{$type}_stats";

        if ($startDate) {
            $key .= '_' . $startDate->format('Ymd');
        }

        if ($endDate) {
            $key .= '_' . $endDate->format('Ymd');
        }

        if (!empty($filters)) {
            $key .= '_' . md5(json_encode($filters));
        }

        return $key;
    }

    /**
     * Export revenue data to CSV.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return string CSV content
     */
    public function exportRevenueData(?\DateTime $startDate = null, ?\DateTime $endDate = null): string
    {
        $query = AffiliateClick::with(['link.product', 'link.network']);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $clicks = $query->orderBy('created_at', 'desc')->get();

        $csv = "Date,Link,Product,Network,Country,Device,Converted,Revenue\n";

        foreach ($clicks as $click) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%.2f"' . "\n",
                $click->created_at->format('Y-m-d H:i:s'),
                $click->link?->link_text ?? 'N/A',
                $click->link?->product?->name ?? 'N/A',
                $click->link?->network?->name ?? 'N/A',
                $click->country ?? 'N/A',
                $click->device_type,
                $click->converted_at ? 'Yes' : 'No',
                $click->conversion_amount ?? 0
            );
        }

        return $csv;
    }
}
