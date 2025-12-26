<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Post;
use App\Services\AI\ContentGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate AI Content Job
 *
 * Queue job for generating content with AI in the background.
 */
class GenerateAIContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected string $topic;
    protected ?int $categoryId;
    protected array $options;
    protected ?int $userId;

    /**
     * Create a new job instance.
     *
     * @param string $topic
     * @param int|null $categoryId
     * @param array $options
     * @param int|null $userId
     */
    public function __construct(
        string $topic,
        ?int $categoryId = null,
        array $options = [],
        ?int $userId = null
    ) {
        $this->topic = $topic;
        $this->categoryId = $categoryId;
        $this->options = $options;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param ContentGeneratorService $generator
     * @return void
     */
    public function handle(ContentGeneratorService $generator): void
    {
        Log::info('Starting AI content generation', [
            'topic' => $this->topic,
            'category_id' => $this->categoryId,
        ]);

        try {
            $category = $this->categoryId ? Category::find($this->categoryId) : null;

            $options = array_merge($this->options, [
                'user_id' => $this->userId ?? 1,
            ]);

            $post = $generator->generateArticleFromTopic(
                $this->topic,
                $category,
                $options
            );

            Log::info('AI content generated successfully', [
                'post_id' => $post->id,
                'topic' => $this->topic,
            ]);

        } catch (\Exception $e) {
            Log::error('AI content generation failed', [
                'topic' => $this->topic,
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
        Log::error('AI content generation job failed permanently', [
            'topic' => $this->topic,
            'error' => $exception->getMessage(),
        ]);
    }
}
