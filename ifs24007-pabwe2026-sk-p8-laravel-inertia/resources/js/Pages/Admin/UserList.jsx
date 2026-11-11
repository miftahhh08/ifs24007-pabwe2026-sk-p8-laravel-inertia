import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Avatar from '../../components/Avatar';
import Pagination from '../../components/Pagination';
import SearchBox from '../../components/SearchBox';

export default function UserList({ users, filters }) {
    return (
        <AppLayout>
            <Head title="Kelola Pengguna" />

            <h1 className="text-2xl font-bold text-slate-900">Kelola Pengguna</h1>
            <p className="mt-1 text-sm text-slate-500">Total {users.total} pengguna terdaftar.</p>

            <div className="mt-6">
                <SearchBox action="/admin/users" initial={filters.q} placeholder="Cari nama atau username..." label="Cari pengguna" />
            </div>

            {users.data.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    Tidak ada pengguna yang cocok.
                </div>
            ) : (
                <ul className="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                    {users.data.map((u) => (
                        <li key={u.id} className="flex items-center gap-4 px-4 py-3">
                            <Avatar user={u} className="h-11 w-11" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-semibold text-slate-900">{u.name}</p>
                                <p className="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span>@{u.username}</span>
                                    <span
                                        className={`rounded-full px-2 py-0.5 font-semibold ${
                                            u.role === 'admin' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600'
                                        }`}
                                    >
                                        {u.role === 'admin' ? 'Admin' : 'Penulis'}
                                    </span>
                                    <span>Bergabung {u.created_at}</span>
                                </p>
                            </div>
                            <span className="hidden text-sm text-slate-600 sm:block">{u.posts_count} artikel</span>
                            <Link
                                href={`/admin/users/${u.id}/posts`}
                                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                            >
                                Lihat artikel
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination paginator={users} />
        </AppLayout>
    );
}
