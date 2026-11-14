import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import MarkdownEditor from './MarkdownEditor';
import { IconX } from './Icons';

/**
 * Modal FULL SCREEN untuk Tambah / Ubah artikel.
 *  - Tambah : header & footer berwarna indigo
 *  - Ubah   : header & footer berwarna amber
 * Footer berisi tombol Batal dan Simpan, rata kanan.
 */
export default function PostFormModal({ mode, initial, categories, onClose }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        title: initial?.title ?? '',
        category: initial?.category ?? categories[0],
        excerpt: initial?.excerpt ?? '',
        content: initial?.content ?? '',
        is_published: initial?.is_published ?? false,
    });
    const { data, setData, errors, processing } = form;

    const theme = isEdit
        ? { header: 'bg-amber-700', footer: 'border-amber-100 bg-amber-50', save: 'bg-amber-700 hover:bg-amber-800' }
        : { header: 'bg-indigo-700', footer: 'border-indigo-100 bg-indigo-50', save: 'bg-indigo-600 hover:bg-indigo-700' };

    useEffect(() => {
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKey = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };
        document.addEventListener('keydown', onKey);

        return () => {
            document.body.style.overflow = previous;
            document.removeEventListener('keydown', onKey);
        };
    }, [onClose]);

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onClose() };

        if (isEdit) {
            form.put(`/dashboard/posts/${initial.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="post-modal-title"
            className="fixed inset-0 z-50 flex flex-col bg-white"
        >
            <header className={`flex shrink-0 items-center justify-between px-6 py-4 text-white ${theme.header}`}>
                <h2 id="post-modal-title" className="text-lg font-bold">
                    {isEdit ? 'Ubah Artikel' : 'Tambah Artikel'}
                </h2>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Tutup"
                    className="rounded-lg p-1.5 hover:bg-white/15"
                >
                    <IconX />
                </button>
            </header>

            <div className="flex-1 overflow-y-auto">
                <form id="post-form" onSubmit={submit} className="mx-auto w-full max-w-4xl space-y-6 px-6 py-8" noValidate>
                    <div>
                        <label htmlFor="post-title" className="block text-sm font-medium text-slate-700">
                            Judul
                        </label>
                        <input
                            id="post-title"
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            aria-invalid={Boolean(errors.title)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        />
                        {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                    </div>

                    <div>
                        <label htmlFor="post-category" className="block text-sm font-medium text-slate-700">
                            Kategori
                        </label>
                        <select
                            id="post-category"
                            value={data.category}
                            onChange={(e) => setData('category', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                            {categories.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                        {errors.category && <p className="mt-1 text-sm text-red-600">{errors.category}</p>}
                    </div>

                    <div>
                        <label htmlFor="post-excerpt" className="block text-sm font-medium text-slate-700">
                            Ringkasan <span className="font-normal text-slate-500">(opsional)</span>
                        </label>
                        <textarea
                            id="post-excerpt"
                            rows={3}
                            value={data.excerpt}
                            onChange={(e) => setData('excerpt', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        />
                        {errors.excerpt && <p className="mt-1 text-sm text-red-600">{errors.excerpt}</p>}
                    </div>

                    <div>
                        <span className="block text-sm font-medium text-slate-700">Isi artikel (Markdown)</span>
                        <div className="mt-1">
                            <MarkdownEditor value={data.content} onChange={(v) => setData('content', v)} />
                        </div>
                        {errors.content && <p className="mt-1 text-sm text-red-600">{errors.content}</p>}
                    </div>

                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={data.is_published}
                            onChange={(e) => setData('is_published', e.target.checked)}
                            className="h-4 w-4 rounded border-slate-300"
                        />
                        Publikasikan artikel ini
                    </label>
                </form>
            </div>

            <footer className={`flex shrink-0 justify-end gap-3 border-t px-6 py-4 ${theme.footer}`}>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    form="post-form"
                    disabled={processing}
                    className={`flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-70 ${theme.save}`}
                >
                    {processing && (
                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                    )}
                    Simpan
                </button>
            </footer>
        </div>
    );
}
