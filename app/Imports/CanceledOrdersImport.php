<?php

namespace App\Imports;

use App\Models\CanceledOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class CanceledOrdersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Ambil 'order_id', buang yang kosong, DAN HAPUS SEMUA DUPLIKAT.
        $orderIds = $rows->pluck('order_id')
                         ->filter()
                         ->unique(); // <--- INI ADALAH KUNCI PERBAIKANNYA

        if ($orderIds->isEmpty()) {
            Log::warning('Tidak ada order_id yang valid ditemukan di file order cancel.');
            return;
        }

        // 2. Siapkan data yang sudah unik untuk dimasukkan ke database.
        $dataToInsert = $orderIds->map(function ($orderId) {
            return [
                'order_id' => trim($orderId),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        // 3. Gunakan 'upsert' yang aman untuk memasukkan data.
        CanceledOrder::upsert($dataToInsert, ['order_id']);

        Log::info('Berhasil mengimpor ' . count($dataToInsert) . ' order_id UNIK ke tabel canceled_orders.');
    }
}
