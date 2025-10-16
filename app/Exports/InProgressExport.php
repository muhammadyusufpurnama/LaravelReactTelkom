<?php

// app/Exports/InProgressExport.php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InProgressExport implements FromView, ShouldAutoSize
{
    protected Collection $data;
    protected ?string $witel;

    // Constructor sekarang menerima Collection data, bukan lagi parameter filter
    public function __construct(Collection $data, ?string $witel)
    {
        $this->data = $data;
        $this->witel = $witel;
    }

    // Metode ini akan merender Blade view yang sudah kita buat
    public function view(): View
    {
        return view('exports.inprogress', [
            'data' => $this->data,
            'witel' => $this->witel,
        ]);
    }
}
