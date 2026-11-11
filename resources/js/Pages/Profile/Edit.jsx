import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Avatar from '../../components/Avatar';
import { IconUpload } from '../../components/Icons';

function Field({ id, label, type = 'text', value, onChange, error, autoComplete }) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label>
            <input
                id={id}
                type={type}
                value={value}
                onChange={onChange}
                autoComplete={autoComplete}
                aria-invalid={Boolean(error)}
                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            />
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

export default function Edit({ profile }) {
    const { errors: pageErrors } = usePage().props;

    const form = useForm({
        name: profile.name,
        username: profile.username,
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const { data, setData, errors, processing } = form;

    const submit = (e) => {
        e.preventDefault();
        form.put('/profile', {
            preserveScroll: true,
            onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
        });
    };

    const pickPhoto = (e) => {
        const file = e.target.files?.[0];
        e.target.value = '';

        if (file) {
            router.post('/profile/photo', { photo: file }, { forceFormData: true, preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Profil Saya" />

            <h1 className="text-2xl font-bold text-slate-900">Profil Saya</h1>

            <section className="mt-6 rounded-xl border border-slate-200 bg-white p-6" aria-label="Foto profil">
                <h2 className="text-sm font-semibold text-slate-700">Foto profil</h2>
                <div className="mt-4 flex flex-wrap items-center gap-5">
                    <Avatar user={{ name: profile.name, profile_photo_url: profile.profile_photo_url }} className="h-20 w-20" />
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            <IconUpload />
                            {profile.profile_photo_url ? 'Ganti foto' : 'Unggah foto'}
                            <input type="file" accept="image/png,image/jpeg,image/webp" onChange={pickPhoto} className="sr-only" />
                        </label>
                        {profile.profile_photo_url && (
                            <button
                                type="button"
                                onClick={() => router.delete('/profile/photo', { preserveScroll: true })}
                                className="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                            >
                                Hapus foto
                            </button>
                        )}
                        <span className="text-xs text-slate-500">JPG, PNG, atau WEBP, maksimal 2 MB.</span>
                    </div>
                </div>
                {pageErrors.photo && <p className="mt-3 text-sm text-red-600">{pageErrors.photo}</p>}
            </section>

            <form onSubmit={submit} className="mt-6 space-y-8 rounded-xl border border-slate-200 bg-white p-6" noValidate>
                <div className="space-y-5">
                    <h2 className="text-sm font-semibold text-slate-700">Data akun</h2>
                    <Field id="profile-name" label="Nama lengkap" autoComplete="name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                    <Field id="profile-username" label="Username" autoComplete="username" value={data.username} onChange={(e) => setData('username', e.target.value)} error={errors.username} />
                </div>

                <div className="space-y-5">
                    <div>
                        <h2 className="text-sm font-semibold text-slate-700">Ganti password</h2>
                        <p className="text-xs text-slate-500">Kosongkan bagian ini jika tidak ingin mengganti password.</p>
                    </div>
                    <Field id="profile-current-password" label="Password saat ini" type="password" autoComplete="current-password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} error={errors.current_password} />
                    <Field id="profile-password" label="Password baru" type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                    <Field id="profile-password-confirmation" label="Konfirmasi password baru" type="password" autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} error={errors.password_confirmation} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-70"
                >
                    {processing && <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
                    Simpan perubahan
                </button>
            </form>
        </AppLayout>
    );
}
