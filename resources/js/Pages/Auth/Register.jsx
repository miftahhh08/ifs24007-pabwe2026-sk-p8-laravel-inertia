import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

function Field({ id, label, type = 'text', value, onChange, error, autoComplete }) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-slate-700">
                {label}
            </label>
            <input
                id={id}
                name={id}
                type={type}
                autoComplete={autoComplete}
                value={value}
                onChange={onChange}
                aria-invalid={Boolean(error)}
                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            />
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <PublicLayout>
            <Head title="Daftar" />

            <div className="mx-auto max-w-md px-4 py-14">
                <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h1 className="text-2xl font-bold text-slate-900">Daftar Penulis</h1>
                    <p className="mt-1 text-sm text-slate-500">Buat akun untuk mulai menulis artikel.</p>

                    <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                        <Field
                            id="register-name"
                            label="Nama lengkap"
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                        />
                        <Field
                            id="register-username"
                            label="Username"
                            autoComplete="username"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value)}
                            error={errors.username}
                        />
                        <Field
                            id="register-password"
                            label="Password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />
                        <Field
                            id="register-password-confirmation"
                            label="Konfirmasi password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            error={errors.password_confirmation}
                        />

                        <button
                            id="register-submit"
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-70"
                        >
                            {processing && (
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                            )}
                            Daftar
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-slate-500">
                        Sudah punya akun?{' '}
                        <Link href="/login" className="font-medium text-indigo-600 hover:underline">
                            Masuk
                        </Link>
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
