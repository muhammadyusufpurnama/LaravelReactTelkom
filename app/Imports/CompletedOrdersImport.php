<?php

namespace App\Imports;

use App\Models\CompletedOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class CompletedOrdersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Ambil semua 'order_id' dari file, buang yang kosong/null, dan ambil yang unik saja.
        $orderIds = $rows->pluck('order_id')->filter()->unique();

        if ($orderIds->isEmpty()) {
            Log::warning('Tidak ada order_id yang valid ditemukan di file order complete.');
            return;
        }

        // 2. Ubah koleksi order_id menjadi format array yang siap untuk di-insert.
        $dataToInsert = $orderIds->map(function ($orderId) {
            return [
                'order_id' => trim($orderId), // Tambahkan trim untuk kebersihan data
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        // 3. Gunakan 'upsert'. Masukkan data baru, abaikan jika 'order_id' sudah ada.
        CompletedOrder::upsert($dataToInsert, ['order_id']);

        Log::info('Berhasil mengimpor ' . count($dataToInsert) . ' order_id unik ke tabel completed_orders.');
    }
}
