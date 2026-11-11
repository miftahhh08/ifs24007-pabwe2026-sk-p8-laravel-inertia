import { Link } from '@inertiajs/react';

function PageLink({ href, children }) {
    if (!href) {
        return <span className="rounded-lg px-4 py-2 text-sm text-slate-300">{children}</span>;
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

export default function Pagination({ paginator }) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-6 flex items-center justify-between" aria-label="Paginasi">
            <PageLink href={paginator.prev_page_url}>&larr; Sebelumnya</PageLink>
            <span className="text-sm text-slate-500">
                Halaman {paginator.current_page} dari {paginator.last_page}
            </span>
            <PageLink href={paginator.next_page_url}>Berikutnya &rarr;</PageLink>
        </nav>
    );
}
