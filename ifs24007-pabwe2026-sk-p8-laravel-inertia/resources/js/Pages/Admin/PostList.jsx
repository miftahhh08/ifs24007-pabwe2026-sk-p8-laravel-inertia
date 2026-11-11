import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../components/Pagination';
import ReadOnlyPostList from '../../components/ReadOnlyPostList';
import SearchBox from '../../components/SearchBox';
import { IconDownload } from '../../components/Icons';

export default function PostList({ posts, filters }) {
    return (
        <AppLayout>
            <Head title="Semua Artikel" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Semua Artikel</h1>
                    <p className="mt-1 text-sm text-slate-500">Pemantauan hanya-baca. Total {posts.total} artikel.</p>
                </div>
                <a
                    href="/admin/posts/export"
                    className="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    <IconDownload />
                    Ekspor Semua ke Excel
                </a>
            </div>

            <div className="mt-6">
                <SearchBox action="/admin/posts" initial={filters.q} placeholder="Cari judul atau penulis..." label="Cari artikel" />
            </div>

            <ReadOnlyPostList posts={posts.data} />
            <Pagination paginator={posts} />
        </AppLayout>
    );
}
