<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_register_pages(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Register'));
    }

    public function test_member_login_goes_to_dashboard(): void
    {
        $user = User::factory()->create(['username' => 'budi', 'password' => 'rahasia1']);

        $this->post('/login', ['username' => 'budi', 'password' => 'rahasia1', 'remember' => true])
            ->assertRedirect('/dashboard/posts');

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_goes_to_admin_panel(): void
    {
        User::factory()->admin()->create(['username' => 'bos', 'password' => 'rahasia1']);

        $this->post('/login', ['username' => 'bos', 'password' => 'rahasia1'])
            ->assertRedirect('/admin/users');
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia1']);

        $this->post('/login', ['username' => 'budi', 'password' => 'salah'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['username', 'password']);
    }

    public function test_user_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Siti Aminah',
            'username' => 'siti_aminah',
            'password' => 'rahasia1',
            'password_confirmation' => 'rahasia1',
        ])->assertRedirect('/dashboard/posts');

        $this->assertDatabaseHas('users', ['username' => 'siti_aminah', 'role' => 'member']);
        $this->assertAuthenticated();
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['username' => 'dipakai']);

        $this->post('/register', [
            'name' => '',
            'username' => 'dipakai',
            'password' => '123',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['name', 'username', 'password']);
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard/posts')->assertRedirect('/login');
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/dashboard/posts');
        $this->actingAs(User::factory()->admin()->create())->get('/login')->assertRedirect('/admin/users');
    }
}
