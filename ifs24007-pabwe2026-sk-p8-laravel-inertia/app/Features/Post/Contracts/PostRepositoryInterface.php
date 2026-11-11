<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    // ---- Publik ----
    public function paginatePublished(?string $search, ?string $category, int $perPage = 9): LengthAwarePaginator;

    public function findPublishedBySlug(string $slug): ?Post;

    // ---- Penulis ----
    public function paginateByUser(int $userId, ?string $search, int $perPage = 10): LengthAwarePaginator;

    /** @return array{total:int, published:int, draft:int} */
    public function statsByUser(int $userId): array;

    public function create(array $attributes): Post;

    public function update(Post $post, array $attributes): Post;

    public function delete(Post $post): void;

    public function slugExists(string $slug): bool;

    // ---- Ekspor & Admin ----
    /** Semua artikel milik satu pengguna (beserta penulis), terbaru dulu. */
    public function allByUser(int $userId): Collection;

    /** Semua artikel sistem (beserta penulis), terbaru dulu. */
    public function allWithAuthors(): Collection;

    /** Semua artikel sistem, berhalaman, dengan pencarian judul/penulis. */
    public function paginateAll(?string $search, int $perPage = 10): LengthAwarePaginator;
}
