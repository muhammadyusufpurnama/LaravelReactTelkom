<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GalaksiSosReportExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $galaksiData;
    protected $cutoffDate;

    public function __construct(array $galaksiData, string $cutoffDate)
    {
        $this->galaksiData = $galaksiData;
        $this->cutoffDate = $cutoffDate;
    }

    public function view(): View
    {
        return view('exports.galaksi_sos_report', [
            'galaksiData' => $this->galaksiData,
            'cutoffDate' => $this->cutoffDate,
        ]);
    }

    public function title(): string
    {
        return 'Report Galaksi SOS';
    }
}
