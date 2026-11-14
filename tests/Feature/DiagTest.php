<?php

namespace Tests\Feature;

use App\Http\Controllers\DiagController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_token_returns_404(): void
    {
        $this->get('/_diag/token-salah')->assertNotFound();
    }

    public function test_diagnostics_report_server_state(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'admin']);

        $this->get('/_diag/'.DiagController::TOKEN)
            ->assertOk()
            ->assertJsonStructure(['php', 'app_env', 'session_driver', 'users_columns', 'migrations_ran'])
            ->assertJson([
                'admin_exists' => true,
                'admin_password_is_admin' => true,
                'member_password_is_member' => false,
                'sessions_table_exists' => true,
            ]);
    }
}
