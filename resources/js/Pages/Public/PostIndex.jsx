import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { IconLogin, IconSearch, IconUserPlus } from '../../components/Icons';

function cleanParams(params) {
    return Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null));
}

export default function PostIndex({ posts, categories, filters }) {
    const { auth } = usePage().props;
    const [q, setQ] = useState(filters.q ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    const applyFilter = (next) => {
        router.get('/articles', cleanParams(next), { preserveState: true, replace: true });
    };

    const submitSearch = (e) => {
        e.preventDefault();
        applyFilter({ q, category });
    };

    const pickCategory = (value) => {
        setCategory(value);
        applyFilter({ q, category: value });
    };

    return (
        <PublicLayout>
            <Head title="Beranda" />

            {/* Hero */}
            <section className="bg-gradient-to-br from-indigo-600 to-violet-600 text-white">
                <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
                    <h1 className="max-w-2xl text-3xl font-extrabold sm:text-5xl">
                        Tulis, bagikan, dan temukan cerita yang menginspirasi.
                    </h1>
                    <p className="mt-4 max-w-xl text-indigo-100">
                        BlogVerse Pro adalah ruang bagi para penulis untuk menerbitkan artikel seputar teknologi,
                        pendidikan, bisnis, dan banyak lagi.
                    </p>
                    {!auth.user && (
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                            >
                                <IconLogin />
                                Masuk
                            </Link>
                            <Link
                                href="/register"
                                className="inline-flex items-center gap-2 rounded-lg border border-white/60 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10"
                            >
                                <IconUserPlus />
                                Daftar Penulis
                            </Link>
                        </div>
                    )}
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                {/* Pencarian + filter kategori */}
                <form onSubmit={submitSearch} className="flex flex-col gap-3 sm:flex-row" role="search">
                    <div className="relative flex-1">
                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <IconSearch />
                        </span>
                        <input
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari artikel berdasarkan judul atau isi..."
                            aria-label="Cari artikel"
                            className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        />
                    </div>
                    <button
                        type="submit"
                        className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Cari
                    </button>
                </form>

                <div className="mt-4 flex flex-wrap gap-2" aria-label="Filter kategori">
                    <CategoryChip active={category === ''} onClick={() => pickCategory('')}>
                        Semua
                    </CategoryChip>
                    {categories.map((c) => (
                        <CategoryChip key={c} active={category === c} onClick={() => pickCategory(c)}>
                            {c}
                        </CategoryChip>
                    ))}
                </div>

                {/* Daftar artikel */}
                <h2 className="mt-10 text-xl font-bold text-slate-900">Artikel Terbaru</h2>

                {posts.data.length === 0 ? (
                    <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                        Belum ada artikel yang cocok dengan pencarianmu.
                    </div>
                ) : (
                    <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                )}

                {/* Paginasi */}
                {posts.last_page > 1 && (
                    <nav className="mt-10 flex items-center justify-between" aria-label="Paginasi">
                        <PageLink href={posts.prev_page_url}>&larr; Sebelumnya</PageLink>
                        <span className="text-sm text-slate-500">
                            Halaman {posts.current_page} dari {posts.last_page}
                        </span>
                        <PageLink href={posts.next_page_url}>Berikutnya &rarr;</PageLink>
                    </nav>
                )}
            </div>
        </PublicLayout>
    );
}

function CategoryChip({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-full border px-3 py-1 text-sm font-medium ${
                active
                    ? 'border-indigo-600 bg-indigo-600 text-white'
                    : 'border-slate-300 bg-white text-slate-600 hover:border-indigo-400 hover:text-indigo-600'
            }`}
        >
            {children}
        </button>
    );
}

function PostCard({ post }) {
    return (
        <Link
            href={`/articles/${post.slug}`}
            className="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
        >
            {post.cover_url ? (
                <img src={post.cover_url} alt="" className="h-40 w-full object-cover" loading="lazy" />
            ) : (
                <div className="flex h-40 items-center justify-center bg-gradient-to-br from-indigo-100 to-violet-100 text-4xl font-black text-indigo-800">
                    {post.title.charAt(0).toUpperCase()}
                </div>
            )}
            <div className="flex flex-1 flex-col p-5">
                <span className="w-fit rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                    {post.category}
                </span>
                <h3 className="mt-3 text-lg font-bold text-slate-900 group-hover:text-indigo-600">{post.title}</h3>
                {post.excerpt && <p className="mt-2 line-clamp-3 text-sm text-slate-600">{post.excerpt}</p>}
                <p className="mt-auto pt-4 text-xs text-slate-500">
                    {post.author} &middot; {post.published_at}
                </p>
            </div>
        </Link>
    );
}

function PageLink({ href, children }) {
    if (!href) {
        return (
            <span aria-disabled="true" className="rounded-lg px-4 py-2 text-sm text-slate-500">
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
        >
            {children}
        </Link>
    );
}
