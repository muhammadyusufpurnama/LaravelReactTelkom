<?php

namespace App\Jobs;

use App\Models\DocumentData;
use App\Models\OrderProduct;
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

    public function handle(): void
    {
        Log::info("Memulai Job: Memproses Produk Bundling...");
        try {
            DB::transaction(function () {
                // Ambil semua data yang produknya belum diproses
                DocumentData::where('products_processed', false)
                    ->chunkById(100, function ($orders) {
                        foreach ($orders as $order) {
                            $productString = $order->product ?? '';
                            $normalizedString = str_replace(["\r\n", "\n", "\r"], '-', $productString);

                            if (str_contains($normalizedString, '-')) {
                                OrderProduct::where('order_id', $order->order_id)->delete();
                                $productNames = array_filter(array_map('trim', explode('-', $normalizedString)));

                                foreach ($productNames as $productName) {
                                    if (empty($productName) || stripos($productName, 'kidi') !== false || (stripos($order->layanan ?? '', 'mahir') !== false && stripos($productName, 'pijar') !== false)) {
                                        continue;
                                    }
                                    OrderProduct::create([
                                        'order_id'     => $order->order_id,
                                        'product_name' => $productName,
                                        'net_price'    => $this->calculateProductPrice($productName, $order),
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

    private function calculateProductPrice(string $productName, DocumentData $order): int
    {
        $witel = strtoupper(trim($order->nama_witel ?? ''));
        $segment = strtoupper(trim($order->segment ?? ''));

        switch (strtolower(trim($productName))) {
            case 'netmonk': return ($segment === 'LEGS') ? 26100 : (($witel === 'BALI') ? 26100 : 21600);
            case 'oca': return ($segment === 'LEGS') ? 104000 : (($witel === 'NUSA TENGGARA') ? 104000 : 103950);
            case 'antares eazy': return 35000;
            case 'pijar sekolah': return 582750;
            default: return 0;
        }
    }
}
