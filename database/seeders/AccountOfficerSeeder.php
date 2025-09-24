<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountOfficer;

class AccountOfficerSeeder extends Seeder
{
    public function run(): void
    {
        AccountOfficer::truncate();

        $officers = [
            ['name' => 'Alfonsus Jaconias', 'display_witel' => 'JATIM BARAT', 'filter_witel_lama' => 'MADIUN'],
            ['name' => 'Dwieka Septian', 'display_witel' => 'SURAMADU', 'filter_witel_lama' => 'SURAMADU', 'special_filter_column' => 'segment', 'special_filter_value' => 'LEGS'],
            ['name' => 'Ferizka Paramita', 'display_witel' => 'SURAMADU', 'filter_witel_lama' => 'SURAMADU', 'special_filter_column' => 'segment', 'special_filter_value' => 'SME'],
            ['name' => 'Ibrahim Muhammad', 'display_witel' => 'JATIM TIMUR', 'filter_witel_lama' => 'SIDOARJO'],
            ['name' => 'Ilham Miftahul', 'display_witel' => 'JATIM TIMUR', 'filter_witel_lama' => 'JEMBER'],
            ['name' => 'I Wayan Krisna', 'display_witel' => 'JATIM TIMUR', 'filter_witel_lama' => 'PASURUAN'],
            ['name' => 'Luqman Kurniawan', 'display_witel' => 'JATIM BARAT', 'filter_witel_lama' => 'KEDIRI'],
            ['name' => 'Maria Fransiska', 'display_witel' => 'NUSRA', 'filter_witel_lama' => 'NTT'],
            ['name' => 'Nurtria Iman Sari', 'display_witel' => 'JATIM BARAT', 'filter_witel_lama' => 'MALANG'],
            ['name' => 'Andre Yana Wijaya', 'display_witel' => 'NUSRA', 'filter_witel_lama' => 'NTB'],
            ['name' => 'Diastanto', 'display_witel' => 'BALI', 'filter_witel_lama' => 'BALI'],
        ];

        foreach ($officers as $officerData) {
            // Mengisi default value jika tidak ada
            $officerData['special_filter_column'] = $officerData['special_filter_column'] ?? null;
            $officerData['special_filter_value'] = $officerData['special_filter_value'] ?? null;
            AccountOfficer::create($officerData);
        }
    }
}
