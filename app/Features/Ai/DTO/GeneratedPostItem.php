<?php

namespace App\Features\Ai\DTO;

use App\Features\Post\Enums\PostCategory;

final class GeneratedPostItem
{
    public function __construct(
        public readonly string $title,
        public readonly string $category,
        public readonly string $excerpt,
        public readonly string $content,
    ) {
    }

    /** Mengembalikan null jika data tidak layak (judul/isi kosong). */
    public static function fromArray(array $data): ?self
    {
        $title = is_string($data['title'] ?? null) ? trim($data['title']) : '';
        $content = is_string($data['content'] ?? null) ? trim($data['content']) : '';

        if ($title === '' || $content === '') {
            return null;
        }

        $excerpt = is_string($data['excerpt'] ?? null) ? trim($data['excerpt']) : '';
        $category = is_string($data['category'] ?? null) ? $data['category'] : null;

        return new self(
            mb_substr($title, 0, 255),
            PostCategory::fromLabel($category)->value,
            mb_substr($excerpt, 0, 500),
            $content,
        );
    }

    /** @return array{title:string, category:string, excerpt:string, content:string} */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
