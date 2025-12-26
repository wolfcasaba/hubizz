@extends('admin.layout')

@section('title', 'Affiliate Dashboard - Hubizz')

@section('content')
<div class="hubizz-affiliate-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i> Affiliate Revenue Dashboard
            </h1>
            <p class="page-subtitle">Track your monetization performance and revenue</p>
        </div>
        <div class="header-actions">
            <div class="period-selector">
                <label>Period:</label>
                <select id="period-select" class="form-control" onchange="window.location.href='?period='+this.value">
                    <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90days" {{ $period == '90days' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Last Year</option>
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>
            <a href="{{ route('admin.affiliate.analytics') }}" class="btn btn-primary">
                <i class="fas fa-chart-bar"></i> Full Analytics
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <!-- Total Revenue -->
        <div class="stat-card stat-revenue">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">${{ number_format($statistics['total_revenue'], 2) }}</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> Track earnings
                </div>
            </div>
        </div>

        <!-- Total Clicks -->
        <div class="stat-card stat-clicks">
            <div class="stat-icon">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Clicks</div>
                <div class="stat-value">{{ number_format($statistics['total_clicks']) }}</div>
                <div class="stat-meta">
                    {{ number_format($statistics['total_links']) }} active links
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="stat-card stat-conversion">
            <div class="stat-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Conversion Rate</div>
                <div class="stat-value">{{ $statistics['conversion_rate'] }}%</div>
                <div class="stat-meta">
                    {{ number_format($statistics['total_conversions']) }} conversions
                </div>
            </div>
        </div>

        <!-- Avg Revenue Per Click -->
        <div class="stat-card stat-avg">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Avg Revenue/Click</div>
                <div class="stat-value">${{ number_format($statistics['avg_revenue_per_click'], 2) }}</div>
                <div class="stat-meta">
                    ${{ number_format($statistics['avg_revenue_per_conversion'], 2) }} per conversion
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Revenue Trend Chart -->
        <div class="chart-card chart-revenue-trend">
            <div class="card-header">
                <h3><i class="fas fa-chart-area"></i> Revenue Trend (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Network Performance Chart -->
        <div class="chart-card chart-network-performance">
            <div class="card-header">
                <h3><i class="fas fa-network-wired"></i> Revenue by Network</h3>
            </div>
            <div class="card-body">
                <canvas id="networkRevenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="top-performers-row">
        <!-- Top Links -->
        <div class="performers-card">
            <div class="card-header">
                <h3><i class="fas fa-link"></i> Top Performing Links</h3>
                <a href="{{ route('admin.affiliate.links') }}" class="view-all">View All</a>
            </div>
            <div class="card-body">
                @if(!empty($statistics['top_links']))
                    <table class="performers-table">
                        <thead>
                            <tr>
                                <th>Link</th>
                                <th>Product</th>
                                <th>Clicks</th>
                                <th>Conv.</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['top_links'] as $link)
                            <tr>
                                <td>
                                    <code>/go/{{ $link['short_code'] }}</code>
                                </td>
                                <td>{{ $link['product'] ?? 'N/A' }}</td>
                                <td>{{ number_format($link['clicks']) }}</td>
                                <td>{{ number_format($link['conversions']) }}</td>
                                <td class="revenue">${{ number_format($link['revenue'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="no-data">No link data available</p>
                @endif
            </div>
        </div>

        <!-- Top Products -->
        <div class="performers-card">
            <div class="card-header">
                <h3><i class="fas fa-box"></i> Top Performing Products</h3>
                <a href="{{ route('admin.affiliate.products') }}" class="view-all">View All</a>
            </div>
            <div class="card-body">
                @if(!empty($statistics['top_products']))
                    <table class="performers-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Clicks</th>
                                <th>Conv. Rate</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['top_products'] as $product)
                            <tr>
                                <td>
                                    <div class="product-name">{{ Str::limit($product['name'], 40) }}</div>
                                    <div class="product-asin">{{ $product['asin'] }}</div>
                                </td>
                                <td>{{ number_format($product['clicks']) }}</td>
                                <td>{{ number_format($product['conversion_rate'], 1) }}%</td>
                                <td class="revenue">${{ number_format($product['revenue'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="no-data">No product data available</p>
                @endif
            </div>
        </div>

        <!-- Top Posts -->
        <div class="performers-card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> Top Performing Posts</h3>
            </div>
            <div class="card-body">
                @if(!empty($statistics['top_posts']))
                    <table class="performers-table">
                        <thead>
                            <tr>
                                <th>Post</th>
                                <th>Clicks</th>
                                <th>Conv.</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statistics['top_posts'] as $post)
                            <tr>
                                <td>
                                    <a href="{{ route('post.show', [$post['slug']]) }}" target="_blank">
                                        {{ Str::limit($post['title'], 50) }}
                                    </a>
                                </td>
                                <td>{{ number_format($post['clicks']) }}</td>
                                <td>{{ number_format($post['conversions']) }}</td>
                                <td class="revenue">${{ number_format($post['revenue'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="no-data">No post data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Network Performance Table -->
    <div class="network-performance-section">
        <div class="section-header">
            <h3><i class="fas fa-network-wired"></i> Network Performance Breakdown</h3>
            <a href="{{ route('admin.affiliate.networks') }}" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Manage Networks
            </a>
        </div>
        <div class="network-table-wrapper">
            @if(!empty($byNetwork))
                <table class="network-table">
                    <thead>
                        <tr>
                            <th>Network</th>
                            <th>Links</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>Conv. Rate</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byNetwork as $network)
                        <tr>
                            <td>
                                <div class="network-name">
                                    <i class="fas fa-{{ $network['network_name'] == 'Amazon' ? 'amazon' : 'shopping-cart' }}"></i>
                                    {{ $network['network_name'] }}
                                </div>
                            </td>
                            <td>{{ number_format($network['total_links']) }}</td>
                            <td>{{ number_format($network['total_clicks']) }}</td>
                            <td>{{ number_format($network['total_conversions']) }}</td>
                            <td>
                                <span class="conversion-badge" style="background: {{ $network['conversion_rate'] > 5 ? '#10b981' : ($network['conversion_rate'] > 2 ? '#f59e0b' : '#ef4444') }}">
                                    {{ number_format($network['conversion_rate'], 2) }}%
                                </span>
                            </td>
                            <td class="revenue">${{ number_format($network['total_revenue'], 2) }}</td>
                            <td>
                                <a href="{{ route('admin.affiliate.networks.show', $network['network_id']) }}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="no-data">No network data available</p>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        <div class="actions-grid">
            <a href="{{ route('admin.affiliate.products.import') }}" class="action-card">
                <i class="fas fa-download"></i>
                <span>Import Product</span>
            </a>
            <a href="{{ route('admin.affiliate.products') }}" class="action-card">
                <i class="fas fa-sync"></i>
                <span>Sync Products</span>
            </a>
            <a href="{{ route('admin.affiliate.reports.generate') }}" class="action-card">
                <i class="fas fa-file-export"></i>
                <span>Generate Report</span>
            </a>
            <a href="{{ route('admin.affiliate.analytics') }}" class="action-card">
                <i class="fas fa-chart-line"></i>
                <span>View Analytics</span>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trend Chart
    const trendData = @json($trend);
    const trendCtx = document.getElementById('revenueTrendChart');

    if (trendCtx && trendData.length > 0) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.period),
                datasets: [{
                    label: 'Revenue ($)',
                    data: trendData.map(d => d.revenue),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Clicks',
                    data: trendData.map(d => d.clicks),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Clicks'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Network Revenue Chart
    const networkData = @json($byNetwork);
    const networkCtx = document.getElementById('networkRevenueChart');

    if (networkCtx && networkData.length > 0) {
        new Chart(networkCtx, {
            type: 'doughnut',
            data: {
                labels: networkData.map(n => n.network_name),
                datasets: [{
                    data: networkData.map(n => n.total_revenue),
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.hubizz-affiliate-dashboard {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.page-subtitle {
    color: #6b7280;
    margin: 5px 0 0;
}

.header-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.period-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    gap: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-revenue .stat-icon {
    background: #d1fae5;
    color: #10b981;
}

.stat-clicks .stat-icon {
    background: #dbeafe;
    color: #3b82f6;
}

.stat-conversion .stat-icon {
    background: #fef3c7;
    color: #f59e0b;
}

.stat-avg .stat-icon {
    background: #ede9fe;
    color: #8b5cf6;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 5px 0;
}

.stat-meta, .stat-change {
    font-size: 13px;
    color: #6b7280;
}

.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.chart-card .card-header {
    margin-bottom: 20px;
}

.chart-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.chart-card .card-body {
    height: 300px;
}

.top-performers-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.performers-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.performers-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.performers-table {
    width: 100%;
    font-size: 13px;
}

.performers-table th {
    text-align: left;
    padding: 8px;
    color: #6b7280;
    font-weight: 600;
    border-bottom: 2px solid #e5e7eb;
}

.performers-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #f3f4f6;
}

.performers-table .revenue {
    color: #10b981;
    font-weight: 600;
}

.network-performance-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.network-table {
    width: 100%;
}

.network-table th {
    text-align: left;
    padding: 12px;
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.network-table td {
    padding: 15px 12px;
    border-top: 1px solid #e5e7eb;
}

.conversion-badge {
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-weight: 600;
    font-size: 12px;
}

.quick-actions-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
}

.action-card:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}

.action-card i {
    font-size: 32px;
    color: #3b82f6;
}

.no-data {
    text-align: center;
    color: #9ca3af;
    padding: 40px;
}
</style>
@endpush
@endsection
