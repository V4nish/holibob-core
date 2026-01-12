# Phase 5: Meilisearch Integration & Search Package - Completion Report

**Date**: January 12, 2026
**Status**: ✅ Complete

## Overview

Phase 5 implements a complete property search system using Meilisearch and Laravel Scout. The Search package provides a powerful, flexible query builder with filtering, sorting, pagination, and analytics capabilities.

---

## Completed Tasks

### 1. Laravel Scout & Meilisearch Installation ✅

**Packages Installed**:
- `laravel/scout` v10.23.0
- `meilisearch/meilisearch-php` v1.16.1
- `http-interop/http-factory-guzzle` v1.2.1

**Configuration Published**:
- Published `config/scout.php` with Meilisearch configuration

### 2. Meilisearch Index Configuration ✅

Configured comprehensive index settings in [config/scout.php](../config/scout.php:143-195):

**Filterable Attributes**:
- `location_id` - Filter by location
- `affiliate_provider_id` - Filter by provider
- `property_type` - cottage, apartment, villa, lodge
- `sleeps`, `bedrooms`, `bathrooms` - Capacity filters
- `price_from` - Price range filtering
- `is_active`, `featured` - Status filters

**Sortable Attributes**:
- `price_from` - Sort by price
- `sleeps` - Sort by capacity
- `bedrooms` - Sort by size
- `created_at` - Sort by date

**Searchable Attributes**:
- `name` - Property name
- `description` - Full description
- `short_description` - Summary
- `postcode` - UK postcode
- `address_line_1` - Street address

**Ranking Rules**:
```php
[
    'words',          // Match query words
    'typo',           // Typo tolerance
    'proximity',      // Word proximity
    'attribute',      // Attribute ranking
    'sort',           // Custom sort
    'exactness',      // Exact matches
    'featured:desc',  // Featured properties first
    'price_from:asc', // Cheaper properties first
]
```

### 3. Search Package Structure ✅

Created modular search package at `packages/search/`:

```
packages/search/
├── src/
│   ├── Builders/
│   │   └── PropertySearchBuilder.php
│   ├── Services/
│   │   └── SearchService.php
│   ├── Console/
│   │   ├── ReindexCommand.php
│   │   └── SearchStatsCommand.php
│   └── SearchServiceProvider.php
└── config/
```

### 4. PropertySearchBuilder ✅

Comprehensive query builder with fluent interface ([PropertySearchBuilder.php](../packages/search/src/Builders/PropertySearchBuilder.php)):

**Filter Methods**:
```php
$builder = new PropertySearchBuilder();

// Text query
$builder->query('cottage in cornwall');

// Location filter (single or multiple)
$builder->location(5);
$builder->location([5, 10, 15]);

// Property type
$builder->propertyType('cottage');
$builder->propertyType(['cottage', 'villa']);

// Capacity filters
$builder->sleeps(4);              // Min 4 people
$builder->sleeps(4, 8);           // 4-8 people
$builder->bedrooms(2);            // Min 2 bedrooms
$builder->bathrooms(1, 2);        // 1-2 bathrooms

// Price range
$builder->priceRange(500, 1000);  // £500-£1000
$builder->priceRange(null, 800);  // Under £800
$builder->priceRange(600, null);  // Over £600

// Status filters
$builder->activeOnly();
$builder->featuredOnly();

// Custom filters
$builder->where('pet_friendly = true');
```

**Sorting Methods**:
```php
// Predefined sorts
$builder->cheapest();              // Price ascending
$builder->mostExpensive();         // Price descending
$builder->largestFirst();          // Sleeps descending
$builder->featuredFirst();         // Featured first

// Custom sort
$builder->sortBy('bedrooms', 'desc');
```

**Execution Methods**:
```php
// Paginated results
$results = $builder->paginate(20, 1)->get();

// Raw results (no pagination)
$properties = $builder->raw();

// Count only
$count = $builder->count();
```

**Faceted Search**:
```php
$results = $builder
    ->withFacets(['property_type', 'sleeps', 'bedrooms'])
    ->get();

// Results include facet data
$facets = $results->facets;
```

### 5. SearchService ✅

High-level service for search operations ([SearchService.php](../packages/search/src/Services/SearchService.php)):

**Search from HTTP Request**:
```php
public function searchFromRequest(Request $request): PropertySearchBuilder
```

