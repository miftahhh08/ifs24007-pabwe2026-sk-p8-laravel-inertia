<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logika bisnis artikel penulis. Controller cukup memanggil service ini.
 */
class PostService
{
    public function __construct(private readonly PostRepositoryInterface $posts)
    {
    }

    public function create(User $user, array $data): Post
    {
        $publish = (bool) ($data['is_published'] ?? false);

        return $this->posts->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'category' => $data['category'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);
    }

    public function update(Post $post, array $data): Post
    {
        // Slug sengaja TIDAK diubah agar tautan artikel tetap stabil.
        $attributes = Arr::only($data, ['title', 'category', 'excerpt', 'content']);

        if (array_key_exists('is_published', $data)) {
            $attributes += $this->publicationAttributes($post, (bool) $data['is_published']);
        }

        return $this->posts->update($post, $attributes);
    }

    /** Draf <-> Terpublikasi. */
    public function togglePublish(Post $post): Post
    {
        return $this->posts->update($post, $this->publicationAttributes($post, ! $post->is_published));
    }

    public function delete(Post $post): void
    {
        $this->deleteCoverFile($post->cover_image_path);
        $this->posts->delete($post);
    }

    public function uploadCover(Post $post, UploadedFile $file): Post
    {
        $old = $post->cover_image_path;
        $path = $file->store('covers', 'public');

        $this->posts->update($post, ['cover_image_path' => $path]);
        $this->deleteCoverFile($old);

        return $post;
    }

    public function removeCover(Post $post): Post
    {
        $old = $post->cover_image_path;

        $this->posts->update($post, ['cover_image_path' => null]);
        $this->deleteCoverFile($old);

        return $post;
    }

    private function publicationAttributes(Post $post, bool $publish): array
    {
        if ($publish) {
            return [
                'is_published' => true,
                // Jika sudah terpublikasi sebelumnya, pertahankan tanggal aslinya.
                'published_at' => $post->is_published ? $post->published_at : now(),
            ];
        }

        return ['is_published' => false, 'published_at' => null];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $slug = $base;
        $i = 2;

        while ($this->posts->slugExists($slug)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function deleteCoverFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
