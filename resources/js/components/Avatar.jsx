/**
 * Foto profil bulat; jika belum ada foto, tampilkan inisial nama.
 */
export default function Avatar({ user, className = 'h-9 w-9' }) {
    if (user.profile_photo_url) {
        return <img src={user.profile_photo_url} alt={`Foto ${user.name}`} width="36" height="36" decoding="async" className={`${className} rounded-full object-cover`} />;
    }

    return (
        <span
            className={`${className} flex shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700`}
            aria-hidden="true"
        >
            {user.name.charAt(0).toUpperCase()}
        </span>
    );
}
