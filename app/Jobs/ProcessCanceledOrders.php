<?php

namespace App\Jobs;

use App\Imports\CanceledOrdersImport; // <-- Gunakan Importer baru
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessCanceledOrders implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    public $timeout = 600;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Dapatkan path absolut dari file, sama seperti di ProcessCompletedOrders
        $filePath = \Illuminate\Support\Facades\Storage::path($this->path);

        \Illuminate\Support\Facades\Log::info("Memulai job untuk memproses order cancel dari file: {$this->path}");

        try {
            // Gunakan $filePath (path absolut) untuk mengimpor
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\CanceledOrdersImport, $filePath);
            \Illuminate\Support\Facades\Log::info("Selesai memproses file order cancel.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("GAGAL memproses file order cancel: " . $e->getMessage());
            $this->fail($e);
        } finally {
            // Hapus file setelah selesai diproses untuk membersihkan storage
            \Illuminate\Support\Facades\Storage::delete($this->path);
        }
    }
}
