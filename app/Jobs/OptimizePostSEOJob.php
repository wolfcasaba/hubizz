<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\SEO\MetaOptimizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Optimize Post SEO Job
 *
 * Queue job for optimizing post SEO metadata in the background.
 */
class OptimizePostSEOJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    protected int $postId;
    protected array $options;

    /**
     * Create a new job instance.
     *
     * @param int $postId
     * @param array $options
     */
    public function __construct(int $postId, array $options = [])
    {
        $this->postId = $postId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @param MetaOptimizerService $optimizer
     * @return void
     */
    public function handle(MetaOptimizerService $optimizer): void
    {
        Log::info('Starting SEO optimization', ['post_id' => $this->postId]);

        try {
            $post = Post::findOrFail($this->postId);

            $optimizer->optimizePost($post, $this->options);

            Log::info('SEO optimization completed', ['post_id' => $this->postId]);

        } catch (\Exception $e) {
            Log::error('SEO optimization failed', [
                'post_id' => $this->postId,
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
        Log::error('SEO optimization job failed', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }
}
