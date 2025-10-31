<?php

namespace App\Jobs;

use App\Imports\DocumentDataImport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel; // <-- TAMBAHKAN INI

class ImportAndProcessDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    public $timeout = 1200; // 20 menit timeout
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $currentBatchId = $this->batch()->id;
        Log::info("Batch [{$currentBatchId}]: Job ImportAndProcessDocument DIMULAI.");

        try {
            // DETEKSI IMPORT BARU
            $isFreshImport = DB::table('document_data')->doesntExist();

            // LANGKAH 1: KOSONGKAN TABEL TEMP (jika bukan import baru)
            if (!$isFreshImport) {
                DB::table('temp_upload_data')->truncate();
            }

            // LANGKAH 2: JALANKAN PROSES IMPORT UTAMA DENGAN CHUNKING
            Log::info("Batch [{$currentBatchId}]: Menjalankan proses import utama dengan chunking.");
            Excel::import(new DocumentDataImport($currentBatchId, $isFreshImport), $this->path);

            // LANGKAH 3 (CANCEL) HANYA JIKA BUKAN IMPORT BARU
            // Cek sekali lagi JIKA user membatalkan TEPAT SETELAH import selesai
            if ($this->batch()->cancelled()) {
                Log::warning("Batch [{$currentBatchId}]: Pembatalan terdeteksi setelah Excel::import selesai. Melewatkan logika pembatalan order.");

                return;
            }

            if (!$isFreshImport) {
                Log::info("Batch [{$currentBatchId}]: Menjalankan logika pembatalan order.");
                DB::transaction(function () use ($currentBatchId) {
                    // Ambil order ID yang ada di database tapi TIDAK ADA di file yang baru diupload
                    $ordersToCancel = DB::table('document_data as d')
                        ->leftJoin('temp_upload_data as t', 'd.order_id', '=', 't.order_id')
                        ->where('d.status_wfm', 'in progress')
                        ->whereNull('t.order_id')
                        ->where('d.batch_id', '!=', $currentBatchId)
                        ->select('d.order_id', 'd.product', 'd.customer_name', 'd.nama_witel', 'd.status_wfm')
                        ->get();

                    if ($ordersToCancel->isNotEmpty()) {
                        // Buat log untuk setiap order yang di-cancel
                        $logs = $ordersToCancel->map(function ($order) {
                            return [
                                'order_id' => $order->order_id,
                                'product_name' => $order->product,
                                'customer_name' => $order->customer_name,
                                'nama_witel' => $order->nama_witel,
                                'status_lama' => $order->status_wfm,
                                'status_baru' => 'cancel', // Anda mungkin ingin 'done close cancel'
                                'sumber_update' => 'Upload Data Mentah Cancel',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        })->all();
                        DB::table('update_logs')->insert($logs);

                        // Update status di tabel utama
                        DB::table('document_data')
                            ->whereIn('order_id', $ordersToCancel->pluck('order_id'))
                            ->update(['status_wfm' => 'done close cancel']); // Sesuaikan status ini
                    }
                });
            }

            Log::info("Batch [{$currentBatchId}]: Job ImportAndProcessDocument SELESAI.");
        } catch (\Throwable $e) {
            // Ini akan memanggil method failed() di bawah
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';

        // =======================================================
        // == LOGIKA BARU UNTUK MENANGANI PEMBATALAN ==
        // =======================================================
        // Periksa apakah exception ini adalah pembatalan yang disengaja
        if (str_contains($exception->getMessage(), 'Import cancelled by user')) {
            // Catat sebagai PERINGATAN (Warning), bukan ERROR
            Log::warning("Batch [{$batchId}]: Job dihentikan secara paksa oleh user.");
            // Hapus cache progress agar progress bar di frontend hilang
            Cache::forget('import_progress_'.$batchId);

            // Jangan jalankan sisa logika 'failed'. Cukup berhenti.
            return;
        }
        // =======================================================
        // =======================================================

        // Jika ini adalah error lain yang sebenarnya, log seperti biasa.
        Log::error("Batch [{$batchId}]: Job ImportAndProcessDocument GAGAL.");
        Log::error($exception->getMessage());
        // Batasi panjang trace agar log tidak terlalu besar
        Log::error(substr($exception->getTraceAsString(), 0, 2000));
    }

    // Fungsi calculateProductPrice tidak perlu diubah, biarkan saja
    private function calculateProductPrice(string $productName, DocumentData $order): int
    {
        $witel = strtoupper(trim($order->nama_witel));
        $segment = strtoupper(trim($order->segment));

        switch (strtolower(trim($productName))) {
            case 'netmonk':
                return ($segment === 'LEGS')
                    ? 26100
                    : (($witel === 'BALI') ? 26100 : 21600);

            case 'oca':
                return ($segment === 'LEGS')
                    ? 104000
                    : (($witel === 'NUSA TENGGARA') ? 104000 : 103950);

            case 'antares eazy':
                return 35000;

            case 'pijar sekolah':
                return 582750;

            default:
                return 0;
        }
    }
}
