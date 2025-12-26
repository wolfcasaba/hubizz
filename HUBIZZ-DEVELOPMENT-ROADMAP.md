# 🔥 HUBIZZ - Development Roadmap
**Where Content Ignites!**

## Project Overview

**Hubizz** is a modern viral content automation platform built on Laravel, transforming the existing Buzzy Media Script into an AI-powered content generation, intelligent RSS aggregation, and automated affiliate monetization platform.

### Brand Identity
- **Name**: HUBIZZ (Hub = Quality Content Center + Izz = Buzz/Fire/Energy)
- **Domain**: hubizz.com
- **Tagline**: Where Content Ignites!
- **Daily Feature**: "Daily Izz" - Top 5 Auto-curated Posts

### Current Base
- **Framework**: Laravel 10.13+
- **PHP**: 8.1+
- **Current System**: Buzzy Media Script v4.9.1
- **Database**: MySQL 8.0+

---

## 📋 Six Content Categories

| URL | Name | Hungarian | Content Focus |
|-----|------|-----------|---------------|
| /tech | 🔥 Tech Buzz | Tech Izzás | AI, Gadgets, Startups, Tech News |
| /viral | 🔥 Viral Buzz | Viral Buzz | TikTok/YouTube Trends, Social Media |
| /news | 🔥 Fresh News | Friss Izzás | Breaking News, Current Events |
| /life | 🔥 Life Spark | Élet Pezsgés | Lifestyle, Motivation, Health |
| /biz | 🔥 Biz Spark | Üzleti Szikra | Business, Career, Finance |
| /daily | 🔥 Daily Izz | Napi TOP 5 | Auto-curated Top 5 Daily Posts |

---

## 🎯 Core Features (Replacing Videos/Polls/Quizzes)

### 1. AI Content Generation Engine
Replace manual content creation with Perplexity AI integration:
- Auto-generate articles from trending topics and RSS feeds
- AI-powered headline optimization for maximum CTR
- Automatic SEO meta descriptions and tags
- Content rewriting to avoid duplicate content penalties
- Multi-language content translation support

### 2. Smart RSS Aggregation System
Intelligent content sourcing from multiple feeds:
- Multi-source RSS feed management with priority ranking
- Duplicate content detection before import
- Auto-categorization using AI content analysis
- Scheduled feed checks (15min, hourly, daily options)
- Content quality scoring and filtering
- Source credibility tracking and blacklisting

### 3. Automated Affiliate Integration
Monetization automation with intelligent affiliate link insertion:
- Auto-detect product mentions in content
- Automatic Amazon/AliExpress/eBay affiliate link insertion
- Comparison box generation for product articles
- Affiliate revenue tracking dashboard
- Link cloaking and tracking with UTM parameters

### 4. Trending Content Discovery
Real-time trend monitoring:
- Google Trends API integration with hourly updates
- Twitter/X trending topics monitoring
- Reddit hot posts aggregation
- AI-powered viral potential scoring
- Auto-suggest content topics to admin panel

### 5. Interactive Story Cards
Modern replacement for quizzes/polls:
- Swipeable story cards (Instagram Stories style)
- Before/After image comparisons
- Numbered listicles with progress indicators
- "This or That" quick engagement cards
- Reaction buttons (🔥 Hot, ❄️ Not, 🤔 Hmm)

---

## 🎨 Design System

### Color Palette
| Name | Hex Code | Usage |
|------|----------|-------|
| Izz Orange | #FF6B35 | Primary brand, CTAs, highlights, fire icons |
| Deep Navy | #1A1A2E | Headers, navigation, footer, dark mode base |
| Golden Accent | #F7931E | Secondary CTAs, hover states, badges |
| Pure White | #FFFFFF | Background, cards, content areas |
| Smoke Gray | #F5F5F7 | Section backgrounds, separators |

### Typography
- **Headlines**: Inter Bold / 32-48px / Navy
- **Subheadlines**: Inter Semi-Bold / 20-24px / Gray
- **Body Text**: Inter Regular / 16-18px / Dark Gray
- **Category Labels**: Inter Bold / 12px / Uppercase / Izz Orange

### Component Designs
- Sticky header with blur backdrop effect
- Large featured cards with gradient overlay
- Grid cards with 16:9 image ratio and hover effects
- Category badges with fire emoji indicators
- Reading time estimate with flame meter
- Dark mode toggle with smooth transition

---

## 📁 Technical Architecture

