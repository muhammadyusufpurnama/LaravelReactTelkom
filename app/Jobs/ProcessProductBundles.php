<?php

namespace App\Jobs;

use App\Models\DocumentData;
use App\Models\OrderProduct;
use App\Traits\CalculatesProductPrice; // <-- 1. Tambahkan use statement untuk Trait
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessProductBundles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use CalculatesProductPrice; // <-- 2. Gunakan Trait di dalam kelas

    public function handle(): void
    {
        Log::info("Memulai Job: Memproses Produk Bundling...");
        try {
            DB::transaction(function () {
                DocumentData::where('products_processed', false)
                    ->chunkById(100, function ($orders) {
                        foreach ($orders as $order) {
                            $productString = $order->product ?? '';
                            if (str_contains($productString, '-')) {
                                OrderProduct::where('order_id', $order->order_id)->delete();
                                $productNames = array_filter(array_map('trim', explode('-', $productString)));

                                foreach ($productNames as $productName) {
                                    // ... (filter produk Anda) ...
                                    OrderProduct::create([
                                        'order_id'     => $order->order_id,
                                        'product_name' => $productName,
                                        // 3. Panggil metode dari Trait
                                        'net_price'    => $this->calculatePrice($productName, $order->segment, $order->nama_witel),
                                        'channel'      => $order->channel,
                                        'status_wfm'   => $order->status_wfm,
                                    ]);
                                }
                            }
                            $order->updateQuietly(['products_processed' => true]);
                        }
                    });
            });
            Log::info("Selesai: Memproses Produk Bundling berhasil.");
        } catch (\Exception $e) {
            Log::error("GAGAL saat memproses produk bundling: " . $e->getMessage());
            $this->fail($e);
        }
    }

    // 4. HAPUS FUNGSI calculateProductPrice() YANG LAMA DARI FILE INI
}
