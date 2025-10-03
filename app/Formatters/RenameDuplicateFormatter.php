<?php

namespace App\Formatters;

use Maatwebsite\Excel\Concerns\WithCustomHeadingRowFormatter;

class RenameDuplicateFormatter implements WithCustomHeadingRowFormatter
{
    public function format(array $headings): array
    {
        // Hitung berapa kali setiap nama header muncul
        $counts = array_count_values($headings);
        $newHeadings = [];
        $processedCounts = [];

        // Loop melalui setiap header asli
        foreach ($headings as $heading) {
            // Jika header ini duplikat (muncul lebih dari 1 kali)
            if ($counts[$heading] > 1) {
                // Lacak sudah berapa kali kita memproses header ini
                $processedCounts[$heading] = ($processedCounts[$heading] ?? 0) + 1;

                // Untuk kemunculan pertama, biarkan nama aslinya.
                // Untuk kemunculan berikutnya, tambahkan "_2", "_3", dst.
                if ($processedCounts[$heading] > 1) {
                    $newHeadings[] = $heading . '_' . $processedCounts[$heading];
                } else {
                    $newHeadings[] = $heading; // Kemunculan pertama
                }
            } else {
                // Jika header unik, langsung tambahkan
                $newHeadings[] = $heading;
            }
        }

        return $newHeadings;
    }
}
