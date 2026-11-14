import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

// Halaman publik & auth dimuat langsung (eager) agar tidak ada request chunk
// tambahan setelah app.js -> LCP lebih cepat. Halaman dashboard/admin (berisi
// editor Markdown yang besar) tetap lazy.
const eagerPages = import.meta.glob(['./Pages/Public/*.jsx', './Pages/Auth/*.jsx', './Pages/Welcome.jsx'], { eager: true });
const lazyPages = import.meta.glob(['./Pages/**/*.jsx', '!./Pages/Public/*.jsx', '!./Pages/Auth/*.jsx', '!./Pages/Welcome.jsx']);

createInertiaApp({
    title: (title) => (title ? `${title} - BlogVerse Pro` : 'BlogVerse Pro'),
    resolve: (name) => {
        const key = `./Pages/${name}.jsx`;

        return eagerPages[key] ?? lazyPages[key]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
