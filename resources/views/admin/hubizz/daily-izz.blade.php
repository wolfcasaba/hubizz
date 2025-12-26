@extends('admin.layout')

@section('title', 'Daily Izz Management - Hubizz')

@section('content')
<div class="hubizz-daily-izz">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-fire"></i> Daily Izz Curation
            </h1>
            <p class="page-subtitle">Manage your daily top 5 curated content</p>
        </div>
        <div class="header-actions">
            <input type="month" value="{{ $month }}" class="form-control" onchange="window.location.href='?month='+this.value">
            <form method="POST" action="{{ route('admin.hubizz.daily-izz.curate', $today) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="auto" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Auto-Curate Today
                </button>
            </form>
        </div>
    </div>

    <!-- Today's Daily Izz -->
    <div class="today-section">
        <div class="section-header">
            <h2><i class="fas fa-calendar-day"></i> Today's Daily Izz - {{ $today->date->format('F d, Y') }}</h2>
            <div class="status-badge {{ $today->is_published ? 'published' : 'draft' }}">
                {{ $today->is_published ? 'Published' : 'Draft' }}
            </div>
        </div>

        <div class="today-content">
            @if($today->posts->count() > 0)
                <div class="curated-posts">
                    @foreach($today->posts as $index => $post)
                    <div class="curated-post">
                        <div class="post-rank">#{{ $index + 1 }}</div>
                        <div class="post-image">
                            @if($post->image)
                            <img src="{{ asset('storage/' . $post->image) }}" alt="{{ $post->title }}">
                            @else
                            <div class="no-image"><i class="fas fa-image"></i></div>
                            @endif
                        </div>
                        <div class="post-details">
                            <h3>{{ $post->title }}</h3>
                            <div class="post-meta">
                                <span class="category">
                                    <i class="fas fa-folder"></i> {{ $post->category->name ?? 'Uncategorized' }}
                                </span>
                                <span class="author">
                                    <i class="fas fa-user"></i> {{ $post->user->name }}
                                </span>
                                <span class="score">
                                    <i class="fas fa-chart-line"></i> Score: {{ $post->score }}
                                </span>
                            </div>
                            <div class="post-stats">
                                <span><i class="fas fa-eye"></i> {{ number_format($post->views) }} views</span>
                                <span><i class="fas fa-heart"></i> {{ number_format($post->reactions_count) }} reactions</span>
                                <span><i class="fas fa-comment"></i> {{ number_format($post->comments_count) }} comments</span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <a href="{{ route('post.show', [$post->category->slug, $post->slug]) }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="today-actions">
                    <a href="{{ route('admin.hubizz.daily-izz.show', $today) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Curation
                    </a>

                    <form method="POST" action="{{ route('admin.hubizz.daily-izz.update', $today) }}" style="display: inline;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_published" value="{{ $today->is_published ? '0' : '1' }}">
                        <button type="submit" class="btn btn-{{ $today->is_published ? 'secondary' : 'success' }}">
                            <i class="fas fa-{{ $today->is_published ? 'eye-slash' : 'check' }}"></i>
                            {{ $today->is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                    </form>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No posts curated for today</h3>
                    <p>Use auto-curate or manually select posts</p>
                    <div class="empty-actions">
                        <form method="POST" action="{{ route('admin.hubizz.daily-izz.curate', $today) }}">
                            @csrf
                            <input type="hidden" name="auto" value="1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Auto-Curate Now
                            </button>
                        </form>
                        <a href="{{ route('admin.hubizz.daily-izz.show', $today) }}" class="btn btn-secondary">
                            <i class="fas fa-hand-pointer"></i> Manual Selection
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Calendar View -->
    <div class="calendar-section">
        <h2><i class="fas fa-calendar-alt"></i> Daily Izz Calendar</h2>

        <div class="calendar-grid">
            @foreach($dailyIzzs as $dailyIzz)
            <div class="calendar-item {{ $dailyIzz->is_published ? 'published' : 'draft' }} {{ $dailyIzz->date->isToday() ? 'today' : '' }}">
                <div class="calendar-date">
                    <div class="day">{{ $dailyIzz->date->format('d') }}</div>
                    <div class="month">{{ $dailyIzz->date->format('M') }}</div>
                </div>

                <div class="calendar-info">
                    <div class="posts-count">
                        <i class="fas fa-file-alt"></i>
                        {{ $dailyIzz->posts->count() }} / 5 posts
                    </div>

                    @if($dailyIzz->is_published)
                    <span class="status-badge published">Published</span>
                    @else
                    <span class="status-badge draft">Draft</span>
                    @endif
                </div>

                <div class="calendar-actions">
                    <a href="{{ route('admin.hubizz.daily-izz.show', $dailyIzz) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Configuration -->
    <div class="config-section">
        <h2><i class="fas fa-cog"></i> Auto-Curation Settings</h2>

        <div class="config-grid">
            <div class="config-item">
                <div class="config-label">
                    <i class="fas fa-clock"></i> Curation Time
                </div>
                <div class="config-value">
                    {{ config('hubizz.daily_izz.curation_time', '06:00') }}
                </div>
                <div class="config-desc">Daily automatic curation time</div>
            </div>

            <div class="config-item">
                <div class="config-label">
                    <i class="fas fa-list-ol"></i> Post Count
                </div>
                <div class="config-value">
                    {{ config('hubizz.daily_izz.post_count', 5) }}
                </div>
                <div class="config-desc">Number of posts to curate</div>
            </div>

            <div class="config-item">
                <div class="config-label">
                    <i class="fas fa-calculator"></i> Min Score
                </div>
                <div class="config-value">
                    {{ config('hubizz.daily_izz.min_score', 50) }}
                </div>
                <div class="config-desc">Minimum viral score required</div>
            </div>

            <div class="config-item">
                <div class="config-label">
                    <i class="fas fa-calendar"></i> Max Age
                </div>
                <div class="config-value">
                    {{ config('hubizz.daily_izz.max_age_hours', 48) }} hours
                </div>
                <div class="config-desc">Maximum post age</div>
            </div>
        </div>

        <p class="config-note">
            <i class="fas fa-info-circle"></i>
            Edit these settings in <code>config/hubizz.php</code> under the <code>daily_izz</code> section.
        </p>
    </div>
</div>

@push('styles')
<style>
.hubizz-daily-izz {
    padding: 20px;
}

.today-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 2px solid #f59e0b;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e7eb;
}

.section-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.published {
    background: #d1fae5;
    color: #10b981;
}

.status-badge.draft {
    background: #fee2e2;
    color: #ef4444;
}

.curated-posts {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.curated-post {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
}

.post-rank {
    width: 40px;
    height: 40px;
    background: #f59e0b;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    flex-shrink: 0;
}

.post-image {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    flex-shrink: 0;
}

.post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
    font-size: 32px;
}

.post-details {
    flex: 1;
}

.post-details h3 {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 10px;
}

.post-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.post-stats {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #374151;
}

.post-stats span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.post-stats i {
    color: #9ca3af;
}

.post-actions {
    display: flex;
    align-items: center;
}

.today-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #374151;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 25px;
}

