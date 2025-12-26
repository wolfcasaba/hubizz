# 🔥 Hubizz - AI-Powered Viral Content Automation Platform

[![Laravel](https://img.shields.io/badge/Laravel-10.13+-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success)](https://github.com)

**Hubizz** is a comprehensive viral content automation platform built on top of Buzzy Media Script v4.9.1. It transforms the Buzzy platform into an AI-powered content engine with automatic monetization, RSS aggregation, and intelligent content curation.

## ✨ Features

### 🤖 AI-Powered Content Generation
- **Perplexity AI Integration** - Advanced content generation and rewriting
- **Viral Headline Optimization** - 0-100 scoring algorithm for maximum engagement
- **SEO Meta Optimization** - Automatic title, description, and keyword generation
- **Token Tracking & Cost Calculation** - Monitor AI usage and costs

### 📡 RSS Feed Aggregation
- **SimplePie Integration** - Powerful RSS feed parsing
- **Smart Duplicate Detection** - Hash-based + similarity scoring (85% threshold)
- **Quality Filtering** - Automatic content quality assessment
- **Auto-Categorization** - Intelligent content categorization
- **Image Downloading** - Automatic media asset retrieval
- **Flexible Scheduling** - 15-minute, hourly, and daily feed processing

### 💰 Affiliate Monetization
- **Intelligent Product Detection** - Pattern, keyword, and database matching
- **Automatic Link Injection** - Seamless affiliate link integration with cloaking
- **Product Comparison Boxes** - Dynamic comparison widgets
- **Revenue Tracking** - Comprehensive click, conversion, and earnings analytics
- **Amazon PA-API 5.0** - Full Amazon product integration
- **Multi-Network Support** - Amazon, AliExpress, eBay Partner Network

### 🔥 Daily Izz Curation
- **Top 5 Daily Posts** - Automated curation based on viral scores
- **Manual & Auto Curation** - Flexible curation workflows
- **Calendar View** - Visual daily content management
- **Publish/Draft Workflow** - Control content publication

### 📊 Professional Admin Panel
- **AdminLTE Theme** - Modern, responsive admin interface
- **Revenue Dashboard** - Interactive Chart.js visualizations
- **Network Management** - Configure multiple affiliate networks
- **Product Catalog** - Import, sync, and manage affiliate products
- **Analytics** - Comprehensive performance metrics

### 🎯 Trending Topics
- **Automatic Trend Detection** - Track viral topics
- **Topic Management** - Manual trending topic curation
- **Integration with Content** - Link posts to trending topics

## 🏗️ Architecture

### Tech Stack
- **Framework**: Laravel 10.13+
- **PHP**: 8.1+
- **Database**: MySQL
- **Queue System**: Laravel Queues
- **Scheduler**: Laravel Task Scheduling
- **Frontend**: AdminLTE (Bootstrap 3), Chart.js 3.9.1
- **AI**: Perplexity API
- **RSS**: SimplePie
- **Affiliate**: Amazon PA-API 5.0

### Design Patterns
- **Service Layer Architecture** - Clean separation of business logic
- **Repository Pattern** - Eloquent models with relationships
- **Queue-Based Processing** - Background job system
- **Scheduled Tasks** - Automated background operations
- **Dependency Injection** - Throughout controllers and services

## 📦 Installation

### Requirements
- PHP >= 8.1
- Composer
- MySQL >= 5.7
- Node.js & NPM (for assets)

### Steps

1. **Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/hubizz.git
cd hubizz
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database** in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hubizz
DB_USERNAME=root
DB_PASSWORD=
```

5. **Configure Perplexity AI** in `.env`
```env
PERPLEXITY_API_KEY=your_api_key_here
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Start queue workers**
```bash
php artisan queue:work --tries=3
```

8. **Setup scheduler** (add to crontab)
```bash
* * * * * cd /path-to-hubizz && php artisan schedule:run >> /dev/null 2>&1
```

Or run continuously:
```bash
php artisan schedule:work
```

9. **Build assets**
```bash
npm run dev
# or for production
npm run build
```

## 🚀 Quick Start

### Access Admin Panel
```
http://your-domain.com/admin/affiliate/dashboard
```

### Configure Affiliate Networks
1. Navigate to **Admin → Affiliate → Networks**
2. Add your affiliate credentials (Amazon, AliExpress, eBay)
3. Enable networks and set commission rates

### Import Products
1. Go to **Admin → Affiliate → Products**
2. Click "Import Product"
3. Enter Amazon ASIN or use search
4. Products auto-sync every 24 hours

### Setup RSS Feeds
1. Navigate to **Admin → Hubizz → RSS Feeds**
2. Add feed URLs with desired intervals (15min, hourly, daily)
3. Feeds automatically process based on schedule

### Generate AI Content
1. Go to **Admin → Hubizz → AI Content**
2. Enter topic or source content
3. AI generates optimized content with SEO

### Curate Daily Izz
1. Visit **Admin → Hubizz → Daily Izz**
2. Click "Auto-Curate Today" or manually select posts
3. Publish when ready

## 📊 Database Schema

### Core Tables (13)
- `rss_feeds` - RSS feed sources
- `rss_imports` - Imported RSS items
- `affiliate_networks` - Affiliate network configurations
- `affiliate_products` - Product catalog
- `affiliate_links` - Generated affiliate links
- `affiliate_clicks` - Click tracking
- `trending_topics` - Trending topics
- `content_scores` - Viral scoring data
- `ai_generations` - AI generation history
- `daily_izz` - Daily top 5 curation
- `story_cards` - Story card content
- `story_reactions` - User reactions
- `content_hashes` - Duplicate detection

## 🎨 Admin Panel

### Hubizz Features Menu
```
HUBIZZ FEATURES
├── Affiliate Monetization
│   ├── Dashboard (Revenue overview)
│   ├── Networks (Manage networks)
│   ├── Products (Product catalog)
│   ├── Links (Link management)
│   └── Analytics (Performance metrics)
├── Daily Izz (Top 5 curation)
├── Trending Topics (Trend management)
├── RSS Feeds (Feed aggregation)
└── AI Content (Content generation)
```

### Color Scheme
- **Primary Orange**: `#f59e0b` (Hubizz brand color)
- **Dark Orange**: `#d97706` (Hover states)
- **Darkest Orange**: `#b45309` (Active states)

## ⚙️ Configuration

### Perplexity AI (`config/hubizz.php`)
```php
'ai' => [
    'default_model' => 'llama-3.1-sonar-large-128k-online',
    'max_tokens' => 4000,
    'temperature' => 0.7,
]
```

### RSS Settings
```php
'rss' => [
    'duplicate_threshold' => 0.85,
    'rewrite_content' => true,
    'min_content_length' => 200,
]
```

### Affiliate Configuration
```php
'affiliate' => [
    'max_links_per_post' => 5,
    'link_style' => 'inline',
    'add_comparison_box' => true,
]
```

### Daily Izz
```php
'daily_izz' => [
    'curation_time' => '06:00',
    'post_count' => 5,
    'min_score' => 50,
]
```

## 🔄 Scheduled Tasks

| Task | Frequency | Description |
|------|-----------|-------------|
| RSS 15-min feeds | Every 15 minutes | High-priority feeds |
| RSS hourly feeds | Every hour | Standard feeds |
| RSS daily feeds | Daily at 00:00 | Low-priority feeds |
| Daily Izz curation | Daily at 06:00 | Auto-curate top 5 |
| Trending update | Hourly | Update trending topics |
| Hash cleanup | Weekly (Sunday) | Clean old hashes |
| AI cache clear | Daily | Clear AI cache |

## 📝 API Integrations

### Perplexity AI
- Content generation
- Content rewriting
- Headline optimization
- SEO meta generation

### Amazon PA-API 5.0
- Product search
- Product lookup
- Product information sync
- Price updates

### SimplePie
- RSS feed parsing
- Feed validation
- Media extraction

## 📚 Documentation

- **[ADMINLTE-INTEGRATION.md](ADMINLTE-INTEGRATION.md)** - Complete AdminLTE theme guide
- **[THEME-INTEGRATION-COMPLETE.md](THEME-INTEGRATION-COMPLETE.md)** - Theme integration summary
- **[IMPLEMENTATION-CHECKLIST.md](IMPLEMENTATION-CHECKLIST.md)** - Complete implementation checklist
- **[FINAL-STATUS.md](FINAL-STATUS.md)** - Final project status
- **Phase Documentation** - PHASE1-5-COMPLETE.md files

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built on top of **Buzzy Media Script v4.9.1**
- **AdminLTE** theme for admin interface
- **Perplexity AI** for content generation
- **SimplePie** for RSS parsing
- **Chart.js** for visualizations

## 📞 Support

For support, email support@hubizz.com or open an issue on GitHub.

## 🔗 Links

- **Documentation**: [docs/](docs/)
- **Issue Tracker**: [GitHub Issues](https://github.com/YOUR_USERNAME/hubizz/issues)

---

**🔥 HUBIZZ - WHERE CONTENT IGNITES!** 🔥

Made with ❤️ using Laravel, AI, and lots of ☕

**Status**: ✅ Production Ready | **Version**: 1.0.0 | **Build**: December 2025
