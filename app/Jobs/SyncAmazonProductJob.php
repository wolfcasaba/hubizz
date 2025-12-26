<?php

namespace App\Jobs;

use App\Models\AffiliateProduct;
use App\Services\Affiliate\AmazonAffiliateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Amazon Product Job
 *
 * Background job for syncing product data from Amazon PA-API.
 */
class SyncAmazonProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected int $productId;

    /**
     * Create a new job instance.
     *
     * @param int $productId
     */
    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     *
     * @param AmazonAffiliateService $amazonService
     * @return void
     */
    public function handle(AmazonAffiliateService $amazonService): void
    {
        Log::info('Syncing Amazon product', ['product_id' => $this->productId]);

        try {
            $product = AffiliateProduct::findOrFail($this->productId);

            if (!$product->asin) {
                Log::warning('Product has no ASIN, skipping sync', ['product_id' => $this->productId]);
                return;
            }

            // Update product from Amazon
            $updated = $amazonService->updateProduct($product);

            Log::info('Amazon product synced successfully', [
                'product_id' => $this->productId,
                'asin' => $product->asin,
                'price' => $updated->price,
                'rating' => $updated->rating,
            ]);

        } catch (\Exception $e) {
            Log::error('Amazon product sync failed', [
                'product_id' => $this->productId,
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
        Log::error('Amazon product sync job failed permanently', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $product = AffiliateProduct::find($this->productId);
            if ($product) {
                $product->update([
                    'metadata' => array_merge($product->metadata ?? [], [
                        'last_sync_error' => $exception->getMessage(),
                        'last_sync_failed_at' => now()->toDateTimeString(),
                    ]),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update product with sync error', ['product_id' => $this->productId]);
        }
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['affiliate', 'amazon', 'product:' . $this->productId];
    }
}
