<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListPoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel sebelum diisi untuk menghindari duplikasi
        DB::table('list_po')->truncate();

        // Path ke file CSV Anda
        $csvFile = database_path('seeders/csv/list po.csv');

        // Baca file CSV, lewati baris header
        $data = array_map('str_getcsv', file($csvFile));
        $header = array_shift($data);

        foreach ($data as $row) {
            DB::table('list_po')->insert([
                'nipnas' => $row[0] ?? null,
                'po' => $row[1] ?? null,
                'segment' => $row[2] ?? null,
                'bill_city' => $row[3] ?? null,
                'witel' => $row[4] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
