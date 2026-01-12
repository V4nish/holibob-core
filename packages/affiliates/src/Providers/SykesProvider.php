<?php

namespace Holibob\Affiliates\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SykesProvider extends AbstractAffiliateProvider
{
    /**
     * Fetch properties from Sykes affiliate feed.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchProperties(): Collection
    {
        try {
            $feedUrl = $this->getConfig('feed_url');
            $feedFormat = $this->getConfig('feed_format', 'csv'); // csv or xml

            if (! $feedUrl) {
                Log::warning('Sykes feed URL not configured');
                return collect();
            }

            Log::info('Fetching Sykes properties from feed', ['url' => $feedUrl]);

            // Fetch the feed
            $response = Http::timeout(120)->get($feedUrl);

            if (! $response->successful()) {
                Log::error('Failed to fetch Sykes feed', [
                    'status' => $response->status(),
                    'url' => $feedUrl,
                ]);
                return collect();
            }

            // Parse based on format
            return $feedFormat === 'xml'
                ? $this->parseXmlFeed($response->body())
                : $this->parseCsvFeed($response->body());

        } catch (\Exception $e) {
            Log::error('Failed to fetch Sykes properties', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Parse CSV feed into collection.
     *
     * @param string $csvContent
     * @return Collection<int, array<string, mixed>>
     */
    protected function parseCsvFeed(string $csvContent): Collection
    {
        $lines = str_getcsv($csvContent, "\n");

        if (empty($lines)) {
            return collect();
        }

        // First line is headers
        $headers = str_getcsv(array_shift($lines));
        $properties = [];

        foreach ($lines as $line) {
            $data = str_getcsv($line);

            if (count($data) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $property = array_combine($headers, $data);

            if ($this->isValidProperty($property)) {
                $properties[] = $property;
            }
        }

        Log::info('Parsed CSV feed', ['count' => count($properties)]);

        return collect($properties);
    }

    /**
     * Parse XML feed into collection.
     *
     * @param string $xmlContent
     * @return Collection<int, array<string, mixed>>
     */
    protected function parseXmlFeed(string $xmlContent): Collection
    {
        try {
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                Log::error('Failed to parse XML feed');
                return collect();
            }

            $properties = [];

            foreach ($xml->product as $product) {
                $property = [
                    'product_id' => (string) $product->pid,
                    'product_name' => (string) $product->name,
                    'description' => (string) $product->desc,
                    'deep_link' => (string) $product->purl,
                    'image_url' => (string) $product->imgurl,
                    'price' => (string) $product->price,
                    'currency' => (string) $product->currency,
                    'merchant_category' => (string) $product->category,
                    'brand_name' => (string) $product->brand,
                ];

                // Add custom fields if present
                if (isset($product->custom)) {
                    foreach ($product->custom as $custom) {
                        $property[(string) $custom['name']] = (string) $custom;
                    }
                }

                if ($this->isValidProperty($property)) {
                    $properties[] = $property;
                }
            }

            Log::info('Parsed XML feed', ['count' => count($properties)]);

            return collect($properties);

        } catch (\Exception $e) {
            Log::error('Failed to parse XML feed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Check if property has required fields.
     *
     * @param array<string, mixed> $property
     * @return bool
     */
    protected function isValidProperty(array $property): bool
    {
        $requiredFields = ['product_id', 'product_name'];

        foreach ($requiredFields as $field) {
            if (empty($property[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Transform Sykes data to internal schema.
     *
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    public function transform(array $rawData): array
    {
        // Support both Awin feed format and custom format
        $productId = $rawData['product_id'] ?? $rawData['pid'] ?? '';
        $productName = $rawData['product_name'] ?? $rawData['name'] ?? '';
        $description = $rawData['description'] ?? $rawData['desc'] ?? '';

        return [
            'external_id' => $productId,
            'name' => $productName,
            'slug' => $this->generateSlug($productName, $productId),
            'description' => $description,
            'short_description' => Str::limit($description, 200),
            'property_type' => $this->mapPropertyType($rawData['property_type'] ?? $rawData['merchant_category'] ?? ''),

            // Location - from custom fields
            'postcode' => $rawData['postcode'] ?? null,
            'address_line_1' => $rawData['address'] ?? null,
            'latitude' => isset($rawData['latitude']) ? (float) $rawData['latitude'] : null,
            'longitude' => isset($rawData['longitude']) ? (float) $rawData['longitude'] : null,

            // Capacity - from custom fields
            'sleeps' => isset($rawData['sleeps']) ? (int) $rawData['sleeps'] : 0,
            'bedrooms' => isset($rawData['bedrooms']) ? (int) $rawData['bedrooms'] : 0,
            'bathrooms' => isset($rawData['bathrooms']) ? (int) $rawData['bathrooms'] : 0,

            // Pricing
            'price_from' => isset($rawData['price']) ? (float) $rawData['price'] : null,
            'price_currency' => $rawData['currency'] ?? 'GBP',

            // Affiliate - use deep_link from Awin or generate custom
            'affiliate_url' => $rawData['deep_link'] ?? $this->generateAffiliateUrl($productId),
            'commission_rate' => $this->getConfig('commission_rate'),

            // Status
            'is_active' => true,
            'featured' => false,

            // Images
            'images' => $this->extractImages($rawData),

            // Amenities
            'amenities' => $this->extractAmenities($rawData),
        ];
    }

    /**
     * Generate Sykes affiliate URL.
     *
     * @param string $externalId
     * @param array<string, mixed> $params
     * @return string
     */
    public function generateAffiliateUrl(string $externalId, array $params = []): string
    {
        $baseUrl = $this->getConfig('affiliate_base_url', 'https://www.sykescottages.co.uk');
        $affiliateId = $this->getConfig('affiliate_id');

        $queryParams = array_merge([
            'propertyId' => $externalId,
            'affiliateId' => $affiliateId,
        ], $params);

        return $baseUrl . '/property/' . $externalId . '?' . http_build_query($queryParams);
    }

    /**
     * Check if provider is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->validateConfig([
            'affiliate_id',
            'feed_url',
        ]);
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Sykes Cottages';
    }

    /**
     * Map property type to internal format.
     *
     * @param string $type
     * @return string
     */
    protected function mapPropertyType(string $type): string
    {
        return match (strtolower($type)) {
            'cottage', 'house' => 'cottage',
            'apartment', 'flat' => 'apartment',
            'villa' => 'villa',
            'lodge', 'cabin' => 'lodge',
            default => 'cottage',
        };
    }

    /**
     * Extract images from raw data.
     *
     * @param array<string, mixed> $rawData
     * @return array<int, array<string, mixed>>
     */
    protected function extractImages(array $rawData): array
    {
        $images = [];

        // Handle Awin feed primary image
        if (isset($rawData['image_url']) && ! empty($rawData['image_url'])) {
            $images[] = [
                'url' => trim($rawData['image_url']),
                'display_order' => 0,
                'is_primary' => true,
            ];
        }

        // Handle additional images (comma or pipe separated)
        if (isset($rawData['images']) && is_string($rawData['images'])) {
            $separator = str_contains($rawData['images'], '|') ? '|' : ',';
            $imageUrls = explode($separator, $rawData['images']);

            foreach ($imageUrls as $index => $url) {
                $url = trim($url);
                if (! empty($url)) {
                    $images[] = [
                        'url' => $url,
                        'display_order' => $index + 1,
                        'is_primary' => false,
                    ];
                }
            }
        }

        // Handle alternate_image field from Awin
        if (isset($rawData['alternate_image']) && ! empty($rawData['alternate_image'])) {
            $images[] = [
                'url' => trim($rawData['alternate_image']),
                'display_order' => count($images),
                'is_primary' => false,
            ];
        }

        return $images;
    }

    /**
     * Extract amenities from raw data.
     *
     * @param array<string, mixed> $rawData
     * @return array<int, string>
     */
    protected function extractAmenities(array $rawData): array
    {
        $amenities = [];

        // Example: Map boolean flags to amenity slugs
        if (! empty($rawData['wifi'])) {
            $amenities[] = 'wifi';
        }

        if (! empty($rawData['parking'])) {
            $amenities[] = 'parking';
        }

        if (! empty($rawData['pet_friendly'])) {
            $amenities[] = 'pet-friendly';
        }

        if (! empty($rawData['hot_tub'])) {
            $amenities[] = 'hot-tub';
        }

        return $amenities;
    }
}
