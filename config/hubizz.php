<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hubizz Brand Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all Hubizz-specific configuration settings including
    | brand identity, colors, categories, and feature settings.
    |
    */

    'name' => env('HUBIZZ_NAME', 'Hubizz'),
    'tagline' => env('HUBIZZ_TAGLINE', 'Where Content Ignites!'),
    'domain' => env('HUBIZZ_DOMAIN', 'hubizz.com'),

    /*
    |--------------------------------------------------------------------------
    | Brand Colors
    |--------------------------------------------------------------------------
    |
    | Define the core color palette for the Hubizz brand.
    |
    */
    'colors' => [
        'primary' => '#FF6B35',    // Izz Orange - Primary brand, CTAs, highlights, fire icons
        'secondary' => '#1A1A2E',  // Deep Navy - Headers, navigation, footer, dark mode base
        'accent' => '#F7931E',     // Golden Accent - Secondary CTAs, hover states, badges
        'white' => '#FFFFFF',      // Pure White - Background, cards, content areas
        'gray' => '#F5F5F7',       // Smoke Gray - Section backgrounds, separators
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories Configuration
    |--------------------------------------------------------------------------
    |
    | Hubizz features 6 main content categories with unique identities.
    |
    */
    'categories' => [
        'tech' => [
            'name' => 'Tech Buzz',
            'hungarian' => '🔥 Tech Izzás',
            'slug' => 'tech',
            'focus' => 'AI, Gadgets, Startups, Tech News',
            'icon' => '💻',
            'color' => '#FF6B35',
        ],
        'viral' => [
            'name' => 'Viral Buzz',
            'hungarian' => '🔥 Viral Buzz',
            'slug' => 'viral',
            'focus' => 'TikTok/YouTube Trends, Social Media',
            'icon' => '📱',
            'color' => '#F7931E',
        ],
        'news' => [
            'name' => 'Fresh News',
            'hungarian' => '🔥 Friss Izzás',
            'slug' => 'news',
            'focus' => 'Breaking News, Current Events',
            'icon' => '📰',
            'color' => '#1A1A2E',
        ],
        'life' => [
            'name' => 'Life Spark',
            'hungarian' => '🔥 Élet Pezsgés',
            'slug' => 'life',
            'focus' => 'Lifestyle, Motivation, Health',
            'icon' => '🌟',
            'color' => '#FF6B35',
        ],
        'biz' => [
            'name' => 'Biz Spark',
            'hungarian' => '🔥 Üzleti Szikra',
            'slug' => 'biz',
            'focus' => 'Business, Career, Finance',
            'icon' => '💼',
            'color' => '#F7931E',
        ],
        'daily' => [
            'name' => 'Daily Izz',
            'hungarian' => '🔥 Napi TOP 5',
            'slug' => 'daily',
            'focus' => 'Auto-curated Top 5 Daily Posts',
            'icon' => '🔥',
            'color' => '#FF6B35',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for AI content generation using Perplexity API.
    |
    */
    'ai' => [
        'enabled' => env('AI_ENABLED', true),
        'provider' => env('AI_PROVIDER', 'perplexity'),
        'perplexity_api_key' => env('PERPLEXITY_API_KEY'),
        'default_model' => env('AI_MODEL', 'sonar'),
        'max_tokens' => env('AI_MAX_TOKENS', 4000),
        'temperature' => env('AI_TEMPERATURE', 0.7),

        // Content generation templates
        'templates' => [
            'article' => 'Generate a comprehensive, engaging article about {topic}. Include relevant facts, current information, and make it viral-worthy.',
            'headline' => 'Create 5 clickable, SEO-friendly headlines for: {title}. Make them engaging and shareable.',
            'meta' => 'Generate a compelling meta description (150-160 chars) for: {title}',
            'tags' => 'Generate 8-10 relevant tags for this article: {title}',
            'rewrite' => 'Rewrite this content to be unique while maintaining the main message: {content}',
        ],

        // Cost tracking (per 1000 tokens)
        'cost_per_1k_tokens' => env('AI_COST_PER_1K', 0.001),

        // Auto-generate settings
        'auto_generate_meta' => env('AI_AUTO_META', true),
        'auto_generate_tags' => env('AI_AUTO_TAGS', true),
        'auto_optimize_headlines' => env('AI_AUTO_HEADLINES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | RSS Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for RSS feed aggregation and content import.
    |
    */
    'rss' => [
        'enabled' => env('RSS_ENABLED', true),
        'default_interval' => env('RSS_DEFAULT_INTERVAL', 'hourly'),
        'max_items_per_import' => env('RSS_MAX_ITEMS', 50),
        'duplicate_threshold' => env('RSS_DUPLICATE_THRESHOLD', 0.85), // Similarity percentage

        // Import settings
        'auto_publish' => env('RSS_AUTO_PUBLISH', false),
        'auto_categorize' => env('RSS_AUTO_CATEGORIZE', true),
        'download_images' => env('RSS_DOWNLOAD_IMAGES', true),
        'rewrite_content' => env('RSS_REWRITE_CONTENT', true), // Use AI to rewrite

        // Quality filters
        'min_content_length' => env('RSS_MIN_LENGTH', 200),
        'max_content_length' => env('RSS_MAX_LENGTH', 10000),
        'skip_if_no_image' => env('RSS_REQUIRE_IMAGE', false),

        // Retry settings
        'max_fail_count' => env('RSS_MAX_FAILS', 5),
        'retry_delay_minutes' => env('RSS_RETRY_DELAY', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Affiliate Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for affiliate network integrations and link management.
    |
    */
    'affiliate' => [
        'enabled' => env('AFFILIATE_ENABLED', true),
        'link_cloaking' => env('AFFILIATE_LINK_CLOAKING', true),
        'link_prefix' => env('AFFILIATE_LINK_PREFIX', 'go'),
        'auto_inject' => env('AFFILIATE_AUTO_INJECT', true),

        // Amazon settings
        'amazon' => [
            'enabled' => env('AMAZON_ENABLED', true),
            'api_key' => env('AMAZON_API_KEY'),
            'api_secret' => env('AMAZON_API_SECRET'),
            'tracking_id' => env('AMAZON_TRACKING_ID'),
            'region' => env('AMAZON_REGION', 'US'),
            'commission_rate' => env('AMAZON_COMMISSION', 4.00),
        ],

        // AliExpress settings
        'aliexpress' => [
            'enabled' => env('ALIEXPRESS_ENABLED', false),
            'api_key' => env('ALIEXPRESS_API_KEY'),
            'tracking_id' => env('ALIEXPRESS_TRACKING_ID'),
            'commission_rate' => env('ALIEXPRESS_COMMISSION', 5.00),
        ],

        // eBay settings
        'ebay' => [
            'enabled' => env('EBAY_ENABLED', false),
            'campaign_id' => env('EBAY_CAMPAIGN_ID'),
            'commission_rate' => env('EBAY_COMMISSION', 3.00),
        ],

        // Link injection settings
        'max_links_per_post' => env('AFFILIATE_MAX_LINKS', 5),
        'min_price' => env('AFFILIATE_MIN_PRICE', 10.00),
        'add_comparison_boxes' => env('AFFILIATE_COMPARISON_BOXES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trending Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for trending topic discovery and viral scoring.
    |
    */
    'trending' => [
        'enabled' => env('TRENDING_ENABLED', true),
        'update_interval' => env('TRENDING_UPDATE_INTERVAL', 'hourly'),
        'min_viral_score' => env('TRENDING_MIN_SCORE', 50),

        // Google Trends
        'google' => [
            'enabled' => env('GOOGLE_TRENDS_ENABLED', true),
            'api_key' => env('GOOGLE_API_KEY'),
            'regions' => env('GOOGLE_REGIONS', 'US,UK,CA'),
            'max_trends' => env('GOOGLE_MAX_TRENDS', 20),
        ],

        // Twitter/X
        'twitter' => [
            'enabled' => env('TWITTER_TRENDS_ENABLED', false),
            'bearer_token' => env('TWITTER_BEARER_TOKEN'),
            'max_trends' => env('TWITTER_MAX_TRENDS', 15),
        ],

        // Reddit
        'reddit' => [
            'enabled' => env('REDDIT_TRENDS_ENABLED', false),
            'subreddits' => env('REDDIT_SUBREDDITS', 'all,news,technology,worldnews'),
            'max_trends' => env('REDDIT_MAX_TRENDS', 10),
        ],

        // Auto-posting
        'auto_create_posts' => env('TRENDING_AUTO_POST', false),
        'auto_post_min_score' => env('TRENDING_AUTO_MIN_SCORE', 70),
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Izz Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Daily Izz (Top 5 daily posts) feature.
    |
    */
    'daily_izz' => [
        'enabled' => env('DAILY_IZZ_ENABLED', true),
        'post_count' => env('DAILY_IZZ_COUNT', 5),
        'curation_time' => env('DAILY_IZZ_TIME', '06:00'), // 6 AM daily
        'min_viral_score' => env('DAILY_IZZ_MIN_SCORE', 70),

        // Selection criteria
        'scoring_weights' => [
            'viral_score' => 40,
            'views' => 25,
            'shares' => 20,
            'comments' => 10,
            'reactions' => 5,
        ],

        // AI summary
        'generate_summary' => env('DAILY_IZZ_SUMMARY', true),
        'summary_length' => env('DAILY_IZZ_SUMMARY_LENGTH', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Story Cards Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for interactive story cards feature.
    |
    */
    'story_cards' => [
        'enabled' => env('STORY_CARDS_ENABLED', true),
        'max_cards_per_post' => env('STORY_MAX_CARDS', 10),
        'allow_reactions' => env('STORY_REACTIONS', true),

        // Card types enabled
        'types' => [
            'swipe' => env('STORY_TYPE_SWIPE', true),
            'before_after' => env('STORY_TYPE_BEFORE_AFTER', true),
            'numbered' => env('STORY_TYPE_NUMBERED', true),
            'this_or_that' => env('STORY_TYPE_THIS_OR_THAT', true),
        ],

        // Reaction emojis
        'reactions' => [
            'hot' => '🔥',
            'not' => '❄️',
            'hmm' => '🤔',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    |
    | SEO optimization settings for Hubizz.
    |
    */
    'seo' => [
        'auto_generate_meta' => env('SEO_AUTO_META', true),
        'auto_generate_schema' => env('SEO_AUTO_SCHEMA', true),
        'sitemap_enabled' => env('SEO_SITEMAP', true),
        'sitemap_update_frequency' => env('SEO_SITEMAP_FREQUENCY', 'daily'),

        // Schema.org settings
        'schema_types' => [
            'Article',
            'NewsArticle',
            'BlogPosting',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance and caching configuration.
    |
    */
    'performance' => [
        'cache_duration' => env('HUBIZZ_CACHE_DURATION', 3600), // 1 hour
        'image_optimization' => env('HUBIZZ_IMAGE_OPTIMIZATION', true),
        'lazy_loading' => env('HUBIZZ_LAZY_LOADING', true),
        'cdn_enabled' => env('HUBIZZ_CDN', false),
        'cdn_url' => env('HUBIZZ_CDN_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle features on/off for testing and gradual rollout.
    |
    */
    'features' => [
        'ai_content_generation' => env('FEATURE_AI_GENERATION', true),
        'rss_aggregation' => env('FEATURE_RSS', true),
        'affiliate_links' => env('FEATURE_AFFILIATES', true),
        'trending_topics' => env('FEATURE_TRENDING', true),
        'daily_izz' => env('FEATURE_DAILY_IZZ', true),
        'story_cards' => env('FEATURE_STORY_CARDS', true),
        'dark_mode' => env('FEATURE_DARK_MODE', true),
    ],
];
