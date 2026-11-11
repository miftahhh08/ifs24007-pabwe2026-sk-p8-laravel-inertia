<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $service)
    {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = $this->service
            ->paginateUsers($search !== '' ? $search : null)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'posts_count' => $user->posts_count,
                'profile_photo_url' => $user->profile_photo_url,
                'created_at' => $user->created_at->format('d M Y'),
            ]);

        return Inertia::render('Admin/UserList', [
            'users' => $users,
            'filters' => ['q' => $search],
        ]);
    }
}
