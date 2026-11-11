<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    private function readDownload($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'dl').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        return IOFactory::load($path)->getActiveSheet()->toArray();
    }

    public function test_guest_is_redirected_and_member_is_forbidden(): void
    {
        $member = User::factory()->create();

        $this->get('/admin/users')->assertRedirect('/login');

        $this->actingAs($member);
        foreach (['/admin/users', '/admin/posts', '/admin/posts/export', "/admin/users/{$member->id}/posts", "/admin/users/{$member->id}/posts/export"] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_sees_users_with_post_counts_and_search(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi']);
        Post::factory()->count(2)->for($budi)->create();
        User::factory()->create(['name' => 'Citra Lestari', 'username' => 'citra']);

        $this->actingAs($this->admin)->get('/admin/users')->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Admin/UserList')->where('users.total', 3)
        );

        $this->actingAs($this->admin)->get('/admin/users?q=BUDI')->assertInertia(
            fn (Assert $p) => $p->where('users.total', 1)->where('users.data.0.posts_count', 2)
        );
    }

    public function test_admin_sees_posts_of_a_user_read_only(): void
    {
        $budi = User::factory()->create(['username' => 'budi']);
        Post::factory()->for($budi)->create(['title' => 'Tulisan Budi Satu']);
        Post::factory()->for($budi)->create(['title' => 'Tulisan Budi Dua']);
        Post::factory()->create(['title' => 'Milik Orang Lain']);

        $this->actingAs($this->admin)->get("/admin/users/{$budi->id}/posts")->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Admin/UserPostList')
                ->where('owner.username', 'budi')
                ->where('posts.total', 2)
        );

        $this->actingAs($this->admin)->get("/admin/users/{$budi->id}/posts?q=satu")->assertInertia(
            fn (Assert $p) => $p->where('posts.total', 1)
        );
    }

    public function test_admin_sees_all_posts_with_search_by_title_or_author(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi']);
        Post::factory()->for($budi)->create(['title' => 'Tentang Kopi']);
        Post::factory()->create(['title' => 'Tentang Teh']);

        $this->actingAs($this->admin)->get('/admin/posts')->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Admin/PostList')->where('posts.total', 2)
        );
        $this->actingAs($this->admin)->get('/admin/posts?q=kopi')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
        $this->actingAs($this->admin)->get('/admin/posts?q=santoso')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
        $this->actingAs($this->admin)->get('/admin/posts?q=budi')->assertInertia(fn (Assert $p) => $p->where('posts.total', 1));
    }

    public function test_admin_can_export_all_posts_with_author_columns(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi']);
        Post::factory()->for($budi)->create(['title' => 'Tentang Kopi']);
        Post::factory()->create(['title' => 'Tentang Teh']);

        $response = $this->actingAs($this->admin)->get('/admin/posts/export');
        $response->assertOk()->assertDownload();

        $rows = $this->readDownload($response);
        $this->assertSame('Penulis', $rows[0][8]);
        $this->assertSame('Username', $rows[0][9]);
        $this->assertCount(3, $rows);
        $this->assertContains('Budi Santoso', array_column($rows, 8));
    }

    public function test_admin_can_export_posts_of_one_user(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi']);
        Post::factory()->count(2)->for($budi)->create();
        Post::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/users/{$budi->id}/posts/export");
        $response->assertOk()->assertDownload();

        $rows = $this->readDownload($response);
        $this->assertCount(3, $rows);
        $this->assertSame(['Budi Santoso'], array_values(array_unique(array_column(array_slice($rows, 1), 8))));
    }

    public function test_non_numeric_user_id_returns_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/abc/posts')->assertNotFound();
    }
}
