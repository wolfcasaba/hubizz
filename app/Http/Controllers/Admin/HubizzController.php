<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAIContentJob;
use App\Models\Category;
use App\Models\DailyIzz;
use App\Models\Post;
use App\Models\RssFeed;
use App\Models\TrendingTopic;
use App\Services\AI\ContentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Hubizz Controller
 *
 * Admin controller for managing Hubizz-specific features:
 * Daily Izz, Trending Topics, RSS Feeds, and AI Content.
 */
class HubizzController extends Controller
{
    protected ContentGeneratorService $contentGenerator;

    public function __construct(ContentGeneratorService $contentGenerator)
    {
        $this->contentGenerator = $contentGenerator;
        $this->middleware('admin');
    }

    /**
     * Daily Izz Management
     */
    public function dailyIzz(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dailyIzzs = DailyIzz::whereBetween('date', [$startDate, $endDate])
            ->with('posts')
            ->orderBy('date', 'desc')
            ->get();

        $today = DailyIzz::getOrCreateToday();

        return view('admin.hubizz.daily-izz', compact('dailyIzzs', 'today', 'month'));
    }

    /**
     * Show specific Daily Izz
     */
    public function showDailyIzz(DailyIzz $dailyIzz)
    {
        $dailyIzz->load(['posts.category', 'posts.user']);

        $availablePosts = Post::published()
            ->where('published_at', '>=', $dailyIzz->date->startOfDay())
            ->where('published_at', '<=', $dailyIzz->date->endOfDay())
            ->whereNotIn('id', $dailyIzz->posts->pluck('id'))
            ->orderBy('score', 'desc')
            ->take(20)
            ->get();

        return view('admin.hubizz.daily-izz-show', compact('dailyIzz', 'availablePosts'));
    }

    /**
     * Curate Daily Izz (auto or manual)
     */
    public function curateDailyIzz(Request $request, DailyIzz $dailyIzz)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'nullable|array|max:5',
            'post_ids.*' => 'exists:posts,id',
            'auto' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($request->boolean('auto')) {
            // Auto-curate
            $count = config('hubizz.daily_izz.post_count', 5);
            $dailyIzz->curateTopPosts($count);

            return back()->with('success', 'Daily Izz auto-curated with top ' . $count . ' posts');
        }

        // Manual curation
        if ($request->has('post_ids')) {
            $dailyIzz->posts()->sync($request->post_ids);

            return back()->with('success', 'Daily Izz updated with ' . count($request->post_ids) . ' posts');
        }

