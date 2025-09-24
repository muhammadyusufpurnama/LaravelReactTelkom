<?php

namespace App\Imports;

use App\Models\DocumentData;
use App\Traits\CalculatesProductPrice;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

class DocumentDataImport implements ToModel, WithUpserts, WithStartRow
{
    use CalculatesProductPrice;

    private $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function startRow(): int
    {
        return 2; // Mulai membaca dari baris ke-2
    }

    public function uniqueBy()
    {
        return 'order_id';
    }

    public function model(array $row)
    {
        // Menggunakan Indeks Kolom (0 = A, 1 = B, 2 = C, dst.)
        $orderIdRaw = $row[9] ?? null; // Kolom J
        if (empty($orderIdRaw)) {
            return null;
        }
        $orderId = is_string($orderIdRaw) && strtoupper(substr($orderIdRaw, 0, 2)) === 'SC'
            ? substr($orderIdRaw, 2) : $orderIdRaw;
        if (empty($orderId)) {
            return null;
        }

        $parseDate = function($date) {
            if (empty($date)) return null;
            if (is_numeric($date)) {
                return Carbon::createFromTimestamp(($date - 25569) * 86400)->format('Y-m-d H:i:s');
            }
            try { return Carbon::parse($date)->format('Y-m-d H:i:s'); } catch (\Exception $e) { return null; }
        };

        // Memetakan semua data menggunakan indeks kolom
        $productValue = $row[0] ?? null;
        $milestoneValue = $row[24] ?? null;
        $segmenN = $row[36] ?? null;
        $segment = (in_array($segmenN, ['RBS', 'SME'])) ? 'SME' : 'LEGS';
        $witel = $row[7] ?? null;
        $channel = $row[2] ?? null;

        if ($milestoneValue && stripos($milestoneValue, 'QC') !== false) {
            $status_wfm = '';
        } else {
            $status_wfm = 'in progress';
            $doneMilestones = ['completed', 'complete', 'baso started', 'fulfill billing complete'];
            if ($milestoneValue && in_array(strtolower(trim($milestoneValue)), $doneMilestones)) {
                $status_wfm = 'done close bima';
            }
        }

        $netPrice = is_numeric($row[26] ?? null) ? (float) $row[26] : 0;
        if ($netPrice <= 0) {
            $netPrice = $this->calculatePrice($productValue, $segment, $witel);
        }

        return new DocumentData([
            'batch_id'           => $this->batchId,
            'order_id'           => $orderId,
            'product'            => $productValue,
            'milestone'          => $milestoneValue,
            'segment'            => $segment,
            'net_price'          => $netPrice,
            'channel'            => ($channel === 'hsi') ? 'SC-One' : $channel,
            'filter_produk'      => $row[3] ?? null,
            'witel_lama'         => $row[11] ?? null,
            'layanan'            => $row[23] ?? null,
            'order_date'         => $parseDate($row[4] ?? null),
            'order_status'       => $row[5] ?? null,
            'order_sub_type'     => $row[6] ?? null,
            'order_status_n'     => $row[27] ?? null,
            'nama_witel'         => $witel,
            'customer_name'      => $row[19] ?? null,
            'tahun'              => $row[39] ?? null,
            'telda'              => $row[41] ?? null,
            'week'               => !empty($row[42]) ? Carbon::parse($row[42])->weekOfYear : null,
            'order_created_date' => $parseDate($row[8] ?? null),
            'status_wfm'         => $status_wfm,
            'products_processed' => false,
        ]);
    }
}
