# Phase 1: Foundation (Week 1-2)

## 🎯 Goal
Set up new database structure and base service classes for the Hubizz platform transformation.

## 📋 Overview
This phase establishes the foundation for all future development by creating the necessary database schema, Eloquent models, configuration files, and service class structure.

---

## Tasks Breakdown

### Task 1.1: Create Database Migrations (13 migrations)

#### Migration 1: RSS Feeds Table
**File**: `database/migrations/YYYY_MM_DD_000001_create_rss_feeds_table.php`

```php
Schema::create('rss_feeds', function (Blueprint $table) {
    $table->id();
    $table->string('url')->unique();
    $table->string('title');
    $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
    $table->enum('fetch_interval', ['15min', 'hourly', 'daily'])->default('hourly');
    $table->boolean('is_active')->default(true);
    $table->integer('priority')->default(0); // Higher = more important
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamp('last_success_at')->nullable();
    $table->integer('fail_count')->default(0);
    $table->json('metadata')->nullable(); // Store feed info
    $table->timestamps();

    $table->index(['is_active', 'fetch_interval']);
    $table->index('last_checked_at');
});
```

#### Migration 2: RSS Imports Table
**File**: `database/migrations/YYYY_MM_DD_000002_create_rss_imports_table.php`

```php
Schema::create('rss_imports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rss_feed_id')->constrained('rss_feeds')->onDelete('cascade');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->integer('items_found')->default(0);
    $table->integer('items_imported')->default(0);
    $table->integer('items_skipped')->default(0); // Duplicates
    $table->text('error_message')->nullable();
    $table->json('import_log')->nullable(); // Detailed import info
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['rss_feed_id', 'status']);
    $table->index('created_at');
});
```

#### Migration 3: Affiliate Networks Table
**File**: `database/migrations/YYYY_MM_DD_000003_create_affiliate_networks_table.php`

```php
Schema::create('affiliate_networks', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Amazon, AliExpress, eBay
    $table->string('slug')->unique();
    $table->boolean('is_active')->default(true);
    $table->string('api_key')->nullable();
    $table->string('api_secret')->nullable();
    $table->string('tracking_id')->nullable();
    $table->decimal('commission_rate', 5, 2)->default(0.00); // Percentage
    $table->json('config')->nullable(); // Network-specific settings
    $table->timestamps();

    $table->index('is_active');
});
```

#### Migration 4: Affiliate Products Table
**File**: `database/migrations/YYYY_MM_DD_000004_create_affiliate_products_table.php`

```php
Schema::create('affiliate_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('network_id')->constrained('affiliate_networks')->onDelete('cascade');
    $table->string('external_id')->nullable(); // Product ID from network
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    $table->string('image_url')->nullable();
    $table->text('affiliate_url');
    $table->json('metadata')->nullable(); // Brand, category, etc.
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['network_id', 'external_id']);
    $table->index('is_active');
    $table->fulltext(['name', 'description']); // For product matching
});
```

#### Migration 5: Affiliate Links Table
**File**: `database/migrations/YYYY_MM_DD_000005_create_affiliate_links_table.php`

```php
Schema::create('affiliate_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('affiliate_products')->onDelete('cascade');
    $table->string('short_code')->unique(); // For link cloaking
    $table->text('original_url');
    $table->text('cloaked_url'); // /go/xyz123
    $table->integer('clicks')->default(0);
    $table->integer('conversions')->default(0);
    $table->decimal('revenue', 10, 2)->default(0.00);
    $table->json('utm_parameters')->nullable();
    $table->timestamps();

    $table->index('post_id');
    $table->index('product_id');
    $table->index('short_code');
});
```

#### Migration 6: Affiliate Clicks Table
**File**: `database/migrations/YYYY_MM_DD_000006_create_affiliate_clicks_table.php`

```php
Schema::create('affiliate_clicks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('link_id')->constrained('affiliate_links')->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->ipAddress('ip_address');
    $table->string('user_agent')->nullable();
    $table->string('referer')->nullable();
    $table->string('country_code', 2)->nullable();
    $table->timestamp('clicked_at');

    $table->index('link_id');
    $table->index('clicked_at');
});
```

