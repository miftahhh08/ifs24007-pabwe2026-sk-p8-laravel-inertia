import { lazy, Suspense, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import ConfirmDeleteModal from '../../components/ConfirmDeleteModal';
import MarkdownViewer from '../../components/MarkdownViewer';
import { IconArrowLeft, IconPencil, IconTrash, IconUpload } from '../../components/Icons';

const PostFormModal = lazy(() => import('../../components/PostFormModal'));

export default function Show({ post, categories }) {
    const { errors } = usePage().props;
    const [editing, setEditing] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const pickCover = (e) => {
        const file = e.target.files?.[0];
        e.target.value = '';

        if (file) {
            router.post(`/dashboard/posts/${post.id}/cover`, { cover: file }, { forceFormData: true, preserveScroll: true });
        }
    };

    const removeCover = () => {
        router.delete(`/dashboard/posts/${post.id}/cover`, { preserveScroll: true });
    };

    const togglePublish = () => {
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title={post.title} />

            <Link href="/dashboard/posts" className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline">
                <IconArrowLeft />
                Kembali ke daftar artikel
            </Link>

            <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                            {post.category}
                        </span>
                        {post.is_published ? (
                            <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                Terpublikasi {post.published_at && `· ${post.published_at}`}
                            </span>
                        ) : (
                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Draf</span>
                        )}
                    </div>
                    <h1 className="mt-2 text-3xl font-extrabold text-slate-900">{post.title}</h1>
                    <p className="mt-1 text-sm text-slate-500">Dibuat {post.created_at}</p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={togglePublish}
                        className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        {post.is_published ? 'Jadikan Draf' : 'Publikasikan'}
                    </button>
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800"
                    >
                        <IconPencil />
                        Ubah
                    </button>
                    <button
                        type="button"
                        onClick={() => setDeleting(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800"
                    >
                        <IconTrash />
                        Hapus
                    </button>
                </div>
            </div>

            <section className="mt-6 rounded-xl border border-slate-200 bg-white p-5" aria-label="Foto sampul">
                <h2 className="text-sm font-semibold text-slate-700">Foto sampul</h2>

                {post.cover_url ? (
                    <img src={post.cover_url} alt={`Sampul ${post.title}`} className="mt-3 max-h-72 w-full rounded-lg object-cover" />
                ) : (
                    <p className="mt-3 text-sm text-slate-500">Belum ada foto sampul.</p>
                )}

                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        <IconUpload />
                        {post.cover_url ? 'Ganti sampul' : 'Unggah sampul'}
                        <input type="file" accept="image/png,image/jpeg,image/webp" onChange={pickCover} className="sr-only" />
                    </label>
                    {post.cover_url && (
                        <button
                            type="button"
                            onClick={removeCover}
                            className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                        >
                            Hapus sampul
                        </button>
                    )}
                    <span className="text-xs text-slate-500">JPG, PNG, atau WEBP, maksimal 2 MB.</span>
                </div>
                {errors.cover && <p className="mt-2 text-sm text-red-600">{errors.cover}</p>}
            </section>

            {post.excerpt && <p className="mt-6 text-lg text-slate-600">{post.excerpt}</p>}

            <div className="mt-6 rounded-xl border border-slate-200 bg-white p-6">
                <MarkdownViewer content={post.content} />
            </div>

            <Suspense fallback={null}>
                {editing && (
                    <PostFormModal mode="edit" initial={post} categories={categories} onClose={() => setEditing(false)} />
                )}
            </Suspense>
            {deleting && <ConfirmDeleteModal post={post} onClose={() => setDeleting(false)} />}
        </AppLayout>
    );
}
