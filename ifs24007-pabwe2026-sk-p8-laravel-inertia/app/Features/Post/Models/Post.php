<?php

namespace App\Features\Post\Models;

use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'cover_image_path',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => PostCategory::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Post::published() -> hanya artikel yang sudah dipublikasikan. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** $post->cover_url -> URL publik gambar sampul (atau null). */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null
        );
    }

    /** Model ada di folder Features, jadi factory harus ditunjuk manual. */
    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