#### Migration 7: Trending Topics Table
**File**: `database/migrations/YYYY_MM_DD_000007_create_trending_topics_table.php`

```php
Schema::create('trending_topics', function (Blueprint $table) {
    $table->id();
    $table->string('keyword');
    $table->enum('source', ['google', 'twitter', 'reddit', 'manual'])->default('google');
    $table->integer('score')->default(0); // Viral potential score
    $table->string('region', 2)->nullable(); // Country code
    $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
    $table->boolean('is_used')->default(false); // Has content been created?
    $table->foreignId('post_id')->nullable()->constrained('posts')->onDelete('set null');
    $table->json('metadata')->nullable(); // Additional trend data
    $table->timestamp('trending_at');
    $table->timestamps();

    $table->index(['source', 'trending_at']);
    $table->index('is_used');
    $table->index('score');
});
```

#### Migration 8: Content Scores Table
**File**: `database/migrations/YYYY_MM_DD_000008_create_content_scores_table.php`

```php
Schema::create('content_scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->unique()->constrained('posts')->onDelete('cascade');
    $table->integer('viral_score')->default(0); // 0-100
    $table->integer('views')->default(0);
    $table->integer('shares')->default(0);
    $table->integer('comments')->default(0);
    $table->integer('reactions')->default(0);
    $table->decimal('engagement_rate', 5, 2)->default(0.00); // Percentage
    $table->decimal('ctr', 5, 2)->default(0.00); // Click-through rate
    $table->timestamp('last_calculated_at')->nullable();
    $table->timestamps();

    $table->index('viral_score');
    $table->index('engagement_rate');
});
```

#### Migration 9: AI Generations Table
**File**: `database/migrations/YYYY_MM_DD_000009_create_ai_generations_table.php`

```php
Schema::create('ai_generations', function (Blueprint $table) {
    $table->id();
    $table->enum('type', ['article', 'headline', 'meta', 'tags', 'rewrite'])->default('article');
    $table->text('input'); // Original prompt/content
    $table->longText('output'); // Generated content
    $table->string('model')->default('perplexity'); // AI model used
    $table->integer('tokens_used')->default(0);
    $table->decimal('cost', 8, 4)->default(0.0000); // USD
    $table->foreignId('post_id')->nullable()->constrained('posts')->onDelete('set null');
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();

    $table->index('type');
    $table->index('created_at');
});
```

#### Migration 10: Daily Izz Table
**File**: `database/migrations/YYYY_MM_DD_000010_create_daily_izz_table.php`

```php
Schema::create('daily_izz', function (Blueprint $table) {
    $table->id();
    $table->date('date')->unique();
    $table->json('post_ids'); // Array of top 5 post IDs
    $table->text('summary')->nullable(); // AI-generated summary
    $table->integer('total_views')->default(0);
    $table->integer('total_shares')->default(0);
    $table->timestamp('curated_at')->nullable();
    $table->timestamps();

    $table->index('date');
});
```

#### Migration 11: Story Cards Table
**File**: `database/migrations/YYYY_MM_DD_000011_create_story_cards_table.php`

```php
Schema::create('story_cards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
    $table->enum('type', ['swipe', 'before_after', 'numbered', 'this_or_that'])->default('swipe');
    $table->json('content'); // Card-specific data
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index('post_id');
    $table->index('type');
});
```

#### Migration 12: Story Reactions Table
**File**: `database/migrations/YYYY_MM_DD_000012_create_story_reactions_table.php`

```php
Schema::create('story_reactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained('story_cards')->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->ipAddress('ip_address')->nullable();
    $table->enum('reaction_type', ['hot', 'not', 'hmm']); // 🔥 ❄️ 🤔
    $table->timestamps();

    $table->unique(['card_id', 'user_id']);
    $table->index('card_id');
    $table->index('reaction_type');
});
```

#### Migration 13: Content Hashes Table
**File**: `database/migrations/YYYY_MM_DD_000013_create_content_hashes_table.php`

