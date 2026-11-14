import { lazy, Suspense } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { IconArrowLeft, IconLogin, IconUserPlus } from '../../components/Icons';

// Viewer Markdown berukuran besar: dimuat terpisah agar bundel utama tetap ringan.
const MarkdownViewer = lazy(() => import('../../components/MarkdownViewer'));

export default function PostShow({ post }) {
    const { auth } = usePage().props;

    return (
        <PublicLayout>
            <Head title={post.title} />

            <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
                <Link href="/" className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline">
                    <IconArrowLeft />
                    Kembali ke beranda
                </Link>

                <span className="mt-6 block w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    {post.category}
                </span>
                <h1 className="mt-3 text-3xl font-extrabold text-slate-900 sm:text-4xl">{post.title}</h1>
                <p className="mt-3 text-sm text-slate-500">
                    Oleh <span className="font-medium text-slate-700">{post.author}</span> &middot; {post.published_at}
                </p>

                {post.cover_url && (
                    <img src={post.cover_url} alt={`Sampul ${post.title}`} width="768" height="432" fetchPriority="high" decoding="async" className="mt-8 aspect-video w-full rounded-xl object-cover" />
                )}

                <div className="mt-8">
                    <Suspense fallback={<div className="min-h-64" aria-busy="true" />}>
                        <MarkdownViewer content={post.content} />
                    </Suspense>
                </div>

                {!auth.user && (
                    <aside className="mt-14 rounded-2xl bg-gradient-to-br from-indigo-700 to-violet-700 p-8 text-center text-white">
                        <h2 className="text-xl font-bold">Suka membaca? Mulai menulis juga!</h2>
                        <p className="mt-2 text-white">
                            Bergabunglah dengan komunitas penulis BlogVerse Pro dan bagikan ceritamu.
                        </p>
                        <div className="mt-6 flex flex-wrap justify-center gap-3">
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                            >
                                <IconLogin />
                                Masuk
                            </Link>
                            <Link
                                href="/register"
                                className="inline-flex items-center gap-2 rounded-lg border border-white/60 px-5 py-3 text-sm font-semibold hover:bg-white/10"
                            >
                                <IconUserPlus />
                                Daftar
                            </Link>
                        </div>
                    </aside>
                )}
            </article>
        </PublicLayout>
    );
}
