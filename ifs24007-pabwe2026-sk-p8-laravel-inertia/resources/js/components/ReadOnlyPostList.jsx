import { Link } from '@inertiajs/react';

/**
 * Daftar artikel hanya-baca untuk Admin.
 */
export default function ReadOnlyPostList({ posts, showAuthor = true }) {
    if (posts.length === 0) {
        return (
            <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                Tidak ada artikel yang cocok.
            </div>
        );
    }

    return (
        <ul className="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
            {posts.map((post) => (
                <li key={post.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-semibold text-slate-900">{post.title}</p>
                        <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span className="rounded-full bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-700">{post.category}</span>
                            {post.is_published ? (
                                <span className="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">Terpublikasi</span>
                            ) : (
                                <span className="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">Draf</span>
                            )}
                            {showAuthor && <span>Oleh {post.author} (@{post.author_username})</span>}
                            <span>{post.created_at}</span>
                        </p>
                    </div>
                    {post.is_published && (
                        <Link href={`/articles/${post.slug}`} className="text-xs font-medium text-indigo-700 hover:underline">
                            Lihat di blog
                        </Link>
                    )}
                </li>
            ))}
        </ul>
    );
}