        return back()->with('error', 'No posts selected');
    }

    /**
     * Update Daily Izz settings
     */
    public function updateDailyIzz(Request $request, DailyIzz $dailyIzz)
    {
        $validator = Validator::make($request->all(), [
            'is_published' => 'boolean',
            'featured_post_id' => 'nullable|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $dailyIzz->update($request->only(['is_published', 'featured_post_id']));

        return back()->with('success', 'Daily Izz updated successfully');
    }

    /**
     * Trending Topics Dashboard
     */
    public function trending(Request $request)
    {
        $period = $request->get('period', '24h');

        $query = TrendingTopic::with('category');

        // Filter by period
        $since = match($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $trending = $query->where('updated_at', '>=', $since)
            ->orderBy('score', 'desc')
            ->paginate(50);

        // Get topic statistics
        $stats = [
            'total_topics' => TrendingTopic::count(),
            'active_topics' => TrendingTopic::where('is_active', true)->count(),
            'top_category' => TrendingTopic::select('category_id')
                ->groupBy('category_id')
                ->orderByRaw('COUNT(*) DESC')
                ->first()
                ?->category
                ?->name ?? 'N/A',
            'avg_score' => round(TrendingTopic::avg('score'), 2),
        ];

        return view('admin.hubizz.trending', compact('trending', 'period', 'stats'));
    }

    /**
     * Add trending topic
     */
    public function addTrending(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255|unique:trending_topics,topic',
            'category_id' => 'nullable|exists:categories,id',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $trending = TrendingTopic::create([
            'topic' => $request->topic,
            'category_id' => $request->category_id,
            'score' => $request->score ?? 50,
            'mentions' => 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Trending topic "' . $trending->topic . '" added');
    }

    /**
     * Update trending topic
     */
    public function updateTrending(Request $request, TrendingTopic $trending)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'score' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $trending->update($request->only(['topic', 'category_id', 'score', 'is_active']));

        return back()->with('success', 'Trending topic updated');
    }

    /**
     * Delete trending topic
     */
    public function deleteTrending(TrendingTopic $trending)
    {
        $topic = $trending->topic;
        $trending->delete();

        return back()->with('success', 'Trending topic "' . $topic . '" deleted');
    }

    /**
     * RSS Feeds Management
     */
    public function rssFeeds()
    {
        $feeds = RssFeed::with(['category', 'imports'])
            ->withCount('imports')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = Category::all();

        $stats = [
            'total_feeds' => $feeds->count(),
            'active_feeds' => $feeds->where('is_active', true)->count(),
            'total_imports' => \App\Models\RssImport::count(),
            'successful_imports' => \App\Models\RssImport::where('status', 'completed')->count(),
        ];

        return view('admin.hubizz.rss-feeds', compact('feeds', 'categories', 'stats'));
    }

    /**
     * Create RSS feed
     */
    public function createRssFeed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|unique:rss_feeds,url',
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'fetch_interval' => 'required|in:15min,hourly,daily',
            'priority' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $feed = RssFeed::create([
            'url' => $request->url,
            'title' => $request->title,
            'category_id' => $request->category_id,
            'fetch_interval' => $request->fetch_interval,
            'priority' => $request->priority ?? 10,
            'is_active' => true,
        ]);

        return back()->with('success', 'RSS feed "' . $feed->title . '" created');
    }

    /**
     * Update RSS feed
     */
    public function updateRssFeed(Request $request, RssFeed $feed)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'fetch_interval' => 'sometimes|in:15min,hourly,daily',
            'priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $feed->update($request->only([
            'title',
            'category_id',
            'fetch_interval',
            'priority',
            'is_active',
        ]));

        return back()->with('success', 'RSS feed updated');
    }

    /**
     * Delete RSS feed
     */
    public function deleteRssFeed(RssFeed $feed)
    {
        $title = $feed->title;
        $feed->delete();

        return back()->with('success', 'RSS feed "' . $title . '" deleted');
    }

    /**
     * AI Content Dashboard
     */
    public function aiContent()
    {
        $recentGenerations = \App\Models\AiGeneration::with('category')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $stats = [
            'total_generations' => \App\Models\AiGeneration::count(),
            'successful_generations' => \App\Models\AiGeneration::where('status', 'completed')->count(),
            'total_tokens' => \App\Models\AiGeneration::sum('tokens_used'),
            'total_cost' => \App\Models\AiGeneration::sum('cost'),
        ];

        $categories = Category::all();
        $trendingTopics = TrendingTopic::where('is_active', true)
            ->orderBy('score', 'desc')
            ->take(10)
            ->get();

        return view('admin.hubizz.ai-content', compact(
            'recentGenerations',
            'stats',
            'categories',
            'trendingTopics'
        ));
    }

    /**
     * Generate AI content
     */
    public function generateAiContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:topic,trending,rss',
            'topic' => 'required_if:type,topic|string|max:255',
            'trending_id' => 'required_if:type,trending|exists:trending_topics,id',
            'category_id' => 'required|exists:categories,id',
            'language' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $category = Category::find($request->category_id);

        try {
            if ($request->type === 'topic') {
                // Generate from topic
                GenerateAIContentJob::dispatch(
                    $request->topic,
                    $category,
                    ['language' => $request->language ?? 'en']
                );

                $message = 'AI content generation queued for topic: ' . $request->topic;

            } elseif ($request->type === 'trending') {
                // Generate from trending topic
                $trending = TrendingTopic::findOrFail($request->trending_id);

                GenerateAIContentJob::dispatch(
                    $trending->topic,
                    $category,
                    ['language' => $request->language ?? 'en']
                );

                $message = 'AI content generation queued for trending topic: ' . $trending->topic;
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to queue AI generation: ' . $e->getMessage());
        }
    }
}
