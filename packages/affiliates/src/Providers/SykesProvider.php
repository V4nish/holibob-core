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
            // Example: Fetch from FTP CSV file or API
            $feedUrl = $this->getConfig('feed_url');

            if (! $feedUrl) {
                Log::warning('Sykes feed URL not configured');
                return collect();
            }

            // For now, return empty collection
            // In real implementation, fetch and parse CSV/XML/API response
            return collect();

        } catch (\Exception $e) {
            Log::error('Failed to fetch Sykes properties', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Transform Sykes data to internal schema.
     *
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    public function transform(array $rawData): array
    {
        return [
            'external_id' => $rawData['property_id'] ?? '',
            'name' => $rawData['property_name'] ?? '',
            'slug' => $this->generateSlug(
                $rawData['property_name'] ?? '',
                $rawData['property_id'] ?? ''
            ),
            'description' => $rawData['description'] ?? '',
            'short_description' => Str::limit($rawData['description'] ?? '', 200),
            'property_type' => $this->mapPropertyType($rawData['type'] ?? ''),

            // Location
            'postcode' => $rawData['postcode'] ?? null,
            'address_line_1' => $rawData['address'] ?? null,
            'latitude' => isset($rawData['lat']) ? (float) $rawData['lat'] : null,
            'longitude' => isset($rawData['lng']) ? (float) $rawData['lng'] : null,

            // Capacity
            'sleeps' => isset($rawData['max_guests']) ? (int) $rawData['max_guests'] : 0,
            'bedrooms' => isset($rawData['bedrooms']) ? (int) $rawData['bedrooms'] : 0,
            'bathrooms' => isset($rawData['bathrooms']) ? (int) $rawData['bathrooms'] : 0,

            // Pricing
            'price_from' => isset($rawData['weekly_price']) ? (float) $rawData['weekly_price'] : null,
            'price_currency' => 'GBP',

            // Affiliate
            'affiliate_url' => $this->generateAffiliateUrl($rawData['property_id'] ?? ''),
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

        // Example: Parse comma-separated image URLs
        if (isset($rawData['images']) && is_string($rawData['images'])) {
            $imageUrls = explode(',', $rawData['images']);

            foreach ($imageUrls as $index => $url) {
                $images[] = [
                    'url' => trim($url),
                    'display_order' => $index,
                    'is_primary' => $index === 0,
                ];
            }
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
