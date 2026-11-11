<?php

namespace App\Features\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterUserAction
{
    /**
     * Membuat akun penulis (role member) lalu langsung login.
     * Password di-hash otomatis oleh cast 'hashed' pada model User.
     */
    public function execute(string $name, string $username, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role' => User::ROLE_MEMBER,
        ]);

        Auth::login($user);

        return $user;
    }
}
