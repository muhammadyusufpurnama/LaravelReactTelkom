<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountOfficerSeeder extends Seeder
{
    public function run(): void
    {
        $officers = [
            [
                'name' => 'Alfonsus Jaconias',
                'display_witel' => 'JATIM BARAT',
                'filter_witel_lama' => 'madiun',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Dwieka Septian',
                'display_witel' => 'SURAMADU',
                'filter_witel_lama' => 'suramadu',
                'special_filter_column' => 'segment', // Dipecah menjadi 2 kolom
                'special_filter_value' => 'LEGS'      // Dipecah menjadi 2 kolom
            ],
            [
                'name' => 'Ferizka Paramita',
                'display_witel' => 'SURAMADU',
                'filter_witel_lama' => 'suramadu',
                'special_filter_column' => 'segment', // Dipecah menjadi 2 kolom
                'special_filter_value' => 'SME'       // Dipecah menjadi 2 kolom
            ],
            [
                'name' => 'Ibrahim Muhammad',
                'display_witel' => 'JATIM TIMUR',
                'filter_witel_lama' => 'sidoarjo',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Ilham Miftahul',
                'display_witel' => 'JATIM TIMUR',
                'filter_witel_lama' => 'jember',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'I Wayan Krisna',
                'display_witel' => 'JATIM TIMUR',
                'filter_witel_lama' => 'pasuruan',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Luqman Kurniawan',
                'display_witel' => 'JATIM BARAT',
                'filter_witel_lama' => 'kediri',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Maria Fransiska',
                'display_witel' => 'NUSRA',
                'filter_witel_lama' => 'ntt',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Nurtria Iman Sari',
                'display_witel' => 'JATIM BARAT',
                'filter_witel_lama' => 'malang',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Andre Yana Wijaya',
                'display_witel' => 'NUSRA',
                'filter_witel_lama' => 'ntb',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
            [
                'name' => 'Diastanto',
                'display_witel' => 'BALI',
                'filter_witel_lama' => 'bali',
                'special_filter_column' => null,
                'special_filter_value' => null
            ],
        ];

        foreach ($officers as $officer) {
            \App\Models\AccountOfficer::create($officer);
        }
    }
}
