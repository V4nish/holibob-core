import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Property } from '@/types/property';
import PropertyCard from '@/Components/PropertyCard';

interface HomeProps extends PageProps {
    featured: Property[];
    recent: Property[];
}

export default function Home({ auth, featured, recent }: HomeProps) {
    return (
        <>
            <Head title="Holibob - Find Your Perfect UK Holiday" />

            <div className="min-h-screen bg-gray-50">
                {/* Header/Navigation */}
                <header className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                        <div className="flex items-center">
                            <h1 className="text-2xl font-bold text-blue-600">Holibob</h1>
                            <p className="ml-4 text-gray-600 hidden sm:block">Find Your Perfect UK Holiday</p>
                        </div>

                        <nav className="flex items-center gap-4">
                            <Link
                                href="/properties"
                                className="text-gray-700 hover:text-blue-600 font-medium"
                            >
                                Search Properties
                            </Link>

                            {auth.user ? (
                                <>
                                    <Link
                                        href="/dashboard"
                                        className="text-gray-700 hover:text-blue-600 font-medium"
                                    >
                                        Dashboard
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href="/login"
                                        className="text-gray-700 hover:text-blue-600 font-medium"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href="/register"
                                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <h2 className="text-5xl font-bold mb-6">
                            Discover Your Perfect UK Holiday
                        </h2>
                        <p className="text-xl mb-8 text-blue-100">
                            Search thousands of cottages, hotels, and unique stays across the UK
                        </p>
                        <Link
                            href="/properties"
                            className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg hover:bg-blue-50 transition-colors text-lg font-semibold"
                        >
                            Start Searching
                        </Link>
                    </div>
                </section>

                {/* Quick Search Categories */}
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {[
                            { type: 'cottage', label: 'Cottages', emoji: '🏡' },
                            { type: 'hotel', label: 'Hotels', emoji: '🏨' },
                            { type: 'caravan', label: 'Caravans', emoji: '🚐' },
                            { type: 'yurt', label: 'Glamping', emoji: '⛺' },
                        ].map((category) => (
                            <Link
                                key={category.type}
                                href={`/properties?type[]=${category.type}`}
                                className="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-xl transition-shadow"
                            >
                                <div className="text-4xl mb-2">{category.emoji}</div>
                                <div className="font-semibold text-gray-900">{category.label}</div>
                            </Link>
                        ))}
                    </div>
                </section>

                {/* Featured Properties */}
                {featured.length > 0 && (
                    <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div className="flex items-center justify-between mb-8">
                            <h3 className="text-3xl font-bold text-gray-900">Featured Properties</h3>
                            <Link
                                href="/properties?featured=true"
                                className="text-blue-600 hover:text-blue-700 font-semibold"
                            >
                                View All →
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {featured.map((property) => (
                                <PropertyCard key={property.id} property={property} />
                            ))}
                        </div>
                    </section>
                )}

                {/* Recent Properties */}
                {recent.length > 0 && (
                    <section className="bg-white py-12">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center justify-between mb-8">
                                <h3 className="text-3xl font-bold text-gray-900">Recently Added</h3>
                                <Link
                                    href="/properties"
                                    className="text-blue-600 hover:text-blue-700 font-semibold"
                                >
                                    View All →
                                </Link>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                {recent.map((property) => (
                                    <PropertyCard key={property.id} property={property} />
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* CTA Section */}
                <section className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <h3 className="text-3xl font-bold mb-4">
                            Ready to Find Your Perfect Holiday?
                        </h3>
                        <p className="text-xl mb-8 text-blue-100">
                            Browse our extensive collection of UK holiday properties
                        </p>
                        <Link
                            href="/properties"
                            className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg hover:bg-blue-50 transition-colors text-lg font-semibold"
                        >
                            Search Now
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-gray-300 py-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <p className="mb-2">© 2026 Holibob. All rights reserved.</p>
                        <p className="text-sm text-gray-500">
                            Find the perfect holiday cottage, hotel, or unique stay across the UK
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
