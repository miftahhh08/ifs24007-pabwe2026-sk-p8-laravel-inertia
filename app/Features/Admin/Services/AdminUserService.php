<?php

namespace App\Features\Admin\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminUserService
{
    /** Daftar pengguna + jumlah artikelnya, dengan pencarian nama/username. */
    public function paginateUsers(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('posts')
            ->when($search, function (Builder $query, string $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function (Builder $q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$like])->orWhereRaw('LOWER(username) LIKE LOWER(?)', [$like]);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
