<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MergedFilesExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ExcelMergeController extends Controller
{
    public function create()
    {
        $lastResult = session()->get('last_merge_result_'.auth()->id());

        return Inertia::render('Admin/MergeExcel', [
            'lastMergeResult' => $lastResult,
        ]);
    }

    public function merge(Request $request)
    {
        Log::info('=== MERGE REQUEST STARTED ===');

        $request->validate([
            // --- MODIFIKASI DI SINI ---
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'file|max:20480', // Validasi 'mimes' sudah kita hapus sebelumnya
        ], [
            'files.*.max' => 'Ukuran setiap file tidak boleh lebih dari 20MB.',
            'files.required' => 'Anda harus memilih setidaknya satu file untuk diunggah.',
            'files.min' => 'Anda harus memilih setidaknya satu file untuk diunggah.',
            // --- MODIFIKASI DI SINI ---
            'files.max' => 'Anda hanya dapat mengunggah maksimal 20 file sekaligus.',
        ]);

        Log::info('Validation passed, processing '.count($request->file('files')).' files');

        $filePaths = [];
        $directory = 'temp-merges/'.uniqid();

        try {
            foreach ($request->file('files') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                    throw new \Exception('File dengan format .'.$extension.' tidak didukung.');
                }

                $path = $file->store($directory, 'public');
                $filePaths[] = [
                    'path' => $path,
                    'extension' => $extension,
                    'original_name' => $file->getClientOriginalName(),
                ];

                Log::info("File stored: {$path}");
            }

            $mergeResult = $this->processMerge($filePaths);
            $this->cleanupTempFiles($directory);

            Log::info('=== MERGE PROCESS COMPLETED ===');
            Log::info('Result: '.json_encode($mergeResult));

            return back()->with([
                'success' => 'Proses penggabungan selesai! File telah berhasil digabungkan.',
                'mergeResult' => $mergeResult,
            ]);
        } catch (\Exception $e) {
            Log::error('Merge process failed: '.$e->getMessage());
            if (isset($directory)) {
                $this->cleanupTempFiles($directory);
            }

            return back()->with('error', 'Terjadi kesalahan saat memproses file: '.$e->getMessage());
        }
    }

    protected function processMerge($filePaths)
    {
        $filesWithInfo = [];
        foreach ($filePaths as $fileInfo) {
            $path = $fileInfo['path'];
            if (Storage::disk('public')->exists($path)) {
                $absolutePath = Storage::disk('public')->path($path);
                $filesWithInfo[] = [
                    'path' => $absolutePath,
                    'extension' => $fileInfo['extension'],
                    'original_name' => $fileInfo['original_name'],
                ];
                Log::info("Processing file: {$fileInfo['original_name']}");
            } else {
                throw new \Exception("File tidak ditemukan: {$path}");
            }
        }

        if (empty($filesWithInfo)) {
            throw new \Exception('Tidak ada file valid yang ditemukan');
        }

        $fileName = 'merged_files_'.now()->format('Ymd_His').'.xlsx';
        $exportPath = 'merged-results/'.$fileName;

        $directory = dirname(Storage::disk('public')->path($exportPath));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Log::info("Starting Excel export to: {$exportPath}");
        Excel::store(new MergedFilesExport($filesWithInfo), $exportPath, 'public');
        Log::info("Excel export completed: {$exportPath}");

        $mergeResult = [
            'file_name' => $fileName,
            'file_path' => $exportPath,
            'download_url' => route('admin.merge-excel.download', ['file_path' => $exportPath]),
            'created_at' => now()->toDateTimeString(),
        ];

        session()->put('last_merge_result_'.auth()->id(), $mergeResult);

        return $mergeResult;
    }

    protected function cleanupTempFiles($directory)
    {
        try {
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
                Log::info("Cleaned up temp directory: {$directory}");
            }
        } catch (\Throwable $e) {
            Log::warning('Cleanup error: '.$e->getMessage());
        }
    }

    public function download(Request $request)
    {
        $filePath = $request->query('file_path');

        if (!$filePath) {
            return back()->with('error', 'Parameter file_path diperlukan.');
        }

        if (strpos($filePath, 'merged-results/') !== 0 || !Storage::disk('public')->exists($filePath)) {
            return back()->with('error', 'File tidak ditemukan atau path tidak valid.');
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $fileName = basename($filePath);

        Log::info("Downloading file: {$filePath} as {$fileName}");

        // Menggunakan response()->download() dan hapus file setelah dikirim
        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }
}

// for CI
