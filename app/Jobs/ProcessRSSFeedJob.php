<?php

namespace App\Jobs;

use App\Models\RssFeed;
use App\Services\RSS\ContentImporterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process RSS Feed Job
 *
 * Queue job for processing RSS feeds in the background.
 */
class ProcessRSSFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    protected int $feedId;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param int $feedId
     * @param array $options
     */
    public function __construct(int $feedId, array $options = [])
    {
        $this->feedId = $feedId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @param ContentImporterService $importer
     * @return void
     */
    public function handle(ContentImporterService $importer): void
    {
        Log::info('Processing RSS feed', ['feed_id' => $this->feedId]);

        try {
            $feed = RssFeed::findOrFail($this->feedId);

            if (!$feed->is_active) {
                Log::warning('Skipping inactive feed', ['feed_id' => $this->feedId]);
                return;
            }

            $import = $importer->importFromFeed($feed, $this->options);

            Log::info('RSS feed processed successfully', [
                'feed_id' => $this->feedId,
                'import_id' => $import->id,
                'items_imported' => $import->items_imported,
                'items_skipped' => $import->items_skipped,
            ]);

        } catch (\Exception $e) {
            Log::error('RSS feed processing failed', [
                'feed_id' => $this->feedId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RSS feed processing job failed permanently', [
            'feed_id' => $this->feedId,
            'error' => $exception->getMessage(),
        ]);

        // Mark feed as having failed
        try {
            $feed = RssFeed::find($this->feedId);
            if ($feed) {
                $feed->markAsChecked(false);
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark feed as failed', ['feed_id' => $this->feedId]);
        }
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['rss', 'feed:' . $this->feedId];
    }
}
