<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DataUserController;

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
    Route::post('/products', function() {
        // Logika menambah produk
    });
});
