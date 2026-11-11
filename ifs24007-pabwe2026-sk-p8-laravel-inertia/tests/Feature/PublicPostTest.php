<?php

namespace Tests\Feature;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_only_published_posts(): void
    {
        Post::factory()->count(3)->published()->create();
        Post::factory()->count(2)->draft()->create();

        $this->get('/')->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Public/PostIndex')->has('posts.data', 3)->where('posts.total', 3)
        );

        $this->get('/articles')->assertOk()->assertInertia(
            fn (Assert $p) => $p->has('posts.data', 3)
        );
    }

    public function test_search_is_case_insensitive_and_covers_title(): void
    {
        Post::factory()->published()->create(['title' => 'Belajar Laravel Dasar']);
        Post::factory()->published()->create(['title' => 'Resep Nasi Goreng']);

        $this->get('/articles?q=LARAVEL')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
        $this->get('/articles?q=tidak-ada-sama-sekali')->assertInertia(fn (Assert $p) => $p->where('posts.total', 0));
    }

    public function test_search_covers_excerpt_and_content(): void
    {
        Post::factory()->published()->create(['excerpt' => 'ringkasan unik zebra', 'content' => 'isi biasa']);
        Post::factory()->published()->create(['excerpt' => 'biasa', 'content' => 'isi mengandung kuda nil']);

        $this->get('/articles?q=zebra')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
        $this->get('/articles?q=kuda')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
    }

    public function test_category_filter_and_invalid_category(): void
    {
        Post::factory()->published()->create(['category' => PostCategory::Bisnis]);
        Post::factory()->published()->create(['category' => PostCategory::Opini]);

        $this->get('/articles?category=Bisnis')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
        $this->get('/articles?category=NgawurBanget')->assertInertia(fn (Assert $p) => $p->where('posts.total', 2));
    }

    public function test_pagination_shows_nine_per_page(): void
    {
        Post::factory()->count(12)->published()->create();

        $this->get('/articles')->assertInertia(fn (Assert $p) => $p->has('posts.data', 9));
        $this->get('/articles?page=2')->assertInertia(fn (Assert $p) => $p->has('posts.data', 3));
    }

    public function test_published_post_can_be_read_with_cover(): void
    {
        $post = Post::factory()->published()->create([
            'user_id' => User::factory()->create(['name' => 'Penulis Satu']),
            'cover_image_path' => 'covers/contoh.jpg',
        ]);

        $this->get('/articles/'.$post->slug)->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Public/PostShow')
                ->where('post.title', $post->title)
                ->where('post.author', 'Penulis Satu')
                ->where('post.cover_url', fn ($url) => str_contains($url, 'covers/contoh.jpg'))
        );
    }

    public function test_draft_or_missing_post_returns_404(): void
    {
        $draft = Post::factory()->draft()->create();

        $this->get('/articles/'.$draft->slug)->assertNotFound();
        $this->get('/articles/tidak-ada')->assertNotFound();
    }
}
