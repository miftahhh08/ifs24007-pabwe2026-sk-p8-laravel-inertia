<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    public function paginatePublished(?string $search, ?string $category, int $perPage = 9): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('user:id,name,username')
            ->when($search, function (Builder $query, string $search) {
                // LOWER + LIKE kompatibel dengan SQLite dan PostgreSQL, serta tidak peka huruf besar/kecil.
                $like = '%'.mb_strtolower($search).'%';

                $query->where(function (Builder $q) use ($like) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(excerpt) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(content) LIKE ?', [$like]);
                });
            })
            ->when($category, fn (Builder $query, string $category) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->published()
            ->with('user:id,name,username')
            ->where('slug', $slug)
            ->first();
    }

    public function paginateByUser(int $userId, ?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->where('user_id', $userId)
            ->when($search, function (Builder $query, string $search) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%'.addcslashes($search, '%_\\').'%']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function statsByUser(int $userId): array
    {
        $row = Post::query()
            ->where('user_id', $userId)
            ->selectRaw('count(*) as total, coalesce(sum(case when is_published then 1 else 0 end), 0) as published')
            ->first();

        $total = (int) $row->total;
        $published = (int) $row->published;

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    public function update(Post $post, array $attributes): Post
    {
        $post->update($attributes);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function slugExists(string $slug): bool
    {
        return Post::query()->where('slug', $slug)->exists();
    }

    public function allByUser(int $userId): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function allWithAuthors(): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function paginateAll(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->with('user:id,name,username')
            ->when($search, function (Builder $query, string $search) {
                $like = '%'.mb_strtolower($search).'%';

                $query->where(function (Builder $q) use ($like) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereHas('user', function (Builder $u) use ($like) {
                            $u->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(username) LIKE ?', [$like]);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
