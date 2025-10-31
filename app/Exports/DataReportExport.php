<?php

// app/Exports/DataReportExport.php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class DataReportExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $reportDataLegs;
    protected $reportDataSme;
    protected $tableConfig; // Ini untuk SME
    protected $detailsLegs;
    protected $detailsSme;
    protected $period;

    // Pastikan constructor menerima semua argumen
    public function __construct(array $reportDataLegs, array $reportDataSme, array $tableConfig, array $detailsLegs, array $detailsSme, string $period)
    {
        $this->reportDataLegs = $reportDataLegs;
        $this->reportDataSme = $reportDataSme;
        $this->tableConfig = $tableConfig; // Ini adalah config untuk SME
        $this->detailsLegs = $detailsLegs;
        $this->detailsSme = $detailsSme;
        $this->period = \Carbon\Carbon::parse($period)->format('F Y');
    }

    public function view(): View
    {
        // Pastikan nama view benar (misal: 'exports.datareport')
        // dan semua variabel diteruskan dengan nama yang sesuai dengan yang diharapkan di Blade
        return view('exports.datareport', [
            'reportDataLegs' => $this->reportDataLegs,
            'reportDataSme' => $this->reportDataSme,
            'tableConfig' => $this->tableConfig, // Variabel untuk tabel SME
            'detailsLegs' => $this->detailsLegs,
            'detailsSme' => $this->detailsSme,
            'period' => $this->period,
        ]);
    }

    public function title(): string
    {
        return 'Data Report All Segments';
    }
}