Automatically maps request parameters to search filters:
- `q` → query string
- `location` → location filter (array or single ID)
- `type` → property type filter
- `sleeps`, `bedrooms`, `bathrooms` → capacity filters
- `price_min`, `price_max` → price range
- `sort` → sorting (relevance, price_asc, price_desc, sleeps_desc, featured)
- `per_page`, `page` → pagination
- `facets` → enable faceted search

**Search Logging**:
```php
public function logSearch(
    string $query,
    int $resultsCount,
    ?int $userId = null,
    ?string $ipAddress = null,
    ?array $filters = null
): SearchLog
```

**Analytics Methods**:
```php
// Popular queries (last 30 days)
$popular = $searchService->popularQueries(limit: 10, days: 30);

// Empty searches (zero results)
$empty = $searchService->emptySearches(limit: 10, days: 30);

// Overall statistics
$stats = $searchService->getStatistics(days: 30);
// Returns: total_searches, unique_queries, avg_results, zero_result_rate
```

**Index Management**:
```php
// Reindex all properties
$count = $searchService->reindexProperties();

// Clear search index
$searchService->clearIndex();
```

### 6. Artisan Commands ✅

**Reindex Command** ([ReindexCommand.php](../packages/search/src/Console/ReindexCommand.php)):
```bash
# Reindex properties
php artisan search:reindex properties

# Clear and reindex
php artisan search:reindex --clear

# Reindex all models
php artisan search:reindex all
```

**Search Statistics Command** ([SearchStatsCommand.php](../packages/search/src/Console/SearchStatsCommand.php)):
```bash
# Last 30 days (default)
php artisan search:stats

# Custom time range
php artisan search:stats --days=7
```

**Output Example**:
```
Search Statistics (Last 30 days)

Metric                    Value
Total Searches            1,245
Unique Queries            423
Avg Results per Search    12.5
Zero Result Rate          8.3%

Top 10 Search Queries:
Query                  Count
cornwall cottage       89
pet friendly devon     45
coastal villa          38
...

Top 10 Queries with Zero Results:
Query                  Count
luxury castle          12
submarine              8
...
```

### 7. Search API Endpoints ✅

RESTful API controller ([SearchController.php](../app/Http/Controllers/Api/SearchController.php)):

**Endpoints**:

#### `GET /api/search/properties`

Search properties with filters:

**Query Parameters**:
- `q` (string) - Search query
- `location` (array) - Location IDs
- `type` (array) - Property types
- `sleeps` (int) - Minimum sleeps
- `bedrooms` (int) - Minimum bedrooms
- `bathrooms` (int) - Minimum bathrooms
- `price_min` (float) - Minimum price
- `price_max` (float) - Maximum price
- `sort` (string) - Sort order: relevance, price_asc, price_desc, sleeps_desc, featured
- `per_page` (int) - Results per page (1-100)
- `page` (int) - Page number
- `facets` (boolean) - Include facet data

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Luxury Cottage in Cornwall",
      "slug": "luxury-cottage-cornwall-SK001",
      "description": "...",
      "property_type": "cottage",
      "sleeps": 6,
      "bedrooms": 3,
      "bathrooms": 2,
      "price_from": 850.00,
      "price_currency": "GBP",
      "affiliate_url": "...",
      "location_id": 5
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "facets": {
    "property_type": {
      "cottage": 80,
      "villa": 40,
      "apartment": 30
    }
  }
}
```

#### `GET /api/search/suggest`

Autocomplete/suggestions:

**Query Parameters**:
- `q` (string, required) - Query string (min 2 chars)
- `limit` (int) - Max suggestions (1-20, default 10)

**Response**:
```json
{
  "suggestions": [
    {
      "id": 1,
      "name": "Luxury Cottage in Cornwall",
      "slug": "luxury-cottage-cornwall-SK001",
      "location": "Cornwall"
    }
  ]
}
```

#### `GET /api/search/popular`

Popular search queries:

**Query Parameters**:
- `limit` (int) - Max queries (default 10, max 50)
- `days` (int) - Days to analyze (default 30, max 365)

**Response**:
```json
{
  "popular_queries": {
    "cornwall cottage": 89,
    "pet friendly devon": 45,
    "coastal villa": 38
  }
}
```

#### `GET /api/search/statistics`

Search analytics (admin only):

**Query Parameters**:
- `days` (int) - Days to analyze (default 30, max 365)

**Response**:
```json
{
  "statistics": {
    "total_searches": 1245,
    "unique_queries": 423,
    "avg_results": 12.5,
    "zero_result_rate": 8.3
  },
  "popular_queries": {
    "cornwall cottage": 89
  },
  "zero_result_queries": {
    "luxury castle": 12
  }
}
```

### 8. Environment Configuration ✅

Updated [.env.example](../.env.example:74-95):

```env
# Laravel Scout & Meilisearch
SCOUT_DRIVER=meilisearch
SCOUT_PREFIX=
SCOUT_QUEUE=true
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=holibob-meilisearch-master-key-change-in-production

