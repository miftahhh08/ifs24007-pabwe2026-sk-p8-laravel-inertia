<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Requests\SavePostRequest;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $service,
        private readonly PostRepositoryInterface $posts,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $search = trim((string) $request->query('q', ''));

        $posts = $this->posts
            ->paginateByUser($userId, $search !== '' ? $search : null)
            ->through(fn (Post $post) => $this->toItem($post));

        return Inertia::render('Post/Index', [
            'posts' => $posts,
            'stats' => $this->posts->statsByUser($userId),
            'categories' => PostCategory::values(),
            'filters' => ['q' => $search],
        ]);
    }

    public function store(SavePostRequest $request): RedirectResponse
    {
        $this->service->create($request->user(), $request->validated());

        return redirect()->route('dashboard.posts.index')->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        return Inertia::render('Post/Show', [
            'post' => $this->toItem($post),
            'categories' => PostCategory::values(),
        ]);
    }

    public function update(SavePostRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $this->service->update($post, $request->validated());

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $this->service->delete($post);

        return redirect()->route('dashboard.posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function togglePublish(Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $post = $this->service->togglePublish($post);

        return back()->with('success', $post->is_published ? 'Artikel dipublikasikan.' : 'Artikel dijadikan draf.');
    }

    public function uploadCover(Request $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'cover.required' => 'Pilih gambar sampul terlebih dahulu.',
            'cover.image' => 'Berkas harus berupa gambar.',
            'cover.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'cover.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $this->service->uploadCover($post, $request->file('cover'));

        return back()->with('success', 'Foto sampul berhasil diunggah.');
    }

    public function removeCover(Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $this->service->removeCover($post);

        return back()->with('success', 'Foto sampul dihapus.');
    }

    private function toItem(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category->value,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'cover_url' => $post->cover_url,
            'is_published' => $post->is_published,
            'published_at' => $post->published_at?->format('d M Y'),
            'created_at' => $post->created_at->format('d M Y'),
        ];
    }
}
