import { Link, router, usePage } from '@inertiajs/react';
import Avatar from '../components/Avatar';
import FlashMessage from '../components/FlashMessage';
import LoadingIndicator from '../components/LoadingIndicator';
import { IconDashboard, IconFileText, IconGlobe, IconLogout, IconUser, IconUsers } from '../components/Icons';

/**
 * Layout dashboard (area login): sidebar di layar lebar, bilah atas di ponsel.
 */
export default function AppLayout({ children }) {
    const { props, url } = usePage();
    const user = props.auth.user;
    const isAdmin = user.role === 'admin';

    const links = [
        { href: '/dashboard/posts', label: 'Artikel Saya', icon: <IconDashboard />, active: url.startsWith('/dashboard/posts') },
        ...(isAdmin
            ? [
                  { href: '/admin/users', label: 'Pengguna', icon: <IconUsers />, active: url.startsWith('/admin/users') },
                  { href: '/admin/posts', label: 'Semua Artikel', icon: <IconFileText />, active: url.startsWith('/admin/posts') },
              ]
            : []),
        { href: '/profile', label: 'Profil', icon: <IconUser />, active: url.startsWith('/profile') },
        { href: '/', label: 'Lihat Blog', icon: <IconGlobe />, active: false },
    ];

    return (
        <div className="min-h-screen md:flex">
            <LoadingIndicator />

            <aside className="border-b border-slate-200 bg-white md:fixed md:inset-y-0 md:left-0 md:w-60 md:border-b-0 md:border-r">
                <div className="flex h-16 items-center justify-between px-4 md:flex-col md:items-start md:justify-center md:gap-0">
                    <Link href="/" className="flex items-center gap-2 text-lg font-bold text-indigo-600">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">B</span>
                        BlogVerse Pro
                    </Link>
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 md:hidden"
                    >
                        <IconLogout />
                        Keluar
                    </button>
                </div>

                <nav aria-label="Menu dashboard" className="flex gap-1 overflow-x-auto px-3 pb-3 md:flex-col md:pb-0 md:pt-2">
                    {links.map((link) => (
                        <Link
                            key={link.label}
                            href={link.href}
                            className={`inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium ${
                                link.active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'
                            }`}
                        >
                            {link.icon}
                            {link.label}
                        </Link>
                    ))}
                </nav>

                <div className="hidden border-t border-slate-200 p-4 md:absolute md:bottom-0 md:block md:w-full">
                    <div className="flex items-center gap-3">
                        <Avatar user={user} />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-800">{user.name}</p>
                            <p className="truncate text-xs text-slate-500">
                                @{user.username} &middot; {isAdmin ? 'Admin' : 'Penulis'}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        <IconLogout />
                        Keluar
                    </button>
                </div>
            </aside>

            <div className="flex-1 md:ml-60">
                <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                    <FlashMessage />
                    {children}
                </main>
            </div>
        </div>
    );
}
