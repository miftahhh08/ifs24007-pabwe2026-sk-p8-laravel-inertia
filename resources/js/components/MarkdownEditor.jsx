import { useEffect, useRef } from 'react';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';
import sanitizeHtml from '../lib/sanitizeHtml';

/**
 * Editor Markdown (TOAST UI Editor).
 * Nilai awal dibaca sekali saat komponen dipasang; perubahan dikirim lewat onChange.
 */
export default function MarkdownEditor({ value, onChange, height = '420px' }) {
    const elRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        const editor = new Editor({
            el: elRef.current,
            height,
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: value ?? '',
            usageStatistics: false,
            customHTMLSanitizer: sanitizeHtml,
            events: {
                change: () => onChangeRef.current(editor.getMarkdown()),
            },
        });

        return () => editor.destroy();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <div ref={elRef} />;
}
