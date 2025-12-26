@extends('admin.layout')

@section('title', 'Affiliate Products - Hubizz')

@section('content')
<div class="hubizz-affiliate-products">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-box"></i> Affiliate Products
            </h1>
            <p class="page-subtitle">Manage your product catalog and sync with affiliate networks</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openImportModal()">
                <i class="fas fa-download"></i> Import from Amazon
            </button>
            <form method="POST" action="{{ route('admin.affiliate.products.sync-all') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-sync"></i> Sync All Products
                </button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Network</label>
                <select name="network_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Networks</option>
                    @foreach($networks as $network)
                    <option value="{{ $network->id }}" {{ request('network_id') == $network->id ? 'selected' : '' }}>
                        {{ $network->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>Category</label>
                <select name="category_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\Category::all() as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ASIN..." value="{{ request('search') }}">
            </div>

            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>

            @if(request()->hasAny(['network_id', 'category_id', 'status', 'search']))
            <div class="filter-group">
                <label>&nbsp;</label>
                <a href="{{ route('admin.affiliate.products') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
        @forelse($products as $product)
        <div class="product-card {{ $product->is_active ? 'active' : 'inactive' }}">
            <div class="product-image">
                @if($product->image)
                <img src="{{ $product->image }}" alt="{{ $product->name }}" loading="lazy">
                @else
                <div class="no-image">
                    <i class="fas fa-image"></i>
                </div>
                @endif

                <div class="product-badges">
                    @if(!$product->is_active)
                    <span class="badge badge-inactive">Inactive</span>
                    @endif
                    @if($product->metadata['prime'] ?? false)
                    <span class="badge badge-prime">Prime</span>
                    @endif
                </div>
            </div>

            <div class="product-content">
                <div class="product-header">
                    <h3 class="product-name">{{ Str::limit($product->name, 60) }}</h3>
                    <div class="product-meta">
                        <span class="product-asin">{{ $product->asin }}</span>
                        <span class="product-network">
                            <i class="fas fa-{{ $product->network->slug == 'amazon' ? 'amazon' : 'shopping-cart' }}"></i>
                            {{ $product->network->name }}
                        </span>
                    </div>
                </div>

                <div class="product-price-rating">
                    <div class="price">
                        ${{ number_format($product->price, 2) }}
                        <span class="currency">{{ $product->currency }}</span>
                    </div>
                    @if($product->rating > 0)
                    <div class="rating">
                        <i class="fas fa-star"></i> {{ number_format($product->rating, 1) }}
                    </div>
                    @endif
                </div>

                <div class="product-stats">
                    <div class="stat">
                        <i class="fas fa-mouse-pointer"></i>
                        <span>{{ number_format($product->clicks) }} clicks</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ number_format($product->conversions) }} conv.</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-percent"></i>
                        <span>{{ number_format($product->conversion_rate, 1) }}%</span>
                    </div>
                    <div class="stat stat-revenue">
                        <i class="fas fa-dollar-sign"></i>
                        <span>${{ number_format($product->revenue, 2) }}</span>
                    </div>
                </div>

                @if($product->category)
                <div class="product-category">
                    <i class="fas fa-folder"></i> {{ $product->category->name }}
                </div>
                @endif

                <div class="product-actions">
                    <a href="{{ route('admin.affiliate.products.show', $product) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <form method="POST" action="{{ route('admin.affiliate.products.sync', $product) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <i class="fas fa-sync"></i> Sync
                        </button>
                    </form>
                    <a href="{{ $product->affiliate_url }}" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <h3>No products found</h3>
            <p>Import products from Amazon to get started</p>
            <button class="btn btn-primary" onclick="openImportModal()">
                <i class="fas fa-download"></i> Import Product
            </button>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="pagination-wrapper">
        {{ $products->links() }}
    </div>
    @endif
</div>

<!-- Import Product Modal -->
<div id="importModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Product from Amazon</h3>
            <button class="modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.affiliate.products.import') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>Amazon ASIN *</label>
                    <input type="text" name="asin" class="form-control" placeholder="B08N5WRWNW" required maxlength="10" pattern="[A-Z0-9]{10}">
                    <small>10-character Amazon Standard Identification Number</small>
                </div>

                <div class="form-group">
                    <label>Category (Optional)</label>
                    <select name="category_id" class="form-control">
                        <option value="">Auto-detect</option>
                        @foreach(\App\Models\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <small>Leave blank to auto-detect from product data</small>
                </div>

                <div class="import-help">
                    <h4>How to find ASIN:</h4>
                    <ol>
                        <li>Go to the product page on Amazon</li>
                        <li>Scroll to "Product Information" section</li>
                        <li>Look for "ASIN" field</li>
                        <li>Or check the URL: amazon.com/dp/<strong>ASIN</strong></li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-download"></i> Import Product
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openImportModal() {
    document.getElementById('importModal').style.display = 'flex';
}

function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('importModal');
    if (event.target === modal) {
        closeImportModal();
    }
}
</script>
@endpush

@push('styles')
<style>
.hubizz-affiliate-products {
    padding: 20px;
}

.filters-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filters-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 5px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-card.active {
    border-color: #d1fae5;
}

.product-card.inactive {
    opacity: 0.7;
}

.product-image {
    position: relative;
    width: 100%;
    height: 200px;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
}

.no-image {
    font-size: 48px;
    color: #d1d5db;
}

.product-badges {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-inactive {
    background: #fee2e2;
    color: #ef4444;
}

.badge-prime {
    background: #00a8e1;
    color: white;
}

.product-content {
    padding: 15px;
}

.product-name {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 8px;
    line-height: 1.4;
    min-height: 42px;
}

.product-meta {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 12px;
}

.product-asin {
    font-family: monospace;
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 3px;
}

.product-price-rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.price {
    font-size: 20px;
    font-weight: 700;
    color: #10b981;
}

.currency {
    font-size: 12px;
    color: #6b7280;
    font-weight: 400;
}

.rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 14px;
    font-weight: 600;
    color: #f59e0b;
}

.product-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6b7280;
}

.stat i {
    color: #9ca3af;
}

.stat-revenue {
    color: #10b981;
    font-weight: 600;
}

.product-category {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 12px;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.product-actions .btn-sm {
    flex: 1;
    padding: 8px;
    font-size: 12px;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
}

.no-products i {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.no-products h3 {
    font-size: 24px;
    color: #374151;
    margin-bottom: 10px;
}

.no-products p {
    color: #6b7280;
    margin-bottom: 20px;
}

.import-help {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}

.import-help h4 {
    font-size: 14px;
    margin-bottom: 10px;
}

.import-help ol {
    font-size: 13px;
    color: #6b7280;
    padding-left: 20px;
}

.import-help li {
    margin-bottom: 5px;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
}
</style>
@endpush
@endsection