# Affiliate Providers
# Sykes Cottages (via Awin)
SYKES_ENABLED=false
SYKES_AFFILIATE_ID=
SYKES_FEED_URL=
SYKES_FEED_FORMAT=csv
SYKES_BASE_URL=https://www.sykescottages.co.uk
SYKES_COMMISSION_RATE=6.0

# Hoseasons
HOSEASONS_ENABLED=false
HOSEASONS_API_KEY=
HOSEASONS_AFFILIATE_ID=
HOSEASONS_API_URL=https://api.hoseasons.co.uk
HOSEASONS_COMMISSION_RATE=5.0
```

### 9. Service Provider Registration ✅

Registered in [bootstrap/providers.php](../bootstrap/providers.php):
```php
return [
    App\Providers\AppServiceProvider::class,
    Holibob\Affiliates\AffiliatesServiceProvider::class,
    Holibob\Search\SearchServiceProvider::class, // NEW
];
```

---

## Architecture Details

### Data Flow

```
┌──────────────────────────────────────────────────────┐
│  1. User Request: GET /api/search/properties         │
│     ?q=cornwall&sleeps=4&price_max=1000              │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  2. SearchController::properties()                    │
│     → Validate request parameters                     │
│     → Call SearchService::searchFromRequest()         │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  3. SearchService Maps Request to Builder             │
│     → Create PropertySearchBuilder                    │
│     → Apply filters from request params               │
│     → Set sorting and pagination                      │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  4. PropertySearchBuilder::get()                      │
│     → Build Scout query: Property::search('cornwall') │
│     → Apply filters as Meilisearch filter string      │
│     → Apply sorting rules                             │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  5. Laravel Scout → Meilisearch                       │
│     → HTTP request to Meilisearch server              │
│     → JSON payload with query, filters, sort          │
│     → Meilisearch processes search                    │
│     → Returns results with relevance scores           │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  6. Scout Hydrates Eloquent Models                    │
│     → Maps Meilisearch results to Property models     │
│     → Loads relationships if needed                   │
│     → Creates LengthAwarePaginator                    │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  7. SearchService::logSearch()                        │
│     → Create SearchLog record                         │
│     → Store: query, results_count, user_id, IP, etc.  │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  8. Controller Returns JSON Response                  │
│     → Format results with meta and links              │
│     → Include facets if requested                     │
│     → Return to client                                │
└──────────────────────────────────────────────────────┘
```

### Meilisearch Filter Syntax

The PropertySearchBuilder generates Meilisearch filter strings:

**Examples**:
```php
// Single filter
"location_id = 5"

// Multiple filters (AND)
"location_id = 5 AND sleeps >= 4 AND price_from <= 1000"

// Array filter (OR)
"property_type IN ['cottage', 'villa']"
"location_id IN [5, 10, 15]"

// Range filters
"price_from >= 500 AND price_from <= 1000"
"sleeps >= 4 AND sleeps <= 8"
```

### Search Relevance Ranking

Meilisearch uses a multi-criteria ranking system:

1. **Words**: How many query words are in the document
2. **Typo**: Fewer typos = higher rank
3. **Proximity**: Closer words = higher rank
4. **Attribute**: Match in name > description
5. **Sort**: Custom sorting (price, sleeps, etc.)
6. **Exactness**: Exact matches rank higher
7. **Featured**: Featured properties boosted
8. **Price**: Cheaper properties slightly boosted

---

## Usage Examples

### Basic Search

```php
use Holibob\Search\Builders\PropertySearchBuilder;

$builder = new PropertySearchBuilder();

$results = $builder
    ->query('cottage in cornwall')
    ->sleeps(4)
    ->priceRange(500, 1000)
    ->activeOnly()
    ->cheapest()
    ->paginate(20, 1)
    ->get();

