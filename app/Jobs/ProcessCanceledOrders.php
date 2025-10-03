<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\CanceledOrdersImport; // Pastikan memanggil Importer yang benar
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCanceledOrders implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        $filePath = Storage::path($this->path);

        Log::info("Memulai job untuk memproses order cancel dari file: {$this->path}");
        try {
            Excel::import(new CanceledOrdersImport, $filePath); // Panggil Importer yang benar
            Log::info("Selesai memproses file order cancel.");
        } catch (\Exception $e) {
            Log::error("GAGAL memproses file order cancel: " . $e->getMessage());
            $this->fail($e);
        } finally {
            Storage::delete($this->path);
        }
    }
}
