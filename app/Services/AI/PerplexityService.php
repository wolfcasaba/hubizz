<?php

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\Post;
use App\Services\BaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

/**
 * Perplexity AI Service
 *
 * Handles all interactions with the Perplexity AI API for content generation.
 * Supports article generation, rewriting, and various content types.
 */
class PerplexityService extends BaseService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.perplexity.ai';
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;
    protected float $costPer1kTokens;

    public function __construct()
    {
        $this->apiKey = config('hubizz.ai.perplexity_api_key');
        $this->model = config('hubizz.ai.default_model', 'sonar');
        $this->maxTokens = config('hubizz.ai.max_tokens', 4000);
        $this->temperature = config('hubizz.ai.temperature', 0.7);
        $this->costPer1kTokens = config('hubizz.ai.cost_per_1k_tokens', 0.001);

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate content using Perplexity AI.
     *
     * @param string $prompt
     * @param array $options
     * @return array{content: string, tokens: int, cost: float}
     * @throws \Exception
     */
    public function generate(string $prompt, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Perplexity API key is not configured');
        }

        $this->logInfo('Generating content', ['prompt_length' => strlen($prompt)]);

        try {
            $response = $this->client->post('/chat/completions', [
                'json' => [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional content writer creating engaging, viral-worthy content. Write in a clear, compelling style that captures attention.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                    'temperature' => $options['temperature'] ?? $this->temperature,
                    'top_p' => $options['top_p'] ?? 0.9,
                    'return_citations' => $options['return_citations'] ?? false,
                    'return_images' => $options['return_images'] ?? false,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $content = $data['choices'][0]['message']['content'] ?? '';
            $tokensUsed = $data['usage']['total_tokens'] ?? 0;
            $cost = ($tokensUsed / 1000) * $this->costPer1kTokens;

            $this->logInfo('Content generated successfully', [
                'tokens_used' => $tokensUsed,
                'cost' => $cost,
            ]);

            return [
                'content' => $content,
                'tokens' => $tokensUsed,
                'cost' => $cost,
                'model' => $data['model'] ?? $this->model,
            ];

        } catch (GuzzleException $e) {
            $this->handleException($e, 'Failed to generate content');
            throw new \Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate content and log it.
     *
     * @param string $type
     * @param string $input
     * @param Post|null $post
     * @param int|null $userId
     * @param array $options
     * @return AIGeneration
     * @throws \Exception
     */
    public function generateAndLog(
        string $type,
        string $input,
        ?Post $post = null,
        ?int $userId = null,
        array $options = []
    ): AIGeneration {
        $result = $this->generate($input, $options);

        return AIGeneration::create([
            'type' => $type,
            'input' => $input,
            'output' => $result['content'],
            'model' => $result['model'] ?? $this->model,
            'tokens_used' => $result['tokens'],
            'cost' => $result['cost'],
            'post_id' => $post?->id,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Generate article content.
     *
     * @param string $topic
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function generateArticle(string $topic, array $options = []): string
    {
        $template = config('hubizz.ai.templates.article');
        $prompt = str_replace('{topic}', $topic, $template);

        // Add word count requirement
        $wordCount = $options['word_count'] ?? 800;
        $prompt .= " Write approximately {$wordCount} words.";

        // Add style preferences
        if (isset($options['style'])) {
            $prompt .= " Style: {$options['style']}.";
        }

        // Add target audience
        if (isset($options['audience'])) {
            $prompt .= " Target audience: {$options['audience']}.";
        }

        $result = $this->generate($prompt, $options);
        return $result['content'];
    }

    /**
     * Rewrite content to make it unique.
     *
     * @param string $content
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function rewriteContent(string $content, array $options = []): string
    {
        $template = config('hubizz.ai.templates.rewrite');
        $prompt = str_replace('{content}', $content, $template);

        $result = $this->generate($prompt, $options);
        return $result['content'];
    }

    /**
     * Generate meta description.
     *
     * @param string $title
     * @param string|null $excerpt
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function generateMetaDescription(string $title, ?string $excerpt = null, array $options = []): string
    {
        $template = config('hubizz.ai.templates.meta');
        $prompt = str_replace('{title}', $title, $template);

        if ($excerpt) {
            $prompt .= " Context: " . substr($excerpt, 0, 200);
        }

        $options['max_tokens'] = 60; // Meta descriptions are short
        $result = $this->generate($prompt, $options);

        // Ensure it's within character limit
        $meta = trim($result['content']);
        if (strlen($meta) > 160) {
            $meta = substr($meta, 0, 157) . '...';
        }

        return $meta;
    }

    /**
     * Generate tags for content.
     *
     * @param string $title
     * @param string|null $content
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function generateTags(string $title, ?string $content = null, array $options = []): array
    {
        $template = config('hubizz.ai.templates.tags');
        $prompt = str_replace('{title}', $title, $template);

        if ($content) {
            $prompt .= " Content excerpt: " . substr(strip_tags($content), 0, 300);
        }

        $options['max_tokens'] = 100;
        $result = $this->generate($prompt, $options);

        // Parse tags from response
        $tags = array_map('trim', explode(',', $result['content']));
        $tags = array_filter($tags);
        $tags = array_slice($tags, 0, 10); // Max 10 tags

        return $tags;
    }

    /**
     * Generate optimized headlines.
     *
     * @param string $title
     * @param int $count
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function generateHeadlines(string $title, int $count = 5, array $options = []): array
    {
        $template = config('hubizz.ai.templates.headline');
        $prompt = str_replace('{title}', $title, $template);
        $prompt = str_replace('5', (string)$count, $prompt);

        $result = $this->generate($prompt, $options);

        // Parse headlines from response
        $headlines = array_filter(
            array_map('trim', explode("\n", $result['content']))
        );

        // Remove numbering if present (1., 2., etc.)
        $headlines = array_map(function($headline) {
            return preg_replace('/^\d+[\.\)]\s*/', '', $headline);
        }, $headlines);

        return array_values(array_filter($headlines));
    }

    /**
     * Generate content from RSS feed item.
     *
     * @param array $feedItem
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function generateFromFeedItem(array $feedItem, array $options = []): string
    {
        $title = $feedItem['title'] ?? '';
        $description = $feedItem['description'] ?? '';

        $prompt = "Rewrite this article with a fresh perspective while keeping the main facts:\n\n";
        $prompt .= "Title: {$title}\n\n";
        $prompt .= "Content: {$description}\n\n";
        $prompt .= "Create an engaging, unique article that covers the same topic but with different wording and structure.";

        $result = $this->generate($prompt, $options);
        return $result['content'];
    }

    /**
     * Check if API key is valid.
     *
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            $result = $this->generate('Test', ['max_tokens' => 10]);
            return !empty($result['content']);
        } catch (\Exception $e) {
            $this->logError('API key validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get usage statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getUsageStats(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $cacheKey = 'ai_usage_stats_' . md5(($startDate?->format('Y-m-d') ?? '') . '_' . ($endDate?->format('Y-m-d') ?? ''));

        return Cache::remember($cacheKey, 300, function() use ($startDate, $endDate) {
            return [
                'total_generations' => AIGeneration::query()
                    ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
                    ->count(),
                'total_tokens' => AIGeneration::getTotalTokens($startDate, $endDate),
                'total_cost' => AIGeneration::getTotalCost($startDate, $endDate),
                'by_type' => AIGeneration::query()
                    ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
                    ->selectRaw('type, COUNT(*) as count, SUM(tokens_used) as tokens, SUM(cost) as cost')
                    ->groupBy('type')
                    ->get()
                    ->toArray(),
            ];
        });
    }

    /**
     * Clear usage stats cache.
     *
     * @return void
     */
    public function clearStatsCache(): void
    {
        Cache::flush();
    }
}
