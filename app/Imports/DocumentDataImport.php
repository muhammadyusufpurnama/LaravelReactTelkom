<?php

namespace App\Imports;

use App\Models\DocumentData;
use App\Models\OrderProduct;
use App\Models\UpdateLog;
use App\Traits\CalculatesProductPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row; // <-- TAMBAHKAN INI
use PhpOffice\PhpSpreadsheet\Shared\Date; // <-- TAMBAHKAN INI

class DocumentDataImport implements OnEachRow, WithChunkReading, WithEvents, WithHeadingRow, SkipsEmptyRows
{
    use CalculatesProductPrice;

    private string $batchId;
    private bool $isFreshImport;
    private int $totalRows = 0;
    private int $processedRows = 0;
    private array $chunkOrderIds = [];

    public function __construct(string $batchId, bool $isFreshImport)
    {
        $this->batchId = $batchId;
        $this->isFreshImport = $isFreshImport;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                if (!$this->isFreshImport) {
                    DB::table('temp_upload_data')->truncate();
                }

                $totalRows = $event->getReader()->getTotalRows();
                $worksheetName = array_key_first($totalRows);

                if (isset($totalRows[$worksheetName])) {
                    $this->totalRows = $totalRows[$worksheetName] - 1; // Kurangi 1 untuk header
                    Cache::put('import_progress_'.$this->batchId, 0, now()->addHour());
                }
            },

            AfterChunk::class => function (AfterChunk $event) {
                if (!$this->isFreshImport && !empty($this->chunkOrderIds)) {
                    DB::table('temp_upload_data')->insertOrIgnore($this->chunkOrderIds);
                    $this->chunkOrderIds = [];
                }
            },

