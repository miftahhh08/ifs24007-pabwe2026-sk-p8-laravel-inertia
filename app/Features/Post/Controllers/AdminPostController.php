<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pemantauan artikel oleh Admin (read-only) + ekspor Excel.
 */
class AdminPostController extends Controller
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly PostExcelService $excel,
    ) {
    }

    /** GET /admin/posts */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $posts = $this->posts
            ->paginateAll($search !== '' ? $search : null)
            ->through(fn (Post $post) => $this->toItem($post));

        return Inertia::render('Admin/PostList', [
            'posts' => $posts,
            'filters' => ['q' => $search],
        ]);
    }

    /** GET /admin/users/{user}/posts */
    public function userPosts(Request $request, User $user): Response
    {
        $search = trim((string) $request->query('q', ''));

        $posts = $this->posts
            ->paginateByUser($user->id, $search !== '' ? $search : null)
            ->through(fn (Post $post) => $this->toItem($post, $user));

        return Inertia::render('Admin/UserPostList', [
            'owner' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
            'posts' => $posts,
            'filters' => ['q' => $search],
        ]);
    }

    /** GET /admin/posts/export */
    public function exportAll(): StreamedResponse
    {
        return $this->excel->download(
            $this->posts->allWithAuthors(),
            'semua-artikel-'.now()->format('Ymd-His').'.xlsx',
            withAuthor: true,
        );
    }

    /** GET /admin/users/{user}/posts/export */
    public function exportUser(User $user): StreamedResponse
    {
        return $this->excel->download(
            $this->posts->allByUser($user->id),
            'artikel-'.$user->username.'-'.now()->format('Ymd-His').'.xlsx',
            withAuthor: true,
        );
    }

    private function toItem(Post $post, ?User $author = null): array
    {
        $author ??= $post->user;

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category->value,
            'is_published' => $post->is_published,
            'published_at' => $post->published_at?->format('d M Y'),
            'created_at' => $post->created_at->format('d M Y'),
            'author' => $author->name,
            'author_username' => $author->username,
        ];
    }
}
