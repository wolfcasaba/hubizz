<?php

namespace App\Services\AI;

use App\Services\BaseService;

/**
 * Headline Optimizer Service
 *
 * Generates and optimizes headlines for maximum click-through rate (CTR).
 * Uses AI to create engaging, viral-worthy headlines.
 */
class HeadlineOptimizerService extends BaseService
{
    protected PerplexityService $perplexity;

    public function __construct(PerplexityService $perplexity)
    {
        $this->perplexity = $perplexity;
    }

    /**
     * Generate multiple headline variations.
     *
     * @param string $title
     * @param int $count
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function generateVariations(string $title, int $count = 5, array $options = []): array
    {
        $this->logInfo('Generating headline variations', ['title' => $title, 'count' => $count]);

        $headlines = $this->perplexity->generateHeadlines($title, $count, $options);

        // Score each headline
        $scoredHeadlines = array_map(function($headline) {
            return [
                'headline' => $headline,
                'score' => $this->scoreHeadline($headline),
                'length' => strlen($headline),
                'word_count' => str_word_count($headline),
            ];
        }, $headlines);

        // Sort by score
        usort($scoredHeadlines, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredHeadlines;
    }

    /**
     * Generate the best optimized headline.
     *
     * @param string $title
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function generateBestHeadline(string $title, array $options = []): string
    {
        $variations = $this->generateVariations($title, $options['count'] ?? 5, $options);

        if (empty($variations)) {
            return $title;
        }

        // Return the highest scoring headline
        return $variations[0]['headline'];
    }

    /**
     * Score a headline for viral potential (0-100).
     *
     * @param string $headline
     * @return float
     */
    public function scoreHeadline(string $headline): float
    {
        $score = 50.0; // Base score

        $length = strlen($headline);
        $wordCount = str_word_count($headline);
        $lowerHeadline = strtolower($headline);

        // Length optimization (ideal: 50-70 characters)
        if ($length >= 50 && $length <= 70) {
            $score += 15;
        } elseif ($length >= 40 && $length <= 80) {
            $score += 10;
        } elseif ($length > 100) {
            $score -= 10;
        }

        // Word count (ideal: 6-12 words)
        if ($wordCount >= 6 && $wordCount <= 12) {
            $score += 10;
        } elseif ($wordCount >= 4 && $wordCount <= 15) {
            $score += 5;
        }

        // Power words that increase CTR
        $powerWords = [
            'ultimate', 'complete', 'essential', 'perfect', 'amazing', 'incredible',
            'shocking', 'secret', 'proven', 'powerful', 'exclusive', 'free',
            'new', 'best', 'top', 'how to', 'why', 'what', 'guide', 'tips',
        ];

        foreach ($powerWords as $word) {
            if (str_contains($lowerHeadline, $word)) {
                $score += 3;
            }
        }

        // Numbers attract attention
        if (preg_match('/\d+/', $headline)) {
            $score += 8;
        }

        // Question marks can increase engagement
        if (str_contains($headline, '?')) {
            $score += 5;
        }

        // Emotional words
        $emotionalWords = [
            'love', 'hate', 'fear', 'surprise', 'anger', 'joy', 'disgust',
            'trust', 'anticipation', 'unbelievable', 'stunning', 'heartbreaking',
        ];

        foreach ($emotionalWords as $word) {
            if (str_contains($lowerHeadline, $word)) {
                $score += 4;
            }
        }

        // Urgency words
        $urgencyWords = ['now', 'today', 'urgent', 'breaking', 'just', 'latest', 'trending'];
        foreach ($urgencyWords as $word) {
            if (str_contains($lowerHeadline, $word)) {
                $score += 3;
            }
        }

        // Negative words (curiosity gap)
        $negativeWords = ['never', 'stop', 'avoid', 'worst', 'don\'t', 'no', 'without'];
        foreach ($negativeWords as $word) {
            if (str_contains($lowerHeadline, $word)) {
                $score += 2;
            }
        }

        // Capitalize first letter (standard practice)
        if (ctype_upper($headline[0])) {
            $score += 2;
        }

        // ALL CAPS is bad
        if ($headline === strtoupper($headline) && $length > 5) {
            $score -= 15;
        }

        // Excessive punctuation is bad
        $punctuationCount = preg_match_all('/[!?.,;:]/', $headline);
        if ($punctuationCount > 3) {
            $score -= 5;
        }

        // Cap score at 0-100
        return max(0, min(100, $score));
    }

