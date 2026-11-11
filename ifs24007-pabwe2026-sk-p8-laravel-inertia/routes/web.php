<?php

use App\Features\Auth\Controllers\AuthController;
use App\Features\Post\Controllers\PostController;
use App\Features\Post\Controllers\PublicPostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tingkat 1: Publik (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicPostController::class, 'index'])->name('home');
Route::get('/articles', [PublicPostController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [PublicPostController::class, 'show'])->name('articles.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Tingkat 2: Anggota (Penulis) - perlu login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');

    Route::get('posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('posts.show');
    Route::put('posts/{post}', [PostController::class, 'update'])->whereNumber('post')->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->whereNumber('post')->name('posts.destroy');

    Route::patch('posts/{post}/publish', [PostController::class, 'togglePublish'])->whereNumber('post')->name('posts.publish');
    Route::post('posts/{post}/cover', [PostController::class, 'uploadCover'])->whereNumber('post')->name('posts.cover.store');
    Route::delete('posts/{post}/cover', [PostController::class, 'removeCover'])->whereNumber('post')->name('posts.cover.destroy');
});

/*
|--------------------------------------------------------------------------
| SEMENTARA: Panel Admin diisi sungguhan pada Tahap 5.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::inertia('/admin/users', 'Dashboard/Placeholder', ['heading' => 'Panel Admin'])
        ->name('admin.users.index');
});
