<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AnalysisDigitalProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardDigitalProductController;
use App\Http\Controllers\ManualUpdateController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountOfficerController;
use Inertia\Inertia;

Route::get('/info', function () { phpinfo(); });

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Rute yang memerlukan autentikasi
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboardDigitalProduct', [DashboardDigitalProductController::class, 'index'])->name('dashboardDigitalProduct');

    // Fitur AnalysisDigitalProduct
    Route::get('/analysisDigitalProduct', [AnalysisDigitalProductController::class, 'index'])->name('analysisDigitalProduct');
    Route::prefix('analysisDigitalProduct')->controller(AnalysisDigitalProductController::class)->group(function () {
        Route::post('/upload', 'upload')->name('analysisDigitalProduct.upload');
        Route::post('/targets', 'updateTargets')->name('analysisDigitalProduct.targets');
    });

    // Aksi Manual Update (dipindahkan ke sini agar terlindungi)
    Route::put('/manual-update/{order_id}/complete', [ManualUpdateController::class, 'complete'])->name('manual.update.complete');
    Route::put('/manual-update/cancel/{order_id}', [AnalysisDigitalProductController::class, 'updateManualCancel'])->name('manual.update.cancel');
    Route::put('/manual-update/cancel/{order_id}', [AnalysisDigitalProductController::class, 'updateManualCancel'])->name('manual.update.cancel');
    Route::put('/manual-update/complete/{order_id}', [AnalysisDigitalProductController::class, 'updateManualComplete'])->name('manual.update.complete');

    // Profil Pengguna
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'edit')->name('profile.edit');
        Route::patch('/', 'update')->name('profile.update');
        Route::delete('/', 'destroy')->name('profile.destroy');
    });

    Route::post('/analysis-digital-product/sync-complete', [AnalysisDigitalProductController::class, 'syncCompletedOrders'])->name('analysisDigitalProduct.syncComplete');

    Route::get('/analysis/export/inprogress', [AnalysisDigitalProductController::class, 'exportInProgress'])->name('analysis.export.inprogress');

    Route::post('/account-officers', [AccountOfficerController::class, 'store'])->name('account-officers.store');
    Route::put('/account-officers/{officer}', [AccountOfficerController::class, 'update'])->name('account-officers.update');

    Route::put('/qc-update/{order_id}/progress', [AnalysisDigitalProductController::class, 'updateQcStatusToProgress'])->name('qc.update.progress');
    Route::put('/qc-update/{order_id}/done', [AnalysisDigitalProductController::class, 'updateQcStatusToDone'])->name('qc.update.done');

    Route::get('/dashboardDigitalProduct', [DashboardDigitalProductController::class, 'index'])->name('dashboardDigitalProduct');

    Route::get('/info', function () { phpinfo(); });
});

// Rute Autentikasi Bawaan Laravel
require __DIR__.'/auth.php';

