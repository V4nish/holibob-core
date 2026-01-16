<?php

namespace Holibob\Search\Builders;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Builder;

class PropertySearchBuilder
{
    protected string $query = '';

    protected array $filters = [];

    protected array $sort = [];

    protected int $perPage = 20;

    protected int $page = 1;

    protected array $facets = [];

    /**
     * Set the search query.
     */
    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Filter by location.
     */
    public function location(int|array $locationId): self
    {
        if (is_array($locationId)) {
            $this->filters[] = 'location_id IN [' . implode(',', $locationId) . ']';
        } else {
            $this->filters[] = "location_id = {$locationId}";
        }

        return $this;
    }

    /**
     * Filter by property type.
     */
    public function propertyType(string|array $type): self
    {
        if (is_array($type)) {
            $types = array_map(fn ($t) => "'{$t}'", $type);
            $this->filters[] = 'property_type IN [' . implode(',', $types) . ']';
        } else {
            $this->filters[] = "property_type = '{$type}'";
        }

        return $this;
    }

    /**
     * Filter by minimum sleeps.
     */
    public function sleeps(int $min, ?int $max = null): self
    {
        $this->filters[] = "sleeps >= {$min}";

        if ($max !== null) {
            $this->filters[] = "sleeps <= {$max}";
        }

        return $this;
    }

    /**
     * Filter by bedrooms.
     */
    public function bedrooms(int $min, ?int $max = null): self
    {
        $this->filters[] = "bedrooms >= {$min}";

        if ($max !== null) {
            $this->filters[] = "bedrooms <= {$max}";
        }

        return $this;
    }

    /**
     * Filter by bathrooms.
     */
    public function bathrooms(int $min, ?int $max = null): self
    {
        $this->filters[] = "bathrooms >= {$min}";

        if ($max !== null) {
            $this->filters[] = "bathrooms <= {$max}";
        }

        return $this;
    }

    /**
     * Filter by price range.
     */
    public function priceRange(?float $min = null, ?float $max = null): self
    {
        if ($min !== null) {
            $this->filters[] = "price_from >= {$min}";
        }

        if ($max !== null) {
            $this->filters[] = "price_from <= {$max}";
        }

        return $this;
    }

    /**
     * Filter by affiliate provider.
     */
    public function provider(int|array $providerId): self
    {
        if (is_array($providerId)) {
            $this->filters[] = 'affiliate_provider_id IN [' . implode(',', $providerId) . ']';
        } else {
            $this->filters[] = "affiliate_provider_id = {$providerId}";
        }

        return $this;
    }

    /**
     * Filter to active properties only.
     */
    public function activeOnly(): self
    {
        $this->filters[] = 'is_active = true';

        return $this;
    }

    /**
     * Filter to featured properties only.
     */
    public function featuredOnly(): self
    {
        $this->filters[] = 'featured = true';

        return $this;
    }

    /**
     * Add a custom filter.
     */
    public function where(string $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Sort by field.
     */
    public function sortBy(string $field, string $direction = 'asc'): self
    {
        $this->sort[] = "{$field}:{$direction}";

        return $this;
    }

    /**
     * Sort by price ascending.
     */
    public function cheapest(): self
    {
        return $this->sortBy('price_from', 'asc');
    }

    /**
     * Sort by price descending.
     */
    public function mostExpensive(): self
    {
        return $this->sortBy('price_from', 'desc');
    }

    /**
     * Sort by capacity (sleeps).
     */
    public function largestFirst(): self
    {
        return $this->sortBy('sleeps', 'desc');
    }

    /**
     * Sort by featured first.
     */
    public function featuredFirst(): self
    {
        return $this->sortBy('featured', 'desc');
    }

    /**
     * Set pagination parameters.
     */
    public function paginate(int $perPage = 20, int $page = 1): self
    {
        $this->perPage = $perPage;
        $this->page = $page;

        return $this;
    }

    /**
     * Enable faceted search for specific attributes.
     */
    public function withFacets(array $facets): self
    {
        $this->facets = $facets;

        return $this;
    }

    /**
     * Execute the search and return paginated results.
     */
    public function get(): LengthAwarePaginator
    {
        try {
            $builder = $this->buildScoutQuery();

            // Apply Meilisearch filters and sorting via options callback
            $builder->options($this->buildMeilisearchOptions());

            // Execute search with pagination
            $results = $builder->paginate($this->perPage, 'page', $this->page);

            // Add facets to results if requested
            if (! empty($this->facets)) {
                $results->facets = $this->getFacets();
            }

            Log::info('Property search executed', [
                'query' => $this->query,
                'filters' => $this->filters,
                'total' => $results->total(),
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Property search failed', [
                'query' => $this->query,
                'error' => $e->getMessage(),
            ]);

            // Return empty paginator on error
            return new LengthAwarePaginator(
                [],
                0,
                $this->perPage,
                $this->page
            );
        }
    }

    /**
     * Execute the search and return raw results (no pagination).
     */
    public function raw(): EloquentCollection
    {
        try {
            $builder = $this->buildScoutQuery();

            // Apply Meilisearch filters and sorting via options callback
            $builder->options($this->buildMeilisearchOptions());

            return $builder->get();

        } catch (\Exception $e) {
            Log::error('Property search failed', [
                'query' => $this->query,
                'error' => $e->getMessage(),
            ]);

            return new EloquentCollection();
        }
    }

    /**
     * Get the total count of results without fetching them.
     */
    public function count(): int
    {
        try {
            $builder = $this->buildScoutQuery();

            // Apply Meilisearch filters via options callback
            $builder->options($this->buildMeilisearchOptions());

            return $builder->count();

        } catch (\Exception $e) {
            Log::error('Property count failed', [
                'query' => $this->query,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Build Meilisearch options array for filters and sorting.
     */
    protected function buildMeilisearchOptions(): array
    {
        $options = [];

        // Apply filters
        if (! empty($this->filters)) {
            $options['filter'] = implode(' AND ', $this->filters);
        }

        // Apply sorting
        if (! empty($this->sort)) {
            $options['sort'] = $this->sort;
        }

        return $options;
    }

    /**
     * Build the Scout query builder.
     */
    protected function buildScoutQuery(): Builder
    {
        return Property::search($this->query);
    }

    /**
     * Get facet data for the current search.
     */
    protected function getFacets(): array
    {
        // This would require Meilisearch faceting API
        // Implementation depends on actual facet requirements
        return [];
    }

    /**
     * Get the current filters as an array.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get the current query string.
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
