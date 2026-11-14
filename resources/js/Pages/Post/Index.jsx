import { lazy, Suspense, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import ConfirmDeleteModal from '../../components/ConfirmDeleteModal';
import KebabMenu from '../../components/KebabMenu';
import Pagination from '../../components/Pagination';
import { IconDownload, IconEye, IconPencil, IconPlus, IconSearch, IconSparkles, IconTrash, IconUpload } from '../../components/Icons';

// Modal dimuat saat pertama dibuka (code-splitting): editor Markdown TOAST UI berukuran besar
// dan tidak boleh ikut diunduh hanya untuk menampilkan daftar artikel.
const PostFormModal = lazy(() => import('../../components/PostFormModal'));
const AiGeneratorModal = lazy(() => import('../../components/AiGeneratorModal'));
const ImportModal = lazy(() => import('../../components/ImportModal'));

function StatCard({ label, value, tone }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{label}</p>
            <p className={`mt-1 text-3xl font-bold ${tone}`}>{value}</p>
        </div>
    );
}

function StatusBadge({ published }) {
    return published ? (
        <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Terpublikasi</span>
    ) : (
        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Draf</span>
    );
}

export default function Index({ posts, stats, categories, filters }) {
    const [q, setQ] = useState(filters.q ?? '');
    const [formState, setFormState] = useState(null); // null | { mode: 'create' } | { mode: 'edit', post }
    const [deleting, setDeleting] = useState(null);
    const [busyId, setBusyId] = useState(null);
    const [showImport, setShowImport] = useState(false);
    const [showAi, setShowAi] = useState(false);

    const search = (e) => {
        e.preventDefault();
        router.get('/dashboard/posts', q ? { q } : {}, { preserveState: true, replace: true });
    };

    const toggle = (post) => {
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, {
            preserveScroll: true,
            onStart: () => setBusyId(post.id),
            onFinish: () => setBusyId(null),
        });
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-bold text-slate-900">Artikel Saya</h1>
                <div className="flex flex-wrap gap-2">
                    <a
                        href="/dashboard/posts/export"
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        <IconDownload />
                        Ekspor
                    </a>
                    <button
                        type="button"
                        onClick={() => setShowImport(true)}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        <IconUpload />
                        Impor
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowAi(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-violet-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-violet-800"
                    >
                        <IconSparkles />
                        AI Generator
                    </button>
                    <button
                        type="button"
                        onClick={() => setFormState({ mode: 'create' })}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        <IconPlus />
                        Tambah Artikel
                    </button>
                </div>
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Total Artikel" value={stats.total} tone="text-slate-900" />
                <StatCard label="Total Selesai" value={stats.published} tone="text-emerald-600" />
                <StatCard label="Belum Selesai" value={stats.draft} tone="text-amber-600" />
            </div>

            <form onSubmit={search} role="search" className="mt-6 flex gap-3">
                <div className="relative flex-1">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <IconSearch />
                    </span>
                    <input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari judul artikel..."
                        aria-label="Cari judul artikel"
                        className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    />
                </div>
                <button type="submit" className="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
            </form>

            {posts.data.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    {filters.q ? 'Tidak ada artikel yang cocok.' : 'Belum ada artikel. Klik "Tambah Artikel" untuk mulai menulis.'}
                </div>
            ) : (
                <ul className="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                    {posts.data.map((post) => (
                        <li key={post.id} className="flex items-center gap-4 px-4 py-3">
                            {post.cover_url ? (
                                <img src={post.cover_url} alt="" className="h-14 w-14 shrink-0 rounded-lg object-cover" />
                            ) : (
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xl font-black text-indigo-800">
                                    {post.title.charAt(0).toUpperCase()}
                                </div>
                            )}

                            <div className="min-w-0 flex-1">
                                <Link
                                    href={`/dashboard/posts/${post.id}`}
                                    className="block truncate font-semibold text-slate-900 hover:text-indigo-600"
                                >
                                    {post.title}
                                </Link>
                                <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span className="rounded-full bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-700">
                                        {post.category}
                                    </span>
                                    <StatusBadge published={post.is_published} />
                                    <span>{post.created_at}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => toggle(post)}
                                disabled={busyId === post.id}
                                className="hidden items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-60 sm:inline-flex"
                            >
                                {busyId === post.id && (
                                    <span className="h-3 w-3 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600" />
                                )}
                                {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                            </button>

                            <KebabMenu
                                label={`Aksi untuk ${post.title}`}
                                items={[
                                    { label: 'Detail', icon: <IconEye />, onClick: () => router.visit(`/dashboard/posts/${post.id}`) },
                                    { label: 'Ubah', icon: <IconPencil />, onClick: () => setFormState({ mode: 'edit', post }) },
                                    { label: 'Hapus', icon: <IconTrash />, danger: true, onClick: () => setDeleting(post) },
                                ]}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={posts} />

            <Suspense fallback={null}>
                {formState && (
                    <PostFormModal
                        key={formState.post?.id ?? 'new'}
                        mode={formState.mode}
                        initial={formState.post}
                        categories={categories}
                        onClose={() => setFormState(null)}
                    />
                )}
                {showImport && <ImportModal onClose={() => setShowImport(false)} />}
                {showAi && <AiGeneratorModal onClose={() => setShowAi(false)} />}
            </Suspense>
            {deleting && <ConfirmDeleteModal post={deleting} onClose={() => setDeleting(null)} />}
        </AppLayout>
    );
}