            AfterImport::class => function (AfterImport $event) {
                // Biarkan kosong. Progres 100% ditangani oleh onRow.
            },
        ];
    }

    public function onRow(Row $row)
    {
        ++$this->processedRows;

        // =======================================================
        // == PENGECEKAN PEMBATALAN BARU (SETIAP 10 BARIS) ==
        // =======================================================
        // Untuk efisiensi, kita hanya mengecek ke database setiap 10 baris
        if ($this->processedRows % 10 === 0) {
            // Cari status batch saat ini
            $batch = Bus::findBatch($this->batchId);

            // Jika batch sudah dibatalkan oleh user
            if ($batch && $batch->cancelled()) {
                Log::warning("Batch [{$this->batchId}]: Pembatalan terdeteksi di baris {$this->processedRows}. Melempar exception untuk menghentikan impor.");

                // Lempar exception untuk menghentikan proses
                // Ini akan ditangkap oleh method `failed()` di Job.
                throw new \Exception("Import cancelled by user at row {$this->processedRows}");
            }
        }
        // =======================================================
        // =======================================================

        // [IMPLEMENTASI DARI KODE LAWAS]
        // Logika ini akan memastikan progress berjalan mulus hingga 100%
        if ($this->totalRows > 0) {
            $progress = 0;
            // Jika ini adalah baris terakhir yang diproses, langsung set 100%
            if ($this->processedRows >= $this->totalRows) {
                $progress = 100;
            }
            // Update secara berkala (misal setiap 10 baris) untuk efisiensi
            elseif ($this->processedRows % 10 === 0) {
                $progress = round(($this->processedRows / $this->totalRows) * 100);
            }

            if ($progress > 0) {
                $lastProgress = Cache::get('import_progress_'.$this->batchId, 0);
                if ($progress > $lastProgress) {
                    Cache::put('import_progress_'.$this->batchId, $progress, now()->addHour());
                }
            }
        }

        // ... (SISA KODE onRow ANDA DI SINI, TIDAK PERLU DIUBAH) ...

        $rowAsArray = $row->toArray();
        $orderIdRaw = $rowAsArray['order_id'] ?? null;
        if (empty($orderIdRaw)) {
            return;
        }

        $orderId = is_string($orderIdRaw) && strtoupper(substr($orderIdRaw, 0, 2)) === 'SC'
            ? substr($orderIdRaw, 2)
            : $orderIdRaw;

        if (empty($orderId)) {
            return;
        }

        if (!$this->isFreshImport) {
            $this->chunkOrderIds[] = ['order_id' => $orderId];
        }

        $productWithOrderId = trim($rowAsArray['product_order_id'] ?? $rowAsArray['product'] ?? '');
        $productValue = '';

        if (!empty($productWithOrderId)) {
            $productValue = str_ends_with($productWithOrderId, (string) $orderId)
                ? trim(substr($productWithOrderId, 0, -strlen((string) $orderId)))
                : trim(str_replace((string) $orderId, '', $productWithOrderId));
        }

        $layanan = trim($rowAsArray['layanan'] ?? '');
        if (in_array(strtolower($productValue), ['kidi']) || stripos($layanan, 'mahir') !== false) {
            return;
        }

        $witel = trim($rowAsArray['nama_witel'] ?? '');
        if (stripos($witel, 'JATENG') !== false) {
            return;
        }

        $segmenN = trim($rowAsArray['segmen_n'] ?? '');
        $segment = (in_array($segmenN, ['RBS', 'SME'])) ? 'SME' : 'LEGS';

        $parseDate = function ($date) {
            if (empty($date)) {
                return null;
            }

            try {
                if (is_numeric($date)) {
                    $dateTimeObject = Date::excelToDateTimeObject($date);

                    return Carbon::instance($dateTimeObject)->format('Y-m-d H:i:s');
                }

                return Carbon::parse($date)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        $netPrice = 0.0;
        $isTemplatePrice = false;
        $excelNetPriceRaw = $rowAsArray['net_price'] ?? null;

        if (is_numeric($excelNetPriceRaw) && (float) $excelNetPriceRaw > 0) {
            $netPrice = (float) $excelNetPriceRaw;
        } else {
            $netPrice = $this->calculatePrice($productValue, $segment, $witel);
            $isTemplatePrice = $netPrice > 0;
        }

        $existingRecord = DocumentData::where('order_id', $orderId)->first();
        $milestoneValue = trim($rowAsArray['milestone'] ?? '');
        $status_wfm = 'in progress';
        $doneMilestones = ['completed', 'complete', 'baso started', 'fulfill billing complete'];

        if ($milestoneValue && stripos($milestoneValue, 'QC') !== false) {
            $status_wfm = '';
        } elseif ($milestoneValue && in_array(strtolower($milestoneValue), $doneMilestones)) {
            $status_wfm = 'done close bima';
        }

        if ($existingRecord && $existingRecord->status_wfm !== $status_wfm) {
            UpdateLog::create([
                'order_id' => $orderId,
                'product_name' => $existingRecord->product_name ?? $existingRecord->product,
                'customer_name' => $existingRecord->customer_name,
                'nama_witel' => $existingRecord->nama_witel,
                'status_lama' => $existingRecord->status_wfm,
                'status_baru' => $status_wfm,
                'sumber_update' => 'Upload Data Mentah',
            ]);
        }

        $weekValue = $rowAsArray['week'] ?? null;
        $parsedWeekDate = $parseDate($weekValue);

        $newData = [
            'batch_id' => $this->batchId,
            'order_id' => $orderId,
            'product' => $productValue,
            'net_price' => $netPrice,
            'is_template_price' => $isTemplatePrice,
            'milestone' => $milestoneValue,
            'segment' => $segment,
            'nama_witel' => $witel,
            'status_wfm' => $status_wfm,
            'products_processed' => false,
            'channel' => ($rowAsArray['channel'] ?? null) === 'hsi' ? 'SC-One' : ($rowAsArray['channel'] ?? null),
            'filter_produk' => $rowAsArray['filter_produk'] ?? null,
            'witel_lama' => $rowAsArray['witel'] ?? null,
            'layanan' => $layanan,
            'order_date' => $parseDate($rowAsArray['order_date'] ?? null),
            'order_status' => $rowAsArray['order_status'] ?? null,
            'order_sub_type' => $rowAsArray['order_subtype'] ?? null,
            'order_status_n' => $rowAsArray['order_status_n'] ?? null,
            'customer_name' => $rowAsArray['customer_name'] ?? null,
            'tahun' => $rowAsArray['tahun'] ?? null,
            'telda' => trim($rowAsArray['telda'] ?? ''),
            'week' => $parsedWeekDate ? Carbon::parse($parsedWeekDate)->weekOfYear : null,
            'order_created_date' => $parseDate($rowAsArray['order_created_date'] ?? null),
            'previous_milestone' => $existingRecord && $existingRecord->milestone !== $milestoneValue
                ? $existingRecord->milestone
                : ($existingRecord ? $existingRecord->previous_milestone : null),
        ];

        DocumentData::updateOrCreate(['order_id' => $orderId], $newData);

        if ($productValue && str_contains($productValue, '-')) {
            OrderProduct::where('order_id', $orderId)->delete();
            $individualProducts = explode('-', $productValue);

            foreach ($individualProducts as $pName) {
                $pName = trim($pName);
                if (empty($pName)) {
                    continue;
                }

                OrderProduct::create([
                    'order_id' => $orderId,
                    'product_name' => $pName,
                    'net_price' => $this->calculatePrice($pName, $segment, $witel),
                    'status_wfm' => $status_wfm,
                    'channel' => ($rowAsArray['channel'] ?? null) === 'hsi' ? 'SC-One' : ($rowAsArray['channel'] ?? null),
                ]);
            }
        }
    }
}
