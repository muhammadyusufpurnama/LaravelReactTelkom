<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, \Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // ============================================
                // == PERBAIKAN LOGIKA REDIRECT ==
                // ============================================
                $user = Auth::user(); // Dapatkan user yang sedang login

                // Pastikan user object tidak null sebelum mengakses properti
                if ($user && $user->role === 'superadmin') {
                    // Jika Super Admin, alihkan ke halaman user management
                    // [FIX LAGI] Menggunakan nama route yang BENAR: 'superadmin.users.index'
                    return redirect()->route('superadmin.users.index');
                }
                // Anda bisa menambahkan else if untuk role lain jika perlu
                // else if ($user && $user->role === 'admin') {
                //     // Pastikan route 'admin.dashboard' atau yang sesuai ada
                //     // return redirect()->route('admin.dashboard'); // Contoh redirect admin
                //     return redirect(RouteServiceProvider::HOME); // Default jika route admin tidak ada
                // }

                // Jika bukan Super Admin (atau role khusus lainnya), gunakan redirect default
                return redirect('/dashboard');
                // ============================================
            }
        }

        return $next($request);
    }
}
