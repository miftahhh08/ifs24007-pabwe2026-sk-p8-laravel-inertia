<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Penulis Satu', 'username' => 'penulis_satu', 'password' => 'rahasia1']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_profile_page_shows_current_data(): void
    {
        $this->actingAs($this->user)->get('/profile')->assertOk()->assertInertia(
            fn (Assert $p) => $p->component('Profile/Edit')
                ->where('profile.username', 'penulis_satu')
                ->where('profile.profile_photo_url', null)
        );
    }

    public function test_user_can_update_name_and_username_without_changing_password(): void
    {
        $this->actingAs($this->user)->put('/profile', ['name' => 'Nama Baru', 'username' => 'penulis_satu'])
            ->assertSessionHasNoErrors(); // username sendiri boleh dipertahankan

        $this->actingAs($this->user)->put('/profile', ['name' => 'Nama Baru', 'username' => 'nama_baru'])
            ->assertSessionHas('success');

        $fresh = $this->user->fresh();
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('nama_baru', $fresh->username);
        $this->assertTrue(Hash::check('rahasia1', $fresh->password));
    }

    public function test_username_must_be_unique_and_valid(): void
    {
        User::factory()->create(['username' => 'sudah_ada']);

        $this->actingAs($this->user)->put('/profile', ['name' => 'X', 'username' => 'sudah_ada'])
            ->assertSessionHasErrors('username');
        $this->actingAs($this->user)->put('/profile', ['name' => '', 'username' => 'a b'])
            ->assertSessionHasErrors(['name', 'username']);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $this->actingAs($this->user)->put('/profile', [
            'name' => 'Penulis Satu',
            'username' => 'penulis_satu',
            'current_password' => 'rahasia1',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru', $this->user->fresh()->password));
    }

    public function test_password_change_needs_correct_current_password(): void
    {
        $base = ['name' => 'Penulis Satu', 'username' => 'penulis_satu', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'];

        $this->actingAs($this->user)->put('/profile', $base + ['current_password' => 'salah'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($this->user)->put('/profile', $base)
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('rahasia1', $this->user->fresh()->password));
    }

    public function test_photo_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('a.jpg')])->assertSessionHasNoErrors();
        $first = $this->user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('b.png')]);
        $second = $this->user->fresh()->profile_photo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->delete('/profile/photo')->assertSessionHas('success');
        $this->assertNull($this->user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($second);

        $this->delete('/profile/photo')->assertSessionHas('success'); // tanpa foto pun aman
    }

    public function test_photo_upload_is_validated(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $this->post('/profile/photo', [])->assertSessionHasErrors('photo');
        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');
    }
}
