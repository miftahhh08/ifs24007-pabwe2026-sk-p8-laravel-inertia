<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEMENTARA: ringkasan kondisi server untuk menelusuri masalah login saat penilaian.
 * Tidak menampilkan rahasia apa pun (tanpa key, password, atau isi .env).
 * HAPUS file ini, route-nya, dan DiagTest setelah masalah selesai.
 */
class DiagController extends Controller
{
    public const TOKEN = 'dg-7f3a9c1e5b2d48a6';

    public function __invoke(Request $request, string $token): JsonResponse
    {
        abort_unless(hash_equals(self::TOKEN, $token), 404);

        return response()->json([
            'php' => PHP_VERSION,
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'session_driver' => config('session.driver'),
            'session_secure_cookie' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
            'db_driver' => config('database.default'),
            'request_scheme' => $request->getScheme(),
            'forwarded_proto_header' => $request->header('X-Forwarded-Proto'),
            'storage_sessions_writable' => is_writable(storage_path('framework/sessions')),
            'users_columns' => rescue(fn () => Schema::getColumnListing('users'), fn ($e) => 'ERROR: '.$e->getMessage()),
            'users_count' => rescue(fn () => User::count(), fn ($e) => 'ERROR: '.$e->getMessage()),
            'posts_count' => rescue(fn () => DB::table('posts')->count(), fn ($e) => 'ERROR: '.$e->getMessage()),
            'sessions_table_exists' => rescue(fn () => Schema::hasTable('sessions'), fn ($e) => 'ERROR: '.$e->getMessage()),
            'admin_exists' => rescue(fn () => User::where('username', 'admin')->exists(), fn ($e) => 'ERROR: '.$e->getMessage()),
            'admin_password_is_admin' => rescue(fn () => Auth::validate(['username' => 'admin', 'password' => 'admin']), fn ($e) => 'ERROR: '.$e->getMessage()),
            'member_password_is_member' => rescue(fn () => Auth::validate(['username' => 'member', 'password' => 'member']), fn ($e) => 'ERROR: '.$e->getMessage()),
            'migrations_ran' => rescue(fn () => DB::table('migrations')->pluck('migration'), fn ($e) => 'ERROR: '.$e->getMessage()),
        ]);
    }
}
