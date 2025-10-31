<?php

namespace App\Jobs;

use App\Imports\SOSDataImport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessSOSImport implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 1800; // Timeout 30 menit untuk file besar
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
        Log::info("Batch [{$currentBatchId}]: Job ProcessSOSImport DIMULAI.");

        try {
            // Memanggil class import untuk memulai proses
            Excel::import(new SOSDataImport($currentBatchId), $this->path);

            Log::info("Batch [{$currentBatchId}]: Job ProcessSOSImport SELESAI.");
        } catch (\Throwable $e) {
            // Jika terjadi error, batalkan batch dan catat log
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';
        Log::error("Batch [{$batchId}]: Job ProcessSOSImport GAGAL.");
        Log::error($exception->getMessage());
        Log::error(substr($exception->getTraceAsString(), 0, 2000));
    }
}
