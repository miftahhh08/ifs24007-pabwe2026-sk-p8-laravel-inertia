<?php

use App\Features\Admin\Controllers\AdminUserController;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Post\Controllers\AdminPostController;
use App\Features\Post\Controllers\PostController;
use App\Features\Post\Controllers\PublicPostController;
use App\Features\Profile\Controllers\ProfileController;
use App\Http\Controllers\DiagController;
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
| Semua pengguna yang login: profil
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo.store');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.destroy');
});

/*
|--------------------------------------------------------------------------
| Tingkat 2: Anggota (Penulis)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'post.owner'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Rute statis harus sebelum rute {post}
    Route::get('posts/export', [PostController::class, 'export'])->name('posts.export');
    Route::post('posts/import', [PostController::class, 'import'])->name('posts.import');
    Route::post('posts/ai-generate', [PostController::class, 'aiGenerate'])->name('posts.ai-generate');
    Route::post('posts/ai-save', [PostController::class, 'aiSave'])->name('posts.ai-save');

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
| Tingkat 3: Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/posts', [AdminPostController::class, 'userPosts'])->whereNumber('user')->name('users.posts');
    Route::get('users/{user}/posts/export', [AdminPostController::class, 'exportUser'])->whereNumber('user')->name('users.posts.export');

    Route::get('posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('posts/export', [AdminPostController::class, 'exportAll'])->name('posts.export');
});

/*
|--------------------------------------------------------------------------
| SEMENTARA: diagnostik server untuk penelusuran masalah login. HAPUS setelah selesai.
|--------------------------------------------------------------------------
*/
Route::get('/_diag/{token}', DiagController::class);
