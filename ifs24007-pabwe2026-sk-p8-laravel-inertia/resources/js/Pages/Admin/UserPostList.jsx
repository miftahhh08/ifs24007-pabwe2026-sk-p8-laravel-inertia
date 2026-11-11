import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../components/Pagination';
import ReadOnlyPostList from '../../components/ReadOnlyPostList';
import SearchBox from '../../components/SearchBox';
import { IconArrowLeft, IconDownload } from '../../components/Icons';

export default function UserPostList({ owner, posts, filters }) {
    return (
        <AppLayout>
            <Head title={`Artikel ${owner.name}`} />

            <Link href="/admin/users" className="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline">
                <IconArrowLeft />
                Kembali ke daftar pengguna
            </Link>

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Artikel {owner.name}</h1>
                    <p className="mt-1 text-sm text-slate-500">@{owner.username} &middot; pemantauan hanya-baca &middot; {posts.total} artikel</p>
                </div>
                <a
                    href={`/admin/users/${owner.id}/posts/export`}
                    className="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    <IconDownload />
                    Ekspor ke Excel
                </a>
            </div>

            <div className="mt-6">
                <SearchBox
                    action={`/admin/users/${owner.id}/posts`}
                    initial={filters.q}
                    placeholder="Cari judul artikel..."
                    label="Cari artikel"
                />
            </div>

            <ReadOnlyPostList posts={posts.data} showAuthor={false} />
            <Pagination paginator={posts} />
        </AppLayout>
    );
}
