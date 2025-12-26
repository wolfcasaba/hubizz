# 🚀 Hubizz - Quick Start Guide

**Transform your Buzzy Media Script into a modern AI-powered viral content platform**

## 📚 Documentation Structure

1. **[HUBIZZ-DEVELOPMENT-ROADMAP.md](HUBIZZ-DEVELOPMENT-ROADMAP.md)** - Complete project overview and roadmap
2. **[docs/PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md)** - Detailed Phase 1 implementation guide
3. **This file** - Quick reference for getting started

---

## ✅ Pre-Flight Checklist

### Before You Begin:

- [ ] Backup your current Buzzy database
- [ ] Backup your current Buzzy files
- [ ] Create a new Git branch: `git checkout -b feature/hubizz-transformation`
- [ ] Ensure you have PHP 8.1+, MySQL 8.0+, Redis, and Composer installed

---

## 🎯 Development Path Overview

```
Phase 1: Foundation          → Database & Models (Week 1-2)
    ↓
Phase 2: AI Integration      → Perplexity API (Week 3-4)
    ↓
Phase 3: RSS & Automation    → Feed Aggregation (Week 5-6)
    ↓
Phase 4: Monetization        → Affiliate System (Week 7-8)
    ↓
Phase 5: UI/Theme           → Hubizz Theme (Week 9-10)
```

---

## 🔥 Phase 1: Get Started (Start Here!)

### Step 1: Review Current Codebase
Your existing Laravel Buzzy script has been analyzed. Key findings:
- 23 existing models (User, Post, Category, Comment, etc.)
- 50+ existing migrations
- Admin panel with 120+ routes
- 5 existing themes
- Full multi-language support

### Step 2: Create Database Migrations

Run these commands to create all 13 new migrations:

```bash
# Create migrations for new tables
php artisan make:migration create_rss_feeds_table
php artisan make:migration create_rss_imports_table
php artisan make:migration create_affiliate_networks_table
php artisan make:migration create_affiliate_products_table
php artisan make:migration create_affiliate_links_table
php artisan make:migration create_affiliate_clicks_table
php artisan make:migration create_trending_topics_table
php artisan make:migration create_content_scores_table
php artisan make:migration create_ai_generations_table
php artisan make:migration create_daily_izz_table
php artisan make:migration create_story_cards_table
php artisan make:migration create_story_reactions_table
php artisan make:migration create_content_hashes_table
```

