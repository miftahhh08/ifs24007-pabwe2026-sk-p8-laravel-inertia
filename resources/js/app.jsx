import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title) => (title ? `${title} - BlogVerse Pro` : 'BlogVerse Pro'),
    // Setiap halaman dimuat saat dibutuhkan (code-splitting), sehingga halaman
    // publik/login tidak ikut mengunduh editor Markdown yang besar.
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');

        return pages[`./Pages/${name}.jsx`]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
