<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->fill(Arr::only($data, ['name', 'username']));

        if (! empty($data['password'])) {
            $user->password = $data['password']; // di-hash otomatis oleh cast 'hashed'
        }

        $user->save();

        return $user;
    }

    public function uploadPhoto(User $user, UploadedFile $file): User
    {
        $old = $user->profile_photo_path;

        $user->update(['profile_photo_path' => $file->store('profile-photos', 'public')]);
        $this->deleteFile($old);

        return $user;
    }

    public function removePhoto(User $user): User
    {
        $old = $user->profile_photo_path;

        $user->update(['profile_photo_path' => null]);
        $this->deleteFile($old);

        return $user;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
