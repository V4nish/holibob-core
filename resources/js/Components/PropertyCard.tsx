import { Link } from '@inertiajs/react';
import { Property } from '@/types/property';

export default function PropertyCard({ property }: { property: Property }) {
    const primaryImage = property.images?.find(img => img.is_primary) || property.images?.[0];

    const propertyTypeLabels: Record<string, string> = {
        'cottage': 'Cottage',
        'hotel': 'Hotel',
        'caravan': 'Caravan',
        'holiday-park': 'Holiday Park',
        'yurt': 'Glamping',
        'apartment': 'Apartment',
        'villa': 'Villa',
        'lodge': 'Lodge',
    };

    return (
        <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
            {/* Image */}
            <div className="relative h-48 bg-gray-200">
                {primaryImage ? (
                    <img
                        src={primaryImage.url}
                        alt={property.name}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-400">
                        <svg className="w-16 h-16" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                        </svg>
                    </div>
                )}

                {property.featured && (
                    <span className="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-semibold px-2 py-1 rounded">
                        Featured
                    </span>
                )}

                <span className="absolute bottom-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                    {propertyTypeLabels[property.property_type] || property.property_type}
                </span>
            </div>

            {/* Content */}
            <div className="p-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                    {property.name}
                </h3>

                {property.location && (
                    <p className="text-sm text-gray-600 mb-2">
                        📍 {property.location.name}
                    </p>
                )}

                <p className="text-sm text-gray-700 mb-3 line-clamp-2">
                    {property.short_description || property.description}
                </p>

                {/* Property Details */}
                <div className="flex items-center gap-4 text-sm text-gray-600 mb-3">
                    <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                        {property.sleeps}
                    </span>
                    <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        {property.bedrooms} bed
                    </span>
                    <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clipRule="evenodd" />
                        </svg>
                        {property.bathrooms} bath
                    </span>
                </div>

                {/* Price and CTA */}
                <div className="flex items-center justify-between">
                    <div>
                        <span className="text-2xl font-bold text-blue-600">
                            £{property.price_from.toFixed(0)}
                        </span>
                        <span className="text-sm text-gray-600"> /week</span>
                    </div>

                    <a
                        href={property.affiliate_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold"
                    >
                        View Details
                    </a>
                </div>
            </div>
        </div>
    );
}
