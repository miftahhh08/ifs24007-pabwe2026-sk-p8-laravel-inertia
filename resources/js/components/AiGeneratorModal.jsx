import { useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { IconSparkles, IconX } from './Icons';

/**
 * AI Generator: minta draf artikel dari topik, pilih yang ingin disimpan.
 */
export default function AiGeneratorModal({ onClose }) {
    const form = useForm({ topic: '', count: 3 });
    const { data, setData, errors, processing } = form;

    const [drafts, setDrafts] = useState([]);
    const [selected, setSelected] = useState([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape' && !processing && !saving) {
                onClose();
            }
        };
        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [onClose, processing, saving]);

    const generate = (e) => {
        e.preventDefault();
        form.post('/dashboard/posts/ai-generate', {
            preserveScroll: true,
            onSuccess: (page) => {
                const items = page.props.flash?.ai_drafts ?? [];
                setDrafts(items);
                setSelected(items.map((_, i) => i));
            },
        });
    };

    const toggle = (i) => {
        setSelected((prev) => (prev.includes(i) ? prev.filter((x) => x !== i) : [...prev, i]));
    };

    const save = () => {
        router.post('/dashboard/posts/ai-save', { drafts: selected.map((i) => drafts[i]) }, {
            preserveScroll: true,
            onStart: () => setSaving(true),
            onFinish: () => setSaving(false),
            onSuccess: () => onClose(),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="ai-title"
                className="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
            >
                <div className="flex shrink-0 items-center justify-between bg-violet-700 px-6 py-4 text-white">
                    <h2 id="ai-title" className="flex items-center gap-2 text-lg font-bold">
                        <IconSparkles className="h-5 w-5" />
                        AI Generator Artikel
                    </h2>
                    <button type="button" onClick={onClose} aria-label="Tutup" className="rounded-lg p-1 hover:bg-white/15">
                        <IconX />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-6 py-5">
                    <form onSubmit={generate} className="grid gap-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
                        <div>
                            <label htmlFor="ai-topic" className="block text-sm font-medium text-slate-700">Topik artikel</label>
                            <input
                                id="ai-topic"
                                type="text"
                                value={data.topic}
                                onChange={(e) => setData('topic', e.target.value)}
                                placeholder="Contoh: manfaat belajar Laravel"
                                aria-invalid={Boolean(errors.topic)}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
                            />
                        </div>
                        <div>
                            <label htmlFor="ai-count" className="block text-sm font-medium text-slate-700">Jumlah (1-5)</label>
                            <input
                                id="ai-count"
                                type="number"
                                min="1"
                                max="5"
                                step="1"
                                value={data.count}
                                onChange={(e) => setData('count', e.target.value)}
                                aria-invalid={Boolean(errors.count)}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center justify-center gap-2 rounded-lg bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-800 disabled:opacity-70"
                        >
                            {processing && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
                            {processing ? 'Menghasilkan...' : 'Hasilkan'}
                        </button>
                    </form>
                    {errors.topic && <p className="mt-2 text-sm text-red-600">{errors.topic}</p>}
                    {errors.count && <p className="mt-2 text-sm text-red-600">{errors.count}</p>}

                    {drafts.length > 0 && (
                        <div className="mt-6">
                            <h3 className="text-sm font-semibold text-slate-700">Hasil ({selected.length} dipilih)</h3>
                            <ul className="mt-2 space-y-3">
                                {drafts.map((d, i) => (
                                    <li key={i} className="rounded-lg border border-slate-200 p-3">
                                        <label className="flex cursor-pointer items-start gap-3">
                                            <input
                                                type="checkbox"
                                                checked={selected.includes(i)}
                                                onChange={() => toggle(i)}
                                                className="mt-1 h-4 w-4 rounded border-slate-300"
                                            />
                                            <span className="min-w-0">
                                                <span className="block font-semibold text-slate-900">{d.title}</span>
                                                <span className="mt-0.5 block text-xs font-medium text-violet-700">{d.category}</span>
                                                {d.excerpt && <span className="mt-1 block text-sm text-slate-600">{d.excerpt}</span>}
                                            </span>
                                        </label>
                                    </li>
                                ))}
                            </ul>
                            {errors.drafts && <p className="mt-2 text-sm text-red-600">{errors.drafts}</p>}
                        </div>
                    )}
                </div>

                <div className="flex shrink-0 justify-end gap-3 border-t border-violet-100 bg-violet-50 px-6 py-4">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={saving}
                        className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        onClick={save}
                        disabled={saving || selected.length === 0}
                        className="flex items-center gap-2 rounded-lg bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800 disabled:opacity-60"
                    >
                        {saving && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
                        Simpan sebagai draf
                    </button>
                </div>
            </div>
        </div>
    );
}
