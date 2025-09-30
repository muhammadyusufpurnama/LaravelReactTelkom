<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountOfficer;
use App\Models\DocumentData; // Pastikan Anda punya model ini
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ActionBasedController extends Controller
{
    public function index(Request $request)
    {
        $selectedWitel = $request->input('witel', 'ALL');
        $threeBillion = 3000000000;

        // Ambil daftar Account Officer berdasarkan filter witel
        $officersQuery = AccountOfficer::query();
        if ($selectedWitel !== 'ALL') {
            $officersQuery->where('display_witel', $selectedWitel);
        }
        $officers = $officersQuery->orderBy('name')->get();

        // Siapkan array untuk menampung data tabel
        $tableDataLessThan3Bln = [];
        $tableDataMoreThan3Bln = [];

        // Asumsi: Kolom untuk nilai proyek adalah 'nilai_total_deal'
        // Asumsi: Kolom untuk menghubungkan officer adalah 'witel_correct' di document_data
        //          dan 'filter_witel_lama' di account_officers.
        foreach ($officers as $officer) {
            $baseQuery = DocumentData::where('witel_correct', $officer->filter_witel_lama);

            // Terapkan filter khusus jika ada (sesuai seeder Anda)
            if ($officer->special_filter_column && $officer->special_filter_value) {
                $baseQuery->where($officer->special_filter_column, $officer->special_filter_value);
            }

            // Hitung agregat untuk < 3 Miliar
            $statsLessThan = (clone $baseQuery)
                ->where('nilai_total_deal', '<', $threeBillion)
                ->selectRaw("
                    COUNT(CASE WHEN status_wfm = 'Done Close Bima' THEN 1 END) as done_count,
                    COUNT(CASE WHEN status_wfm = 'Done Close Cancel' THEN 1 END) as cancel_count,
                    COUNT(CASE WHEN status_wfm = '' THEN 1 END) as no_status_count
                ")->first();

            $totalLess = $statsLessThan->done_count + $statsLessThan->cancel_count + $statsLessThan->no_status_count;
            $tableDataLessThan3Bln[] = [
                'po' => $officer->name,
                'witel' => $officer->display_witel,
                'done' => (int) $statsLessThan->done_count,
                'cancel' => (int) $statsLessThan->cancel_count,
                'no_status' => (int) $statsLessThan->no_status_count,
                'total' => $totalLess,
            ];

            // Hitung agregat untuk > 3 Miliar
            $statsMoreThan = (clone $baseQuery)
                ->where('nilai_total_deal', '>=', $threeBillion)
                ->selectRaw("
                    COUNT(CASE WHEN status_wfm = 'Done Close Bima' THEN 1 END) as done_count,
                    COUNT(CASE WHEN status_wfm = 'Done Close Cancel' THEN 1 END) as cancel_count,
                    COUNT(CASE WHEN status_wfm = '' THEN 1 END) as no_status_count
                ")->first();

            $totalMore = $statsMoreThan->done_count + $statsMoreThan->cancel_count + $statsMoreThan->no_status_count;
            $tableDataMoreThan3Bln[] = [
                'po' => $officer->name,
                'witel' => $officer->display_witel,
                'done' => (int) $statsMoreThan->done_count,
                'cancel' => (int) $statsMoreThan->cancel_count,
                'no_status' => (int) $statsMoreThan->no_status_count,
                'total' => $totalMore,
            ];
        }

        // Menyiapkan data untuk Pie Charts (agregat per witel)
        // Logika ini mengasumsikan pemetaan 1-ke-1 antara display_witel dan witel_correct
        $witelList = AccountOfficer::distinct()->pluck('display_witel')->toArray();
        $chartData = [];
        foreach ($witelList as $witelName) {
            $stats = DocumentData::whereIn('witel_correct', function($query) use ($witelName) {
                    $query->select('filter_witel_lama')
                          ->from('account_officers')
                          ->where('display_witel', $witelName);
                })
                ->selectRaw("
                    COUNT(CASE WHEN status_wfm = 'Done Close Bima' THEN 1 END) as done,
                    COUNT(CASE WHEN status_wfm = 'Done Close Cancel' THEN 1 END) as cancel,
                    COUNT(CASE WHEN status_wfm = '' THEN 1 END) as no_status
                ")->first();

            $chartData[$witelName] = [
                'done' => (int) $stats->done,
                'cancel' => (int) $stats->cancel,
                'no_status' => (int) $stats->no_status,
            ];
        }

        return Inertia::render('ActionBased', [
            'tableDataLessThan3Bln' => $tableDataLessThan3Bln,
            'tableDataMoreThan3Bln' => $tableDataMoreThan3Bln,
            'chartData' => $chartData,
            'filters' => ['witel' => $selectedWitel],
            'witelOptions' => array_merge(['ALL'], $witelList),
        ]);
    }
}
