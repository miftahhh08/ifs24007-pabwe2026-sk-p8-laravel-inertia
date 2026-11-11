<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'password' => 'admin', 'role' => User::ROLE_ADMIN],
        );

        $member = User::updateOrCreate(
            ['username' => 'member'],
            ['name' => 'Penulis Member', 'password' => 'member', 'role' => User::ROLE_MEMBER],
        );

        Post::factory()->count(6)->published()->for($member)->create();
        Post::factory()->count(3)->draft()->for($member)->create();
        Post::factory()->count(3)->published()->for($admin)->create();
    }
}
