<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    /**
     * @return bool true jika username & password cocok dan sesi dimulai.
     */
    public function execute(string $username, string $password, bool $remember = false): bool
    {
        return Auth::attempt([
            'username' => $username,
            'password' => $password,
        ], $remember);
    }
}
