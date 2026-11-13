import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

/**
 * Form login memakai input TIDAK terkontrol: nilai dibaca langsung dari DOM saat dikirim.
 * Dengan begitu login tetap berhasil walau kolom diisi lewat skrip/autofill/pengelola
 * password (yang tidak memicu event input React).
 */
export default function Login() {
    const { errors } = usePage().props;
    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        const form = new FormData(e.currentTarget);

        router.post('/login', {
            username: form.get('username') ?? '',
            password: form.get('password') ?? '',
            remember: form.get('remember') === 'on',
        }, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <PublicLayout>
            <Head title="Masuk" />

            <div className="mx-auto max-w-md px-4 py-14">
                <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h1 className="text-2xl font-bold text-slate-900">Masuk</h1>
                    <p className="mt-1 text-sm text-slate-500">Masuk untuk mengelola artikelmu.</p>

                    <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                        <div>
                            <label htmlFor="login-username" className="block text-sm font-medium text-slate-700">
                                Username
                            </label>
                            <input
                                id="login-username"
                                name="username"
                                type="text"
                                autoComplete="username"
                                defaultValue=""
                                aria-invalid={Boolean(errors.username)}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            />
                            {errors.username && <p className="mt-1 text-sm text-red-600">{errors.username}</p>}
                        </div>

                        <div>
                            <label htmlFor="login-password" className="block text-sm font-medium text-slate-700">
                                Password
                            </label>
                            <input
                                id="login-password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                defaultValue=""
                                aria-invalid={Boolean(errors.password)}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            />
                            {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                        </div>

                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                name="remember"
                                className="h-4 w-4 rounded border-slate-300"
                            />
                            Ingat saya
                        </label>

                        <button
                            id="login-submit"
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-70"
                        >
                            {processing && (
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                            )}
                            Masuk
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-slate-500">
                        Belum punya akun?{' '}
                        <Link href="/register" className="font-medium text-indigo-600 hover:underline">
                            Daftar di sini
                        </Link>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
