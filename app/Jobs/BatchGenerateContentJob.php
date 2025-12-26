<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\AI\ContentGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Batch Generate Content Job
 *
 * Queue job for generating multiple articles in batch.
 */
class BatchGenerateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes for batch processing

    protected array $topics;
    protected ?int $categoryId;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param array $topics
     * @param int|null $categoryId
     * @param array $options
     */
    public function __construct(array $topics, ?int $categoryId = null, array $options = [])
    {
        $this->topics = $topics;
        $this->categoryId = $categoryId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @param ContentGeneratorService $generator
     * @return void
     */
    public function handle(ContentGeneratorService $generator): void
    {
        Log::info('Starting batch AI content generation', [
            'topics_count' => count($this->topics),
            'category_id' => $this->categoryId,
        ]);

        try {
            $category = $this->categoryId ? Category::find($this->categoryId) : null;

            $results = $generator->batchGenerate($this->topics, $category, $this->options);

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $failCount = count($results) - $successCount;

            Log::info('Batch AI content generation completed', [
                'total' => count($results),
                'success' => $successCount,
                'failed' => $failCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Batch AI content generation failed', [
                'topics_count' => count($this->topics),
                'error' => $e->getMessage(),
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
        Log::error('Batch content generation job failed', [
            'topics_count' => count($this->topics),
            'error' => $exception->getMessage(),
        ]);
    }
}
