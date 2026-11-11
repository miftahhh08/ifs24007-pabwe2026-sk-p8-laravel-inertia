import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { IconTrash } from './Icons';

/**
 * Dialog konfirmasi hapus artikel.
 */
export default function ConfirmDeleteModal({ post, onClose }) {
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape' && !processing) {
                onClose();
            }
        };
        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [onClose, processing]);

    const confirm = () => {
        router.delete(`/dashboard/posts/${post.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="delete-title"
                className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl"
            >
                <div className="flex items-center gap-3 bg-red-700 px-6 py-4 text-white">
                    <IconTrash className="h-5 w-5" />
                    <h2 id="delete-title" className="text-lg font-bold">
                        Hapus Artikel
                    </h2>
                </div>
                <div className="px-6 py-5 text-sm text-slate-700">
                    Yakin ingin menghapus artikel <strong>{post.title}</strong>? Tindakan ini tidak dapat dibatalkan.
                </div>
                <div className="flex justify-end gap-3 border-t border-red-100 bg-red-50 px-6 py-4">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        onClick={confirm}
                        disabled={processing}
                        className="flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-70"
                    >
                        {processing && (
                            <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                        )}
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    );
}
