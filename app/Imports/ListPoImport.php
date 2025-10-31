<?php

namespace App\Imports;

use App\Models\ListPo;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading; // WithBatchInserts dihapus
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

// Hapus 'WithBatchInserts' dari sini
class ListPoImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Logika ini sekarang akan berjalan dengan benar untuk setiap baris
        return ListPo::updateOrCreate(
            ['nipnas' => $row['nipnas']], // Kunci unik untuk dicari
            [
                'po' => $row['po'],
                'segment' => $row['segmen'],
                'bill_city' => $row['bill_city'],
                'witel' => $row['witel'],
            ]
        );
    }

    /**
     * Aturan validasi.
     */
    public function rules(): array
    {
        return [
            // Anda mungkin ingin menghapus 'distinct' jika NIPNAS yang sama bisa muncul
            // beberapa kali di Excel dan Anda ingin baris terakhir yang diimpor yang berlaku.
            // Namun jika setiap NIPNAS harus unik di file Excel, biarkan saja.
            'nipnas' => ['required'],
            'po' => ['required', 'string'],
        ];
    }

    // method batchSize() bisa dihapus karena WithBatchInserts sudah tidak digunakan
    // public function batchSize(): int
    // {
    //     return 500;
    // }

    public function chunkSize(): int
    {
        return 500;
    }
}