```php
Schema::create('content_hashes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->unique()->constrained('posts')->onDelete('cascade');
    $table->string('title_hash', 64); // SHA-256 hash
    $table->string('content_hash', 64); // SHA-256 hash
    $table->timestamps();

    $table->index('title_hash');
    $table->index('content_hash');
});
```

---

### Task 1.2: Create Eloquent Models

#### Model 1: RssFeed Model
**File**: `app/Models/RssFeed.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RssFeed extends Model
{
    protected $fillable = [
        'url',
        'title',
        'category_id',
        'fetch_interval',
        'is_active',
        'priority',
        'last_checked_at',
        'last_success_at',
        'fail_count',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_checked_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(RssImport::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForCheck($query)
    {
        // Add logic for fetch_interval check
        return $query->where('is_active', true);
    }
}
```

#### Model 2: RssImport Model
**File**: `app/Models/RssImport.php`

#### Model 3: AffiliateNetwork Model
**File**: `app/Models/AffiliateNetwork.php`

#### Model 4: AffiliateProduct Model
**File**: `app/Models/AffiliateProduct.php`

#### Model 5: AffiliateLink Model
**File**: `app/Models/AffiliateLink.php`

#### Model 6: AffiliateClick Model
**File**: `app/Models/AffiliateClick.php`

#### Model 7: TrendingTopic Model
**File**: `app/Models/TrendingTopic.php`

#### Model 8: ContentScore Model
**File**: `app/Models/ContentScore.php`

#### Model 9: AIGeneration Model
**File**: `app/Models/AIGeneration.php`

#### Model 10: DailyIzz Model
**File**: `app/Models/DailyIzz.php`

#### Model 11: StoryCard Model
**File**: `app/Models/StoryCard.php`

#### Model 12: StoryReaction Model
**File**: `app/Models/StoryReaction.php`

#### Model 13: ContentHash Model
**File**: `app/Models/ContentHash.php`

---

### Task 1.3: Create Configuration File

**File**: `config/hubizz.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hubizz Brand Configuration
    |--------------------------------------------------------------------------
    */
    'name' => env('HUBIZZ_NAME', 'Hubizz'),
    'tagline' => env('HUBIZZ_TAGLINE', 'Where Content Ignites!'),
    'domain' => env('HUBIZZ_DOMAIN', 'hubizz.com'),

    /*
    |--------------------------------------------------------------------------
    | Brand Colors
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'primary' => '#FF6B35',    // Izz Orange
        'secondary' => '#1A1A2E',  // Deep Navy
        'accent' => '#F7931E',     // Golden Accent
        'white' => '#FFFFFF',      // Pure White
        'gray' => '#F5F5F7',       // Smoke Gray
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories Configuration
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'tech' => [
            'name' => 'Tech Buzz',
            'hungarian' => '🔥 Tech Izzás',
            'slug' => 'tech',
            'focus' => 'AI, Gadgets, Startups, Tech News',
        ],
        'viral' => [
            'name' => 'Viral Buzz',
            'hungarian' => '🔥 Viral Buzz',
            'slug' => 'viral',
            'focus' => 'TikTok/YouTube Trends, Social Media',
        ],
        'news' => [
            'name' => 'Fresh News',
            'hungarian' => '🔥 Friss Izzás',
            'slug' => 'news',
            'focus' => 'Breaking News, Current Events',
        ],
        'life' => [
            'name' => 'Life Spark',
            'hungarian' => '🔥 Élet Pezsgés',
            'slug' => 'life',
            'focus' => 'Lifestyle, Motivation, Health',
        ],
        'biz' => [
            'name' => 'Biz Spark',
            'hungarian' => '🔥 Üzleti Szikra',
            'slug' => 'biz',
            'focus' => 'Business, Career, Finance',
        ],
        'daily' => [
            'name' => 'Daily Izz',
            'hungarian' => '🔥 Napi TOP 5',
            'slug' => 'daily',
            'focus' => 'Auto-curated Top 5 Daily Posts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Settings
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'provider' => env('AI_PROVIDER', 'perplexity'),
        'perplexity_api_key' => env('PERPLEXITY_API_KEY'),
        'default_model' => env('AI_MODEL', 'sonar'),
        'max_tokens' => env('AI_MAX_TOKENS', 4000),
        'temperature' => env('AI_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | RSS Settings
    |--------------------------------------------------------------------------
    */
    'rss' => [
        'default_interval' => env('RSS_DEFAULT_INTERVAL', 'hourly'),
        'max_items_per_import' => env('RSS_MAX_ITEMS', 50),
        'duplicate_threshold' => env('RSS_DUPLICATE_THRESHOLD', 0.85), // Similarity %
    ],

    /*
    |--------------------------------------------------------------------------
    | Affiliate Settings
    |--------------------------------------------------------------------------
    */
    'affiliate' => [
        'amazon' => [
            'api_key' => env('AMAZON_API_KEY'),
            'api_secret' => env('AMAZON_API_SECRET'),
            'tracking_id' => env('AMAZON_TRACKING_ID'),
            'enabled' => env('AMAZON_ENABLED', true),
        ],
        'link_cloaking' => env('AFFILIATE_LINK_CLOAKING', true),
        'link_prefix' => env('AFFILIATE_LINK_PREFIX', 'go'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trending Settings
    |--------------------------------------------------------------------------
    */
    'trending' => [
        'google_api_key' => env('GOOGLE_API_KEY'),
        'twitter_bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'update_interval' => env('TRENDING_UPDATE_INTERVAL', 'hourly'),
        'min_viral_score' => env('TRENDING_MIN_SCORE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Izz Settings
    |--------------------------------------------------------------------------
    */
    'daily_izz' => [
        'post_count' => 5,
        'curation_time' => env('DAILY_IZZ_TIME', '06:00'), // 6 AM
        'min_score' => env('DAILY_IZZ_MIN_SCORE', 70),
    ],
];
```

