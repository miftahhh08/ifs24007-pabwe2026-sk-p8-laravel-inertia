import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash } = usePage().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    const isError = Boolean(flash.error);

    return (
        <div
            role="status"
            className={`mx-auto mt-4 max-w-6xl rounded-lg border px-4 py-3 text-sm ${
                isError
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700'
            }`}
        >
            {flash.error ?? flash.success}
        </div>
    );
}
