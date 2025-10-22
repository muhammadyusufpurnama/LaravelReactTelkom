<?php

// routes/api.php

use App\Http\Controllers\Admin\DataUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =================== TAMBAHKAN ROUTE INI ===================
// Route publik untuk statistik jaringan
Route::get('/network-stats', function (Request $request) {
    $target_host = 'google.com';
    $command = '';

    // Mendeteksi Sistem Operasi (OS)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Jika OS adalah Windows (untuk lingkungan lokal Anda)
        $command = 'ping -n 5 '.escapeshellarg($target_host);
    } else {
        // Jika OS adalah Linux (untuk server hosting Anda)
        $command = 'ping -c 5 '.escapeshellarg($target_host);
    }

    exec($command, $output, $status);

    if ($status === 0) {
        $result_string = implode("\n", $output);
        $packet_matches = [];
        $time_matches = [];

        // Gunakan pola regex yang sesuai dengan OS
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Pola regex untuk output Windows
            preg_match('/Sent = (\d+), Received = (\d+), Lost = \d+ \((\d+)% loss\)/', $result_string, $packet_matches_win);
            preg_match('/Average = (\d+)ms/', $result_string, $time_matches_win);

            // Susun ulang array agar cocok dengan format yang diharapkan
            $packet_matches = [null, $packet_matches_win[1] ?? 'N/A', $packet_matches_win[2] ?? 'N/A', $packet_matches_win[3] ?? 'N/A'];
            $time_matches = [null, $time_matches_win[1] ?? 'N/A'];
        } else {
            // Pola regex untuk output Linux
            preg_match('/(\d+)\s+packets transmitted,\s+(\d+)\s+received,\s+([\d.]+)%\s+packet loss/', $result_string, $packet_matches);
            preg_match('/rtt min\/avg\/max\/mdev = [\d.]+\/([\d.]+)\//', $result_string, $time_matches);
        }

        return response()->json([
            'ip' => $request->ip(), // Di lokal, ini akan menjadi 127.0.0.1
            'alive' => true,
            'transmitted' => $packet_matches[1] ?? 'N/A',
            'received' => $packet_matches[2] ?? 'N/A',
            'loss' => isset($packet_matches[3]) ? $packet_matches[3].'%' : 'N/A',
            'time' => isset($time_matches[1]) ? round($time_matches[1]).' ms' : 'N/A',
            'traceroute' => 'N/A',
        ]);
    } else {
        return response()->json([
            'ip' => $request->ip(),
            'alive' => false,
            'transmitted' => 0,
            'received' => 0,
            'loss' => '100%',
            'time' => 'N/A',
            'traceroute' => 'N/A',
        ], 500);
    }
});
// ==========================================================

// Route untuk otentikasi (login, register, logout)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Grup route yang memerlukan otentikasi (berlaku untuk user & admin)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route umum untuk user
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Grup route KHUSUS ADMIN
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Contoh: GET /api/admin/users
    Route::get('/users', [DataUserController::class, 'getAllUsers']);
    // Contoh: POST /api/admin/products
    Route::post('/products', function () {
        // Logika menambah produk
    });
});
