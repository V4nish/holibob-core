import { Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

export default function PublicLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navigation */}
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <span className="text-2xl font-bold text-blue-600">
                                        Holibob
                                    </span>
                                </Link>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Link
                                href="/properties"
                                className="text-gray-700 hover:text-blue-600 font-medium"
                            >
                                Search Properties
                            </Link>
                            <Link
                                href="/login"
                                className="text-gray-700 hover:text-blue-600 font-medium"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Register
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Page Header */}
            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {/* Page Content */}
            <main>{children}</main>
        </div>
    );
}
