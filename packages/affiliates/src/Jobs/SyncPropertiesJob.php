<?php

namespace Holibob\Affiliates\Jobs;

use App\Models\AffiliateProvider;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\SyncLog;
use Holibob\Affiliates\Contracts\AffiliateProviderInterface;
use Holibob\Affiliates\Events\PropertySynced;
use Holibob\Affiliates\Events\SyncFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPropertiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AffiliateProvider $affiliateProvider
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $syncLog = SyncLog::create([
            'affiliate_provider_id' => $this->affiliateProvider->id,
            'status' => 'started',
            'started_at' => now(),
        ]);

        try {
            // Instantiate the provider
            $providerClass = $this->affiliateProvider->adapter_class;

            if (! class_exists($providerClass)) {
                throw new \Exception("Provider class {$providerClass} not found");
            }

            /** @var AffiliateProviderInterface $provider */
            $provider = new $providerClass($this->affiliateProvider->config ?? []);

            if (! $provider->isConfigured()) {
                throw new \Exception("Provider {$provider->getName()} is not properly configured");
            }

            Log::info("Starting sync for {$provider->getName()}");

            // Fetch properties
            $rawProperties = $provider->fetchProperties();
            $syncLog->update(['properties_fetched' => $rawProperties->count()]);

            $created = 0;
            $updated = 0;

            foreach ($rawProperties as $rawData) {
                try {
                    $transformedData = $provider->transform($rawData);
                    $this->upsertProperty($transformedData, $created, $updated);
                } catch (\Exception $e) {
                    Log::error('Failed to sync individual property', [
                        'external_id' => $rawData['property_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Mark as successful
            $syncLog->update([
                'status' => 'success',
                'completed_at' => now(),
                'properties_created' => $created,
                'properties_updated' => $updated,
            ]);

            $this->affiliateProvider->update([
                'last_sync_at' => now(),
            ]);

            PropertySynced::dispatch($this->affiliateProvider, $syncLog);

            Log::info("Sync completed for {$provider->getName()}", [
                'created' => $created,
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            SyncFailed::dispatch($this->affiliateProvider, $syncLog, $e);

            Log::error('Sync failed', [
                'provider' => $this->affiliateProvider->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upsert a property.
     *
     * @param array<string, mixed> $data
     * @param int &$created
     * @param int &$updated
     * @return void
     */
    protected function upsertProperty(array $data, int &$created, int &$updated): void
    {
        DB::transaction(function () use ($data, &$created, &$updated) {
            // Find or create location
            $location = $this->findOrCreateLocation($data);

            // Extract images and amenities before creating property
            $images = $data['images'] ?? [];
            $amenitySlugs = $data['amenities'] ?? [];
            unset($data['images'], $data['amenities']);

            // Upsert property
            $property = Property::updateOrCreate(
                [
                    'affiliate_provider_id' => $this->affiliateProvider->id,
                    'external_id' => $data['external_id'],
                ],
                array_merge($data, [
                    'location_id' => $location->id,
                    'last_synced_at' => now(),
                ])
            );

            if ($property->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            // Sync images
            if (! empty($images)) {
                $this->syncImages($property, $images);
            }

            // Sync amenities
            if (! empty($amenitySlugs)) {
                $this->syncAmenities($property, $amenitySlugs);
            }
        });
    }

    /**
     * Find or create location from postcode.
     *
     * @param array<string, mixed> $data
     * @return Location
     */
    protected function findOrCreateLocation(array $data): Location
    {
        // For now, create a simple location
        // In production, implement proper postcode lookup service
        return Location::firstOrCreate(
            ['slug' => 'uk'],
            [
                'name' => 'United Kingdom',
                'type' => 'country',
                'latitude' => 54.7023545,
                'longitude' => -3.2765753,
            ]
        );
    }

    /**
     * Sync property images.
     *
     * @param Property $property
     * @param array<int, array<string, mixed>> $images
     * @return void
     */
    protected function syncImages(Property $property, array $images): void
    {
        // Delete existing images
        $property->images()->delete();

        // Create new images
        foreach ($images as $imageData) {
            PropertyImage::create(array_merge($imageData, [
                'property_id' => $property->id,
            ]));
        }
    }

    /**
     * Sync property amenities.
     *
     * @param Property $property
     * @param array<int, string> $amenitySlugs
     * @return void
     */
    protected function syncAmenities(Property $property, array $amenitySlugs): void
    {
        $amenityIds = Amenity::whereIn('slug', $amenitySlugs)->pluck('id')->toArray();
        $property->amenities()->sync($amenityIds);
    }
}
