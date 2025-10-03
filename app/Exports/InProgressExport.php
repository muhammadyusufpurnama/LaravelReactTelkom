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
    protected $witel;

    public function __construct(string $segment, int $year, ?string $witel)
    {
        $this->segment = $segment;
        $this->year = $year;
        $this->witel = $witel;
    }

    public function query()
    {
        return DocumentData::query()
            ->where('status_wfm', 'in progress')
            ->where('segment', $this->segment)
            ->whereYear('order_created_date', $this->year)
            ->when($this->witel, function ($query) {
                return $query->where('nama_witel', $this->witel);
            })
            // [FIX] Mengambil kolom 'witel_lama' bukan 'branch'
            ->select('order_id', 'product as product_name', 'nama_witel', 'customer_name', 'milestone', 'order_created_date', 'segment', 'witel_lama')
            ->orderBy('order_created_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'PRODUCT NAME',
            'WITEL',
            'Customer Name',
            'Milestone',
            'Order created date',
            'Segment',
            'Branch', // Header di Excel tetap 'Branch'
        ];
    }

    public function map($row): array
    {
        return [
            $row->order_id,
            $row->product_name,
            $row->nama_witel,
            $row->customer_name,
            $row->milestone,
            \Carbon\Carbon::parse($row->order_created_date)->format('Y/m/d H:i:s A'),
            $row->segment,
            // [FIX] Menggunakan data dari 'witel_lama' untuk kolom Branch
            $row->witel_lama ?? 'N/A',
        ];
    }
}
