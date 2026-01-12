import { useState, FormEvent } from 'react';
import { SearchFilters as SearchFiltersType } from '@/types/property';

interface SearchFiltersProps {
    initialFilters?: SearchFiltersType;
    onSearch: (filters: SearchFiltersType) => void;
}

export default function SearchFilters({ initialFilters = {}, onSearch }: SearchFiltersProps) {
    const [filters, setFilters] = useState<SearchFiltersType>(initialFilters);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        onSearch(filters);
    };

    const handleReset = () => {
        const resetFilters: SearchFiltersType = {};
        setFilters(resetFilters);
        onSearch(resetFilters);
    };

    const propertyTypes = [
        { value: 'cottage', label: 'Cottage' },
        { value: 'hotel', label: 'Hotel' },
        { value: 'caravan', label: 'Caravan' },
        { value: 'holiday-park', label: 'Holiday Park' },
        { value: 'yurt', label: 'Glamping' },
        { value: 'apartment', label: 'Apartment' },
        { value: 'villa', label: 'Villa' },
        { value: 'lodge', label: 'Lodge' },
    ];

    return (
        <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 className="text-xl font-semibold mb-4">Search Filters</h2>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {/* Search Query */}
                <div className="lg:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Search
                    </label>
                    <input
                        type="text"
                        value={filters.q || ''}
                        onChange={(e) => setFilters({ ...filters, q: e.target.value })}
                        placeholder="e.g., Cornwall cottage, pet-friendly..."
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                {/* Property Type */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Property Type
                    </label>
                    <select
                        value={filters.type?.[0] || ''}
                        onChange={(e) => setFilters({ ...filters, type: e.target.value ? [e.target.value] : undefined })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Types</option>
                        {propertyTypes.map(type => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                </div>

                {/* Sleeps */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Sleeps
                    </label>
                    <select
                        value={filters.sleeps || ''}
                        onChange={(e) => setFilters({ ...filters, sleeps: e.target.value ? Number(e.target.value) : undefined })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Any</option>
                        <option value="2">2+</option>
                        <option value="4">4+</option>
                        <option value="6">6+</option>
                        <option value="8">8+</option>
                        <option value="10">10+</option>
                    </select>
                </div>

                {/* Bedrooms */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Bedrooms
                    </label>
                    <select
                        value={filters.bedrooms || ''}
                        onChange={(e) => setFilters({ ...filters, bedrooms: e.target.value ? Number(e.target.value) : undefined })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Any</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                    </select>
                </div>

                {/* Price Min */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Min Price (£/week)
                    </label>
                    <input
                        type="number"
                        value={filters.price_min || ''}
                        onChange={(e) => setFilters({ ...filters, price_min: e.target.value ? Number(e.target.value) : undefined })}
                        placeholder="e.g., 500"
                        min="0"
                        step="50"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                {/* Price Max */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Max Price (£/week)
                    </label>
                    <input
                        type="number"
                        value={filters.price_max || ''}
                        onChange={(e) => setFilters({ ...filters, price_max: e.target.value ? Number(e.target.value) : undefined })}
                        placeholder="e.g., 2000"
                        min="0"
                        step="50"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                {/* Sort */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Sort By
                    </label>
                    <select
                        value={filters.sort || 'relevance'}
                        onChange={(e) => setFilters({ ...filters, sort: e.target.value as any })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="relevance">Most Relevant</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="sleeps_desc">Largest First</option>
                        <option value="featured">Featured First</option>
                    </select>
                </div>
            </div>

            {/* Buttons */}
            <div className="flex gap-3 mt-6">
                <button
                    type="submit"
                    className="flex-1 bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors font-semibold"
                >
                    Search Properties
                </button>
                <button
                    type="button"
                    onClick={handleReset}
                    className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors font-semibold"
                >
                    Reset
                </button>
            </div>
        </form>
    );
}
