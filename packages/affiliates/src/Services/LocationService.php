<?php

namespace Holibob\Affiliates\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationService
{
    /**
     * Find or create a location by postcode.
     *
     * @param string|null $postcode
     * @param float|null $latitude
     * @param float|null $longitude
     * @return Location
     */
    public function findOrCreateByPostcode(
        ?string $postcode,
        ?float $latitude = null,
        ?float $longitude = null
    ): Location {
        // If no postcode provided, use default UK location
        if (empty($postcode)) {
            return $this->getDefaultLocation();
        }

        // Normalize postcode (uppercase, remove extra spaces)
        $postcode = $this->normalizePostcode($postcode);

        // Try to find existing location by postcode
        $location = Location::where('postcode', $postcode)->first();

        if ($location) {
            return $location;
        }

        // If coordinates not provided, try to geocode the postcode
        if (! $latitude || ! $longitude) {
            $geocoded = $this->geocodePostcode($postcode);
            $latitude = $geocoded['latitude'] ?? null;
            $longitude = $geocoded['longitude'] ?? null;
        }

        // Extract area from postcode (e.g., "SW1A" from "SW1A 1AA")
        $area = $this->extractArea($postcode);
        $district = $this->extractDistrict($postcode);

        // Find or create parent location (district/area)
        $parent = $this->findOrCreateParentLocation($area, $district);

        // Create the location
        return Location::create([
            'name' => $postcode,
            'slug' => Str::slug($postcode),
            'type' => 'postcode',
            'postcode' => $postcode,
            'parent_id' => $parent?->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'property_count' => 0,
        ]);
    }

    /**
     * Geocode a UK postcode using Postcodes.io API (free).
     *
     * @param string $postcode
     * @return array{latitude: float|null, longitude: float|null, district: string|null}
     */
    protected function geocodePostcode(string $postcode): array
    {
        $cacheKey = 'geocode:' . Str::slug($postcode);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($postcode) {
            try {
                // Use free Postcodes.io API for UK postcodes
                $response = Http::timeout(10)->get('https://api.postcodes.io/postcodes/' . urlencode($postcode));

                if ($response->successful() && $response->json('status') === 200) {
                    $result = $response->json('result');

                    return [
                        'latitude' => $result['latitude'] ?? null,
                        'longitude' => $result['longitude'] ?? null,
                        'district' => $result['admin_district'] ?? null,
                        'county' => $result['admin_county'] ?? null,
                        'region' => $result['region'] ?? null,
                    ];
                }

                Log::warning('Failed to geocode postcode', [
                    'postcode' => $postcode,
                    'status' => $response->status(),
                ]);

                return ['latitude' => null, 'longitude' => null, 'district' => null];

            } catch (\Exception $e) {
                Log::error('Geocoding exception', [
                    'postcode' => $postcode,
                    'error' => $e->getMessage(),
                ]);

                return ['latitude' => null, 'longitude' => null, 'district' => null];
            }
        });
    }

    /**
     * Normalize UK postcode format.
     *
     * @param string $postcode
     * @return string
     */
    protected function normalizePostcode(string $postcode): string
    {
        // Remove extra spaces and convert to uppercase
        $postcode = strtoupper(trim($postcode));

        // Ensure proper spacing (e.g., "SW1A1AA" -> "SW1A 1AA")
        if (preg_match('/^([A-Z]{1,2}\d{1,2}[A-Z]?)\s*(\d[A-Z]{2})$/', $postcode, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        return $postcode;
    }

    /**
     * Extract area from postcode (e.g., "SW" from "SW1A 1AA").
     *
     * @param string $postcode
     * @return string|null
     */
    protected function extractArea(string $postcode): ?string
    {
        if (preg_match('/^([A-Z]{1,2})/', $postcode, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract district from postcode (e.g., "SW1" from "SW1A 1AA").
     *
     * @param string $postcode
     * @return string|null
     */
    protected function extractDistrict(string $postcode): ?string
    {
        if (preg_match('/^([A-Z]{1,2}\d{1,2}[A-Z]?)/', $postcode, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Find or create parent location (district/area).
     *
     * @param string|null $area
     * @param string|null $district
     * @return Location|null
     */
    protected function findOrCreateParentLocation(?string $area, ?string $district): ?Location
    {
        if (! $district) {
            return $this->getDefaultLocation();
        }

        // Try to find existing district location
        $location = Location::where('name', $district)
            ->where('type', 'district')
            ->first();

        if ($location) {
            return $location;
        }

        // Create district location
        return Location::create([
            'name' => $district,
            'slug' => Str::slug($district),
            'type' => 'district',
            'parent_id' => $this->getDefaultLocation()->id,
            'property_count' => 0,
        ]);
    }

    /**
     * Get or create default UK location.
     *
     * @return Location
     */
    protected function getDefaultLocation(): Location
    {
        return Location::firstOrCreate(
            ['slug' => 'united-kingdom'],
            [
                'name' => 'United Kingdom',
                'type' => 'country',
                'parent_id' => null,
                'property_count' => 0,
            ]
        );
    }
}
