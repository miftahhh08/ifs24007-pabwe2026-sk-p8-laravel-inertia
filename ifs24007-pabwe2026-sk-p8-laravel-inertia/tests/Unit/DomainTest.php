<?php

namespace Tests\Unit;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DomainTest extends TestCase
{
    public function test_post_category_values_and_descriptions(): void
    {
        $this->assertSame(['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'], PostCategory::values());

        $this->assertSame('Teknologi & Koding', PostCategory::Teknologi->description());
        $this->assertSame('Pendidikan & Tutorial', PostCategory::Pendidikan->description());
        $this->assertSame('Gaya Hidup & Produktivitas', PostCategory::GayaHidup->description());
        $this->assertSame('Bisnis & Karir', PostCategory::Bisnis->description());
        $this->assertSame('Opini & Pemikiran', PostCategory::Opini->description());
        $this->assertSame('Umum', PostCategory::Umum->description());
    }

    public function test_post_category_from_label(): void
    {
        $this->assertSame(PostCategory::GayaHidup, PostCategory::fromLabel('  gaya hidup '));
        $this->assertSame(PostCategory::Bisnis, PostCategory::fromLabel('BISNIS'));
        $this->assertSame(PostCategory::Umum, PostCategory::fromLabel('tidak dikenal'));
        $this->assertSame(PostCategory::Umum, PostCategory::fromLabel(null));
    }

    public function test_user_roles_and_photo_url(): void
    {
        $admin = new User(['role' => 'admin']);
        $member = new User(['role' => 'member']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());

        Storage::fake('public');
        $this->assertNull($member->profile_photo_url);

        $withPhoto = new User(['profile_photo_path' => 'profile-photos/foto.jpg']);
        $this->assertStringContainsString('profile-photos/foto.jpg', $withPhoto->profile_photo_url);
    }

    public function test_post_cover_url(): void
    {
        Storage::fake('public');

        $this->assertNull((new Post())->cover_url);
        $this->assertStringContainsString('covers/a.jpg', (new Post(['cover_image_path' => 'covers/a.jpg']))->cover_url);
    }

    public function test_post_policy_allows_only_owner(): void
    {
        $owner = new User();
        $owner->id = 1;
        $stranger = new User();
        $stranger->id = 2;

        $post = new Post();
        $post->user_id = 1;

        $policy = new PostPolicy();

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue($policy->{$ability}($owner, $post));
            $this->assertFalse($policy->{$ability}($stranger, $post));
        }
    }
}
