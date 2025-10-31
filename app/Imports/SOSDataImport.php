<?php

namespace App\Imports;

use App\Models\SosData; // Anda perlu membuat model ini
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SOSDataImport implements OnEachRow, WithHeadingRow, WithChunkReading, WithEvents, SkipsEmptyRows
{
    private string $batchId;
    private int $totalRows = 0;
    private int $processedRows = 0;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function chunkSize(): int
    {
        return 500; // Proses 500 baris per-query untuk efisiensi memori
    }

    /**
     * Fungsi utama yang dieksekusi untuk setiap baris di file Excel.
     */
    public function onRow(Row $row)
    {
        $this->updateProgress();
        $rowAsArray = $row->toArray();

        // ===================================================================
        // [FILTER 1] Hanya proses WITEL yang diizinkan.
        // ===================================================================
        $billWitel = strtolower(trim($rowAsArray['bill_witel'] ?? ''));
        $allowedWitel = ['bali', 'malang', 'nusa tenggara', 'sidoarjo', 'suramadu'];

        if (!in_array($billWitel, $allowedWitel)) {
            return; // Lewati baris ini jika WITEL tidak ada dalam daftar
        }

        // ===================================================================
        // [FILTER 2] Jangan proses Kategori "BILLING COMPLETED".
        // ===================================================================
        $kategori = strtolower(trim($rowAsArray['kategori'] ?? ''));

        if ($kategori === 'billing completed') {
            return; // Lewati baris ini jika kategori adalah "Billing Completed"
        }

        // Fungsi helper untuk parsing tanggal secara aman
        $parseDate = function ($date) {
            if (empty($date)) {
                return null;
            }
            try {
                return is_numeric($date)
                    ? Carbon::instance(Date::excelToDateTimeObject($date))
                    : Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        // Mapping data dari Excel ke kolom database
        $dataToInsert = [
            'nipnas' => $rowAsArray['nipnas'] ?? null,
            'standard_name' => $rowAsArray['standard_name'] ?? null,
            'order_id' => $rowAsArray['order_id'] ?? null,
            'order_subtype' => $rowAsArray['order_subtype'] ?? null,
            'order_description' => $rowAsArray['order_description'] ?? null,
            'segmen' => $rowAsArray['segmen'] ?? null,
            'sub_segmen' => $rowAsArray['sub_segmen'] ?? null,
            'cust_city' => $rowAsArray['custcity'] ?? null,
            'cust_witel' => $rowAsArray['cust_witel'] ?? null,
            'serv_city' => $rowAsArray['servcity'] ?? null,
            'service_witel' => $rowAsArray['service_witel'] ?? null,
            'bill_witel' => $rowAsArray['bill_witel'] ?? null,
            'li_product_name' => $rowAsArray['li_product_name'] ?? null,
            'li_billdate' => $parseDate($rowAsArray['li_billdate'] ?? null),
            'li_milestone' => $rowAsArray['li_milestone'] ?? null,
            'kategori' => $rowAsArray['kategori'] ?? null,
            'li_status' => $rowAsArray['li_status'] ?? null,
            'li_status_date' => $parseDate($rowAsArray['li_status_date'] ?? null),
            'is_termin' => $rowAsArray['is_termin'] ?? null,
            'biaya_pasang' => is_numeric($rowAsArray['biaya_pasang']) ? $rowAsArray['biaya_pasang'] : 0,
            'hrg_bulanan' => is_numeric($rowAsArray['hrg_bulanan']) ? $rowAsArray['hrg_bulanan'] : 0,
            'revenue' => is_numeric($rowAsArray['revenue']) ? $rowAsArray['revenue'] : 0,
            'order_created_date' => $parseDate($rowAsArray['order_created_date'] ?? null),
            'agree_type' => $rowAsArray['agree_type'] ?? null,
            'agree_start_date' => $parseDate($rowAsArray['agree_start_date'] ?? null),
            'agree_end_date' => $parseDate($rowAsArray['agree_end_date'] ?? null),
            'lama_kontrak_hari' => is_numeric($rowAsArray['lama_kontrak_hari']) ? $rowAsArray['lama_kontrak_hari'] : 0,
            'amortisasi' => $rowAsArray['amortisasi'] ?? null,
            'action_cd' => $rowAsArray['action_cd'] ?? null,
            'kategori_umur' => $rowAsArray['kategori_umur'] ?? null,
            'umur_order' => is_numeric($rowAsArray['umur_order']) ? $rowAsArray['umur_order'] : 0,
        ];

        // Gunakan updateOrCreate untuk menghindari duplikat berdasarkan order_id
        // dan untuk memperbarui data jika order_id sudah ada.
        SosData::updateOrCreate(
            ['order_id' => $dataToInsert['order_id']],
            $dataToInsert
        );
    }

    /**
     * Event listener untuk tracking progress.
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $totalRows = $event->getReader()->getTotalRows();
                $worksheetName = array_key_first($totalRows);
                if (isset($totalRows[$worksheetName])) {
                    $this->totalRows = $totalRows[$worksheetName] - 1; // Kurangi 1 untuk header
                    Cache::put('import_progress_'.$this->batchId, 0, now()->addHour());
                }
            },
        ];
    }

    /**
     * Helper untuk mengupdate progress di Cache.
     */
    private function updateProgress()
    {
        ++$this->processedRows;
        if ($this->totalRows > 0) {
            $progress = round(($this->processedRows / $this->totalRows) * 100);
            if ($progress > Cache::get('import_progress_'.$this->batchId, 0)) {
                Cache::put('import_progress_'.$this->batchId, $progress, now()->addHour());
            }
        }
    }
}
