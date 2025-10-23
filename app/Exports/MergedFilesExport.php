<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
// Impor ini untuk mendapatkan nama kolom terakhir secara dinamis
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class MergedFilesExport implements FromCollection, WithHeadings, WithEvents
{
    use Exportable;

    protected $files;
    protected $actualHeadings = []; // Simpan heading di sini
    protected $boldStartRows = []; // <-- MODIFIKASI: Properti baru untuk menyimpan baris yang akan di-bold

    public function __construct(array $files)
    {
        $this->files = $files;
        $this->prepareData(); // Panggil method persiapan di constructor
    }

    /**
     * Method untuk mempersiapkan heading dan data sekali saja.
     */
    protected function prepareData()
    {
        Log::info('Preparing headings from first file');
        if (empty($this->files)) {
            return;
        }

        try {
            $firstFile = $this->files[0];
            $collection = $this->readFile($firstFile['path'], $firstFile['extension']);

            if (!$collection->isEmpty()) {
                // Ambil baris pertama sebagai heading, pastikan ini adalah array
                $this->actualHeadings = $collection->first()->toArray();
                Log::info('Headings prepared: '.json_encode($this->actualHeadings));
            } else {
                Log::warning('First file is empty, using default headings.');
                $this->actualHeadings = ['No headers found'];
            }
        } catch (\Exception $e) {
            Log::error('Error preparing headings: '.$e->getMessage());
            $this->actualHeadings = ['Error reading headers from first file'];
        }
    }

    /**
     * Kembalikan headings yang sudah disiapkan.
     */
    public function headings(): array
    {
        return $this->actualHeadings;
    }

    /**
     * Bangun koleksi data dari semua file.
     */
    public function collection(): Collection
    {
        Log::info('Starting collection merge with '.count($this->files).' files');
        $mergedData = new Collection();
        $currentRow = 2; // <-- MODIFIKASI: Mulai melacak baris Excel, dimulai dari 2 (setelah header)

        foreach ($this->files as $index => $file) {
            try {
                Log::info("Processing file {$index}: {$file['original_name']}");

                // <-- MODIFIKASI: Tandai baris awal untuk file kedua dan seterusnya -->
                if ($index > 0) { // Hanya untuk file kedua, ketiga, dst.
                    $this->boldStartRows[] = $currentRow; // Catat nomor baris awal
                    Log::info("Marking row {$currentRow} for bolding (start of file: {$file['original_name']})");
                }
                // <-- Akhir Modifikasi -->

                $collection = $this->readFile($file['path'], $file['extension']);

                if ($collection->isEmpty()) {
                    Log::warning("File {$file['original_name']} is empty");
                    continue;
                }

                // Selalu lewati baris pertama (header) dari setiap file
                $dataWithoutHeader = $collection->slice(1)->map(function ($row) {
                    return $row->toArray();
                });

                if ($dataWithoutHeader->isNotEmpty()) {
                    $mergedData = $mergedData->concat($dataWithoutHeader);
                    // <-- MODIFIKASI: Tambahkan jumlah baris yang baru saja dimasukkan ke counter
                    $currentRow += $dataWithoutHeader->count();
                }
            } catch (\Exception $e) {
                Log::error("Error processing file {$file['original_name']}: ".$e->getMessage());
                $errorRow = array_fill(0, count($this->actualHeadings), '');
                $errorRow[0] = "ERROR: Cannot read file {$file['original_name']}";
                $mergedData->push($errorRow);
                // <-- MODIFIKASI: Naikkan juga counter untuk baris error
                ++$currentRow;
            }
        }

        Log::info("Merge completed. Total rows in merged data: {$mergedData->count()}");

        return $mergedData;
    }

    /**
     * Baca file dan kembalikan sebagai Collection.
     */
    protected function readFile(string $filePath, string $extension): Collection
    {
        try {
            Log::info("Reading file: {$filePath} with extension: {$extension}");
            $readerType = match (strtolower($extension)) {
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                'xls' => \Maatwebsite\Excel\Excel::XLS,
                default => \Maatwebsite\Excel\Excel::XLSX,
            };

            $collection = Excel::toCollection(null, $filePath, null, $readerType);

            if ($collection->isEmpty() || $collection->first()->isEmpty()) {
                Log::warning("Empty collection for file: {$filePath}");

                return new Collection();
            }

            return $collection->first();
        } catch (\Exception $e) {
            Log::error("Failed to read file {$filePath}: ".$e->getMessage());
            throw new \Exception('Cannot process file: '.basename($filePath));
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // <-- MODIFIKASI: Dapatkan kolom terakhir secara dinamis -->
                $lastColumnLetter = Coordinate::stringFromColumnIndex(count($this->actualHeadings));

                // 1. Bold header row (sekarang dinamis)
                $headerRange = "A1:{$lastColumnLetter}1";
                Log::info("Bolding header range: {$headerRange}");
                $event->sheet->getStyle($headerRange)->getFont()->setBold(true);

                // 2. Bold 10 baris pertama untuk setiap file baru
                Log::info('Applying bold style to marked file-start rows.');
                foreach ($this->boldStartRows as $startRow) {
                    $endRow = $startRow + 9; // Terapkan untuk 10 baris (misal: 819 s/d 828)
                    $range = "A{$startRow}:{$lastColumnLetter}{$endRow}";

                    Log::info("Bolding range for new file: {$range}");
                    $event->sheet->getStyle($range)->getFont()->setBold(true);
                }
            },
        ];
    }
}
