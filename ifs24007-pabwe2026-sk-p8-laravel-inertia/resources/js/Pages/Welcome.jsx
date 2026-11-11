import { Head } from '@inertiajs/react';

export default function Welcome({ totalUsers, totalPosts, totalPublished, categories }) {
    return (
        <>
            <Head title="Setup Berhasil" />
            <div className="mx-auto max-w-3xl px-6 py-16">
                <h1 className="text-3xl font-bold text-indigo-600">BlogVerse Pro</h1>
                <p className="mt-2 text-slate-600">
                    Tahap 1 berhasil: Laravel + Inertia + React + PostgreSQL sudah tersambung.
                </p>

                <div className="mt-8 grid grid-cols-3 gap-4">
                    <Stat label="Pengguna" value={totalUsers} />
                    <Stat label="Total Artikel" value={totalPosts} />
                    <Stat label="Terpublikasi" value={totalPublished} />
                </div>

                <h2 className="mt-10 text-lg font-semibold">Kategori (dari Enum PostCategory)</h2>
                <div className="mt-3 flex flex-wrap gap-2">
                    {categories.map((c) => (
                        <span key={c} className="rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700">
                            {c}
                        </span>
                    ))}
                </div>
            </div>
        </>
    );
}

function Stat({ label, value }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="text-sm text-slate-500">{label}</div>
            <div className="mt-1 text-3xl font-bold">{value}</div>
        </div>
    );
}
