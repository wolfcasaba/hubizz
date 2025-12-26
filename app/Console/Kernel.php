<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Handlers\FeedFetcher\FeedPostsFetcher;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\FindTranslations::class,
        \App\Console\Commands\TranslateFilesCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Legacy Buzzy feed fetcher (keep for backward compatibility)
        $schedule->call(new FeedPostsFetcher('hourly'))->hourly();
        $schedule->call(new FeedPostsFetcher('daily'))->daily();
        $schedule->call(new FeedPostsFetcher('weekly'))->weekly();
        $schedule->call(new FeedPostsFetcher('monthly'))->monthly();

        // ========================================
        // Hubizz RSS Feed Processing
        // ========================================

        // Process 15-minute interval feeds
        $schedule->call(function () {
            \App\Models\RssFeed::active()
                ->where('fetch_interval', '15min')
                ->dueForCheck()
                ->each(function ($feed) {
                    \App\Jobs\ProcessRSSFeedJob::dispatch($feed->id);
                });
        })->everyFifteenMinutes()->name('hubizz-rss-15min');

        // Process hourly feeds
        $schedule->call(function () {
            \App\Models\RssFeed::active()
                ->where('fetch_interval', 'hourly')
                ->dueForCheck()
                ->each(function ($feed) {
                    \App\Jobs\ProcessRSSFeedJob::dispatch($feed->id);
                });
        })->hourly()->name('hubizz-rss-hourly');

        // Process daily feeds
        $schedule->call(function () {
            \App\Models\RssFeed::active()
                ->where('fetch_interval', 'daily')
                ->dueForCheck()
                ->each(function ($feed) {
                    \App\Jobs\ProcessRSSFeedJob::dispatch($feed->id);
                });
        })->daily()->name('hubizz-rss-daily');

        // ========================================
        // Hubizz Daily Izz Curation
        // ========================================

        // Curate Daily Izz (Top 5) every day at configured time
        $dailyIzzTime = config('hubizz.daily_izz.curation_time', '06:00');
        $schedule->call(function () {
            $dailyIzz = \App\Models\DailyIzz::getOrCreateToday();
            $dailyIzz->curateTopPosts(config('hubizz.daily_izz.post_count', 5));
        })->dailyAt($dailyIzzTime)->name('hubizz-daily-izz');

        // ========================================
        // Hubizz Trending Topics Update
        // ========================================

        // Update trending topics hourly (if enabled)
        if (config('hubizz.trending.enabled', true)) {
            $schedule->call(function () {
                // This will be implemented in Phase 3 continuation
                // For now, placeholder for trending update
                \Log::info('Trending topics update scheduled');
            })->hourly()->name('hubizz-trending-update');
        }

        // ========================================
        // Hubizz Maintenance Tasks
        // ========================================

        // Clean up old content hashes (weekly)
        $schedule->call(function () {
            $service = app(\App\Services\RSS\DuplicateDetectorService::class);
            $deleted = $service->cleanupOldHashes(90);
            \Log::info('Content hashes cleaned', ['deleted' => $deleted]);
        })->weekly()->name('hubizz-cleanup-hashes');

        // Clear AI statistics cache (daily)
        $schedule->call(function () {
            app(\App\Services\AI\PerplexityService::class)->clearStatsCache();
        })->daily()->name('hubizz-clear-ai-cache');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}
