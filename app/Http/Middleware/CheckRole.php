<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  // Menerima satu atau lebih role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Cek apakah user sudah login dan memiliki salah satu role yang diizinkan
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            // Jika tidak, kirim response error 403 (Forbidden)
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        return $next($request);
    }
}
