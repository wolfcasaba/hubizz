<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Affiliate\LinkInjectorService;
use App\Services\Affiliate\ProductMatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Affiliate Product Job
 *
 * Background job for detecting products and injecting affiliate links into posts.
 */
class ProcessAffiliateProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

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
     * @param ProductMatcherService $productMatcher
     * @param LinkInjectorService $linkInjector
     * @return void
     */
    public function handle(ProductMatcherService $productMatcher, LinkInjectorService $linkInjector): void
    {
        Log::info('Processing affiliate products for post', ['post_id' => $this->postId]);

        try {
            $post = Post::findOrFail($this->postId);

            // Find products in content
            $products = $productMatcher->findProductsInPost($post, [
                'min_confidence' => $this->options['min_confidence'] ?? 0.7,
                'max_products' => $this->options['max_products'] ?? 5,
            ]);

            if (empty($products)) {
                Log::info('No products found in post', ['post_id' => $this->postId]);
                return;
            }

            Log::info('Products detected in post', [
                'post_id' => $this->postId,
                'product_count' => count($products),
            ]);

            // Inject affiliate links
            $result = $linkInjector->injectLinks($post, [
                'max_links' => $this->options['max_links'] ?? 5,
                'link_style' => $this->options['link_style'] ?? 'inline',
                'add_comparison_box' => $this->options['add_comparison_box'] ?? true,
            ]);

            if ($result['links_added'] > 0) {
                // Update post content
                $post->update(['body' => $result['content']]);

                Log::info('Affiliate links injected successfully', [
                    'post_id' => $this->postId,
                    'links_added' => $result['links_added'],
                ]);
            } else {
                Log::info('No affiliate links could be created', ['post_id' => $this->postId]);
            }

        } catch (\Exception $e) {
            Log::error('Affiliate product processing failed', [
                'post_id' => $this->postId,
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
        Log::error('Affiliate product job failed permanently', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['affiliate', 'post:' . $this->postId];
    }
}
