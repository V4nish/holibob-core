<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    /**
     * Display the homepage with featured properties.
     */
    public function index(): Response
    {
        $featuredProperties = Property::with(['location', 'images'])
            ->where('is_active', true)
            ->where('featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $recentProperties = Property::with(['location', 'images'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return Inertia::render('Home', [
            'featured' => $featuredProperties,
            'recent' => $recentProperties,
        ]);
    }

    /**
     * Display the search page.
     */
    public function search(Request $request): Response
    {
        // Extract filters from request
        $filters = $request->only([
            'q',
            'location',
            'type',
            'sleeps',
            'bedrooms',
            'bathrooms',
            'price_min',
            'price_max',
            'sort',
            'per_page',
            'page',
        ]);

        return Inertia::render('Properties/Index', [
            'initialFilters' => $filters,
        ]);
    }
}
