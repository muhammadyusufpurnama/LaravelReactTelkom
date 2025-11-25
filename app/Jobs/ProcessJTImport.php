<?php

namespace App\Jobs;

use App\Imports\JtDataImport;
use Illuminate\Bus\Batchable;
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

class ProcessJTImport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $currentBatchId = $this->batch()->id;
        $cacheKey = 'import_progress_' . $currentBatchId;

        // 1. Reset Progress
        Cache::put($cacheKey, 0, now()->addHour());

        try {
            $filePath = Storage::disk('local')->path($this->path);
            $totalRows = $this->countCsvLines($filePath);
            Cache::put($cacheKey, 5, now()->addHour());

            // --- BAGIAN PENTING: TRUNCATE ---
            // Kosongkan tabel SATU KALI sebelum import dimulai
            DB::table('spmk_mom')->truncate();
            // --------------------------------

            // 2. Jalankan Import
            Excel::import(
                new JtDataImport($currentBatchId, $totalRows),
                $this->path
            );

            // 3. Selesai
            Cache::put($cacheKey, 100, now()->addHour());

        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }

    private function countCsvLines(string $filePath): int
    {
        $lines = 0;
        if (file_exists($filePath)) {
            $handle = fopen($filePath, "r");
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line !== false && trim($line) !== '') {
                    $lines++;
                }
            }
            fclose($handle);
        }
        return max($lines - 1, 1);
    }

    public function failed(\Throwable $exception): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';
        Cache::put('import_progress_'.$batchId, -1, now()->addHour());
        Log::error("Job ProcessJTImport GAGAL: ".$exception->getMessage());
    }
}
