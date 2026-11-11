<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Judul Artikel Uji',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan singkat.',
            'content' => "## Halo\n\nIsi artikel.",
        ], $override);
    }

    public function test_index_shows_only_own_posts_and_stats(): void
    {
        Post::factory()->count(2)->published()->for($this->member)->create();
        Post::factory()->draft()->for($this->member)->create();
        Post::factory()->count(2)->published()->create(); // milik orang lain

        $this->actingAs($this->member)->get('/dashboard/posts')->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Post/Index')
                ->has('posts.data', 3)
                ->where('stats.total', 3)
                ->where('stats.published', 2)
                ->where('stats.draft', 1)
        );
    }

    public function test_index_can_be_searched_by_title(): void
    {
        Post::factory()->for($this->member)->create(['title' => 'Panduan Docker']);
        Post::factory()->for($this->member)->create(['title' => 'Kue Lapis']);

        $this->actingAs($this->member)->get('/dashboard/posts?q=docker')
            ->assertInertia(fn (Assert $p) => $p->has('posts.data', 1));
    }

    public function test_member_can_store_draft_and_published_posts(): void
    {
        $this->actingAs($this->member)->post('/dashboard/posts', $this->payload())
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'Judul Artikel Uji',
            'slug' => 'judul-artikel-uji',
            'user_id' => $this->member->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->member)->post('/dashboard/posts', $this->payload([
            'title' => 'Artikel Terbit',
            'is_published' => true,
        ]));

        $published = Post::where('title', 'Artikel Terbit')->first();
        $this->assertTrue($published->is_published);
        $this->assertNotNull($published->published_at);
    }

    public function test_store_validates_input(): void
    {
        $this->actingAs($this->member)->post('/dashboard/posts', [])
            ->assertSessionHasErrors(['title', 'category', 'content']);

        $this->actingAs($this->member)->post('/dashboard/posts', $this->payload(['category' => 'Ngawur']))
            ->assertSessionHasErrors('category');
    }

    public function test_slug_is_unique_and_has_fallback(): void
    {
        $this->actingAs($this->member);
        $this->post('/dashboard/posts', $this->payload(['title' => 'Judul Sama']));
        $this->post('/dashboard/posts', $this->payload(['title' => 'Judul Sama']));
        $this->post('/dashboard/posts', $this->payload(['title' => '!!!']));

        $this->assertDatabaseHas('posts', ['slug' => 'judul-sama']);
        $this->assertDatabaseHas('posts', ['slug' => 'judul-sama-2']);
        $this->assertDatabaseHas('posts', ['slug' => 'artikel']);
    }

    public function test_member_can_view_own_post_but_not_others(): void
    {
        $own = Post::factory()->for($this->member)->create();
        $other = Post::factory()->create();

        $this->actingAs($this->member)->get('/dashboard/posts/'.$own->id)->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Post/Show')->where('post.id', $own->id)
        );

        $this->actingAs($this->member)->get('/dashboard/posts/'.$other->id)->assertForbidden();
    }

    public function test_member_can_update_own_post_and_publication_state(): void
    {
        $post = Post::factory()->draft()->for($this->member)->create(['slug' => 'slug-tetap']);
        $this->actingAs($this->member);

        $this->put('/dashboard/posts/'.$post->id, $this->payload(['title' => 'Judul Baru', 'is_published' => true]))
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('Judul Baru', $post->title);
        $this->assertSame('slug-tetap', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);

        $this->put('/dashboard/posts/'.$post->id, $this->payload(['is_published' => false]));
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_update_keeps_original_publish_date_and_ignores_missing_flag(): void
    {
        $date = now()->subDays(10)->startOfMinute();
        $post = Post::factory()->for($this->member)->create(['is_published' => true, 'published_at' => $date]);
        $this->actingAs($this->member);

        $this->put('/dashboard/posts/'.$post->id, $this->payload(['is_published' => true]));
        $this->assertTrue($post->fresh()->published_at->equalTo($date));

        $this->put('/dashboard/posts/'.$post->id, $this->payload(['title' => 'Tanpa Flag']));
        $this->assertTrue($post->fresh()->is_published);
    }

    public function test_member_cannot_update_delete_or_toggle_others_posts(): void
    {
        $other = Post::factory()->create();
        $this->actingAs($this->member);

        $this->put('/dashboard/posts/'.$other->id, $this->payload())->assertForbidden();
        $this->delete('/dashboard/posts/'.$other->id)->assertForbidden();
        $this->patch('/dashboard/posts/'.$other->id.'/publish')->assertForbidden();
        $this->post('/dashboard/posts/'.$other->id.'/cover')->assertForbidden();
        $this->delete('/dashboard/posts/'.$other->id.'/cover')->assertForbidden();
    }

    public function test_member_can_delete_post_and_its_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('covers/lama.jpg', 'x');
        $post = Post::factory()->for($this->member)->create(['cover_image_path' => 'covers/lama.jpg']);

        $this->actingAs($this->member)->delete('/dashboard/posts/'.$post->id)
            ->assertRedirect(route('dashboard.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('covers/lama.jpg');
    }

    public function test_toggle_publish_switches_state(): void
    {
        $post = Post::factory()->draft()->for($this->member)->create();
        $this->actingAs($this->member);

        $this->patch('/dashboard/posts/'.$post->id.'/publish')->assertSessionHas('success');
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->patch('/dashboard/posts/'.$post->id.'/publish')->assertSessionHas('success');
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_cover_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $post = Post::factory()->for($this->member)->create();
        $this->actingAs($this->member);

        $this->post('/dashboard/posts/'.$post->id.'/cover', ['cover' => UploadedFile::fake()->image('a.jpg')])
            ->assertSessionHasNoErrors();
        $first = $post->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->post('/dashboard/posts/'.$post->id.'/cover', ['cover' => UploadedFile::fake()->image('b.png')]);
        $second = $post->fresh()->cover_image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->delete('/dashboard/posts/'.$post->id.'/cover');
        $this->assertNull($post->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($second);

        $this->delete('/dashboard/posts/'.$post->id.'/cover')->assertSessionHas('success'); // tanpa cover pun aman
    }

    public function test_cover_upload_is_validated(): void
    {
        Storage::fake('public');
        $post = Post::factory()->for($this->member)->create();
        $this->actingAs($this->member);

        $this->post('/dashboard/posts/'.$post->id.'/cover', [])->assertSessionHasErrors('cover');
        $this->post('/dashboard/posts/'.$post->id.'/cover', ['cover' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('cover');
    }
}