---

### Task 1.4: Update Environment File

Add to `.env`:

```env
# Hubizz Configuration
HUBIZZ_NAME="Hubizz"
HUBIZZ_TAGLINE="Where Content Ignites!"
HUBIZZ_DOMAIN="hubizz.com"

# AI Configuration
AI_PROVIDER=perplexity
PERPLEXITY_API_KEY=your_api_key_here
AI_MODEL=sonar
AI_MAX_TOKENS=4000
AI_TEMPERATURE=0.7

# Amazon Affiliate
AMAZON_API_KEY=
AMAZON_API_SECRET=
AMAZON_TRACKING_ID=
AMAZON_ENABLED=true

# Google APIs
GOOGLE_API_KEY=

# Twitter API
TWITTER_BEARER_TOKEN=

# RSS Configuration
RSS_DEFAULT_INTERVAL=hourly
RSS_MAX_ITEMS=50

# Queue Configuration (update if needed)
QUEUE_CONNECTION=redis
```

---

### Task 1.5: Create Base Service Structure

Create service directories:

```bash
mkdir -p app/Services/AI
mkdir -p app/Services/RSS
mkdir -p app/Services/Affiliate
mkdir -p app/Services/Trends
mkdir -p app/Services/SEO
```

Create base service class:

**File**: `app/Services/BaseService.php`

```php
<?php

namespace App\Services;

abstract class BaseService
{
    protected function logError(string $message, array $context = []): void
    {
        \Log::error($message, $context);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        \Log::info($message, $context);
    }
}
```

---

## ✅ Completion Checklist

- [ ] All 13 migrations created and reviewed
- [ ] All 13 Eloquent models created with relationships
- [ ] config/hubizz.php created and configured
- [ ] .env file updated with new variables
- [ ] Service directory structure created
- [ ] BaseService class created
- [ ] Run migrations: `php artisan migrate`
- [ ] Test model relationships in tinker
- [ ] Git commit: "Phase 1: Foundation complete"

---

## 🔄 Next Steps

After completing Phase 1, proceed to:
- [PHASE2-AI-INTEGRATION.md](PHASE2-AI-INTEGRATION.md) - Implement AI content generation

---

## 📝 Notes

- Make sure to backup database before running migrations
- Test each model's relationships in Laravel Tinker
- Verify all foreign keys are properly set up
- Check that indexes are created for frequently queried columns

---

**Phase 1 Foundation**: Complete database structure for Hubizz transformation 🔥
