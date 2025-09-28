<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response; // Import class Response

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles // Menerima satu atau lebih argumen role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Periksa apakah pengguna sudah login dan memiliki salah satu peran yang diizinkan.
        // Middleware 'auth' biasanya sudah menangani kasus pengguna yang belum login,
        // namun pemeriksaan ini memberikan lapisan keamanan tambahan.
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            // Jika pengguna tidak memiliki akses, hentikan permintaan dengan error 403 (Forbidden).
            // Menggunakan abort() akan membuat Laravel secara otomatis menampilkan
            // halaman error yang sesuai (misal: 403.blade.php), yang jauh lebih
            // baik daripada mengembalikan respons JSON mentah ke browser.
            abort(403, 'ANDA TIDAK MEMILIKI AKSES.');
        }

        // Jika pengguna memiliki peran yang sesuai, lanjutkan permintaan ke tujuan berikutnya.
        return $next($request);
    }
}
