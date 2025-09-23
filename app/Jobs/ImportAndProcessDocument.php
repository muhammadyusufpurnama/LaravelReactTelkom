<?php

namespace App\Jobs;

use App\Imports\DocumentDataImport;
use App\Models\DocumentData;
use App\Models\OrderProduct;
use App\Jobs\ProcessProductBundles;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportAndProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        $filePath = Storage::path($this->path);
        $batchId = uniqid('import_', true);

        Log::info("Memulai proses unggah untuk file: {$this->path} dengan Batch ID: {$batchId}");

        try {
            Excel::import(new DocumentDataImport($batchId), $filePath);
            Log::info("Proses unggah data mentah selesai untuk Batch ID: {$batchId}.");
            Cache::put('last_successful_batch_id', $batchId, now()->addDays(30));

            ProcessProductBundles::dispatch();
            Log::info("Job untuk memproses produk bundling telah dijadwalkan.");

        } catch (\Exception $e) {
            Log::error("GAGAL memproses file {$this->path} (Batch ID: {$batchId}): " . $e->getMessage() . " di baris " . $e->getLine());
            $this->fail($e);
        } finally {
            Storage::delete($this->path);
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