foreach ($results as $property) {
    echo "{$property->name} - £{$property->price_from}\n";
}
```

### Using SearchService

```php
use Holibob\Search\Services\SearchService;

$searchService = app(SearchService::class);

// From HTTP request
$builder = $searchService->searchFromRequest($request);
$results = $builder->get();

// Log the search
$searchService->logSearch(
    query: $request->input('q'),
    resultsCount: $results->total(),
    userId: auth()->id(),
    ipAddress: $request->ip()
);
```

### API Request Examples

**Simple text search**:
```bash
GET /api/search/properties?q=cornwall
```

**Complex filtered search**:
```bash
GET /api/search/properties?q=cottage&location[]=5&location[]=10&sleeps=4&bedrooms=2&price_min=500&price_max=1000&sort=price_asc&per_page=20&page=1
```

**Featured properties only**:
```bash
GET /api/search/properties?featured=true&sort=featured&per_page=10
```

**With facets**:
```bash
GET /api/search/properties?q=devon&facets=true
```

---

## Testing the Implementation

### 1. Start Meilisearch (Docker)

Ensure Meilisearch is running:
```bash
docker-compose up -d meilisearch
```

### 2. Sync Properties to Search Index

```bash
# Import properties to Meilisearch
php artisan scout:import "App\Models\Property"

# Or use custom command
php artisan search:reindex properties
```

### 3. Test Search via Artisan Tinker

```bash
php artisan tinker
```

```php
// Simple search
$results = Property::search('cornwall')->get();

// With filters
$results = Property::search('cottage')
    ->where('sleeps >= 4')
    ->where('price_from <= 1000')
    ->get();

// Using PropertySearchBuilder
use Holibob\Search\Builders\PropertySearchBuilder;

$builder = new PropertySearchBuilder();
$results = $builder
    ->query('cornwall')
    ->sleeps(4)
    ->priceRange(null, 1000)
    ->cheapest()
    ->paginate(10)
    ->get();

echo "Found {$results->total()} properties\n";
```

### 4. Test API Endpoints

```bash
# Search properties
curl "http://localhost/api/search/properties?q=cornwall&sleeps=4"

# Get suggestions
curl "http://localhost/api/search/suggest?q=corn"

# Popular queries
curl "http://localhost/api/search/popular?limit=5"

# Statistics
curl "http://localhost/api/search/statistics?days=30"
```

### 5. View Search Statistics

```bash
php artisan search:stats --days=7
```

---

## Performance Considerations

### Indexing Strategy

**When to Sync**:
- After affiliate property sync completes
- When properties are manually created/updated
- Schedule nightly reindex for data integrity

**Queue Configuration**:
```php
// config/scout.php
'queue' => true, // Sync to Meilisearch via queue
```

**Queueing Search Updates**:
```php
// In your sync job
Property::updateOrCreate(...)->searchable(); // Queued automatically
```

### Search Optimization

**Index Only Active Properties**:
```php
// app/Models/Property.php
public function shouldBeSearchable(): bool
{
    return $this->is_active;
}
```

**Eager Loading Relationships**:
```php
$results = $builder->get()->load('location', 'images', 'amenities');
```

### Caching Strategies

**Cache Popular Queries**:
```php
$popular = Cache::remember('search:popular:30d', 3600, function() {
    return $searchService->popularQueries(10, 30);
});
```

**Cache Facet Data**:
```php
$facets = Cache::remember("search:facets:{$cacheKey}", 600, function() {
    return $this->getFacets();
});
```

---

## Monitoring & Analytics

### Search Logs Table

Every search is logged to `search_logs`:

```php
{
    "id": 1,
    "user_id": 123,
    "query": "cornwall cottage",
    "results_count": 45,
    "ip_address": "192.168.1.1",
    "filters": ["location_id = 5", "sleeps >= 4"],
    "created_at": "2026-01-12 10:00:00"
}
```

### Key Metrics to Monitor

**Search Health**:
```php
$stats = $searchService->getStatistics(30);