    /**
     * Analyze headline performance factors.
     *
     * @param string $headline
     * @return array
     */
    public function analyzeHeadline(string $headline): array
    {
        $score = $this->scoreHeadline($headline);
        $length = strlen($headline);
        $wordCount = str_word_count($headline);

        $analysis = [
            'headline' => $headline,
            'score' => round($score, 2),
            'length' => $length,
            'word_count' => $wordCount,
            'has_number' => (bool) preg_match('/\d+/', $headline),
            'has_question' => str_contains($headline, '?'),
            'is_all_caps' => $headline === strtoupper($headline),
            'factors' => [],
            'recommendations' => [],
        ];

        // Length analysis
        if ($length >= 50 && $length <= 70) {
            $analysis['factors'][] = 'Optimal length (50-70 chars)';
        } elseif ($length < 50) {
            $analysis['recommendations'][] = 'Consider adding more context (ideal: 50-70 chars)';
        } else {
            $analysis['recommendations'][] = 'Consider shortening (ideal: 50-70 chars)';
        }

        // Word count analysis
        if ($wordCount >= 6 && $wordCount <= 12) {
            $analysis['factors'][] = 'Optimal word count (6-12 words)';
        } else {
            $analysis['recommendations'][] = 'Aim for 6-12 words for best engagement';
        }

        // Number check
        if (!$analysis['has_number']) {
            $analysis['recommendations'][] = 'Adding a number can increase CTR';
        } else {
            $analysis['factors'][] = 'Contains number (increases CTR)';
        }

        // Question mark
        if ($analysis['has_question']) {
            $analysis['factors'][] = 'Question format (increases engagement)';
        }

        // All caps warning
        if ($analysis['is_all_caps'] && $length > 5) {
            $analysis['recommendations'][] = 'Avoid ALL CAPS - it reduces credibility';
        }

        // Score rating
        if ($score >= 80) {
            $analysis['rating'] = 'Excellent';
        } elseif ($score >= 65) {
            $analysis['rating'] = 'Good';
        } elseif ($score >= 50) {
            $analysis['rating'] = 'Average';
        } else {
            $analysis['rating'] = 'Needs Improvement';
        }

        return $analysis;
    }

    /**
     * Get headline templates for different content types.
     *
     * @param string $type
     * @return array
     */
    public function getTemplates(string $type = 'all'): array
    {
        $templates = [
            'list' => [
                '{number} Ways to {action}',
                '{number} {adjective} Tips for {topic}',
                'Top {number} {items} That {action}',
                'The Ultimate List of {number} {items}',
            ],
            'how_to' => [
                'How to {action} in {timeframe}',
                'The Complete Guide to {topic}',
                'How to {action} (Step-by-Step)',
                'The Ultimate Guide to {topic}',
            ],
            'question' => [
                'Why Do {people} {action}?',
                'What Happens When {event}?',
                'Are {items} Really {adjective}?',
                'Is {topic} Worth It?',
            ],
            'comparison' => [
                '{item1} vs {item2}: Which Is Better?',
                '{number} Differences Between {item1} and {item2}',
                'Why {item1} Is Better Than {item2}',
            ],
            'news' => [
                'Breaking: {event} Just Happened',
                '{topic}: Everything You Need to Know',
                'Latest Update on {topic}',
                'What\'s Happening with {topic}',
            ],
            'curiosity' => [
                'You Won\'t Believe What {subject} Did',
                'The Secret {adjective} {topic}',
                'What Nobody Tells You About {topic}',
                'The Truth About {topic}',
            ],
        ];

        return $type === 'all' ? $templates : ($templates[$type] ?? []);
    }
}
