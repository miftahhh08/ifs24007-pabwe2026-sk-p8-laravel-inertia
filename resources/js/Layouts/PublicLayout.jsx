import { Link, router, usePage } from '@inertiajs/react';
import Avatar from '../components/Avatar';
import FlashMessage from '../components/FlashMessage';
import LoadingIndicator from '../components/LoadingIndicator';
import { IconDashboard, IconLogin, IconLogout, IconUserPlus } from '../components/Icons';

/**
 * Layout halaman publik: Sticky Top Navbar + konten lebar + Footer editorial.
 * Tanpa sidebar.
 */
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isAdmin = user?.role === 'admin';

    return (
        <div className="flex min-h-screen flex-col">
            <LoadingIndicator />

            <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
                <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6" aria-label="Navigasi utama">
                    <Link href="/" className="flex items-center gap-2 text-lg font-bold text-indigo-600">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">B</span>
                        BlogVerse Pro
                    </Link>

                    <div className="flex items-center gap-2">
                        {user ? (
                            <>
                                <Link
                                    href={isAdmin ? '/admin/users' : '/dashboard/posts'}
                                    className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                                >
                                    <IconDashboard />
                                    <span className="hidden sm:inline">{isAdmin ? 'Panel Admin' : 'Dashboard Penulis'}</span>
                                </Link>
                                <Link href="/profile" title="Profil saya" aria-label="Profil saya">
                                    <Avatar user={user} />
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => router.post('/logout')}
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                                >
                                    <IconLogout />
                                    <span className="hidden sm:inline">Keluar</span>
                                </button>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                                >
                                    <IconLogin />
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    <IconUserPlus />
                                    Daftar
                                </Link>
                            </>
                        )}
                    </div>
                </nav>
            </header>

            <main className="flex-1">
                <FlashMessage />
                {children}
            </main>

            <footer className="mt-16 border-t border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6">
                    <div>
                        <p className="font-bold text-indigo-600">BlogVerse Pro</p>
                        <p className="text-sm text-slate-500">Platform publikasi artikel untuk para penulis.</p>
                    </div>
                    <nav aria-label="Navigasi footer" className="flex items-center gap-5 text-sm font-medium text-slate-600">
                        <Link href="/" className="hover:text-indigo-600">Beranda</Link>
                        <Link href="/login" className="hover:text-indigo-600">Masuk</Link>
                        <Link href="/register" className="hover:text-indigo-600">Daftar</Link>
                    </nav>
                </div>
            </footer>
        </div>
    );
}
