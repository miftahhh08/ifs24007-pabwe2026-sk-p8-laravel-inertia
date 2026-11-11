<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController extends Controller
{
    public function __construct(private readonly PostRepositoryInterface $posts)
    {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $category = PostCategory::tryFrom((string) $request->query('category', ''))?->value;

        $posts = $this->posts
            ->paginatePublished($search !== '' ? $search : null, $category)
            ->through(fn (Post $post) => $this->toListItem($post));

        return Inertia::render('Public/PostIndex', [
            'posts' => $posts,
            'categories' => PostCategory::values(),
            'filters' => ['q' => $search, 'category' => $category],
        ]);
    }

    public function show(string $slug): Response
    {
        $post = $this->posts->findPublishedBySlug($slug) ?? abort(404);

        return Inertia::render('Public/PostShow', [
            'post' => $this->toListItem($post) + ['content' => $post->content],
        ]);
    }

    private function toListItem(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category->value,
            'excerpt' => $post->excerpt,
            'cover_url' => $post->cover_image_path
                ? Storage::disk('public')->url($post->cover_image_path)
                : null,
            'author' => $post->user->name,
            'published_at' => $post->published_at?->format('d M Y'),
        ];
    }
}