### New Directory Structure
```
app/
├── Services/
│   ├── AI/
│   │   ├── PerplexityService.php
│   │   ├── ContentGeneratorService.php
│   │   └── HeadlineOptimizerService.php
│   ├── RSS/
│   │   ├── FeedAggregatorService.php
│   │   ├── ContentImporterService.php
│   │   └── DuplicateDetectorService.php
│   ├── Affiliate/
│   │   ├── LinkInjectorService.php
│   │   ├── ProductMatcherService.php
│   │   └── RevenueTrackerService.php
│   ├── Trends/
│   │   ├── GoogleTrendsService.php
│   │   ├── SocialBuzzService.php
│   │   └── ViralScoreService.php
│   └── SEO/
│       ├── SitemapGeneratorService.php
│       ├── SchemaMarkupService.php
│       └── MetaOptimizerService.php
├── Jobs/
│   ├── ProcessRSSFeedJob.php
│   ├── GenerateAIContentJob.php
│   ├── UpdateTrendingJob.php
│   └── CurateDailyIzzJob.php
└── Models/
    ├── RssFeed.php (extends existing Feed)
    ├── AffiliateLink.php
    ├── AffiliateNetwork.php
    ├── AffiliateProduct.php
    ├── TrendingTopic.php
    ├── ContentScore.php
    ├── StoryCard.php
    ├── StoryReaction.php
    ├── DailyIzz.php
    └── AIGeneration.php
```

### New Database Tables (13 tables)
1. **rss_feeds** - RSS feed sources with scheduling and category mapping
2. **rss_imports** - Import history and duplicate tracking
3. **affiliate_networks** - Amazon, AliExpress, eBay credentials
4. **affiliate_links** - Generated links with click/revenue tracking
5. **affiliate_products** - Product database for matching
6. **affiliate_clicks** - Click tracking with IP and user agent
7. **trending_topics** - Real-time trends from multiple sources
8. **content_scores** - Viral scoring and performance metrics
9. **ai_generations** - AI content generation logs
10. **daily_izz** - Daily top 5 curated content
11. **story_cards** - Interactive story card content
12. **story_reactions** - User reactions tracking
13. **content_hashes** - Duplicate detection hashes

### Admin Panel Extensions
New modules to add to existing admin panel:

| Module | Controller | Features |
|--------|-----------|----------|
| 🤖 AI Settings | AISettingsController | API keys, model selection, generation rules, content templates |
| 📡 RSS Manager | RSSManagerController | Add/edit feeds, category mapping, schedule settings, import logs |
| 💰 Affiliates | AffiliateController | Network credentials, link rules, product database, earnings reports |
| 📈 Trends | TrendsController | Trending topics dashboard, viral score settings, auto-post rules |
| 🔍 SEO Center | SEOController | Sitemap config, schema markup, meta templates, keyword tracking |
| 📊 Analytics | AnalyticsController | Traffic stats, content performance, revenue tracking, user engagement |
| 📅 Scheduler | SchedulerController | Editorial calendar, auto-publish queue, optimal timing, Daily Izz curation |
| 🌐 Social | SocialController | Auto-post to social media, cross-posting rules, engagement tracking |

---

## 🚀 Implementation Phases

### Phase 1: Foundation (Week 1-2)
**Goal**: Set up new database structure and base service classes

**Tasks**:
- [ ] Create all 13 new database migrations
- [ ] Set up new Eloquent models with relationships
- [ ] Configure Laravel queues for background processing
- [ ] Create base service class structure
- [ ] Set up new admin routes and controllers
- [ ] Update .env with new API key placeholders
- [ ] Create config/hubizz.php configuration file

**Files**: [PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md)

---

### Phase 2: AI Integration (Week 3-4)
**Goal**: Implement Perplexity AI content generation

**Tasks**:
- [ ] Implement PerplexityService API wrapper
- [ ] Create ContentGeneratorService with templates
- [ ] Build HeadlineOptimizerService for CTR optimization
- [ ] Add AI settings page to admin panel
- [ ] Create content generation queue jobs
- [ ] Build content preview and editing interface
- [ ] Add AI generation logging and tracking

**Files**: [PHASE2-AI-INTEGRATION.md](docs/PHASE2-AI-INTEGRATION.md)

---

### Phase 3: RSS & Automation (Week 5-6)
**Goal**: Full RSS aggregation with auto-import

**Tasks**:
- [ ] Build FeedAggregatorService with SimplePie
- [ ] Implement DuplicateDetectorService (hash-based)
- [ ] Create ContentImporterService with AI rewriting
- [ ] Add RSS Manager to admin with CRUD interface
- [ ] Set up scheduled feed processing (Laravel scheduler)
- [ ] Build import logs and monitoring dashboard
- [ ] Integrate with existing Feed model

**Files**: [PHASE3-RSS-AUTOMATION.md](docs/PHASE3-RSS-AUTOMATION.md)

