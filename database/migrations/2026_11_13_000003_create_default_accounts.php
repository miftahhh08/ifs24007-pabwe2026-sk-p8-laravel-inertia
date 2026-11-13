<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Menjamin akun bawaan untuk penilaian (admin / member) ada di database mana pun
 * yang dimigrasi, termasuk server deploy yang tidak menjalankan seeder.
 * Aman dijalankan berulang: akun yang sudah ada tidak diubah.
 *
 * PENTING: ganti password kedua akun ini setelah penilaian selesai.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Saat test berjalan, database harus tetap bersih dari data bawaan.
        if (app()->runningUnitTests()) {
            return;
        }

        $accounts = [
            ['username' => 'admin', 'name' => 'Administrator', 'password' => 'admin', 'role' => 'admin'],
            ['username' => 'member', 'name' => 'Penulis Member', 'password' => 'member', 'role' => 'member'],
        ];

        foreach ($accounts as $account) {
            if (DB::table('users')->where('username', $account['username'])->exists()) {
                continue;
            }

            DB::table('users')->insert([
                'name' => $account['name'],
                'username' => $account['username'],
                'password' => Hash::make($account['password']),
                'role' => $account['role'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja kosong: akun tidak dihapus agar artikel milik mereka tetap aman.
    }
};
