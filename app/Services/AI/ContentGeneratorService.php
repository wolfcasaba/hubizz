<?php

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\Category;
use App\Models\ContentHash;
use App\Models\Post;
use App\Models\TrendingTopic;
use App\Services\BaseService;

/**
 * Content Generator Service
 *
 * High-level service for generating complete posts with AI.
 * Handles article creation, meta generation, tagging, and deduplication.
 */
class ContentGeneratorService extends BaseService
{
    protected PerplexityService $perplexity;
    protected HeadlineOptimizerService $headlineOptimizer;

    public function __construct(
        PerplexityService $perplexity,
        HeadlineOptimizerService $headlineOptimizer
    ) {
        $this->perplexity = $perplexity;
        $this->headlineOptimizer = $headlineOptimizer;
    }

    /**
     * Generate a complete article from a topic.
     *
     * @param string $topic
     * @param Category|null $category
     * @param array $options
     * @return Post
     * @throws \Exception
     */
    public function generateArticleFromTopic(
        string $topic,
        ?Category $category = null,
        array $options = []
    ): Post {
        $this->logInfo('Generating article from topic', ['topic' => $topic]);

        // Generate content
        $content = $this->perplexity->generateArticle($topic, $options);

        // Generate optimized title if not provided
        $title = $options['title'] ?? $this->headlineOptimizer->generateBestHeadline($topic);

        // Check for duplicates
        if ($this->isDuplicate($title, $content)) {
            $this->logWarning('Duplicate content detected', ['title' => $title]);

            if (!($options['allow_duplicates'] ?? false)) {
                throw new \Exception('Duplicate content detected. Article already exists.');
            }
        }

        // Create post
        $post = Post::create([
            'title' => $title,
            'slug' => \Str::slug($title),
            'body' => $content,
            'language' => $options['language'] ?? config('hubizz.ai.default_language', 'en'),
            'category_id' => $category?->id,
            'user_id' => $options['user_id'] ?? auth()->id(),
            'status' => $options['status'] ?? ($this->getConfig('rss.auto_publish') ? 'published' : 'draft'),
            'published_at' => $options['status'] === 'published' ? now() : null,
        ]);

        // Generate and set meta description
        if (config('hubizz.ai.auto_generate_meta', true)) {
            $metaDescription = $this->perplexity->generateMetaDescription($title, substr($content, 0, 500));
            $post->update(['meta_description' => $metaDescription]);
        }

        // Generate and attach tags
        if (config('hubizz.ai.auto_generate_tags', true)) {
            $tags = $this->perplexity->generateTags($title, $content);
            $this->attachTags($post, $tags);
        }

        // Create content hash for duplicate detection
        ContentHash::createForPost($post);

        // Log generation
        $this->perplexity->generateAndLog('article', $topic, $post);

        $this->logInfo('Article generated successfully', ['post_id' => $post->id]);

        return $post;
    }

    /**
     * Generate article from trending topic.
     *
     * @param TrendingTopic $topic
     * @param array $options
     * @return Post
     * @throws \Exception
     */
    public function generateFromTrendingTopic(TrendingTopic $topic, array $options = []): Post
    {
        if ($topic->is_used && !($options['force'] ?? false)) {
            throw new \Exception('Trending topic already used');
        }

        $this->logInfo('Generating from trending topic', [
            'topic' => $topic->keyword,
            'score' => $topic->score,
        ]);

        $options['category'] = $topic->category;
        $post = $this->generateArticleFromTopic($topic->keyword, $topic->category, $options);

        // Mark topic as used
        $topic->markAsUsed($post);

        return $post;
    }

