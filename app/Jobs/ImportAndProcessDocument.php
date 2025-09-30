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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportAndProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 1200; // 20 menit timeout
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        // Pastikan job ini memiliki akses ke batch ID
        if (!$this->batch()) {
            Log::error('Job ImportAndProcessDocument dijalankan tanpa batch.');
            return;
        }

        Log::info("Batch [" . $this->batch()->id . "]: Job ImportAndProcessDocument DIMULAI.");

        if ($this->batch()->cancelled()) {
            Log::warning("Batch [" . $this->batch()->id . "]: Proses dibatalkan sebelum import.");
            return;
        }

        try {
            // Cukup panggil Importer dan kirimkan ID batch.
            // Importer akan menangani perhitungan total baris dan update progresnya sendiri.
            Excel::import(new DocumentDataImport($this->batch()->id), $this->path);

            // Setelah import selesai, pastikan progres di set ke 100% sebagai penanda final.
            Cache::put('import_progress_' . $this->batch()->id, 100, now()->addMinutes(30));

            // Simpan ID batch terakhir yang sukses
            Cache::put('last_successful_batch_id', $this->batch()->id, now()->addHours(24));

            Log::info("Batch [" . $this->batch()->id . "]: Job ImportAndProcessDocument SELESAI.");

        } catch (\Throwable $e) {
            $this->fail($e); // Panggil method failed() jika terjadi error
        }
    }

    public function failed(\Throwable $exception): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';
        Log::error("Batch [{$batchId}]: Job ImportAndProcessDocument GAGAL.");
        Log::error($exception->getMessage());
        Log::error($exception->getTraceAsString()); // Tambahkan trace untuk debug

        // Set progress ke -1 untuk menandakan error di frontend (opsional)
        if ($this->batch()) {
            Cache::put('import_progress_' . $this->batch()->id, -1, now()->addMinutes(30));
        }
    }

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
