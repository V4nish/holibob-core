export interface Property {
    id: number;
    name: string;
    slug: string;
    description: string;
    short_description: string;
    property_type: string;
    sleeps: number;
    bedrooms: number;
    bathrooms: number;
    price_from: number;
    price_currency: string;
    affiliate_url: string;
    is_active: boolean;
    featured: boolean;
    location?: {
        id: number;
        name: string;
        type: string;
    };
    images?: PropertyImage[];
    amenities?: Amenity[];
}

export interface PropertyImage {
    id: number;
    url: string;
    display_order: number;
    is_primary: boolean;
}

export interface Amenity {
    id: number;
    name: string;
    slug: string;
    icon: string;
    category: string;
}

export interface Location {
    id: number;
    name: string;
    slug: string;
    type: string;
    postcode?: string;
}

export interface SearchFilters {
    q?: string;
    location?: number[];
    type?: string[];
    sleeps?: number;
    bedrooms?: number;
    bathrooms?: number;
    price_min?: number;
    price_max?: number;
    sort?: 'relevance' | 'price_asc' | 'price_desc' | 'sleeps_desc' | 'featured';
    per_page?: number;
    page?: number;
}

export interface SearchResponse {
    data: Property[];
    meta: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
    };
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    facets?: Record<string, Record<string, number>>;
}
