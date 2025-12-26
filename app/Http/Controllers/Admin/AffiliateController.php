<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateRevenueReportJob;
use App\Jobs\ProcessAffiliateProductJob;
use App\Jobs\SyncAmazonProductJob;
use App\Models\AffiliateLink;
use App\Models\AffiliateNetwork;
use App\Models\AffiliateProduct;
use App\Models\Post;
use App\Services\Affiliate\AmazonAffiliateService;
use App\Services\Affiliate\LinkInjectorService;
use App\Services\Affiliate\ProductMatcherService;
use App\Services\Affiliate\RevenueTrackerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Affiliate Controller
 *
 * Admin controller for managing affiliate networks, products, and revenue.
 */
class AffiliateController extends Controller
{
    protected ProductMatcherService $productMatcher;
    protected LinkInjectorService $linkInjector;
    protected RevenueTrackerService $revenueTracker;
    protected AmazonAffiliateService $amazonService;

    public function __construct(
        ProductMatcherService $productMatcher,
        LinkInjectorService $linkInjector,
        RevenueTrackerService $revenueTracker,
        AmazonAffiliateService $amazonService
    ) {
        $this->productMatcher = $productMatcher;
        $this->linkInjector = $linkInjector;
        $this->revenueTracker = $revenueTracker;
        $this->amazonService = $amazonService;

        $this->middleware('admin');
    }

    /**
     * Dashboard with revenue overview.
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30days');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $statistics = $this->revenueTracker->getRevenueStatistics($startDate, $endDate);
        $byNetwork = $this->revenueTracker->getRevenueByNetwork($startDate, $endDate);
        $trend = $this->revenueTracker->getRevenueTrend('daily', 30);

        return view('admin.affiliate.dashboard', compact('statistics', 'byNetwork', 'trend', 'period'));
    }

    /**
     * List all affiliate networks.
     */
    public function networks()
    {
        $networks = AffiliateNetwork::withCount('products')
            ->withCount('links')
            ->get();

        return view('admin.affiliate.networks', compact('networks'));
    }

    /**
     * Show network details.
     */
    public function showNetwork(AffiliateNetwork $network)
    {
        $network->load(['products', 'links']);

        $statistics = $this->revenueTracker->getRevenueStatistics(null, null, [
            'network_id' => $network->id,
        ]);

        return view('admin.affiliate.network-details', compact('network', 'statistics'));
    }

    /**
     * Update network settings.
     */
    public function updateNetwork(Request $request, AffiliateNetwork $network)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'tracking_id' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $network->update($request->only([
            'name',
            'api_key',
            'api_secret',
            'tracking_id',
            'commission_rate',
            'is_active',
        ]));

        return back()->with('success', 'Network updated successfully');
    }

    /**
     * List all affiliate products.
     */
    public function products(Request $request)
    {
        $query = AffiliateProduct::with(['network', 'category']);

        // Filters
        if ($request->has('network_id')) {
            $query->where('affiliate_network_id', $request->network_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asin', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'revenue');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $products = $query->paginate(20);

        $networks = AffiliateNetwork::all();

        return view('admin.affiliate.products', compact('products', 'networks'));
    }

    /**
     * Show product details.
     */
    public function showProduct(AffiliateProduct $product)
    {
        $product->load(['network', 'category', 'links']);

        return view('admin.affiliate.product-details', compact('product'));
    }

    /**
     * Import product from Amazon.
     */
    public function importAmazonProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asin' => 'required|string|size:10',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $product = $this->amazonService->importProduct(
            $request->asin,
            $request->category_id
        );

        if (!$product) {
            return back()->with('error', 'Failed to import product from Amazon');
        }

        return redirect()->route('admin.affiliate.products.show', $product)
            ->with('success', 'Product imported successfully');
    }

    /**
     * Sync product with Amazon.
     */
    public function syncProduct(AffiliateProduct $product)
    {
        if (!$product->asin) {
            return back()->with('error', 'Product has no ASIN');
        }

        SyncAmazonProductJob::dispatch($product->id);

        return back()->with('success', 'Product sync queued');
    }

    /**
     * Sync all products.
     */
    public function syncAllProducts()
    {
        $products = AffiliateProduct::whereNotNull('asin')
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            SyncAmazonProductJob::dispatch($product->id)->delay(now()->addSeconds($product->id));
        }

        return back()->with('success', count($products) . ' products queued for sync');
    }

    /**
     * List all affiliate links.
     */
    public function links(Request $request)
    {
        $query = AffiliateLink::with(['product', 'network', 'post']);

        // Filters
        if ($request->has('network_id')) {
            $query->where('affiliate_network_id', $request->network_id);
        }

        if ($request->has('post_id')) {
            $query->where('post_id', $request->post_id);
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $links = $query->paginate(50);

        return view('admin.affiliate.links', compact('links'));
    }

    /**
     * Process post for affiliate products.
     */
    public function processPost(Request $request, Post $post)
    {
        ProcessAffiliateProductJob::dispatch($post->id, [
            'max_links' => $request->get('max_links', 5),
            'link_style' => $request->get('link_style', 'inline'),
        ]);

        return back()->with('success', 'Post queued for affiliate processing');
    }

    /**
     * Batch process posts.
     */
    public function batchProcessPosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        foreach ($request->post_ids as $postId) {
            ProcessAffiliateProductJob::dispatch($postId);
        }

        return back()->with('success', count($request->post_ids) . ' posts queued for processing');
    }

    /**
     * Revenue analytics.
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', '30days');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $statistics = $this->revenueTracker->getRevenueStatistics($startDate, $endDate);
        $byNetwork = $this->revenueTracker->getRevenueByNetwork($startDate, $endDate);
        $clickAnalytics = $this->revenueTracker->getClickAnalytics($startDate, $endDate);
        $trend = $this->revenueTracker->getRevenueTrend('daily', 30);

        return view('admin.affiliate.analytics', compact(
            'statistics',
            'byNetwork',
            'clickAnalytics',
            'trend',
            'period'
        ));
    }

    /**
     * Generate revenue report.
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'required|in:csv,json',
            'type' => 'required|in:summary,detailed,network',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $startDate = $request->start_date ? new \DateTime($request->start_date) : null;
        $endDate = $request->end_date ? new \DateTime($request->end_date) : null;

        GenerateRevenueReportJob::dispatch(
            $startDate,
            $endDate,
            $request->format,
            $request->type
        );

        return back()->with('success', 'Report generation queued. Check back in a few minutes.');
    }

    /**
     * Search Amazon products.
     */
    public function searchAmazon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keywords' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid search query'], 400);
        }

        $products = $this->amazonService->searchProducts($request->keywords, [
            'item_count' => 10,
        ]);

        return response()->json(['products' => $products]);
    }

    /**
     * Get period date range.
     *
     * @param string $period
     * @return array
     */
    protected function getPeriodDates(string $period): array
    {
        return match ($period) {
            '7days' => [now()->subDays(7), now()],
            '30days' => [now()->subDays(30), now()],
            '90days' => [now()->subDays(90), now()],
            'year' => [now()->subYear(), now()],
            'all' => [null, null],
            default => [now()->subDays(30), now()],
        };
    }
}
