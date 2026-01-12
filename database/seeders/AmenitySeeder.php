<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // Internet & Entertainment
            ['name' => 'WiFi', 'slug' => 'wifi', 'icon' => 'wifi', 'category' => 'internet'],
            ['name' => 'Smart TV', 'slug' => 'smart-tv', 'icon' => 'tv', 'category' => 'entertainment'],
            ['name' => 'Streaming Services', 'slug' => 'streaming', 'icon' => 'play-circle', 'category' => 'entertainment'],
            ['name' => 'Sky TV', 'slug' => 'sky-tv', 'icon' => 'satellite', 'category' => 'entertainment'],

            // Parking & Access
            ['name' => 'Parking', 'slug' => 'parking', 'icon' => 'car', 'category' => 'parking'],
            ['name' => 'Private Parking', 'slug' => 'private-parking', 'icon' => 'car', 'category' => 'parking'],
            ['name' => 'Garage', 'slug' => 'garage', 'icon' => 'warehouse', 'category' => 'parking'],
            ['name' => 'Electric Car Charging', 'slug' => 'ev-charging', 'icon' => 'bolt', 'category' => 'parking'],

            // Garden & Outdoor
            ['name' => 'Garden', 'slug' => 'garden', 'icon' => 'tree', 'category' => 'outdoor'],
            ['name' => 'Patio', 'slug' => 'patio', 'icon' => 'umbrella', 'category' => 'outdoor'],
            ['name' => 'BBQ', 'slug' => 'bbq', 'icon' => 'grill', 'category' => 'outdoor'],
            ['name' => 'Outdoor Furniture', 'slug' => 'outdoor-furniture', 'icon' => 'chair', 'category' => 'outdoor'],
            ['name' => 'Hot Tub', 'slug' => 'hot-tub', 'icon' => 'water', 'category' => 'outdoor'],
            ['name' => 'Swimming Pool', 'slug' => 'pool', 'icon' => 'swimming-pool', 'category' => 'outdoor'],

            // Pets & Families
            ['name' => 'Pet Friendly', 'slug' => 'pet-friendly', 'icon' => 'dog', 'category' => 'pets'],
            ['name' => 'Dog Welcome', 'slug' => 'dog-welcome', 'icon' => 'dog', 'category' => 'pets'],
            ['name' => 'High Chair', 'slug' => 'high-chair', 'icon' => 'baby', 'category' => 'family'],
            ['name' => 'Cot', 'slug' => 'cot', 'icon' => 'bed', 'category' => 'family'],
            ['name' => 'Stair Gate', 'slug' => 'stair-gate', 'icon' => 'shield', 'category' => 'family'],

            // Kitchen
            ['name' => 'Dishwasher', 'slug' => 'dishwasher', 'icon' => 'dish', 'category' => 'kitchen'],
            ['name' => 'Washing Machine', 'slug' => 'washing-machine', 'icon' => 'washer', 'category' => 'kitchen'],
            ['name' => 'Tumble Dryer', 'slug' => 'tumble-dryer', 'icon' => 'dryer', 'category' => 'kitchen'],
            ['name' => 'Microwave', 'slug' => 'microwave', 'icon' => 'microwave', 'category' => 'kitchen'],
            ['name' => 'Coffee Machine', 'slug' => 'coffee-machine', 'icon' => 'coffee', 'category' => 'kitchen'],

            // Heating & Cooling
            ['name' => 'Central Heating', 'slug' => 'central-heating', 'icon' => 'fire', 'category' => 'heating'],
            ['name' => 'Log Burner', 'slug' => 'log-burner', 'icon' => 'fire', 'category' => 'heating'],
            ['name' => 'Open Fire', 'slug' => 'open-fire', 'icon' => 'flame', 'category' => 'heating'],
            ['name' => 'Air Conditioning', 'slug' => 'air-con', 'icon' => 'snowflake', 'category' => 'cooling'],

            // Special Features
            ['name' => 'Sea View', 'slug' => 'sea-view', 'icon' => 'water', 'category' => 'view'],
            ['name' => 'Beach Access', 'slug' => 'beach-access', 'icon' => 'umbrella-beach', 'category' => 'location'],
            ['name' => 'Coastal Location', 'slug' => 'coastal', 'icon' => 'water', 'category' => 'location'],
            ['name' => 'Rural Location', 'slug' => 'rural', 'icon' => 'tree', 'category' => 'location'],
            ['name' => 'Village Location', 'slug' => 'village', 'icon' => 'home', 'category' => 'location'],

            // Accessibility
            ['name' => 'Wheelchair Accessible', 'slug' => 'wheelchair-accessible', 'icon' => 'wheelchair', 'category' => 'accessibility'],
            ['name' => 'Ground Floor', 'slug' => 'ground-floor', 'icon' => 'building', 'category' => 'accessibility'],
            ['name' => 'Level Access', 'slug' => 'level-access', 'icon' => 'accessible', 'category' => 'accessibility'],

            // Other
            ['name' => 'Enclosed Garden', 'slug' => 'enclosed-garden', 'icon' => 'fence', 'category' => 'outdoor'],
            ['name' => 'Pub Nearby', 'slug' => 'pub-nearby', 'icon' => 'beer', 'category' => 'location'],
            ['name' => 'Shop Nearby', 'slug' => 'shop-nearby', 'icon' => 'shopping-cart', 'category' => 'location'],
            ['name' => 'Games Room', 'slug' => 'games-room', 'icon' => 'gamepad', 'category' => 'entertainment'],
            ['name' => 'Log Fire', 'slug' => 'log-fire', 'icon' => 'fire', 'category' => 'heating'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::updateOrCreate(
                ['slug' => $amenity['slug']],
                $amenity
            );
        }

        $this->command->info('Created ' . count($amenities) . ' amenities');
    }
}
