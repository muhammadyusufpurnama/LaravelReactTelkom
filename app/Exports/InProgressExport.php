<?php

namespace App\Exports;

use App\Models\DocumentData;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InProgressExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $segment;
    protected $year;
    protected $rowNumber = 0;

    // Terima filter dari controller
    public function __construct(string $segment, int $year)
    {
        $this->segment = $segment;
        $this->year = $year;
    }

    /**
     * Kueri ini mengambil data dari database.
     * PASTIKAN kueri ini sama dengan yang ada di controller Anda untuk data 'In Progress'.
     */
    public function query()
    {
        return DocumentData::query()
            ->where('status_wfm', 'in progress')
            ->where('segment', $this->segment)
            ->whereYear('order_created_date', $this->year)
            ->select('milestone', 'segment', 'order_status_n', 'product as product_name', 'order_id', 'nama_witel', 'customer_name', 'order_created_date')
            ->orderBy('order_created_date', 'desc');
    }

    /**
     * Menentukan header untuk kolom di file Excel.
     */
    public function headings(): array
    {
        return [
            'No.',
            'Milestone',
            'Segment',
            'Status Order',
            'Product Name',
            'Order ID',
            'Witel',
            'Customer Name',
            'Order Created Date',
        ];
    }

    /**
     * Memetakan data dari setiap baris untuk ditampilkan di Excel.
     * @param \App\Models\DocumentData $row
     */
    public function map($row): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $row->milestone,
            $row->segment,
            $row->order_status_n,
            $row->product_name,
            $row->order_id,
            $row->nama_witel,
            $row->customer_name,
            // Format tanggal agar mudah dibaca di Excel
            \Carbon\Carbon::parse($row->order_created_date)->format('d-m-Y H:i:s'),
        ];
    }
}