    /**
     * Generate article from RSS feed item.
     *
     * @param array $feedItem
     * @param Category|null $category
     * @param array $options
     * @return Post
     * @throws \Exception
     */
    public function generateFromRSSItem(
        array $feedItem,
        ?Category $category = null,
        array $options = []
    ): Post {
        $originalTitle = $feedItem['title'] ?? 'Untitled';

        $this->logInfo('Generating from RSS item', ['title' => $originalTitle]);

        // Check if we should rewrite content
        $shouldRewrite = config('hubizz.rss.rewrite_content', true);

        if ($shouldRewrite) {
            // AI rewrite for uniqueness
            $content = $this->perplexity->generateFromFeedItem($feedItem, $options);
            $title = $this->headlineOptimizer->generateBestHeadline($originalTitle);
        } else {
            $content = $feedItem['description'] ?? '';
            $title = $originalTitle;
        }

        // Check for duplicates
        if ($this->isDuplicate($title, $content)) {
            $this->logWarning('Duplicate RSS content', ['title' => $title]);
            throw new \Exception('Duplicate content from RSS feed');
        }

        // Create post
        $post = Post::create([
            'title' => $title,
            'slug' => \Str::slug($title),
            'body' => $content,
            'language' => $options['language'] ?? 'en',
            'category_id' => $category?->id,
            'user_id' => $options['user_id'] ?? 1, // System user for RSS
            'status' => config('hubizz.rss.auto_publish') ? 'published' : 'draft',
            'published_at' => config('hubizz.rss.auto_publish') ? now() : null,
            'source_url' => $feedItem['link'] ?? null,
        ]);

        // Auto-generate meta and tags
        if ($shouldRewrite && config('hubizz.ai.auto_generate_meta')) {
            $metaDescription = $this->perplexity->generateMetaDescription($title, substr($content, 0, 500));
            $post->update(['meta_description' => $metaDescription]);
        }

        if ($shouldRewrite && config('hubizz.ai.auto_generate_tags')) {
            $tags = $this->perplexity->generateTags($title, $content);
            $this->attachTags($post, $tags);
        }

        // Create content hash
        ContentHash::createForPost($post);

        // Log if rewritten
        if ($shouldRewrite) {
            $this->perplexity->generateAndLog('rewrite', $originalTitle, $post);
        }

        $this->logInfo('RSS article created', ['post_id' => $post->id]);

        return $post;
    }

    /**
     * Rewrite existing content.
     *
     * @param Post $post
     * @param array $options
     * @return Post
     * @throws \Exception
     */
    public function rewritePost(Post $post, array $options = []): Post
    {
        $this->logInfo('Rewriting post', ['post_id' => $post->id]);

        $newContent = $this->perplexity->rewriteContent($post->body, $options);

        // Optionally rewrite title
        if ($options['rewrite_title'] ?? false) {
            $newTitle = $this->headlineOptimizer->generateBestHeadline($post->title);
            $post->update([
                'title' => $newTitle,
                'slug' => \Str::slug($newTitle),
            ]);
        }

        $post->update(['body' => $newContent]);

        // Update content hash
        ContentHash::createForPost($post);

        // Log rewrite
        $this->perplexity->generateAndLog('rewrite', $post->body, $post);

        $this->logInfo('Post rewritten successfully', ['post_id' => $post->id]);

        return $post->fresh();
    }

    /**
     * Generate meta description for existing post.
     *
     * @param Post $post
     * @return string
     * @throws \Exception
     */
    public function generateMetaForPost(Post $post): string
    {
        $meta = $this->perplexity->generateMetaDescription(
            $post->title,
            substr($post->body, 0, 500)
        );

        $post->update(['meta_description' => $meta]);

        // Log generation
        $this->perplexity->generateAndLog('meta', $post->title, $post);

        return $meta;
    }

    /**
     * Generate tags for existing post.
     *
     * @param Post $post
     * @return array
     * @throws \Exception
     */
    public function generateTagsForPost(Post $post): array
    {
        $tags = $this->perplexity->generateTags($post->title, $post->body);

        $this->attachTags($post, $tags);

        // Log generation
        $this->perplexity->generateAndLog('tags', $post->title, $post);

        return $tags;
    }

    /**
     * Batch generate articles from topics.
     *
     * @param array $topics
     * @param Category|null $category
     * @param array $options
     * @return array
     */
    public function batchGenerate(array $topics, ?Category $category = null, array $options = []): array
    {
        $results = [];

        foreach ($topics as $topic) {
            try {
                $post = $this->generateArticleFromTopic($topic, $category, $options);
                $results[] = [
                    'success' => true,
                    'topic' => $topic,
                    'post_id' => $post->id,
                ];
            } catch (\Exception $e) {
                $this->handleException($e, 'Batch generation failed for topic', ['topic' => $topic]);
                $results[] = [
                    'success' => false,
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ];
            }

            // Add delay to avoid rate limiting
            if (count($topics) > 1) {
                sleep($options['delay'] ?? 2);
            }
        }

        return $results;
    }

    /**
     * Check if content is duplicate.
     *
     * @param string $title
     * @param string $content
     * @return bool
     */
    protected function isDuplicate(string $title, string $content): bool
    {
        $similarity = ContentHash::checkSimilarity($title, $content);
        return $similarity['is_duplicate'];
    }

    /**
     * Attach tags to post.
     *
     * @param Post $post
     * @param array $tags
     * @return void
     */
    protected function attachTags(Post $post, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $tagIds = [];

        foreach ($tags as $tagName) {
            $tag = \App\Models\Tag::firstOrCreate(
                ['name' => $tagName],
                ['slug' => \Str::slug($tagName)]
            );
            $tagIds[] = $tag->id;
        }

        // Attach tags to post
        $post->tags()->sync($tagIds);
    }

    /**
     * Get content generation statistics.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return array
     */
    public function getStatistics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        return $this->perplexity->getUsageStats($startDate, $endDate);
    }
}