---

### Phase 4: Monetization (Week 7-8)
**Goal**: Automated affiliate link insertion and tracking

**Tasks**:
- [ ] Build ProductMatcherService using NLP
- [ ] Implement LinkInjectorService with smart insertion
- [ ] Create affiliate network API integrations
- [ ] Build RevenueTrackerService with click tracking
- [ ] Add affiliate dashboard to admin
- [ ] Create comparison box component for products
- [ ] Implement click tracking and analytics

**Files**: [PHASE4-MONETIZATION.md](docs/PHASE4-MONETIZATION.md)

---

### Phase 5: UI/Theme (Week 9-10)
**Goal**: Complete Hubizz theme implementation

**Tasks**:
- [ ] Create new Blade theme structure
- [ ] Implement Tailwind CSS with custom config
- [ ] Build responsive header with category navigation
- [ ] Create article card components (featured, grid, list)
- [ ] Build Daily Izz section with auto-curation
- [ ] Implement dark mode with system preference detection
- [ ] Add reaction system (🔥 Hot, ❄️ Not, 🤔 Hmm)
- [ ] Mobile optimization and PWA setup

**Files**: [PHASE5-UI-THEME.md](docs/PHASE5-UI-THEME.md)

---

## 🔑 Required API Keys & Services

| Service | Purpose | ENV Variable |
|---------|---------|--------------|
| Perplexity AI | Content generation | PERPLEXITY_API_KEY |
| Amazon Product API | Affiliate links | AMAZON_ACCESS_KEY |
| Google Trends | Trending topics | GOOGLE_API_KEY |
| Twitter/X API | Social trends | TWITTER_BEARER_TOKEN |
| Redis | Queue & caching | REDIS_HOST, REDIS_PORT |

---

## ✅ Pre-Development Checklist

### Environment Setup
- [ ] PHP 8.1+ installed
- [ ] MySQL 8.0+ installed
- [ ] Redis installed
- [ ] Node.js 18+ installed
- [ ] Composer installed

### Project Preparation
- [ ] Backup existing Buzzy Laravel script
- [ ] Backup database
- [ ] Create new Git branch: `feature/hubizz-transformation`
- [ ] Set up local development environment

### API Accounts
- [ ] Register for Perplexity AI API access
- [ ] Set up Amazon Associates account for affiliate API
- [ ] Create Google Cloud project for Trends API
- [ ] Configure Redis for queue processing

### Branding & Domain
- [ ] Domain hubizz.com DNS configuration
- [ ] Register @Hubizz social handles
- [ ] Create logo and favicon
- [ ] Prepare RSS feed list (10+ per category)

---

## 📚 Development Guidelines

### Code Standards
- Follow Laravel best practices and PSR-12 coding standards
- Use service classes for business logic
- Keep controllers thin
- Write comprehensive PHPDoc comments
- Use meaningful variable and method names

### Database Standards
- Use migrations for all schema changes
- Add proper indexes for frequently queried columns
- Use foreign key constraints where appropriate
- Add soft deletes for important data

### Security Best Practices
- Validate all user input
- Use Laravel's built-in security features
- Sanitize output to prevent XSS
- Implement rate limiting on API endpoints
- Use queue jobs for heavy processing

### Testing Strategy
- Write unit tests for service classes
- Create feature tests for API endpoints
- Test admin panel functionality
- Verify queue job execution
- Test affiliate link generation and tracking

---

## 🎓 Next Steps

### To Start Development:

1. **Review the current codebase analysis** (completed above)
2. **Begin Phase 1: Foundation**
   - Read the detailed [PHASE1-FOUNDATION.md](docs/PHASE1-FOUNDATION.md) guide
   - Create database migrations
   - Set up models and relationships

3. **Use Claude Code AI Prompts** (from the 10-step guide):
   - PROMPT A: Analyze existing structure ✅ (completed)
   - PROMPT B: Create migrations
   - PROMPT C: Create service classes
   - PROMPT D: Create admin controllers
   - PROMPT E: Create Hubizz theme

### Development Workflow:
1. Work on one phase at a time
2. Test each component before moving forward
3. Commit changes regularly to Git
4. Update this roadmap as you complete tasks
5. Document any deviations or improvements

---

## 📞 Support & Resources

- **Laravel Documentation**: https://laravel.com/docs/10.x
- **Perplexity AI Docs**: https://perplexity.ai
- **Tailwind CSS**: https://tailwindcss.com
- **Original Buzzy Docs**: http://buzzy.akbilisim.com/admin/docs

---

**🔥 HUBIZZ - Where Content Ignites!**

*Ready to transform Buzzy into a modern viral content platform. Let's build something amazing!*
