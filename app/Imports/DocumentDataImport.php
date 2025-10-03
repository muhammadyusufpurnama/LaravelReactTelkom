<?php

namespace App\Imports;

use App\Models\DocumentData;
use App\Models\OrderProduct;
use App\Traits\CalculatesProductPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;

// SOLUSI FINAL V3: Menggunakan kolom "Product + Order Id" sebagai sumber data utama.
class DocumentDataImport implements OnEachRow, WithChunkReading, WithEvents, WithUpserts, WithHeadingRow
{
    use CalculatesProductPrice;

    private string $batchId;
    private int $totalRows = 0;
    private int $processedRows = 0;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function uniqueBy()
    {
        return 'order_id';
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $totalRows = $event->getReader()->getTotalRows();
                $worksheetName = array_key_first($totalRows);

                if (isset($totalRows[$worksheetName])) {
                    $this->totalRows = $totalRows[$worksheetName] - 1;
                    Log::info("Batch [{$this->batchId}]: Ditemukan total {$this->totalRows} baris untuk diproses.");
                } else {
                    Log::warning("Batch [{$this->batchId}]: Tidak dapat menghitung total baris.");
                }
            },
            AfterChunk::class => function (AfterChunk $event) {
                if ($this->totalRows > 0) {
                    $percentage = round(($this->processedRows / $this->totalRows) * 100);
                    $percentage = min($percentage, 100);
                    Cache::put('import_progress_' . $this->batchId, $percentage, now()->addMinutes(30));
                    Log::info("Batch [{$this->batchId}]: Progres: {$percentage}% ({$this->processedRows} dari {$this->totalRows} baris).");
                }
            },
        ];
    }

    public function onRow(Row $row)
    {
        $this->processedRows++;
        $rowAsArray = $row->toArray();

        // Ambil Order ID terlebih dahulu
        $orderIdRaw = $rowAsArray['order_id'] ?? null;
        if (empty($orderIdRaw)) return;

        $orderId = is_string($orderIdRaw) && strtoupper(substr($orderIdRaw, 0, 2)) === 'SC' ? substr($orderIdRaw, 2) : $orderIdRaw;
        if (empty($orderId)) return;

        // ======================== LOGIKA BARU BERDASARKAN SARAN ANDA ========================
        // Maatwebsite akan mengubah header "Product + Order Id" menjadi "product_order_id"
        $productWithOrderId = trim($rowAsArray['product_order_id'] ?? '');

        $productValue = '';
        if (!empty($productWithOrderId)) {
            // Hapus Order ID dari akhir string untuk mendapatkan nama produk murni
            if (str_ends_with($productWithOrderId, (string)$orderId)) {
                 $productValue = trim(substr($productWithOrderId, 0, -strlen((string)$orderId)));
            } else {
                // Fallback jika formatnya tidak terduga, meskipun seharusnya tidak terjadi
                $productValue = trim(str_replace((string)$orderId, '', $productWithOrderId));
                Log::warning("Batch [{$this->batchId}]: Format 'Product + Order Id' tidak terduga untuk Order ID {$orderId}.");
            }
        }
        // ===================================================================================

        // Jika setelah semua logika, productValue masih kosong, log sebagai warning
        if (empty($productValue)) {
            Log::warning("Batch [{$this->batchId}]: Gagal mengekstrak nama produk untuk Order ID {$orderId}. Nilai kolom 'Product + Order Id' adalah '{$productWithOrderId}'.");
        }

        // Ambil data lain yang diperlukan
        $layanan = trim($rowAsArray['layanan'] ?? '');
        $teldaValue = trim($rowAsArray['telda'] ?? '');

        // ... Sisa kode Anda dari sini ke bawah tidak perlu diubah ...

        $isPijarMahir = !empty($layanan) && stripos($layanan, 'mahir') !== false;
        if ($isPijarMahir) {
            if (str_contains($productValue, '-')) {
                $products = explode('-', $productValue);
                $validProducts = array_filter($products, function ($product) {
                    return stripos(trim($product), 'pijar') === false;
                });
                if (empty($validProducts)) {
                    return;
                }
                $productValue = implode('-', $validProducts);
            } else {
                return;
            }
        }

        if (in_array(strtolower($productValue), ['kidi'])) {
            return;
        }

        $milestoneValue = trim($rowAsArray['milestone'] ?? '');
        $segmenN = trim($rowAsArray['segmen_n'] ?? '');
        $segment = (in_array($segmenN, ['RBS', 'SME'])) ? 'SME' : 'LEGS';
        $witel = trim($rowAsArray['nama_witel'] ?? '');

        if (stripos($witel, 'JATENG') !== false) {
            return;
        }

        $existingRecord = DocumentData::where('order_id', $orderId)->first();
        $excelNetPrice = is_numeric($rowAsArray['net_price'] ?? null) ? (float) $rowAsArray['net_price'] : 0;

        if ($excelNetPrice > 0) {
            $netPrice = $excelNetPrice;
        } elseif ($existingRecord && $existingRecord->net_price > 0) {
            $netPrice = $existingRecord->net_price;
        } else {
            $netPrice = $this->calculatePrice($productValue, $segment, $witel);
        }

        $parseDate = function ($date) {
            if (empty($date)) return null;
            if (is_numeric($date)) return Carbon::createFromTimestamp(($date - 25569) * 86400)->format('Y-m-d H:i:s');
            try {
                return Carbon::parse($date)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        $status_wfm = 'in progress';
        if ($milestoneValue && stripos($milestoneValue, 'QC') !== false) {
            $status_wfm = '';
        } else {
            $doneMilestones = ['completed', 'complete', 'baso started', 'fulfill billing complete'];
            if ($milestoneValue && in_array(strtolower($milestoneValue), $doneMilestones)) {
                $status_wfm = 'done close bima';
            }
        }

        $newData = [
            'batch_id'           => $this->batchId,
            'order_id'           => $orderId,
            'product'            => $productValue,
            'net_price'          => $netPrice,
            'milestone'          => $milestoneValue,
            'previous_milestone' => null,
            'segment'            => $segment,
            'nama_witel'         => $witel,
            'status_wfm'         => $status_wfm,
            'products_processed' => false,
            'channel'            => ($rowAsArray['channel'] ?? null) === 'hsi' ? 'SC-One' : ($rowAsArray['channel'] ?? null),
            'filter_produk'      => $rowAsArray['filter_produk'] ?? null,
            'witel_lama'         => $rowAsArray['witel'] ?? null,
            'layanan'            => $layanan,
            'order_date'         => $parseDate($rowAsArray['order_date'] ?? null),
            'order_status'       => $rowAsArray['order_status'] ?? null,
            'order_sub_type'     => $rowAsArray['order_subtype'] ?? null,
            'order_status_n'     => $rowAsArray['order_status_n'] ?? null,
            'customer_name'      => $rowAsArray['customer_name'] ?? null,
            'tahun'              => $rowAsArray['tahun'] ?? null,
            'telda'              => $teldaValue,
            'week'               => !empty($rowAsArray['week']) ? Carbon::parse($rowAsArray['week'])->weekOfYear : null,
            'order_created_date' => $parseDate($rowAsArray['order_created_date'] ?? null),
        ];

        if ($existingRecord && $existingRecord->milestone !== $newData['milestone']) {
            $newData['previous_milestone'] = $existingRecord->milestone;
        } else if (!$existingRecord) {
            $newData['previous_milestone'] = null;
        }

        DocumentData::updateOrCreate(
            ['order_id' => $orderId],
            $newData
        );

        if ($productValue && str_contains($productValue, '-')) {
            OrderProduct::where('order_id', $orderId)->delete();
            $individualProducts = explode('-', $productValue);

            foreach ($individualProducts as $pName) {
                $pName = trim($pName);
                if (empty($pName)) continue;

                $individualPrice = $this->calculatePrice($pName, $segment, $witel);

                OrderProduct::create([
                    'order_id'     => $orderId,
                    'product_name' => $pName,
                    'net_price'    => $individualPrice,
                    'status_wfm'   => $status_wfm,
                    'channel'      => ($rowAsArray['channel'] ?? null) === 'hsi' ? 'SC-One' : ($rowAsArray['channel'] ?? null),
                ]);
            }
        }
    }
}

