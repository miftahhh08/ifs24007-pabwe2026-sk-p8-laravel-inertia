import { useEffect, useRef } from 'react';
import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';
import sanitizeHtml from '../lib/sanitizeHtml';

/**
 * Merender teks Markdown menjadi HTML memakai TOAST UI Viewer.
 */
export default function MarkdownViewer({ content }) {
    const ref = useRef(null);

    useEffect(() => {
        const viewer = new Viewer({
            el: ref.current,
            initialValue: content ?? '',
            customHTMLSanitizer: sanitizeHtml,
        });

        return () => viewer.destroy();
    }, [content]);

    return <div ref={ref} />;
}
