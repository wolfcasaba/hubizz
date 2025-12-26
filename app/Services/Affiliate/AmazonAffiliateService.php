<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateNetwork;
use App\Models\AffiliateProduct;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Amazon Affiliate Service
 *
 * Integrates with Amazon Product Advertising API (PA-API 5.0)
 * for product lookup, pricing, and affiliate link generation.
 */
class AmazonAffiliateService extends BaseService
{
    protected string $accessKey;
    protected string $secretKey;
    protected string $trackingId;
    protected string $region;
    protected string $endpoint;

    public function __construct()
    {
        $this->accessKey = config('hubizz.affiliate.networks.amazon.access_key');
        $this->secretKey = config('hubizz.affiliate.networks.amazon.secret_key');
        $this->trackingId = config('hubizz.affiliate.networks.amazon.tracking_id');
        $this->region = config('hubizz.affiliate.networks.amazon.region', 'us-east-1');
        $this->endpoint = $this->getEndpointForRegion($this->region);
    }

    /**
     * Search for products on Amazon.
     *
     * @param string $keywords
     * @param array $options
     * @return array
     */
    public function searchProducts(string $keywords, array $options = []): array
    {
        $this->logInfo('Searching Amazon products', ['keywords' => $keywords]);

        $cacheKey = 'amazon_search_' . md5($keywords . json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($keywords, $options) {
            $itemCount = $options['item_count'] ?? 10;
            $searchIndex = $options['search_index'] ?? 'All';
            $resources = $options['resources'] ?? $this->getDefaultResources();

            $payload = [
                'Keywords' => $keywords,
                'ItemCount' => $itemCount,
                'SearchIndex' => $searchIndex,
                'Resources' => $resources,
                'PartnerTag' => $this->trackingId,
                'PartnerType' => 'Associates',
                'Marketplace' => $this->getMarketplace($this->region),
            ];

            try {
                $response = $this->makeRequest('SearchItems', $payload);

                if (!isset($response['SearchResult']['Items'])) {
                    $this->logWarning('No items found in Amazon search', ['keywords' => $keywords]);
                    return [];
                }

                $products = array_map(
                    fn($item) => $this->parseProductItem($item),
                    $response['SearchResult']['Items']
                );

                $this->logInfo('Amazon products found', [
                    'keywords' => $keywords,
                    'count' => count($products),
                ]);

                return $products;

            } catch (\Exception $e) {
                $this->handleException($e, 'Amazon product search failed', ['keywords' => $keywords]);
                return [];
            }
        });
    }

    /**
     * Get product details by ASIN.
     *
     * @param string|array $asin
     * @param array $options
     * @return array
     */
    public function getProduct($asin, array $options = []): array
    {
        $asins = is_array($asin) ? $asin : [$asin];

        $this->logInfo('Getting Amazon product details', ['asins' => $asins]);

        $cacheKey = 'amazon_product_' . md5(implode(',', $asins));

        return Cache::remember($cacheKey, 3600, function () use ($asins, $options) {
            $resources = $options['resources'] ?? $this->getDefaultResources();

            $payload = [
                'ItemIds' => $asins,
                'Resources' => $resources,
                'PartnerTag' => $this->trackingId,
                'PartnerType' => 'Associates',
                'Marketplace' => $this->getMarketplace($this->region),
            ];

            try {
                $response = $this->makeRequest('GetItems', $payload);

                if (!isset($response['ItemsResult']['Items'])) {
                    $this->logWarning('No items found in Amazon response', ['asins' => $asins]);
                    return count($asins) === 1 ? [] : [];
                }

                $products = array_map(
                    fn($item) => $this->parseProductItem($item),
                    $response['ItemsResult']['Items']
                );

                return count($asins) === 1 ? ($products[0] ?? []) : $products;

            } catch (\Exception $e) {
                $this->handleException($e, 'Failed to get Amazon product', ['asins' => $asins]);
                return count($asins) === 1 ? [] : [];
            }
        });
    }

