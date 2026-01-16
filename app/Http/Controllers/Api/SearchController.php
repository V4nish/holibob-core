<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Holibob\Search\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search properties.
     */
    public function properties(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:500',
            'location' => 'nullable|array',
            'location.*' => 'integer|exists:locations,id',
            'type' => 'nullable|array',
            'type.*' => 'string|in:cottage,hotel,caravan,holiday-park,yurt,apartment,villa,lodge',
            'sleeps' => 'nullable|integer|min:1|max:50',
            'bedrooms' => 'nullable|integer|min:0|max:20',
            'bathrooms' => 'nullable|integer|min:0|max:10',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'sort' => 'nullable|string|in:,relevance,price_asc,price_desc,sleeps_desc,featured',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'facets' => 'nullable|boolean',
        ]);

        $builder = $this->searchService->searchFromRequest($request);
        $results = $builder->get();

        // Log the search
        $this->searchService->logSearch(
            query: $request->input('q', ''),
            resultsCount: $results->total(),
            userId: auth()->id(),
            ipAddress: $request->ip(),
            filters: $builder->getFilters()
        );

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'links' => [
                'first' => $results->url(1),
                'last' => $results->url($results->lastPage()),
                'prev' => $results->previousPageUrl(),
                'next' => $results->nextPageUrl(),
            ],
            'facets' => $results->facets ?? null,
        ]);
    }

    /**
     * Get search suggestions/autocomplete.
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        // Simple implementation: search property names
        $suggestions = $this->searchService->properties()
            ->query($query)
            ->activeOnly()
            ->paginate($limit, 1)
            ->items();

        return response()->json([
            'suggestions' => collect($suggestions)->map(fn ($property) => [
                'id' => $property->id,
                'name' => $property->name,
                'slug' => $property->slug,
                'location' => $property->location->name ?? null,
            ]),
        ]);
    }

    /**
     * Get popular searches.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 50);
        $days = min((int) $request->input('days', 30), 365);

        $queries = $this->searchService->popularQueries($limit, $days);

        return response()->json([
            'popular_queries' => $queries,
        ]);
    }

    /**
     * Get search statistics (admin only).
     */
    public function statistics(Request $request): JsonResponse
    {
        // Add authorization check here
        // $this->authorize('viewSearchStatistics');

        $days = min((int) $request->input('days', 30), 365);

        $stats = $this->searchService->getStatistics($days);
        $popular = $this->searchService->popularQueries(10, $days);
        $empty = $this->searchService->emptySearches(10, $days);

        return response()->json([
            'statistics' => $stats,
            'popular_queries' => $popular,
            'zero_result_queries' => $empty,
        ]);
    }
}
