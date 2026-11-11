<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Requests\SavePostRequest;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $service,
        private readonly PostRepositoryInterface $posts,
        private readonly PostExcelService $excel,
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

    /** GET /dashboard/posts/export */
    public function export(Request $request): StreamedResponse
    {
        return $this->excel->download(
            $this->posts->allByUser($request->user()->id),
            'artikel-saya-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    /** POST /dashboard/posts/import */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx', 'max:5120'],
        ], [
            'file.required' => 'Pilih berkas Excel (.xlsx) terlebih dahulu.',
            'file.extensions' => 'Berkas harus berformat .xlsx.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $result = $this->excel->import($request->user(), $request->file('file')->getRealPath());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        $message = "{$result['created']} artikel berhasil diimpor";

        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} baris dilewati ({$result['errors'][0]})";
        }

        return back()->with('success', $message.'.');
    }

    /** POST /dashboard/posts/ai-generate -> draf dikirim lewat flash 'ai_drafts'. */
    public function aiGenerate(Request $request, AiClientInterface $ai): RedirectResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'min:3', 'max:200'],
            'count' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'topic.required' => 'Topik wajib diisi.',
            'topic.min' => 'Topik minimal 3 karakter.',
            'topic.max' => 'Topik maksimal 200 karakter.',
            'count.required' => 'Jumlah artikel wajib diisi.',
            'count.integer' => 'Jumlah artikel harus berupa angka.',
            'count.min' => 'Jumlah artikel minimal 1.',
            'count.max' => 'Jumlah artikel maksimal 5.',
        ]);

        try {
            $items = $ai->generatePosts($data['topic'], (int) $data['count']);
        } catch (AiGenerationException $e) {
            throw ValidationException::withMessages(['topic' => $e->getMessage()]);
        }

        return back()->with('ai_drafts', array_map(fn (GeneratedPostItem $item) => $item->toArray(), $items));
    }

    /** POST /dashboard/posts/ai-save -> simpan batch hasil AI sebagai draf. */
    public function aiSave(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'drafts' => ['required', 'array', 'min:1', 'max:5'],
            'drafts.*.title' => ['required', 'string', 'max:255'],
            'drafts.*.category' => ['required', Rule::enum(PostCategory::class)],
            'drafts.*.excerpt' => ['nullable', 'string', 'max:500'],
            'drafts.*.content' => ['required', 'string'],
        ], [
            'drafts.required' => 'Pilih minimal satu artikel untuk disimpan.',
            'drafts.min' => 'Pilih minimal satu artikel untuk disimpan.',
        ]);

        foreach ($data['drafts'] as $draft) {
            $this->service->create($request->user(), $draft + ['is_published' => false]);
        }

        return redirect()->route('dashboard.posts.index')
            ->with('success', count($data['drafts']).' artikel hasil AI disimpan sebagai draf.');
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
