import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

// SEMENTARA: hanya untuk membuktikan alur login berjalan.
// Akan diganti halaman sungguhan pada Tahap 3 (penulis) dan Tahap 5 (admin).
export default function Placeholder({ heading }) {
    const { auth } = usePage().props;

    return (
        <PublicLayout>
            <Head title={heading} />
            <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
                <h1 className="text-2xl font-bold text-slate-900">{heading}</h1>
                <div className="mt-6 rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-slate-700">
                        Login berhasil sebagai <strong>{auth.user.name}</strong> (@{auth.user.username}), peran:{' '}
                        <strong>{auth.user.role}</strong>.
                    </p>
                    <p className="mt-3 text-sm text-slate-500">Fitur halaman ini hadir pada tahap berikutnya.</p>
                </div>
            </div>
        </PublicLayout>
    );
}