// Alert if zero-result rate > 15%
if ($stats['zero_result_rate'] > 15) {
    // Send alert to team
}
```

**Performance Metrics**:
- Average search response time
- 95th percentile response time
- Meilisearch index size
- Queue processing time for syncs

**Business Metrics**:
- Click-through rate (searches → property views)
- Conversion rate (searches → bookings)
- Most popular search terms
- Most filtered attributes

---

## Meilisearch Administration

### Access Meilisearch UI

```bash
# Meilisearch runs at http://localhost:7700
open http://localhost:7700
```

### View Index Statistics

```bash
curl http://localhost:7700/indexes/properties/stats \
  -H 'Authorization: Bearer holibob-meilisearch-master-key-change-in-production'
```

### Update Index Settings

```bash
curl -X PATCH http://localhost:7700/indexes/properties/settings \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_KEY' \
  --data-binary '{
    "rankingRules": [
      "words",
      "typo",
      "proximity",
      "attribute",
      "sort",
      "exactness",
      "featured:desc",
      "price_from:asc"
    ]
  }'
```

---

## Error Handling

### Search Failures

**No Results Found**:
- Returns empty paginator
- Logs zero-result search
- Facets still returned if requested

**Meilisearch Unavailable**:
```php
try {
    $results = $builder->get();
} catch (\Exception $e) {
    Log::error('Search failed', ['error' => $e->getMessage()]);

    // Return empty results
    return new LengthAwarePaginator([], 0, 20, 1);
}
```

**Invalid Filters**:
- Validation in API controller
- Builder methods type-safe
- Meilisearch returns 400 for invalid syntax

### Index Sync Failures

**Property Fails to Index**:
```php
// In Property model
protected static function booted()
{
    static::created(function ($property) {
        try {
            $property->searchable();
        } catch (\Exception $e) {
            Log::error('Failed to index property', [
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);
        }
    });
}
```

---

## Success Criteria - ✅ All Met

- ✅ Laravel Scout installed and configured
- ✅ Meilisearch index settings configured
- ✅ PropertySearchBuilder with fluent interface
- ✅ SearchService with HTTP request mapping
- ✅ Search logging system
- ✅ Analytics methods (popular, empty searches, statistics)
- ✅ Artisan commands (reindex, stats)
- ✅ RESTful API endpoints
- ✅ API routes registered
- ✅ Service provider registered
- ✅ Environment configuration updated
- ✅ Comprehensive filtering support
- ✅ Sorting and pagination
- ✅ Faceted search foundation

---

## Files Created/Modified

### New Files

**Search Package**:
- `packages/search/src/Builders/PropertySearchBuilder.php`
- `packages/search/src/Services/SearchService.php`
- `packages/search/src/Console/ReindexCommand.php`
- `packages/search/src/Console/SearchStatsCommand.php`
- `packages/search/src/SearchServiceProvider.php`

**API**:
- `app/Http/Controllers/Api/SearchController.php`
- `routes/api.php`

### Modified Files

- `config/scout.php` - Meilisearch index configuration
- `.env.example` - Scout and affiliate provider settings
- `bootstrap/providers.php` - Registered SearchServiceProvider
- `composer.json` - Added Scout and Meilisearch packages

---

## Next Steps

### Frontend Implementation

**Search Interface**:
- React search component with filters
- Autocomplete/suggestions
- Faceted navigation
- Sort controls
- Pagination

**Property Display**:
- Search results grid/list view
- Property cards with images
- Quick filters
- Save search functionality

### Advanced Features

**Geo-Search**:
```php
// Add to PropertySearchBuilder
public function near(float $lat, float $lng, float $radiusKm): self
{
    // Use Meilisearch _geoRadius filter
    $radiusMeters = $radiusKm * 1000;
    $this->filters[] = "_geoRadius($lat, $lng, $radiusMeters)";

    return $this;
}
```

**Synonym Support**:
```php
// In Meilisearch settings
'synonyms' => [
    'cottage' => ['house', 'cabin', 'lodge'],
    'pet-friendly' => ['dog-friendly', 'pets-allowed'],
]
```

**Stop Words**:
```php
// Common words to ignore
'stopWords' => ['the', 'a', 'an', 'in', 'at', 'to', 'for']
```

**Typo Tolerance**:
```php
// Configure in Meilisearch settings
'typoTolerance' => [
    'enabled' => true,
    'minWordSizeForTypos' => [
        'oneTypo' => 5,
        'twoTypos' => 9,
    ],
]
```

---

**Phase 5 Complete** 🎉

The Search package is production-ready with powerful filtering, comprehensive analytics, and RESTful API endpoints. Properties can now be searched efficiently with Meilisearch!
