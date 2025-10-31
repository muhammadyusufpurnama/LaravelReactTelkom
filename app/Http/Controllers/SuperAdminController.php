<?php

namespace App\Http\Controllers;

use App\Models\DocumentData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia; // Pastikan model ini diimpor jika belum

class SuperAdminController extends Controller
{
    /**
     * Menampilkan halaman rollback batch.
     */
    public function showRollbackPage()
    {
        // Ambil beberapa batch ID terakhir dari document_data untuk ditampilkan
        // Batasi jumlahnya agar tidak terlalu banyak, misal 20 terakhir
        $recentBatches = DocumentData::select('batch_id', DB::raw('MAX(created_at) as last_upload_time'))
            ->whereNotNull('batch_id')
            ->groupBy('batch_id')
            ->orderBy('last_upload_time', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('SuperAdmin/RollbackPage', [
            'recentBatches' => $recentBatches,
        ]);
    }

    /**
     * Mengeksekusi rollback/cleanup data dari batch yang gagal atau dibatalkan.
     */
    public function executeRollback(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'batch_id' => 'required|string|exists:document_data,batch_id',
        ], [
            'batch_id.exists' => 'Batch ID ini tidak ditemukan di database. Tidak ada data untuk dihapus.',
        ]);

        $batchId = $validated['batch_id'];
        Log::warning('Super Admin ['.auth()->id()."] memulai rollback untuk Batch ID: {$batchId}");

        try {
            DB::transaction(function () use ($batchId) {
                // Kumpulkan semua Order ID yang terkait dengan batch ini
                $orderIds = DB::table('document_data')
                              ->where('batch_id', $batchId)
                              ->pluck('order_id');

                if ($orderIds->isEmpty()) {
                    return; // Tidak ada yang perlu dihapus
                }

                // 1. Hapus dari tabel 'order_products' (data bundling)
                $deletedBundles = DB::table('order_products')->whereIn('order_id', $orderIds)->delete();
                Log::info("Rollback Batch [{$batchId}]: {$deletedBundles} baris dihapus dari order_products.");

                // 2. Hapus dari tabel 'update_logs' yang sumbernya dari batch ini
                // Perlu asumsi atau penyesuaian jika log tidak secara eksplisit menandai batch_id
                // Contoh: Hapus log yang order_id nya ada di batch ini
                $deletedLogs = DB::table('update_logs')->whereIn('order_id', $orderIds)->delete();
                Log::info("Rollback Batch [{$batchId}]: {$deletedLogs} baris dihapus dari update_logs.");

                // 3. Hapus dari tabel 'document_data' (Tabel utama)
                $deletedDocs = DB::table('document_data')->where('batch_id', $batchId)->delete();
                Log::info("Rollback Batch [{$batchId}]: {$deletedDocs} baris dihapus dari document_data.");

                // Opsional: Hapus data dari tabel lain jika ada relasi, misal 'temp_upload_data' jika relevan
            });

            return Redirect::back()->with('success', "Rollback untuk Batch ID: {$batchId} berhasil. Semua data terkait telah dihapus.");
        } catch (\Exception $e) {
            Log::error("Gagal melakukan rollback batch {$batchId}: ".$e->getMessage());

            return Redirect::back()->with('error', 'Gagal melakukan rollback. Silakan cek log sistem.');
        }
    }
}
