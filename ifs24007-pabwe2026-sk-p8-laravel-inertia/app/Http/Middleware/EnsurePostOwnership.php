<?php

namespace App\Http\Middleware;

use App\Features\Post\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lapisan pengaman kepemilikan: pada route yang memuat {post},
 * artikel harus milik pengguna yang sedang login.
 */
class EnsurePostOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $post = $request->route('post');

        if ($post instanceof Post && $post->user_id !== $request->user()?->id) {
            abort(403, 'Anda tidak berhak mengakses artikel ini.');
        }

        return $next($request);
    }
}
