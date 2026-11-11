import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Indikator loading global:
 *  - progress bar di atas layar
 *  - pill melayang "Memproses tindakan..."
 * Muncul otomatis setiap ada navigasi / request Inertia.
 */
export default function LoadingIndicator() {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setLoading(true));
        const offFinish = router.on('finish', () => setLoading(false));

        return () => {
            offStart();
            offFinish();
        };
    }, []);

    if (!loading) {
        return null;
    }

    return (
        <>
            <div className="fixed inset-x-0 top-0 z-[100] h-1 overflow-hidden bg-indigo-100" role="progressbar" aria-label="Memuat">
                <div className="progress-bar-anim h-full w-1/4 bg-indigo-600" />
            </div>
            <div
                className="fixed bottom-6 left-1/2 z-[100] flex -translate-x-1/2 items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"
                role="status"
            >
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                Memproses tindakan...
            </div>
        </>
    );
}