    /**
     * Parse product item from Amazon API response.
     *
     * @param array $item
     * @return array
     */
    protected function parseProductItem(array $item): array
    {
        $asin = $item['ASIN'] ?? null;
        $title = $item['ItemInfo']['Title']['DisplayValue'] ?? 'Unknown Product';

        // Extract price
        $price = 0;
        if (isset($item['Offers']['Listings'][0]['Price']['Amount'])) {
            $price = $item['Offers']['Listings'][0]['Price']['Amount'];
        } elseif (isset($item['ItemInfo']['Price']['Amount'])) {
            $price = $item['ItemInfo']['Price']['Amount'];
        }

        // Extract image
        $image = $item['Images']['Primary']['Large']['URL']
            ?? $item['Images']['Primary']['Medium']['URL']
            ?? null;

        // Extract rating
        $rating = $item['ItemInfo']['Rating']['AverageRating'] ?? 0;

        // Extract features
        $features = $item['ItemInfo']['Features']['DisplayValues'] ?? [];

        // Generate affiliate URL
        $affiliateUrl = $item['DetailPageURL'] ?? "https://www.amazon.com/dp/{$asin}?tag={$this->trackingId}";

        return [
            'asin' => $asin,
            'title' => $title,
            'price' => $price,
            'currency' => $item['Offers']['Listings'][0]['Price']['Currency'] ?? 'USD',
            'image' => $image,
            'rating' => $rating,
            'features' => $features,
            'affiliate_url' => $affiliateUrl,
            'availability' => $item['Offers']['Listings'][0]['Availability']['Type'] ?? 'Unknown',
            'prime' => isset($item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'])
                && $item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'],
        ];
    }

    /**
     * Import product to database.
     *
     * @param string $asin
     * @param int|null $categoryId
     * @return AffiliateProduct|null
     */
    public function importProduct(string $asin, ?int $categoryId = null): ?AffiliateProduct
    {
        $this->logInfo('Importing Amazon product', ['asin' => $asin]);

        // Check if product already exists
        $existing = AffiliateProduct::where('asin', $asin)->first();

        if ($existing) {
            $this->logInfo('Product already exists, updating', ['asin' => $asin]);
            return $this->updateProduct($existing);
        }

        // Get product details from Amazon
        $productData = $this->getProduct($asin);

        if (empty($productData)) {
            $this->logWarning('Failed to get product data from Amazon', ['asin' => $asin]);
            return null;
        }

        // Get Amazon network
        $network = AffiliateNetwork::where('slug', 'amazon')->first();

        if (!$network) {
            $this->logError('Amazon affiliate network not found');
            return null;
        }

        // Create product
        try {
            $product = AffiliateProduct::create([
                'affiliate_network_id' => $network->id,
                'category_id' => $categoryId,
                'name' => $productData['title'],
                'asin' => $asin,
                'price' => $productData['price'],
                'currency' => $productData['currency'],
                'affiliate_url' => $productData['affiliate_url'],
                'image' => $productData['image'],
                'rating' => $productData['rating'],
                'is_active' => true,
                'metadata' => [
                    'features' => $productData['features'],
                    'prime' => $productData['prime'],
                    'availability' => $productData['availability'],
                    'imported_at' => now()->toDateTimeString(),
                ],
            ]);

            $this->logInfo('Amazon product imported successfully', [
                'asin' => $asin,
                'product_id' => $product->id,
            ]);

            return $product;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to import Amazon product', ['asin' => $asin]);
            return null;
        }
    }

    /**
     * Update existing product with latest data from Amazon.
     *
     * @param AffiliateProduct $product
     * @return AffiliateProduct
     */
    public function updateProduct(AffiliateProduct $product): AffiliateProduct
    {
        if (!$product->asin) {
            return $product;
        }

        $productData = $this->getProduct($product->asin);

        if (empty($productData)) {
            return $product;
        }

        $product->update([
            'name' => $productData['title'],
            'price' => $productData['price'],
            'currency' => $productData['currency'],
            'affiliate_url' => $productData['affiliate_url'],
            'image' => $productData['image'],
            'rating' => $productData['rating'],
            'metadata' => array_merge($product->metadata ?? [], [
                'features' => $productData['features'],
                'prime' => $productData['prime'],
                'availability' => $productData['availability'],
                'last_updated' => now()->toDateTimeString(),
            ]),
        ]);

        $this->logInfo('Amazon product updated', ['product_id' => $product->id]);

        return $product->fresh();
    }

    /**
     * Make authenticated request to Amazon PA-API.
     *
     * @param string $operation
     * @param array $payload
     * @return array
     */
    protected function makeRequest(string $operation, array $payload): array
    {
        $timestamp = gmdate('Ymd\THis\Z');
        $path = '/paapi5/' . strtolower($operation);

        $headers = [
            'content-encoding' => 'amz-1.0',
            'content-type' => 'application/json; charset=utf-8',
            'host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-date' => $timestamp,
            'x-amz-target' => 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $operation,
        ];

        $payloadJson = json_encode($payload);

        // Generate AWS Signature Version 4
        $signature = $this->generateSignature($path, $headers, $payloadJson, $timestamp);
        $headers['Authorization'] = $signature;

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($this->endpoint . $path, $payload);

        if (!$response->successful()) {
            $error = $response->json()['Errors'][0] ?? ['Message' => 'Unknown error'];
            throw new \Exception("Amazon API error: {$error['Message']}");
        }

        return $response->json();
    }

    /**
     * Generate AWS Signature Version 4.
     *
     * @param string $path
     * @param array $headers
     * @param string $payload
     * @param string $timestamp
     * @return string
     */
    protected function generateSignature(string $path, array $headers, string $payload, string $timestamp): string
    {
        // This is a simplified signature generation
        // For production, use AWS SDK or a complete implementation

        $service = 'ProductAdvertisingAPI';
        $date = substr($timestamp, 0, 8);

        // Create canonical request
        $canonicalHeaders = '';
        $signedHeaders = '';
        ksort($headers);

        foreach ($headers as $key => $value) {
            $canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
            $signedHeaders .= strtolower($key) . ';';
        }
        $signedHeaders = rtrim($signedHeaders, ';');

        $canonicalRequest = "POST\n{$path}\n\n{$canonicalHeaders}\n{$signedHeaders}\n" . hash('sha256', $payload);

        // Create string to sign
        $credentialScope = "{$date}/{$this->region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$timestamp}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        // Calculate signature
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    }

    /**
     * Get default resources to request from API.
     *
     * @return array
     */
    protected function getDefaultResources(): array
    {
        return [
            'ItemInfo.Title',
            'ItemInfo.Features',
            'ItemInfo.Rating',
            'Offers.Listings.Price',
            'Offers.Listings.Availability.Type',
            'Offers.Listings.DeliveryInfo.IsPrimeEligible',
            'Images.Primary.Large',
            'Images.Primary.Medium',
        ];
    }

    /**
     * Get API endpoint for region.
     *
     * @param string $region
     * @return string
     */
    protected function getEndpointForRegion(string $region): string
    {
        $endpoints = [
            'us-east-1' => 'https://webservices.amazon.com',
            'eu-west-1' => 'https://webservices.amazon.co.uk',
            'ap-northeast-1' => 'https://webservices.amazon.co.jp',
        ];

        return $endpoints[$region] ?? $endpoints['us-east-1'];
    }

    /**
     * Get marketplace for region.
     *
     * @param string $region
     * @return string
     */
    protected function getMarketplace(string $region): string
    {
        $marketplaces = [
            'us-east-1' => 'www.amazon.com',
            'eu-west-1' => 'www.amazon.co.uk',
            'ap-northeast-1' => 'www.amazon.co.jp',
        ];

        return $marketplaces[$region] ?? $marketplaces['us-east-1'];
    }

    /**
     * Batch import products by ASINs.
     *
     * @param array $asins
     * @param int|null $categoryId
     * @return array
     */
    public function batchImport(array $asins, ?int $categoryId = null): array
    {
        $results = [];

        foreach ($asins as $asin) {
            try {
                $product = $this->importProduct($asin, $categoryId);

                $results[] = [
                    'asin' => $asin,
                    'success' => $product !== null,
                    'product_id' => $product?->id,
                ];

                // Rate limiting - Amazon allows 1 request per second
                sleep(1);

            } catch (\Exception $e) {
                $this->handleException($e, 'Batch import failed for ASIN', ['asin' => $asin]);

                $results[] = [
                    'asin' => $asin,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get best sellers in category.
     *
     * @param string $browseNodeId
     * @param int $count
     * @return array
     */
    public function getBestSellers(string $browseNodeId, int $count = 10): array
    {
        // This would use the GetBrowseNodes operation
        // For now, return empty array as placeholder
        $this->logInfo('Getting Amazon best sellers', [
            'browse_node' => $browseNodeId,
            'count' => $count,
        ]);

        return [];
    }
}
