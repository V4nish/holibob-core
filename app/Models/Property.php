<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Property extends Model
{
    use Searchable;

    protected $fillable = [
        'affiliate_provider_id',
        'external_id',
        'name',
        'slug',
        'description',
        'short_description',
        'property_type',
        'location_id',
        'address_line_1',
        'address_line_2',
        'postcode',
        'latitude',
        'longitude',
        'sleeps',
        'bedrooms',
        'bathrooms',
        'price_from',
        'price_currency',
        'affiliate_url',
        'commission_rate',
        'is_active',
        'featured',
        'last_synced_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price_from' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function affiliateProvider(): BelongsTo
    {
        return $this->belongsTo(AffiliateProvider::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('display_order');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities')
            ->withTimestamps();
    }

    public function clickEvents(): HasMany
    {
        return $this->hasMany(ClickEvent::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'property_type' => $this->property_type,
            'location_id' => $this->location_id,
            'sleeps' => $this->sleeps,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'price_from' => $this->price_from,
            'is_active' => $this->is_active,
            'featured' => $this->featured,
        ];
    }
}
