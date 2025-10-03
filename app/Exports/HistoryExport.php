<?php

namespace App\Exports;

use App\Models\UpdateLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return UpdateLog::query()->latest();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Waktu Update',
            'Order ID',
            'Customer',
            'Witel',
            'Status Lama',
            'Status Baru',
            'Sumber Update',
        ];
    }

    /**
     * @param UpdateLog $log
     * @return array
     */
    public function map($log): array
    {
        return [
            $log->created_at->format('d/m/Y H:i:s'),
            $log->order_id,
            $log->customer_name,
            $log->nama_witel,
            $log->status_lama,
            $log->status_baru,
            $log->sumber_update,
        ];
    }
}
