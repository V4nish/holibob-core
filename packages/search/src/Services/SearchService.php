<?php

namespace Holibob\Search\Services;

use App\Models\SearchLog;
use Holibob\Search\Builders\PropertySearchBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Create a new property search builder.
     */
    public function properties(): PropertySearchBuilder
    {
        return new PropertySearchBuilder();
    }

    /**
     * Search properties from HTTP request parameters.
     */
    public function searchFromRequest(Request $request): PropertySearchBuilder
    {
        $builder = $this->properties();

        // Query
        if ($request->filled('q')) {
            $builder->query($request->input('q'));
        }

        // Location filter
        if ($request->filled('location')) {
            $locationIds = is_array($request->input('location'))
                ? $request->input('location')
                : [$request->input('location')];

            $builder->location($locationIds);
        }

        // Property type filter
        if ($request->filled('type')) {
            $types = is_array($request->input('type'))
                ? $request->input('type')
                : [$request->input('type')];

            $builder->propertyType($types);
        }

        // Capacity filters
        if ($request->filled('sleeps')) {
            $builder->sleeps((int) $request->input('sleeps'));
        }

        if ($request->filled('bedrooms')) {
            $builder->bedrooms((int) $request->input('bedrooms'));
        }

        if ($request->filled('bathrooms')) {
            $builder->bathrooms((int) $request->input('bathrooms'));
        }

        // Price range filter
        if ($request->filled('price_min') || $request->filled('price_max')) {
            $builder->priceRange(
                $request->filled('price_min') ? (float) $request->input('price_min') : null,
                $request->filled('price_max') ? (float) $request->input('price_max') : null
            );
        }

        // Active only (default)
        if ($request->input('include_inactive') !== 'true') {
            $builder->activeOnly();
        }

        // Featured filter
        if ($request->input('featured') === 'true') {
            $builder->featuredOnly();
        }

        // Sorting
        $sortBy = $request->input('sort', 'relevance');

        match ($sortBy) {
            'price_asc' => $builder->cheapest(),
            'price_desc' => $builder->mostExpensive(),
            'sleeps_desc' => $builder->largestFirst(),
            'featured' => $builder->featuredFirst(),
            default => null, // relevance (default Meilisearch scoring)
        };

        // Pagination
        $perPage = min((int) $request->input('per_page', 20), 100);
        $page = (int) $request->input('page', 1);
        $builder->paginate($perPage, $page);

        // Facets
        if ($request->input('facets') === 'true') {
            $builder->withFacets([
                'property_type',
                'sleeps',
                'bedrooms',
                'bathrooms',
            ]);
        }

        return $builder;
    }

    /**
     * Log a search query for analytics.
     */
    public function logSearch(
        string $query,
        int $resultsCount,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?array $filters = null
    ): SearchLog {
        return SearchLog::create([
            'user_id' => $userId,
            'query' => $query,
            'results_count' => $resultsCount,
            'ip_address' => $ipAddress,
            'filters' => $filters,
        ]);
    }

    /**
     * Get popular search queries.
     */
    public function popularQueries(int $limit = 10, int $days = 30): array
    {
        return SearchLog::whereNotNull('query')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'query')
            ->toArray();
    }

    /**
     * Get search queries with no results.
     */
    public function emptySearches(int $limit = 10, int $days = 30): array
    {
        return SearchLog::whereNotNull('query')
            ->where('results_count', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'query')
            ->toArray();
    }

    /**
     * Get search statistics.
     */
    public function getStatistics(int $days = 30): array
    {
        $logs = SearchLog::where('created_at', '>=', now()->subDays($days));

        return [
            'total_searches' => $logs->count(),
            'unique_queries' => $logs->distinct('query')->count('query'),
            'avg_results' => round($logs->avg('results_count'), 2),
            'zero_result_rate' => round(
                ($logs->where('results_count', 0)->count() / max($logs->count(), 1)) * 100,
                2
            ),
        ];
    }

    /**
     * Reindex all properties.
     */
    public function reindexProperties(): int
    {
        try {
            Log::info('Starting property reindex');

            $count = \App\Models\Property::query()
                ->where('is_active', true)
                ->searchable();

            Log::info('Property reindex completed', ['count' => $count]);

            return $count;

        } catch (\Exception $e) {
            Log::error('Property reindex failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clear search index.
     */
    public function clearIndex(): void
    {
        try {
            Log::info('Clearing property search index');

            \App\Models\Property::removeAllFromSearch();

            Log::info('Property search index cleared');

        } catch (\Exception $e) {
            Log::error('Failed to clear search index', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
