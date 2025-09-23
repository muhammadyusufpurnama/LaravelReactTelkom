<?php

namespace App\Observers;

use App\Models\DocumentData;
use App\Models\OrderProduct;

class DocumentDataObserver
{
    /**
     * Handle the DocumentData "saved" event.
     * Metode ini akan berjalan setiap kali record DocumentData dibuat atau diperbarui.
     */
    public function saved(DocumentData $documentData): void
    {
        $productString = $documentData->product ?? '';
        $normalizedString = str_replace(["\r\n", "\n", "\r"], '-', $productString);

        // Hanya proses jika nama produk mengandung '-' (tanda bundling)
        if (str_contains($normalizedString, '-')) {
            // Hapus data produk bundling yang lama untuk order ini (jika ada)
            OrderProduct::where('order_id', $documentData->order_id)->delete();

            $productNames = array_filter(array_map('trim', explode('-', $normalizedString)));

            foreach ($productNames as $productName) {
                // Logika filter Anda yang sudah ada
                if (
                    empty($productName) ||
                    stripos($productName, 'kidi') !== false ||
                    (stripos($documentData->layanan ?? '', 'mahir') !== false && stripos($productName, 'pijar') !== false)
                ) {
                    continue;
                }

                OrderProduct::create([
                    'order_id'     => $documentData->order_id,
                    'product_name' => $productName,
                    'net_price'    => $this->calculateProductPrice($productName, $documentData),
                    'channel'      => $documentData->channel,
                    'status_wfm'   => $documentData->status_wfm,
                ]);
            }
        }

        // Tandai bahwa produk sudah diproses (opsional, tapi praktik yang baik)
        // Kita gunakan quiet() agar tidak memicu event 'saved' lagi dan menyebabkan loop tak terbatas.
        $documentData->products_processed = true;
        $documentData->saveQuietly();
    }

    /**
     * Metode kalkulasi harga yang kita pindahkan ke sini.
     */
    private function calculateProductPrice(string $productName, DocumentData $order): int
    {
        $witel = strtoupper(trim($order->nama_witel ?? ''));
        $segment = strtoupper(trim($order->segment ?? ''));

        switch (strtolower(trim($productName))) {
            case 'netmonk':
                return ($segment === 'LEGS') ? 26100 : (($witel === 'BALI') ? 26100 : 21600);
            case 'oca':
                return ($segment === 'LEGS') ? 104000 : (($witel === 'NUSA TENGGARA') ? 104000 : 103950);
            case 'antares eazy':
                return 35000;
            case 'pijar sekolah':
                return 582750;
            default:
                return 0;
        }
    }

    /**
     * Handle the DocumentData "deleted" event.
     */
    public function deleted(DocumentData $documentData): void
    {
        // Jika suatu saat Anda menghapus DocumentData, kita juga hapus OrderProduct terkait
        OrderProduct::where('order_id', $documentData->order_id)->delete();
    }
}
