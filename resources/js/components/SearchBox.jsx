import { useState } from 'react';
import { router } from '@inertiajs/react';
import { IconSearch } from './Icons';

/**
 * Kotak pencarian sederhana: mengirim ?q= ke alamat yang diberikan.
 */
export default function SearchBox({ action, initial = '', placeholder, label }) {
    const [q, setQ] = useState(initial);

    const submit = (e) => {
        e.preventDefault();
        router.get(action, q ? { q } : {}, { preserveState: true, replace: true });
    };

    return (
        <form onSubmit={submit} role="search" className="flex gap-3">
            <div className="relative flex-1">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <IconSearch />
                </span>
                <input
                    type="search"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder={placeholder}
                    aria-label={label}
                    className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                />
            </div>
            <button type="submit" className="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Cari
            </button>
        </form>
    );
}
