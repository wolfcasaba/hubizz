<?php

namespace App\Services\SEO;

use App\Models\Post;
use App\Services\AI\PerplexityService;
use App\Services\BaseService;

/**
 * Meta Optimizer Service
 *
 * Handles SEO optimization including meta descriptions, schema markup,
 * and other SEO-related metadata.
 */
class MetaOptimizerService extends BaseService
{
    protected PerplexityService $perplexity;

    public function __construct(PerplexityService $perplexity)
    {
        $this->perplexity = $perplexity;
    }

    /**
     * Optimize all meta data for a post.
     *
     * @param Post $post
     * @param array $options
     * @return Post
     * @throws \Exception
     */
    public function optimizePost(Post $post, array $options = []): Post
    {
        $this->logInfo('Optimizing SEO meta data', ['post_id' => $post->id]);

        // Generate meta description if not exists or force regenerate
        if (!$post->meta_description || ($options['force'] ?? false)) {
            $metaDescription = $this->generateMetaDescription($post);
            $post->update(['meta_description' => $metaDescription]);
        }

        // Generate keywords if not exists
        if (!$post->meta_keywords || ($options['force'] ?? false)) {
            $keywords = $this->generateKeywords($post);
            $post->update(['meta_keywords' => implode(', ', $keywords)]);
        }

        // Generate OG tags
        if (config('hubizz.seo.auto_generate_schema', true)) {
            $this->generateOpenGraphTags($post);
        }

        // Generate schema markup
        if (config('hubizz.seo.auto_generate_schema', true)) {
            $schema = $this->generateSchemaMarkup($post);
            $post->update(['schema_markup' => json_encode($schema)]);
        }

        $this->logInfo('SEO optimization complete', ['post_id' => $post->id]);

        return $post->fresh();
    }

    /**
     * Generate optimized meta description.
     *
     * @param Post $post
     * @return string
     * @throws \Exception
     */
    public function generateMetaDescription(Post $post): string
    {
        $excerpt = substr(strip_tags($post->body), 0, 500);
        return $this->perplexity->generateMetaDescription($post->title, $excerpt);
    }

    /**
     * Generate SEO keywords.
     *
     * @param Post $post
     * @return array
     * @throws \Exception
     */
    public function generateKeywords(Post $post): array
    {
        return $this->perplexity->generateTags($post->title, $post->body);
    }

    /**
     * Generate Open Graph tags.
     *
     * @param Post $post
     * @return array
     */
    public function generateOpenGraphTags(Post $post): array
    {
        return [
            'og:title' => $post->title,
            'og:description' => $post->meta_description ?? substr(strip_tags($post->body), 0, 160),
            'og:type' => 'article',
            'og:url' => route('post.show', $post->slug),
            'og:image' => $post->image ? asset($post->image) : null,
            'og:site_name' => config('hubizz.name'),
            'article:published_time' => $post->published_at?->toIso8601String(),
            'article:modified_time' => $post->updated_at->toIso8601String(),
            'article:author' => $post->user?->name,
            'article:section' => $post->category?->name,
        ];
    }

