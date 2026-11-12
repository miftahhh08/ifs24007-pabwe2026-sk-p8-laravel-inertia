import DOMPurify from 'dompurify';

/**
 * Sanitizer HTML untuk TOAST UI Editor/Viewer.
 *
 * @toast-ui/editor membawa DOMPurify versi lama di dalam bundelnya sendiri
 * (yang punya celah XSS yang sudah diketahui). Dengan opsi `customHTMLSanitizer`,
 * HTML hasil render Markdown disaring memakai DOMPurify terbaru dari dependensi proyek.
 */
export default function sanitizeHtml(html) {
    return DOMPurify.sanitize(html);
}
