# Holibob - Technical Specification for Development

## Document Version
**Version**: 1.0  
**Date**: January 12, 2026  
**Project**: Holibob UK Holiday Search Engine  
**Architecture**: Laravel + Inertia.js (React) + PostgreSQL + Meilisearch

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Database Schema](#database-schema)
5. [Package Structure](#package-structure)
6. [Development Phases](#development-phases)
7. [API Specifications](#api-specifications)
8. [Docker Configuration](#docker-configuration)
9. [CI/CD Pipeline](#cicd-pipeline)
10. [Security Considerations](#security-considerations)

---

## Project Overview

### Purpose
Holibob is a UK-focused holiday search engine that aggregates cottage rentals, resorts, and holiday accommodations through affiliate partnerships. The platform will expand to include places of interest (restaurants, attractions, amenities) to become a comprehensive family holiday planning resource.

### Goals
- Aggregate holiday properties from multiple UK affiliate providers
- Provide fast, intuitive search with rich filtering
- Track affiliate clicks and conversions for commission
- Create modular, maintainable architecture
- Enable future mobile app development
- Scale to thousands of properties and POIs

### Core User Journey
1. User searches by location, dates, guests, preferences
2. System displays relevant properties with photos, pricing, amenities
3. User views property details and nearby places of interest
4. User clicks "Book Now" → redirected to affiliate site with tracking
5. System logs click for commission tracking

---

## Technology Stack

### Backend
- **Framework**: Laravel 11.x (latest stable)
- **PHP Version**: 8.3
- **Database**: PostgreSQL 15
- **Cache/Queue**: Redis 7.x
- **Search Engine**: Meilisearch (latest stable)
- **HTTP Client**: Guzzle 7.x

### Frontend
- **Framework**: React 18.x
- **Integration**: Inertia.js 1.x (Laravel + React SSR)
- **Styling**: Tailwind CSS 3.x
- **Build Tool**: Vite 5.x
- **UI Components**: Headless UI (Tailwind companion)

### DevOps
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx (within Docker)
- **CI/CD**: GitHub Actions
- **Deployment**: Ansible playbooks to Vultr infrastructure
- **Process Manager**: Supervisor (for Laravel queues/scheduler)

### Development Tools
- **Version Control**: Git (private GitHub repository)
- **Code Quality**: PHPStan (static analysis), ESLint + Prettier (JS/React)
- **Testing**: PHPUnit (backend), Vitest (frontend)
- **API Testing**: Pest PHP (expressive testing)

---

## System Architecture

### Architectural Principles
1. **Modularity**: Core features in Laravel packages (packages/)
2. **Event-Driven**: Packages communicate via Laravel events
3. **API-Ready**: While using Inertia, maintain REST API capability for future mobile apps
4. **Separation of Concerns**: Affiliate logic isolated from core application
5. **Configuration-Driven**: Provider adapters configurable without code changes

### High-Level Architecture Diagram
```
┌─────────────────────────────────────────────────────────────┐
│                        User Interface                        │
│                  (React via Inertia.js)                      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                     Laravel Application                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Core Package │  │  Affiliates  │  │   Tracking   │     │
│  │              │  │   Package    │  │   Package    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐                        │
│  │   Places     │  │    Search    │                        │
│  │   Package    │  │   Package    │                        │
│  └──────────────┘  └──────────────┘                        │
└───────────┬────────────────────┬────────────────────────────┘
            │                    │
┌───────────▼─────────┐  ┌───────▼──────────┐
│   PostgreSQL DB     │  │   Meilisearch    │
│  (Properties, POIs) │  │  (Search Index)  │
└─────────────────────┘  └──────────────────┘
            │
┌───────────▼─────────┐
│   Redis Cache       │
│   (Sessions/Queue)  │
└─────────────────────┘
```

### Package Architecture

#### Core Package (app/)
- User authentication and authorization
- Property browsing and search interface
- Booking click-through handling
- Admin dashboard

#### Affiliates Package (packages/affiliates/)
```
packages/affiliates/
├── src/
│   ├── Contracts/
│   │   ├── AffiliateProviderInterface.php
│   │   └── DataTransformerInterface.php
│   ├── Providers/
│   │   ├── SykesProvider.php
│   │   ├── HoseasonsProvider.php
│   │   └── CottagesComProvider.php
│   ├── Transformers/
│   │   ├── PropertyTransformer.php
│   │   └── AvailabilityTransformer.php
│   ├── Jobs/
│   │   ├── SyncPropertiesJob.php
│   │   └── UpdateAvailabilityJob.php
│   ├── Events/
│   │   ├── PropertySynced.php
│   │   └── SyncFailed.php
│   ├── Models/
│   │   ├── AffiliateProvider.php
│   │   └── SyncLog.php
│   └── AffiliatesServiceProvider.php
├── config/
│   └── affiliates.php
├── database/
│   └── migrations/
└── tests/
```

**Key Responsibilities**:
- Define provider interface/contract
- Implement provider-specific adapters (CSV/XML/FTP/API)
- Transform external data to internal schema
- Schedule and execute sync jobs
- Log sync status and errors
- Fire events on successful/failed syncs

#### Tracking Package (packages/tracking/)
```
packages/tracking/
├── src/
│   ├── Models/
│   │   ├── ClickEvent.php
│   │   └── Conversion.php
│   ├── Services/
│   │   └── TrackingService.php
│   ├── Jobs/
│   │   └── ProcessConversionWebhook.php
│   └── TrackingServiceProvider.php
├── config/
│   └── tracking.php
└── database/
    └── migrations/
```

**Key Responsibilities**:
- Generate tracked affiliate URLs with parameters
- Log all outbound clicks (property, user/session, timestamp)
- Capture pre-redirect user data (dates, guests)
- Handle affiliate conversion webhooks (future)
- Provide analytics data

#### Places Package (packages/places/)
```
packages/places/
├── src/
│   ├── Models/
│   │   ├── PlaceOfInterest.php
│   │   └── PlaceCategory.php
│   ├── Services/
│   │   ├── GooglePlacesService.php
│   │   └── RadiusSearchService.php
│   └── PlacesServiceProvider.php
├── config/
│   └── places.php
└── database/
    └── migrations/
```

**Key Responsibilities**:
- Store and manage POI data (restaurants, attractions, amenities)
- Integrate with external APIs (Google Places, OpenStreetMap)
- Calculate distances from properties
- Categorize and filter places

#### Search Package (packages/search/)
```
packages/search/
├── src/
│   ├── Services/
│   │   ├── SearchService.php
│   │   └── FilterService.php
│   ├── Indexes/
│   │   └── PropertyIndex.php
│   └── SearchServiceProvider.php
└── config/
    └── search.php
```

**Key Responsibilities**:
- Manage Meilisearch indexing
- Build search queries with filters
- Handle faceted search (location, dates, price, amenities)
- Return paginated results
- Update index when properties change

---

## Database Schema

### Core Tables

#### users
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
```

#### properties
```sql
CREATE TABLE properties (
    id BIGSERIAL PRIMARY KEY,
    affiliate_provider_id BIGINT NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(1000),
    property_type VARCHAR(100), -- cottage, apartment, villa, etc.
    
    -- Location
    location_id BIGINT NOT NULL,
    address_line_1 VARCHAR(255),
    address_line_2 VARCHAR(255),
    postcode VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Capacity
    sleeps SMALLINT NOT NULL,
    bedrooms SMALLINT NOT NULL,
    bathrooms SMALLINT NOT NULL,
    
    -- Pricing (indicative, real-time from affiliate)
    price_from DECIMAL(10, 2),
    price_currency CHAR(3) DEFAULT 'GBP',
    
    -- Affiliate tracking
    affiliate_url TEXT NOT NULL,
    commission_rate DECIMAL(5, 2),
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    last_synced_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (affiliate_provider_id) REFERENCES affiliate_providers(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

CREATE INDEX idx_properties_slug ON properties(slug);
CREATE INDEX idx_properties_location ON properties(location_id);
CREATE INDEX idx_properties_affiliate ON properties(affiliate_provider_id, external_id);
CREATE INDEX idx_properties_active ON properties(is_active) WHERE is_active = TRUE;
CREATE INDEX idx_properties_coords ON properties(latitude, longitude);
CREATE INDEX idx_properties_sleeps ON properties(sleeps);
```

#### locations
Hierarchical structure: Country > Region > Area > Town/City
```sql
CREATE TABLE locations (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- country, region, area, town, postcode
    
    -- Geographic center
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Counts for filtering
    property_count INT DEFAULT 0,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (parent_id) REFERENCES locations(id)
);

CREATE INDEX idx_locations_parent ON locations(parent_id);
CREATE INDEX idx_locations_slug ON locations(slug);
CREATE INDEX idx_locations_type ON locations(type);
CREATE UNIQUE INDEX idx_locations_unique ON locations(parent_id, slug, type);
```

#### amenities
```sql
CREATE TABLE amenities (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(100), -- icon identifier
    category VARCHAR(100), -- general, entertainment, outdoor, etc.
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_amenities_category ON amenities(category);
```

#### property_amenities (pivot)
```sql
CREATE TABLE property_amenities (
    property_id BIGINT NOT NULL,
    amenity_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    PRIMARY KEY (property_id, amenity_id),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);
```

#### property_images
```sql
CREATE TABLE property_images (
    id BIGSERIAL PRIMARY KEY,
    property_id BIGINT NOT NULL,
    url TEXT NOT NULL,
    thumbnail_url TEXT,
    alt_text VARCHAR(500),
    display_order SMALLINT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE INDEX idx_property_images_property ON property_images(property_id);
CREATE INDEX idx_property_images_primary ON property_images(property_id, is_primary) WHERE is_primary = TRUE;
```

### Affiliate Tables

#### affiliate_providers
```sql
CREATE TABLE affiliate_providers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    adapter_class VARCHAR(255) NOT NULL, -- PHP class handling this provider
    
    -- Configuration (JSON)
    config JSONB, -- API keys, FTP credentials, file paths, etc.
    
    -- Sync settings
    sync_frequency VARCHAR(50) DEFAULT 'daily', -- daily, hourly, manual
    last_sync_at TIMESTAMP NULL,
    next_sync_at TIMESTAMP NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_affiliate_providers_active ON affiliate_providers(is_active);
```

#### sync_logs
```sql
CREATE TABLE sync_logs (
    id BIGSERIAL PRIMARY KEY,
    affiliate_provider_id BIGINT NOT NULL,
    
    status VARCHAR(50) NOT NULL, -- started, success, failed, partial
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    
    -- Metrics
    properties_fetched INT DEFAULT 0,
    properties_created INT DEFAULT 0,
    properties_updated INT DEFAULT 0,
    properties_deactivated INT DEFAULT 0,
    
    -- Error tracking
    error_message TEXT NULL,
    error_trace TEXT NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (affiliate_provider_id) REFERENCES affiliate_providers(id)
);

CREATE INDEX idx_sync_logs_provider ON sync_logs(affiliate_provider_id);
CREATE INDEX idx_sync_logs_status ON sync_logs(status);
CREATE INDEX idx_sync_logs_started ON sync_logs(started_at DESC);
```

### Tracking Tables

#### click_events
```sql
CREATE TABLE click_events (
    id BIGSERIAL PRIMARY KEY,
    
    -- What was clicked
    property_id BIGINT NOT NULL,
    affiliate_provider_id BIGINT NOT NULL,
    
    -- Who clicked
    user_id BIGINT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    
    -- Search context (what led to this click)
    search_query VARCHAR(500),
    search_location_id BIGINT NULL,
    search_dates_from DATE NULL,
    search_dates_to DATE NULL,
    search_guests SMALLINT NULL,
    
    -- Tracking
    tracked_url TEXT NOT NULL,
    referrer TEXT NULL,
    
    -- Conversion tracking (updated later if webhook received)
    converted BOOLEAN DEFAULT FALSE,
    conversion_value DECIMAL(10, 2) NULL,
    conversion_date TIMESTAMP NULL,
    
    clicked_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (affiliate_provider_id) REFERENCES affiliate_providers(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (search_location_id) REFERENCES locations(id) ON DELETE SET NULL
);

CREATE INDEX idx_click_events_property ON click_events(property_id);
CREATE INDEX idx_click_events_user ON click_events(user_id);
CREATE INDEX idx_click_events_session ON click_events(session_id);
CREATE INDEX idx_click_events_clicked_at ON click_events(clicked_at DESC);
CREATE INDEX idx_click_events_converted ON click_events(converted) WHERE converted = TRUE;
```

#### user_favorites
```sql
CREATE TABLE user_favorites (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    property_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    UNIQUE(user_id, property_id)
);

CREATE INDEX idx_user_favorites_user ON user_favorites(user_id);
```

#### search_logs
```sql
CREATE TABLE search_logs (
    id BIGSERIAL PRIMARY KEY,
    
    user_id BIGINT NULL,
    session_id VARCHAR(255) NOT NULL,
    
    query VARCHAR(500),
    location_id BIGINT NULL,
    dates_from DATE NULL,
    dates_to DATE NULL,
    guests SMALLINT NULL,
    
    -- Filters applied
    filters JSONB,
    
    results_count INT,
    clicked_result_id BIGINT NULL, -- if user clicked a property
    
    searched_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

CREATE INDEX idx_search_logs_session ON search_logs(session_id);
CREATE INDEX idx_search_logs_searched_at ON search_logs(searched_at DESC);
```

### Places Tables

#### places_of_interest
```sql
CREATE TABLE places_of_interest (
    id BIGSERIAL PRIMARY KEY,
    
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE NOT NULL,
    place_category_id BIGINT NOT NULL,
    
    description TEXT,
    
    -- Location
    address VARCHAR(500),
    postcode VARCHAR(20),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    
    -- External data
    google_place_id VARCHAR(255) NULL,
    phone VARCHAR(50),
    website VARCHAR(500),
    
    -- Metadata
    rating DECIMAL(3, 2), -- e.g., 4.5 stars
    price_level SMALLINT, -- 1-4 (£ to ££££)
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (place_category_id) REFERENCES place_categories(id)
);

CREATE INDEX idx_places_coords ON places_of_interest(latitude, longitude);
CREATE INDEX idx_places_category ON places_of_interest(place_category_id);
CREATE INDEX idx_places_active ON places_of_interest(is_active) WHERE is_active = TRUE;
```

#### place_categories
```sql
CREATE TABLE place_categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(100),
    parent_id BIGINT NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    FOREIGN KEY (parent_id) REFERENCES place_categories(id)
);

-- Examples: restaurants, attractions, supermarkets, hospitals, beaches, theme_parks
```

---

## Package Structure

### Creating Laravel Packages

Each package follows Laravel package conventions:

```php
// packages/affiliates/src/AffiliatesServiceProvider.php
namespace Holibob\Affiliates;

use Illuminate\Support\ServiceProvider;

class AffiliatesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/affiliates.php', 'affiliates'
        );
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/affiliates.php' => config_path('affiliates.php'),
        ], 'affiliates-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\SyncPropertiesCommand::class,
            ]);
        }
    }
}
```

### Registering Packages in Main Application

```json
// composer.json (root)
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Holibob\\Affiliates\\": "packages/affiliates/src/",
            "Holibob\\Tracking\\": "packages/tracking/src/",
            "Holibob\\Places\\": "packages/places/src/",
            "Holibob\\Search\\": "packages/search/src/"
        }
    }
}
```

```php
// config/app.php
'providers' => [
    // ...
    Holibob\Affiliates\AffiliatesServiceProvider::class,
    Holibob\Tracking\TrackingServiceProvider::class,
    Holibob\Places\PlacesServiceProvider::class,
    Holibob\Search\SearchServiceProvider::class,
],
```

### Affiliate Provider Interface

```php
// packages/affiliates/src/Contracts/AffiliateProviderInterface.php
namespace Holibob\Affiliates\Contracts;

interface AffiliateProviderInterface
{
    /**
     * Fetch properties from affiliate source
     * 
     * @return \Illuminate\Support\Collection
     */
    public function fetchProperties(): Collection;
    
    /**
     * Transform raw affiliate data to internal schema
     * 
     * @param array $rawData
     * @return array
     */
    public function transform(array $rawData): array;
    
    /**
     * Generate tracked affiliate URL
     * 
     * @param string $externalId
     * @param array $params
     * @return string
     */
    public function generateAffiliateUrl(string $externalId, array $params = []): string;
    
    /**
     * Check if provider is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool;
}
```

### Example Provider Implementation

```php
// packages/affiliates/src/Providers/SykesProvider.php
namespace Holibob\Affiliates\Providers;

use Holibob\Affiliates\Contracts\AffiliateProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SykesProvider implements AffiliateProviderInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function fetchProperties(): Collection
    {
        // Example: Fetch from FTP CSV file
        $ftpPath = $this->config['ftp_path'];
        $csvContent = Storage::disk('ftp')->get($ftpPath);
        
        return $this->parseCsv($csvContent);
    }

    public function transform(array $rawData): array
    {
        return [
            'external_id' => $rawData['property_id'],
            'name' => $rawData['property_name'],
            'description' => $rawData['description'],
            'sleeps' => (int) $rawData['max_guests'],
            'bedrooms' => (int) $rawData['bedrooms'],
            'bathrooms' => (int) $rawData['bathrooms'],
            'price_from' => (float) $rawData['weekly_price'],
            'latitude' => (float) $rawData['lat'],
            'longitude' => (float) $rawData['lng'],
            'postcode' => $rawData['postcode'],
            'affiliate_url' => $this->generateAffiliateUrl($rawData['property_id']),
            // ... more fields
        ];
    }

    public function generateAffiliateUrl(string $externalId, array $params = []): string
    {
        $baseUrl = $this->config['affiliate_base_url'];
        $affiliateId = $this->config['affiliate_id'];
        
        return sprintf(
            '%s?propertyId=%s&affiliateId=%s',
            $baseUrl,
            $externalId,
            $affiliateId
        );
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['ftp_path']) 
            && !empty($this->config['affiliate_id']);
    }

    protected function parseCsv(string $content): Collection
    {
        // CSV parsing logic
        // Return collection of raw property arrays
    }
}
```

---

## Development Phases

### Phase 1: Foundation & Setup (Week 1)

**Objective**: Establish Laravel project, database, Docker environment

**Tasks**:
1. Initialize Laravel 11 project with Breeze + React (Inertia)
2. Configure PostgreSQL connection
3. Set up Redis for cache and queues
4. Create Docker Compose configuration
5. Set up GitHub repository with branch protection
6. Configure ESLint, Prettier, PHPStan
7. Create initial `.env.example` with all required variables
8. Set up basic authentication (register, login, password reset)

**Deliverables**:
- Working Laravel + Inertia + React app
- Docker containers running (nginx, php-fpm, postgres, redis)
- Authentication working
- Code quality tools configured

**Testing**:
- `docker-compose up` successfully starts all services
- Can register/login users
- PHPStan level 5 passes on existing code

---

### Phase 2: Database Schema & Migrations (Week 1-2)

**Objective**: Create complete database structure

**Tasks**:
1. Create migrations for all core tables (users, properties, locations, amenities)
2. Create migrations for affiliate tables (providers, sync_logs)
3. Create migrations for tracking tables (click_events, search_logs)
4. Create migrations for places tables (POIs, categories)
5. Define Eloquent models with relationships
6. Create seeders for:
   - UK locations hierarchy (countries, regions, major towns)
   - Common amenities (WiFi, parking, pet-friendly, hot tub, etc.)
   - Place categories (restaurants, attractions, etc.)
7. Add proper indexes and foreign keys

**Deliverables**:
- Complete migration files in `database/migrations/`
- Eloquent models in `app/Models/`
- Seeders in `database/seeders/`
- Database relationships tested

**Testing**:
- `php artisan migrate:fresh --seed` runs successfully
- All relationships work via Tinker
- Check indexes exist in PostgreSQL

---

### Phase 3: Affiliates Package - Architecture (Week 2)

**Objective**: Build affiliate import framework

**Tasks**:
1. Create `packages/affiliates/` directory structure
2. Define `AffiliateProviderInterface`
3. Create `AffiliatesServiceProvider`
4. Create base `AbstractAffiliateProvider` class
5. Implement configuration system (config/affiliates.php)
6. Create `SyncPropertiesJob` (queued job)
7. Create Artisan command: `affiliate:sync {provider}`
8. Set up event system:
   - `PropertySynced` event
   - `SyncFailed` event
9. Create sync logging mechanism

**Deliverables**:
- Functional affiliate package structure
- Working provider interface/contract
- Command to trigger syncs
- Event system in place

**Testing**:
- Can register package in main app
- Artisan command lists correctly
- Events fire when sync attempted

---

### Phase 4: First Affiliate Provider (Week 2-3)

**Objective**: Implement one complete affiliate provider (Sykes or Hoseasons)

**Tasks**:
1. Research chosen provider's data format (CSV/XML/API)
2. Implement provider class (e.g., `SykesProvider`)
3. Build data fetcher (FTP/HTTP download)
4. Create data transformer (raw → internal schema)
5. Implement property upsert logic (create or update)
6. Handle property images (store URLs or download)
7. Map amenities to internal amenities table
8. Handle location matching (postcode → location_id)
9. Test with sample data file
10. Run full sync and verify data in database

**Deliverables**:
- One working affiliate provider
- Properties imported into database
- Images associated with properties
- Amenities properly linked

**Testing**:
- `php artisan affiliate:sync sykes` imports properties
- Check properties table has data
- Verify relationships (images, amenities, locations)
- Check sync_logs table

---

### Phase 5: Search Package - Meilisearch Integration (Week 3-4)

**Objective**: Set up Meilisearch and index properties

**Tasks**:
1. Create `packages/search/` structure
2. Install Laravel Scout and Meilisearch driver
3. Configure Property model as searchable
4. Define searchable attributes and filterable fields
5. Create `SearchService` for complex queries
6. Implement filter system:
   - Location (with radius search)
   - Dates (future: availability checking)
   - Guests (sleeps >= X)
   - Price range
   - Amenities (multiple select)
7. Create search API endpoints (Inertia-compatible)
8. Index existing properties into Meilisearch
9. Set up automatic indexing on property create/update

**Deliverables**:
- Meilisearch running and connected
- Properties indexed
- Search working via Tinker/API
- Filter system functional

**Testing**:
- Can search by location name
- Filters reduce result set correctly
- Search is fast (<100ms response time)
- Updates to properties reflect in search

---

### Phase 6: Frontend - Search Interface (Week 4-5)

**Objective**: Build React-based search and browse UI

**Tasks**:
1. Design component structure:
   - SearchForm (location, dates, guests)
   - FilterSidebar (amenities, price, property type)
   - PropertyGrid/List toggle
   - PropertyCard component
   - MapView (Google Maps or Mapbox)
2. Create Inertia pages:
   - Homepage with hero search
   - Search results page
   - Property detail page
3. Implement search state management (URL params)
4. Build filter UI with checkboxes, range sliders
5. Add pagination for results
6. Implement property card with image carousel
7. Add responsive design (mobile-first)
8. Integrate map with property pins
9. Add "Save to Favorites" functionality (auth required)

**Deliverables**:
- Functional search page
- Property listing with filters
- Property detail view
- Mobile-responsive design

**Testing**:
- Search returns expected results
- Filters update results in real-time
- Can view property details
- Map displays property locations
- Works on mobile/tablet/desktop

---

### Phase 7: Tracking Package (Week 5-6)

**Objective**: Implement click tracking system

**Tasks**:
1. Create `packages/tracking/` structure
2. Create `TrackingService` class
3. Implement tracked URL generation:
   - Add unique click_id to URL
   - Capture search context (query, dates, guests)
   - Log to click_events table
4. Create redirect controller:
   - `/track/{click_id}` → logs click → redirects to affiliate
5. Capture user data:
   - Session ID
   - User ID (if logged in)
   - IP address
   - User agent
   - Referrer
6. Build analytics dashboard (admin):
   - Total clicks
   - Clicks by property
   - Clicks by provider
   - Conversion rate (if webhook data available)
7. Add tracking to "Book Now" buttons

**Deliverables**:
- Click tracking system working
- Analytics dashboard showing data
- Tracked URLs generated for all properties

**Testing**:
- Clicking "Book Now" logs to click_events
- Redirects to correct affiliate site
- Dashboard shows click counts
- Can filter analytics by date range

---

### Phase 8: Admin Dashboard (Week 6)

**Objective**: Build admin interface for managing system

**Tasks**:
1. Install Laravel Filament (free admin panel)
2. Create resources for:
   - Properties (view, edit, feature/unfeature)
   - Affiliate Providers (add, configure, enable/disable)
   - Sync Logs (view status, errors)
   - Locations (manage hierarchy)
   - Amenities (add/edit)
3. Create dashboard widgets:
   - Total properties
   - Recent syncs status
   - Today's clicks
   - Top properties by clicks
4. Add manual sync trigger button
5. Implement role-based access (admin role)

**Deliverables**:
- Admin panel accessible at `/admin`
- Can manage all core entities
- Dashboard provides overview

**Testing**:
- Admin can manually trigger sync
- Can edit property details
- Can add new affiliate provider

---

### Phase 9: Additional Features (Week 7)

**Objective**: Polish and enhance

**Tasks**:
1. Add property image carousel on detail page
2. Implement "Similar Properties" recommendation
3. Add breadcrumb navigation
4. Create sitemap.xml generation
5. Add structured data (Schema.org) for SEO
6. Implement social sharing (Open Graph tags)
7. Add email notifications for saved searches (future feature)
8. Create user dashboard (favorites, search history)
9. Add "Report Issue" functionality for properties

**Deliverables**:
- Enhanced user experience
- SEO optimizations in place
- User dashboard functional

---

### Phase 10: Testing & Deployment (Week 8)

**Objective**: Comprehensive testing and production deployment

**Tasks**:
1. Write feature tests for all major flows:
   - User registration/login
   - Search and filter
   - View property details
   - Click tracking
   - Admin functions
2. Write unit tests for:
   - Affiliate providers
   - Search service
   - Tracking service
3. Perform manual testing:
   - Cross-browser (Chrome, Firefox, Safari)
   - Mobile devices (iOS, Android)
   - Accessibility audit
4. Load testing:
   - Search performance under load
   - Sync job performance
5. Create deployment Ansible playbook
6. Set up GitHub Actions CI/CD:
   - Run tests on PR
   - Deploy to staging on merge to `develop`
   - Deploy to production on tag
7. Configure production environment:
   - Environment variables
   - SSL certificates
   - Database backups
   - Log aggregation
8. Monitor and fix any issues post-deployment

**Deliverables**:
- Test coverage >70%
- Production deployment successful
- CI/CD pipeline working
- Monitoring in place

---

## API Specifications

### REST API Endpoints (for future mobile apps)

While using Inertia.js for web, maintain these API endpoints:

#### Authentication
```
POST   /api/register
POST   /api/login
POST   /api/logout
POST   /api/forgot-password
POST   /api/reset-password
GET    /api/user
```

#### Search
```
GET    /api/search
  Query params:
    - q (search query)
    - location_id
    - guests
    - bedrooms
    - price_min, price_max
    - amenities[] (array)
    - page

Response:
{
  "data": [
    {
      "id": 1,
      "name": "Luxury Cottage in Cornwall",
      "slug": "luxury-cottage-cornwall",
      "location": {...},
      "sleeps": 6,
      "bedrooms": 3,
      "price_from": 850.00,
      "images": [...],
      "amenities": [...]
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1
  }
}
```

#### Properties
```
GET    /api/properties/{slug}
GET    /api/properties/{id}/similar

Response:
{
  "data": {
    "id": 1,
    "name": "...",
    "description": "...",
    "location": {...},
    "images": [...],
    "amenities": [...],
    "nearby_places": [...],
    "affiliate_url": "https://..."
  }
}
```

#### User
```
GET    /api/user/favorites
POST   /api/user/favorites/{property_id}
DELETE /api/user/favorites/{property_id}
GET    /api/user/search-history
```

#### Tracking
```
POST   /api/track/click
  Body:
    - property_id
    - search_context (query, location, dates, guests)

Response:
{
  "tracked_url": "https://holibob.cf6.co.uk/track/abc123"
}
```

---

## Docker Configuration

### docker-compose.yml

```yaml
version: '3.8'

services:
  nginx:
    image: nginx:alpine
    container_name: holibob_nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    depends_on:
      - php
    networks:
      - holibob

  php:
    build:
      context: .
      dockerfile: ./docker/php/Dockerfile
    container_name: holibob_php
    volumes:
      - ./:/var/www/html
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    depends_on:
      - postgres
      - redis
    networks:
      - holibob

  postgres:
    image: postgres:15-alpine
    container_name: holibob_postgres
    environment:
      POSTGRES_DB: holibob
      POSTGRES_USER: holibob
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - holibob

  redis:
    image: redis:7-alpine
    container_name: holibob_redis
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - holibob

  meilisearch:
    image: getmeili/meilisearch:latest
    container_name: holibob_meilisearch
    environment:
      MEILI_MASTER_KEY: your-master-key-here
      MEILI_ENV: development
    ports:
      - "7700:7700"
    volumes:
      - meilisearch_data:/meili_data
    networks:
      - holibob

  node:
    image: node:20-alpine
    container_name: holibob_node
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    command: npm run dev
    ports:
      - "5173:5173"
    networks:
      - holibob

volumes:
  postgres_data:
  redis_data:
  meilisearch_data:

networks:
  holibob:
    driver: bridge
```

### PHP Dockerfile

```dockerfile
# docker/php/Dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip bcmath opcache

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Supervisor config for Laravel queues
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

CMD ["php-fpm"]
```

### Nginx Configuration

```nginx
# docker/nginx/conf.d/default.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Vite HMR proxy
    location /vite-hmr {
        proxy_pass http://node:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

---

## CI/CD Pipeline

### GitHub Actions Workflow

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_USER: holibob
          POSTGRES_PASSWORD: secret
          POSTGRES_DB: holibob_test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432
      
      redis:
        image: redis:7-alpine
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo, pgsql, redis, zip
          coverage: xdebug

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy .env
        run: cp .env.ci .env

      - name: Generate key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse

      - name: Run tests
        run: vendor/bin/pest --coverage

      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'

      - name: Install NPM dependencies
        run: npm ci

      - name: Build frontend
        run: npm run build

      - name: Run ESLint
        run: npm run lint

  deploy-staging:
    needs: test
    if: github.ref == 'refs/heads/develop'
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to staging
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/holibob-staging
            git pull origin develop
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo supervisorctl restart holibob-worker:*

  deploy-production:
    needs: test
    if: startsWith(github.ref, 'refs/tags/')
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PRODUCTION_HOST }}
          username: ${{ secrets.PRODUCTION_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/holibob
            git fetch --tags
            git checkout ${{ github.ref_name }}
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan down
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo supervisorctl restart holibob-worker:*
            php artisan up
```

---

## Security Considerations

### Authentication & Authorization
- Use Laravel Sanctum for API authentication (future mobile apps)
- Implement CSRF protection (enabled by default in Laravel)
- Rate limiting on login attempts (Laravel built-in)
- Role-based access control for admin panel (use Spatie Permission package)

### Data Protection
- Encrypt sensitive affiliate credentials in database (Laravel's encrypted casts)
- Use HTTPS only in production
- Validate and sanitize all user inputs
- Use prepared statements (Eloquent does this automatically)
- Hash passwords with bcrypt (Laravel default)

### API Security
- Rate limit API endpoints (throttle middleware)
- Validate all request data with Form Requests
- Use signed URLs for sensitive actions
- Implement CORS properly for API endpoints

### Environment Security
```env
# Never commit .env to git
# Use strong random keys
APP_KEY=base64:...

# Database credentials
DB_PASSWORD=strong-random-password

# API keys
MEILISEARCH_KEY=strong-master-key

# Affiliate credentials (encrypted in database)
```

### Logging & Monitoring
- Log all affiliate sync attempts and errors
- Log all click tracking events
- Monitor failed jobs in queue
- Set up alerts for:
  - Failed syncs
  - High error rates
  - Database connection issues
  - Disk space warnings

---

## Environment Variables

### .env.example

```env
# Application
APP_NAME=Holibob
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=holibob
DB_USERNAME=holibob
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Meilisearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=your-master-key-here

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@holibob.co.uk
MAIL_FROM_NAME="${APP_NAME}"

# Affiliate Providers (examples)
SYKES_FTP_HOST=
SYKES_FTP_USERNAME=
SYKES_FTP_PASSWORD=
SYKES_AFFILIATE_ID=

HOSEASONS_API_KEY=
HOSEASONS_AFFILIATE_ID=

# Google Services (optional, for Places API)
GOOGLE_MAPS_API_KEY=
GOOGLE_PLACES_API_KEY=

# Monitoring (optional)
SENTRY_LARAVEL_DSN=
```

---

## Development Commands Reference

### Initial Setup
```bash
# Clone repository
git clone <repo-url> holibob
cd holibob

# Start Docker containers
docker-compose up -d

# Install PHP dependencies
docker-compose exec php composer install

# Install NPM dependencies
docker-compose exec node npm install

# Copy environment file
cp .env.example .env

# Generate application key
docker-compose exec php php artisan key:generate

# Run migrations and seeders
docker-compose exec php php artisan migrate:fresh --seed

# Build frontend assets
docker-compose exec node npm run dev
```

### Daily Development
```bash
# Start services
docker-compose up -d

# Watch frontend changes
docker-compose exec node npm run dev

# Run queue worker
docker-compose exec php php artisan queue:work

# Run scheduler (cron jobs)
docker-compose exec php php artisan schedule:work
```

### Affiliate Syncing
```bash
# Sync specific provider
docker-compose exec php php artisan affiliate:sync sykes

# Sync all active providers
docker-compose exec php php artisan affiliate:sync-all

# View sync logs
docker-compose exec php php artisan affiliate:logs
```

### Search & Indexing
```bash
# Import all properties to Meilisearch
docker-compose exec php php artisan scout:import "App\Models\Property"

# Flush and reimport
docker-compose exec php php artisan scout:flush "App\Models\Property"
docker-compose exec php php artisan scout:import "App\Models\Property"
```

### Testing
```bash
# Run all tests
docker-compose exec php php artisan test

# Run specific test file
docker-compose exec php php artisan test tests/Feature/SearchTest.php

# Run with coverage
docker-compose exec php php artisan test --coverage

# Run PHPStan
docker-compose exec php vendor/bin/phpstan analyse
```

### Database
```bash
# Create new migration
docker-compose exec php php artisan make:migration create_table_name

# Run migrations
docker-compose exec php php artisan migrate

# Rollback last migration
docker-compose exec php php artisan migrate:rollback

# Fresh database
docker-compose exec php php artisan migrate:fresh --seed

# Database backup
docker-compose exec postgres pg_dump -U holibob holibob > backup.sql
```

### Code Generation
```bash
# Create new model with migration and factory
docker-compose exec php php artisan make:model ModelName -mf

# Create controller
docker-compose exec php php artisan make:controller ControllerName

# Create form request
docker-compose exec php php artisan make:request RequestName

# Create job
docker-compose exec php php artisan make:job JobName

# Create event
docker-compose exec php php artisan make:event EventName
```

---

## Success Criteria

### Phase 1-2 (Foundation)
- [ ] Docker containers running successfully
- [ ] Can access app at localhost
- [ ] Database migrations run without errors
- [ ] Seeded data visible in database
- [ ] Authentication works (register, login, logout)

### Phase 3-4 (Affiliates)
- [ ] Affiliate package structure created
- [ ] At least one provider fully implemented
- [ ] Can sync properties from affiliate source
- [ ] Properties appear in database with correct data
- [ ] Images and amenities properly associated

### Phase 5 (Search)
- [ ] Meilisearch connected and running
- [ ] Properties indexed successfully
- [ ] Can search via Tinker: `Property::search('Cornwall')->get()`
- [ ] Filters work correctly
- [ ] Search responds in <100ms

### Phase 6 (Frontend)
- [ ] Homepage displays with search form
- [ ] Search results page shows properties
- [ ] Filters update results
- [ ] Property detail page displays all info
- [ ] Mobile responsive (test on phone)
- [ ] Map shows property locations

### Phase 7 (Tracking)
- [ ] Clicking "Book Now" logs to click_events table
- [ ] Redirects to correct affiliate URL
- [ ] Admin dashboard shows click statistics
- [ ] Can filter clicks by date range

### Phase 8 (Admin)
- [ ] Admin panel accessible at /admin
- [ ] Can view and edit properties
- [ ] Can trigger manual syncs
- [ ] Dashboard widgets display data
- [ ] Role-based access works

### Phase 9-10 (Polish & Deploy)
- [ ] All tests passing (>70% coverage)
- [ ] SEO metadata present on all pages
- [ ] Sitemap.xml generated
- [ ] Production deployment successful
- [ ] SSL certificate active
- [ ] Monitoring and alerts configured

---

## Notes for Claude Agent

When executing this specification:

1. **Work sequentially**: Complete each phase before moving to next
2. **Test frequently**: Run tests after each major change
3. **Commit often**: Small, atomic commits with clear messages
4. **Ask for clarification**: If requirements are ambiguous
5. **Document decisions**: Add comments explaining complex logic
6. **Follow Laravel conventions**: Use framework patterns and best practices
7. **Keep it DRY**: Extract reusable logic into services/traits
8. **Performance matters**: Use eager loading, indexing, caching appropriately
9. **Security first**: Validate inputs, escape outputs, use CSRF protection
10. **Mobile-first CSS**: Start with mobile layout, then enhance for desktop

---

## Glossary

- **Affiliate**: Third-party property provider (Sykes, Hoseasons, etc.)
- **Provider**: Implementation of affiliate integration
- **Adapter**: Class handling specific affiliate's data format
- **Transformer**: Converts affiliate data to internal schema
- **Sync**: Process of fetching and importing properties from affiliate
- **POI**: Place of Interest (restaurant, attraction, etc.)
- **Tracked URL**: Affiliate link with tracking parameters
- **Click Event**: Logged instance of user clicking "Book Now"
- **Conversion**: Successful booking through affiliate link

---

**End of Technical Specification v1.0**