    /**
     * Generate Schema.org markup.
     *
     * @param Post $post
     * @return array
     */
    public function generateSchemaMarkup(Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $post->meta_description ?? substr(strip_tags($post->body), 0, 160),
            'image' => $post->image ? asset($post->image) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->user?->name ?? 'Admin',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('hubizz.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset(config('app.logo')),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('post.show', $post->slug),
            ],
        ];
    }

    /**
     * Generate Twitter Card tags.
     *
     * @param Post $post
     * @return array
     */
    public function generateTwitterCardTags(Post $post): array
    {
        return [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $post->title,
            'twitter:description' => $post->meta_description ?? substr(strip_tags($post->body), 0, 160),
            'twitter:image' => $post->image ? asset($post->image) : null,
            'twitter:site' => config('hubizz.social.twitter'),
        ];
    }

    /**
     * Analyze SEO score for a post.
     *
     * @param Post $post
     * @return array
     */
    public function analyzeSEO(Post $post): array
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];
        $recommendations = [];

        // Title length (ideal: 50-60 characters)
        $titleLength = strlen($post->title);
        if ($titleLength >= 50 && $titleLength <= 60) {
            $score += 15;
        } elseif ($titleLength < 50 || $titleLength > 70) {
            $issues[] = 'Title length should be 50-60 characters';
            $recommendations[] = 'Adjust title length to optimal range';
            $score += 5;
        } else {
            $score += 10;
        }

        // Meta description
        if ($post->meta_description) {
            $metaLength = strlen($post->meta_description);
            if ($metaLength >= 150 && $metaLength <= 160) {
                $score += 15;
            } elseif ($metaLength < 150 || $metaLength > 165) {
                $issues[] = 'Meta description should be 150-160 characters';
                $recommendations[] = 'Optimize meta description length';
                $score += 8;
            } else {
                $score += 12;
            }
        } else {
            $issues[] = 'Missing meta description';
            $recommendations[] = 'Add a compelling meta description';
        }

        // Keywords/Tags
        $tagCount = $post->tags()->count();
        if ($tagCount >= 5 && $tagCount <= 10) {
            $score += 10;
        } elseif ($tagCount < 5) {
            $issues[] = 'Add more relevant tags (5-10 recommended)';
            $recommendations[] = 'Include 5-10 relevant tags';
            $score += 5;
        } elseif ($tagCount > 10) {
            $issues[] = 'Too many tags (5-10 recommended)';
            $recommendations[] = 'Reduce tags to 5-10 most relevant';
            $score += 5;
        }

        // Content length (ideal: 800+ words)
        $wordCount = str_word_count(strip_tags($post->body));
        if ($wordCount >= 800) {
            $score += 15;
        } elseif ($wordCount >= 500) {
            $score += 10;
            $recommendations[] = 'Consider expanding content to 800+ words';
        } else {
            $issues[] = 'Content too short (minimum 500 words)';
            $recommendations[] = 'Expand content to at least 800 words';
            $score += 5;
        }

        // Image presence
        if ($post->image) {
            $score += 10;
        } else {
            $issues[] = 'Missing featured image';
            $recommendations[] = 'Add a relevant featured image';
        }

        // Internal links check (basic)
        $internalLinkCount = substr_count($post->body, route('home'));
        if ($internalLinkCount >= 2) {
            $score += 10;
        } elseif ($internalLinkCount >= 1) {
            $score += 5;
            $recommendations[] = 'Add more internal links';
        } else {
            $issues[] = 'No internal links found';
            $recommendations[] = 'Add 2-3 internal links to related content';
        }

        // Heading structure (H2, H3 tags)
        $h2Count = substr_count($post->body, '<h2>');
        $h3Count = substr_count($post->body, '<h3>');

        if ($h2Count >= 2) {
            $score += 10;
        } else {
            $issues[] = 'Use more heading tags (H2, H3) for structure';
            $recommendations[] = 'Add 2-4 H2 headings to break up content';
        }

        // Schema markup
        if ($post->schema_markup) {
            $score += 10;
        } else {
            $issues[] = 'Missing schema markup';
            $recommendations[] = 'Add schema.org structured data';
        }

        // URL/Slug optimization
        $slugLength = strlen($post->slug);
        if ($slugLength >= 3 && $slugLength <= 75) {
            $score += 5;
        } else {
            $issues[] = 'URL slug should be concise and descriptive';
            $recommendations[] = 'Optimize URL slug (3-75 characters)';
        }

        return [
            'score' => min($score, $maxScore),
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100, 1),
            'grade' => $this->getGrade($score),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'metrics' => [
                'title_length' => $titleLength,
                'meta_length' => strlen($post->meta_description ?? ''),
                'word_count' => $wordCount,
                'tag_count' => $tagCount,
                'has_image' => (bool) $post->image,
                'internal_links' => $internalLinkCount,
                'h2_count' => $h2Count,
                'h3_count' => $h3Count,
            ],
        ];
    }

    /**
     * Get letter grade from score.
     *
     * @param float $score
     * @return string
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'C+';
        if ($score >= 65) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
