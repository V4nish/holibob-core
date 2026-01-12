import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PublicLayout from '@/Layouts/PublicLayout';
import PropertyCard from '@/Components/PropertyCard';
import SearchFilters from '@/Components/SearchFilters';
import { Property, SearchFilters as SearchFiltersType, SearchResponse } from '@/types/property';
import { PageProps } from '@/types';
import axios from 'axios';

interface PropertiesIndexProps extends PageProps {
    initialFilters?: SearchFiltersType;
}

export default function PropertiesIndex({ auth, initialFilters = {} }: PropertiesIndexProps) {
    const [searchResponse, setSearchResponse] = useState<SearchResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const Layout = auth.user ? AuthenticatedLayout : PublicLayout;

    const performSearch = async (filters: SearchFiltersType) => {
        setLoading(true);
        setError(null);

        try {
            // Build query string
            const params = new URLSearchParams();

            if (filters.q) params.append('q', filters.q);
            if (filters.type) filters.type.forEach(t => params.append('type[]', t));
            if (filters.sleeps) params.append('sleeps', filters.sleeps.toString());
            if (filters.bedrooms) params.append('bedrooms', filters.bedrooms.toString());
            if (filters.bathrooms) params.append('bathrooms', filters.bathrooms.toString());
            if (filters.price_min) params.append('price_min', filters.price_min.toString());
            if (filters.price_max) params.append('price_max', filters.price_max.toString());
            if (filters.sort) params.append('sort', filters.sort);
            params.append('per_page', (filters.per_page || 20).toString());
            params.append('page', (filters.page || 1).toString());

            const response = await axios.get<SearchResponse>(`/api/search/properties?${params.toString()}`);
            setSearchResponse(response.data);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to search properties. Please try again.');
            console.error('Search error:', err);
        } finally {
            setLoading(false);
        }
    };

    // Perform initial search on mount
    useEffect(() => {
        performSearch(initialFilters);
    }, []);

    const handlePageChange = (page: number) => {
        performSearch({ ...initialFilters, page });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <Layout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Search Holiday Properties
                </h2>
            }
        >
            <Head title="Search Properties" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Search Filters */}
                    <SearchFilters
                        initialFilters={initialFilters}
                        onSearch={performSearch}
                    />

                    {/* Loading State */}
                    {loading && (
                        <div className="flex justify-center items-center py-12">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                    )}

                    {/* Error State */}
                    {error && (
                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                            {error}
                        </div>
                    )}

                    {/* Results */}
                    {!loading && searchResponse && (
                        <>
                            {/* Results Header */}
                            <div className="mb-6 flex items-center justify-between">
                                <p className="text-gray-700">
                                    Showing <span className="font-semibold">{searchResponse.meta.from}</span> to{' '}
                                    <span className="font-semibold">{searchResponse.meta.to}</span> of{' '}
                                    <span className="font-semibold">{searchResponse.meta.total}</span> properties
                                </p>
                            </div>

                            {/* Property Grid */}
                            {searchResponse.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                    {searchResponse.data.map((property) => (
                                        <PropertyCard key={property.id} property={property} />
                                    ))}
                                </div>
                            ) : (
                                <div className="bg-white rounded-lg shadow-md p-12 text-center">
                                    <svg
                                        className="mx-auto h-12 w-12 text-gray-400 mb-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        No properties found
                                    </h3>
                                    <p className="text-gray-600">
                                        Try adjusting your filters or search terms
                                    </p>
                                </div>
                            )}

                            {/* Pagination */}
                            {searchResponse.meta.last_page > 1 && (
                                <div className="flex justify-center items-center gap-2">
                                    <button
                                        onClick={() => handlePageChange(searchResponse.meta.current_page - 1)}
                                        disabled={!searchResponse.links.prev}
                                        className="px-4 py-2 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                    >
                                        Previous
                                    </button>

                                    <span className="px-4 py-2 text-gray-700">
                                        Page {searchResponse.meta.current_page} of {searchResponse.meta.last_page}
                                    </span>

                                    <button
                                        onClick={() => handlePageChange(searchResponse.meta.current_page + 1)}
                                        disabled={!searchResponse.links.next}
                                        className="px-4 py-2 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                    >
                                        Next
                                    </button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </Layout>
    );
}