.empty-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.calendar-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.calendar-section h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.calendar-item {
    background: #f9fafb;
    border-radius: 8px;
    padding: 15px;
    border: 2px solid #e5e7eb;
    transition: all 0.2s;
}

.calendar-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.calendar-item.today {
    border-color: #f59e0b;
    background: #fffbeb;
}

.calendar-item.published {
    border-color: #d1fae5;
}

.calendar-date {
    text-align: center;
    margin-bottom: 12px;
}

.calendar-date .day {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.calendar-date .month {
    font-size: 14px;
    color: #6b7280;
    text-transform: uppercase;
}

.calendar-info {
    margin-bottom: 12px;
}

.posts-count {
    font-size: 13px;
    color: #374151;
    margin-bottom: 8px;
}

.calendar-actions {
    display: flex;
    gap: 8px;
}

.calendar-actions .btn-sm {
    flex: 1;
}

.config-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.config-section h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
}

.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.config-item {
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    text-align: center;
}

.config-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 10px;
}

.config-label i {
    color: #9ca3af;
    margin-right: 5px;
}

.config-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 5px;
}

.config-desc {
    font-size: 12px;
    color: #9ca3af;
}

.config-note {
    padding: 15px;
    background: #eff6ff;
    border-radius: 6px;
    font-size: 13px;
    color: #1e40af;
}

.config-note code {
    background: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
@endpush
@endsection
