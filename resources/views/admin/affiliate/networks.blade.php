@extends('admin.layout')

@section('title', 'Affiliate Networks - Hubizz')

@section('content')
<div class="hubizz-affiliate-networks">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fas fa-network-wired"></i> Affiliate Networks
            </h1>
            <p class="page-subtitle">Manage your affiliate network integrations</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.affiliate.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Networks Grid -->
    <div class="networks-grid">
        @foreach($networks as $network)
        <div class="network-card {{ $network->is_active ? 'active' : 'inactive' }}">
            <div class="network-header">
                <div class="network-icon">
                    <i class="fas fa-{{ $network->slug == 'amazon' ? 'amazon' : ($network->slug == 'aliexpress' ? 'shopping-bag' : 'shopping-cart') }}"></i>
                </div>
                <div class="network-info">
                    <h3>{{ $network->name }}</h3>
                    <span class="network-status {{ $network->is_active ? 'active' : 'inactive' }}">
                        {{ $network->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <div class="network-stats">
                <div class="stat">
                    <div class="stat-label">Products</div>
                    <div class="stat-value">{{ number_format($network->products_count) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Links</div>
                    <div class="stat-value">{{ number_format($network->links_count) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Commission</div>
                    <div class="stat-value">{{ $network->commission_rate }}%</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value">${{ number_format($network->total_revenue, 2) }}</div>
                </div>
            </div>

            <div class="network-config">
                @if($network->slug == 'amazon')
                    <div class="config-item">
                        <i class="fas fa-key"></i>
                        <span>Access Key: {{ $network->api_key ? '••••••••' . substr($network->api_key, -4) : 'Not configured' }}</span>
                    </div>
                    <div class="config-item">
                        <i class="fas fa-tag"></i>
                        <span>Tracking ID: {{ $network->tracking_id ?? 'Not set' }}</span>
                    </div>
                    <div class="config-item">
                        <i class="fas fa-globe"></i>
                        <span>Region: {{ $network->metadata['region'] ?? 'us-east-1' }}</span>
                    </div>
                @elseif($network->slug == 'aliexpress')
                    <div class="config-item">
                        <i class="fas fa-key"></i>
                        <span>App Key: {{ $network->api_key ? '••••••••' . substr($network->api_key, -4) : 'Not configured' }}</span>
                    </div>
                    <div class="config-item">
                        <i class="fas fa-tag"></i>
                        <span>Tracking ID: {{ $network->tracking_id ?? 'Not set' }}</span>
                    </div>
                @elseif($network->slug == 'ebay')
                    <div class="config-item">
                        <i class="fas fa-key"></i>
                        <span>App ID: {{ $network->api_key ? '••••••••' . substr($network->api_key, -4) : 'Not configured' }}</span>
                    </div>
                    <div class="config-item">
                        <i class="fas fa-tag"></i>
                        <span>Campaign ID: {{ $network->tracking_id ?? 'Not set' }}</span>
                    </div>
                @endif
            </div>

            <div class="network-actions">
                <a href="{{ route('admin.affiliate.networks.show', $network) }}" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <button class="btn btn-secondary" onclick="openEditModal({{ $network->id }})">
                    <i class="fas fa-edit"></i> Configure
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Setup Guide -->
    <div class="setup-guide">
        <h2><i class="fas fa-book"></i> Network Setup Guides</h2>

        <div class="guide-accordion">
            <!-- Amazon Setup -->
            <div class="guide-item">
                <button class="guide-header" onclick="toggleGuide('amazon')">
                    <i class="fab fa-amazon"></i> Amazon Associates Setup
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="guide-content" id="guide-amazon">
                    <ol>
                        <li>Sign up for <a href="https://affiliate-program.amazon.com/" target="_blank">Amazon Associates</a></li>
                        <li>Get your Tracking ID from the Associates dashboard</li>
                        <li>Register for <a href="https://webservices.amazon.com/paapi5/documentation/" target="_blank">Product Advertising API</a></li>
                        <li>Generate Access Key and Secret Key</li>
                        <li>Add credentials to your .env file:
                            <pre>AMAZON_AFFILIATE_ACCESS_KEY=your_access_key
AMAZON_AFFILIATE_SECRET_KEY=your_secret_key
AMAZON_AFFILIATE_TRACKING_ID=your_tracking_id
AMAZON_AFFILIATE_REGION=us-east-1</pre>
                        </li>
                        <li>Configure the network using the form above</li>
                    </ol>
                </div>
            </div>

            <!-- AliExpress Setup -->
            <div class="guide-item">
                <button class="guide-header" onclick="toggleGuide('aliexpress')">
                    <i class="fas fa-shopping-bag"></i> AliExpress Affiliate Setup
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="guide-content" id="guide-aliexpress">
                    <ol>
                        <li>Sign up for <a href="https://portals.aliexpress.com/" target="_blank">AliExpress Affiliate</a></li>
                        <li>Get your App Key and Secret from the developer portal</li>
                        <li>Get your Tracking ID from the affiliate dashboard</li>
                        <li>Add credentials to your .env file:
                            <pre>ALIEXPRESS_AFFILIATE_APP_KEY=your_app_key
ALIEXPRESS_AFFILIATE_APP_SECRET=your_app_secret
ALIEXPRESS_AFFILIATE_TRACKING_ID=your_tracking_id</pre>
                        </li>
                        <li>Configure the network using the form above</li>
                    </ol>
                </div>
            </div>

            <!-- eBay Setup -->
            <div class="guide-item">
                <button class="guide-header" onclick="toggleGuide('ebay')">
                    <i class="fas fa-shopping-cart"></i> eBay Partner Network Setup
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="guide-content" id="guide-ebay">
                    <ol>
                        <li>Sign up for <a href="https://epn.ebay.com/" target="_blank">eBay Partner Network</a></li>
                        <li>Get your Campaign ID from the EPN dashboard</li>
                        <li>Register for <a href="https://developer.ebay.com/" target="_blank">eBay Developer Program</a></li>
                        <li>Create an application and get App ID and Cert ID</li>
                        <li>Add credentials to your .env file:
                            <pre>EBAY_AFFILIATE_APP_ID=your_app_id
EBAY_AFFILIATE_CERT_ID=your_cert_id
EBAY_AFFILIATE_CAMPAIGN_ID=your_campaign_id</pre>
                        </li>
                        <li>Configure the network using the form above</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Network Modal -->
<div id="editNetworkModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Configure Network</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editNetworkForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>Network Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>API Key / Access Key</label>
                    <input type="text" name="api_key" class="form-control">
                    <small>Your API access key or app key</small>
                </div>

                <div class="form-group">
                    <label>API Secret / Secret Key</label>
                    <input type="password" name="api_secret" class="form-control">
                    <small>Your API secret key</small>
                </div>

                <div class="form-group">
                    <label>Tracking ID</label>
                    <input type="text" name="tracking_id" class="form-control">
                    <small>Your affiliate tracking ID</small>
                </div>

                <div class="form-group">
                    <label>Commission Rate (%)</label>
                    <input type="number" name="commission_rate" class="form-control" step="0.01" min="0" max="100">
                    <small>Default commission rate for this network</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1">
                        Network is active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleGuide(id) {
    const content = document.getElementById('guide-' + id);
    const isOpen = content.style.display === 'block';

    // Close all guides
    document.querySelectorAll('.guide-content').forEach(el => {
        el.style.display = 'none';
    });

    // Toggle current
    content.style.display = isOpen ? 'none' : 'block';
}

function openEditModal(networkId) {
    // In a real implementation, fetch network data via AJAX
    const modal = document.getElementById('editNetworkModal');
    const form = document.getElementById('editNetworkForm');

    // Set form action
    form.action = '/admin/affiliate/networks/' + networkId;

    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editNetworkModal').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('editNetworkModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>
@endpush

@push('styles')
<style>
.hubizz-affiliate-networks {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.networks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.network-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 2px solid transparent;
    transition: all 0.3s;
}

.network-card.active {
    border-color: #10b981;
}

.network-card.inactive {
    opacity: 0.7;
}

.network-header {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.network-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
}

.network-info h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.network-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 5px;
}

.network-status.active {
    background: #d1fae5;
    color: #10b981;
}

.network-status.inactive {
    background: #fee2e2;
    color: #ef4444;
}

.network-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

.network-stats .stat {
    text-align: center;
}

.network-stats .stat-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.network-stats .stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-top: 5px;
}

.network-config {
    margin-bottom: 20px;
}

.config-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    color: #374151;
}

.config-item i {
    color: #9ca3af;
    width: 20px;
}

.network-actions {
    display: flex;
    gap: 10px;
}

.network-actions .btn {
    flex: 1;
}

.setup-guide {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.setup-guide h2 {
    font-size: 22px;
    margin-bottom: 20px;
}

.guide-accordion {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.guide-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.guide-header {
    width: 100%;
    padding: 15px 20px;
    background: #f9fafb;
    border: none;
    text-align: left;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.guide-header:hover {
    background: #f3f4f6;
}

.guide-content {
    padding: 20px;
    display: none;
}

.guide-content ol {
    padding-left: 20px;
}

.guide-content li {
    margin-bottom: 15px;
    line-height: 1.6;
}

.guide-content pre {
    background: #1f2937;
    color: #f9fafb;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    margin-top: 10px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #9ca3af;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #6b7280;
    font-size: 12px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
@endpush
@endsection
