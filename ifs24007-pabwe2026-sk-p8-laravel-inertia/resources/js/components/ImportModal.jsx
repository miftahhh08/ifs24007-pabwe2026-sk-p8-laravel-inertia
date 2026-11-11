import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { IconUpload, IconX } from './Icons';

/**
 * Dialog impor artikel dari berkas Excel (.xlsx).
 */
export default function ImportModal({ onClose }) {
    const { errors } = usePage().props;
    const [file, setFile] = useState(null);
    const [localError, setLocalError] = useState('');
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

    const submit = (e) => {
        e.preventDefault();

        if (!file) {
            setLocalError('Pilih berkas Excel (.xlsx) terlebih dahulu.');
            return;
        }

        setLocalError('');
        router.post('/dashboard/posts/import', { file }, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onClose(),
        });
    };

    const errorText = localError || errors.file;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div role="dialog" aria-modal="true" aria-labelledby="import-title" className="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
                <div className="flex items-center justify-between bg-emerald-700 px-6 py-4 text-white">
                    <h2 id="import-title" className="text-lg font-bold">Impor Artikel dari Excel</h2>
                    <button type="button" onClick={onClose} aria-label="Tutup" className="rounded-lg p-1 hover:bg-white/15">
                        <IconX />
                    </button>
                </div>

                <form onSubmit={submit}>
                    <div className="space-y-4 px-6 py-5 text-sm text-slate-700">
                        <p>
                            Gunakan berkas <strong>.xlsx</strong> dengan baris pertama berisi judul kolom:{' '}
                            <code className="rounded bg-slate-100 px-1">Judul</code>,{' '}
                            <code className="rounded bg-slate-100 px-1">Kategori</code>,{' '}
                            <code className="rounded bg-slate-100 px-1">Ringkasan</code>,{' '}
                            <code className="rounded bg-slate-100 px-1">Isi</code>,{' '}
                            <code className="rounded bg-slate-100 px-1">Status</code>. Kolom Judul dan Isi wajib. Tips: klik{' '}
                            <em>Ekspor</em> dulu untuk melihat contoh format.
                        </p>

                        <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-slate-300 p-4 hover:bg-slate-50">
                            <IconUpload />
                            <span className="truncate">{file ? file.name : 'Pilih berkas .xlsx (maks. 5 MB)'}</span>
                            <input
                                type="file"
                                accept=".xlsx"
                                className="sr-only"
                                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                            />
                        </label>

                        {errorText && <p className="text-sm text-red-600">{errorText}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-emerald-100 bg-emerald-50 px-6 py-4">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-70"
                        >
                            {processing && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
                            Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