**Detailed migration schemas** are in [docs/PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md#task-11-create-database-migrations-13-migrations)

### Step 3: Create Models

```bash
# Create all model files
php artisan make:model RssFeed
php artisan make:model RssImport
php artisan make:model AffiliateNetwork
php artisan make:model AffiliateProduct
php artisan make:model AffiliateLink
php artisan make:model AffiliateClick
php artisan make:model TrendingTopic
php artisan make:model ContentScore
php artisan make:model AIGeneration
php artisan make:model DailyIzz
php artisan make:model StoryCard
php artisan make:model StoryReaction
php artisan make:model ContentHash
```

**Model code with relationships** is in [docs/PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md#task-12-create-eloquent-models)

### Step 4: Create Configuration

Create `config/hubizz.php` with brand identity, colors, and settings.

**Complete config file** is in [docs/PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md#task-13-create-configuration-file)

### Step 5: Update Environment

Add these to your `.env` file:

```env
# Hubizz Configuration
HUBIZZ_NAME="Hubizz"
HUBIZZ_TAGLINE="Where Content Ignites!"
HUBIZZ_DOMAIN="hubizz.com"

# AI Configuration
PERPLEXITY_API_KEY=your_api_key_here

# Amazon Affiliate
AMAZON_API_KEY=
AMAZON_TRACKING_ID=

# Google & Twitter APIs
GOOGLE_API_KEY=
TWITTER_BEARER_TOKEN=

# Queue (important!)
QUEUE_CONNECTION=redis
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Test Models

```bash
php artisan tinker

# Test creating a feed
>>> $feed = App\Models\RssFeed::create(['url' => 'https://example.com/feed', 'title' => 'Test Feed']);

# Test relationships
>>> $feed->category
>>> $feed->imports
```

---

## 🤖 Using Claude Code AI Prompts

You can use these prompts with Claude Code AI to speed up development:

### Master Prompts (Use in Order):

**PROMPT A**: Codebase Analysis ✅ (Already completed!)
```
"Analyze this Laravel Buzzy script. Identify: database schema, Post/Category models,
admin panel, theme system. Create report."
```

**PROMPT B**: Create Migrations
```
"Create all 13 migrations for Hubizz based on the schema in docs/PHASE1-FOUNDATION.md:
rss_feeds, rss_imports, affiliate_networks, affiliate_products, affiliate_links,
affiliate_clicks, trending_topics, content_scores, story_cards, story_reactions,
daily_izz, ai_generations, content_hashes. Follow Laravel 10 best practices."
```

**PROMPT C**: Create Models
```
"Create all 13 Eloquent models for Hubizz with proper relationships, casts, and
scopes based on docs/PHASE1-FOUNDATION.md. Include: RssFeed, RssImport,
AffiliateNetwork, AffiliateProduct, AffiliateLink, AffiliateClick, TrendingTopic,
ContentScore, AIGeneration, DailyIzz, StoryCard, StoryReaction, ContentHash."
```

**PROMPT D**: Create Config File
```
"Create config/hubizz.php configuration file with brand settings, colors,
categories, AI settings, RSS settings, affiliate settings, trending settings,
and Daily Izz settings based on the specification in docs/PHASE1-FOUNDATION.md."
```

---

## 📦 Required API Keys & Accounts

### Immediate (Phase 1-2):
- [ ] **Perplexity AI**: Register at https://perplexity.ai for content generation
- [ ] **Redis**: Install locally for queue processing

### Coming Soon (Phase 3-4):
- [ ] **Amazon Associates**: Register for affiliate program
- [ ] **Google Cloud**: Create project for Trends API
- [ ] **Twitter/X API**: Get bearer token for trending topics

---

## 🎨 Brand Identity Reference

### Colors
- **Primary (Izz Orange)**: #FF6B35
- **Secondary (Deep Navy)**: #1A1A2E
- **Accent (Golden)**: #F7931E

### Categories
1. 🔥 Tech Buzz (`/tech`) - AI, Gadgets, Tech News
2. 🔥 Viral Buzz (`/viral`) - TikTok/YouTube Trends
3. 🔥 Fresh News (`/news`) - Breaking News
4. 🔥 Life Spark (`/life`) - Lifestyle, Health
5. 🔥 Biz Spark (`/biz`) - Business, Finance
6. 🔥 Daily Izz (`/daily`) - Top 5 Daily Posts

---

## 🔧 Development Commands

### Useful Laravel Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate
php artisan migrate:fresh  # WARNING: Drops all tables!

# Create models, migrations, controllers
php artisan make:model ModelName -m
php artisan make:controller ControllerName
php artisan make:migration migration_name

# Queue commands (important for RSS, AI, trending)
php artisan queue:work
php artisan queue:listen
php artisan queue:restart

# Testing
php artisan tinker
```

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/hubizz-transformation

# Commit after each major task
git add .
git commit -m "Phase 1: Created database migrations"
git commit -m "Phase 1: Created Eloquent models"
git commit -m "Phase 1: Foundation complete"

# Merge when phase is complete
git checkout main
git merge feature/hubizz-transformation
```

---

## 📊 Progress Tracking

### Phase 1: Foundation (Current)
- [ ] 13 database migrations created
- [ ] 13 Eloquent models created
- [ ] config/hubizz.php created
- [ ] .env updated
- [ ] Service directory structure created
- [ ] Migrations run successfully
- [ ] Models tested in Tinker

### Phase 2: AI Integration (Next)
- [ ] PerplexityService created
- [ ] ContentGeneratorService created
- [ ] HeadlineOptimizerService created
- [ ] AI admin panel created
- [ ] Content generation tested

### Phase 3: RSS & Automation
- [ ] FeedAggregatorService created
- [ ] DuplicateDetectorService created
- [ ] ContentImporterService created
- [ ] RSS admin panel created
- [ ] Feed import tested

### Phase 4: Monetization
- [ ] ProductMatcherService created
- [ ] LinkInjectorService created
- [ ] Affiliate admin panel created
- [ ] Link tracking tested

### Phase 5: UI/Theme
- [ ] Hubizz theme created
- [ ] Components built
- [ ] Dark mode implemented
- [ ] Mobile optimized

---

## 🆘 Common Issues & Solutions

### Migration Issues

**Problem**: Foreign key constraint fails
```bash
# Solution: Check migration order, foreign tables must exist first
php artisan migrate:fresh
```

**Problem**: Column already exists
```bash
# Solution: Check for duplicate migrations
php artisan migrate:status
```

### Queue Issues

**Problem**: Jobs not processing
```bash
# Solution: Make sure Redis is running and queue worker is active
redis-cli ping  # Should return PONG
php artisan queue:work
```

### Model Issues

**Problem**: Relationship not working
```bash
# Solution: Check foreign key names and relationship methods
php artisan tinker
>>> $model->relationship  # Test relationship
```

---

## 📖 Full Documentation Links

- **[Complete Roadmap](HUBIZZ-DEVELOPMENT-ROADMAP.md)** - Full project overview
- **[Phase 1 Guide](docs/PHASE1-FOUNDATION.md)** - Detailed implementation steps
- **[Original Project Plan](hubizz%20%20project%20plan/)** - Business requirements

---

## 🎓 Learning Resources

- **Laravel 10 Docs**: https://laravel.com/docs/10.x
- **Eloquent ORM**: https://laravel.com/docs/10.x/eloquent
- **Migrations**: https://laravel.com/docs/10.x/migrations
- **Queues**: https://laravel.com/docs/10.x/queues
- **Redis**: https://redis.io/documentation

---

## 🎯 Your Next Action

**Start with Phase 1, Task 1.1**: Create the first database migration

1. Open [docs/PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md)
2. Copy the migration code for `create_rss_feeds_table`
3. Run: `php artisan make:migration create_rss_feeds_table`
4. Paste the code into the migration file
5. Repeat for all 13 migrations
6. Run: `php artisan migrate`

Or use **PROMPT B** with Claude Code AI to create all migrations automatically!

---

**🔥 HUBIZZ - Where Content Ignites!**

*Let's build something amazing. Step by step, with high quality. You've got this!* 💪
