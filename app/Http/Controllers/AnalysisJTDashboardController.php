<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalysisJTDashboardController extends Controller
{
    // ===================================================================
    // 1. DEFINISI & HELPER
    // ===================================================================

    private function getWitelSegments()
    {
        return [
            'WITEL BALI' => ['WITEL DENPASAR', 'WITEL SINGARAJA'],
            'WITEL JATIM BARAT' => ['WITEL KEDIRI', 'WITEL MADIUN', 'WITEL MALANG'],
            'WITEL JATIM TIMUR' => ['WITEL JEMBER', 'WITEL PASURUAN', 'WITEL SIDOARJO'],
            'WITEL NUSA TENGGARA' => ['WITEL NTT', 'WITEL NTB'],
            'WITEL SURAMADU' => ['WITEL SURABAYA UTARA', 'WITEL SURABAYA SELATAN', 'WITEL MADURA'],
        ];
    }

    private function getPoCaseStatementString()
    {
        return "
            CASE
                WHEN witel_lama = 'WITEL MADIUN' THEN 'ALFONSUS'
                WHEN witel_lama IN ('WITEL DENPASAR', 'WITEL SINGARAJA') THEN 'DIASTANTO'
                WHEN witel_lama = 'WITEL JEMBER' THEN 'ILHAM MIFTAHUL'
                WHEN witel_lama = 'WITEL PASURUAN' THEN 'I WAYAN KRISNA'
                WHEN witel_lama = 'WITEL SIDOARJO' THEN 'IBRAHIM MUHAMMAD'
                WHEN witel_lama = 'WITEL KEDIRI' THEN 'LUQMAN KURNIAWAN'
                WHEN witel_lama = 'WITEL MALANG' THEN 'NURTRIA IMAN'
                WHEN witel_lama = 'WITEL NTT' THEN 'MARIA FRANSISKA'
                WHEN witel_lama = 'WITEL NTB' THEN 'ANDRE YANA'
                WHEN witel_lama IN ('WITEL SURABAYA UTARA', 'WITEL SURABAYA SELATAN', 'WITEL MADURA')
                THEN
                    (CASE
                        WHEN segmen = 'DBS' THEN 'FERIZKA PARAMITHA'
                        WHEN segmen = 'DGS' THEN 'EKA SARI'
                        WHEN segmen IN ('DES', 'DSS', 'DPS') THEN 'DWIEKA SEPTIAN'
                        ELSE ''
                    END)
                ELSE ''
            END
        ";
    }

    // Helper Filter Ketat Terpusat
    private function applyStrictFilters($query)
    {
        $excludedWitel = ['WITEL SEMARANG JATENG UTARA', 'WITEL SOLO JATENG TIMUR', 'WITEL YOGYA JATENG SELATAN'];

        $query->whereNotIn('status_proyek', ['Selesai', 'Dibatalkan', 'GO LIVE'])
              ->whereRaw("UPPER(status_tomps_new) NOT LIKE '%DROP%'")
              ->whereRaw("UPPER(status_tomps_new) NOT LIKE '%GO LIVE%'")
              ->where(function ($q) {
                  $q->where('status_tomps_last_activity', '!=', 'CLOSE - 100%')->orWhereNull('status_tomps_last_activity');
              })
              ->whereNotNull('tanggal_mom')
              ->whereNotIn('witel_baru', $excludedWitel)
              ->where('go_live', '=', 'N')
              ->where('populasi_non_drop', '=', 'Y')
              ->where(function ($q) {
                  $q->where('bak', '=', '-')->orWhereNull('bak');
              });
    }

    // [FIXED] Mapping Witel -> PO (Robust & Dynamic)
    private function getWitelPoMap($parentWitelList)
    {
        $poCase = $this->getPoCaseStatementString();

        // 1. Ambil Raw Data dengan perhitungan nama PO
        $rawMap = DB::table('spmk_mom')
            ->select(
                'witel_baru',
                DB::raw("COALESCE(NULLIF(po_name, ''), ({$poCase})) as fixed_po_name")
            )
            ->whereNotNull('witel_baru')
            ->distinct()
            ->get();

        $mapping = [];

        // 2. Buat Kamus Normalisasi (Untuk mencocokkan Witel DB yang mungkin ada spasi)
        $validParents = [];
        foreach ($parentWitelList as $p) {
            $validParents[strtoupper(trim($p))] = $p;
        }

        // 3. Lakukan Mapping Manual
        foreach ($rawMap as $row) {
            // Bersihkan Witel dari DB (Hapus spasi & Uppercase)
            $dbWitelClean = strtoupper(trim($row->witel_baru));
            $po = $row->fixed_po_name;

            if (empty($po) || $po == 'Belum Terdefinisi') {
                continue;
            }

            // Jika Witel DB cocok dengan daftar Witel Induk kita
            if (isset($validParents[$dbWitelClean])) {
                $realKey = $validParents[$dbWitelClean]; // Gunakan Key Asli (misal: "WITEL BALI")

                if (!isset($mapping[$realKey])) {
                    $mapping[$realKey] = [];
                }

                // Masukkan PO jika belum ada di list witel tersebut
                if (!in_array($po, $mapping[$realKey])) {
                    $mapping[$realKey][] = $po;
                }
            }
        }

        // 4. Sortir Nama PO
        foreach ($mapping as $key => $pos) {
            sort($mapping[$key]);
        }

        return $mapping;
    }

    // ===================================================================
    // 2. ENTRY POINTS
    // ===================================================================

    public function index(Request $request)
    {
        $settings = Cache::get('granular_embed_settings', []);
        if (isset($settings['jt']) && $settings['jt']['enabled'] && !empty($settings['jt']['url'])) {
            return Inertia::render('Dashboard/ExternalEmbed', [
                'embedUrl' => $settings['jt']['url'],
                'headerTitle' => 'Dashboard Analysis JT',
            ]);
        }

        return $this->handleRequest($request, false);
    }

    public function embed(Request $request)
    {
        return $this->handleRequest($request, true);
    }

    // ===================================================================
    // 3. LOGIC UTAMA (HANDLE REQUEST)
    // ===================================================================

    private function handleRequest(Request $request, $isEmbed)
    {
        // A. Validasi
        $validated = $request->validate([
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
            'witels' => 'nullable|array', 'witels.*' => 'string|max:255',
            'pos' => 'nullable|array', 'pos.*' => 'string|max:255',
            'limit' => 'nullable|in:10,50,100,500',
        ]);
        $limit = $validated['limit'] ?? '10';

        // B. Setup Tanggal UI
        $firstOrderDate = DB::table('spmk_mom')->min('tanggal_mom');
        $latestOrderDate = DB::table('spmk_mom')->max('tanggal_mom');
        $startDateForUI = $request->input('startDate', $firstOrderDate ? \Carbon\Carbon::parse($firstOrderDate)->format('Y-m-d') : now()->startOfYear()->format('Y-m-d'));
        $endDateForUI = $request->input('endDate', $latestOrderDate ? \Carbon\Carbon::parse($latestOrderDate)->format('Y-m-d') : now()->format('Y-m-d'));

        // C. Setup Struktur Witel & Map
        $witelSegments = $this->getWitelSegments();
        $parentWitelList = array_keys($witelSegments);
        $childWitelList = array_merge(...array_values($witelSegments));

        // [FIX] Ambil Mapping Menggunakan Fungsi Robust
        $witelPoMap = $this->getWitelPoMap($parentWitelList);

        $poCaseString = $this->getPoCaseStatementString();

        // D. Filter Logic
        $applyUserFilters = function ($query) use ($validated, $parentWitelList, $childWitelList, $poCaseString) {
            // 1. Filter Tanggal
            if (isset($validated['startDate']) && isset($validated['endDate'])) {
                $query->whereBetween('tanggal_mom', [$validated['startDate'], $validated['endDate']]);
            }

            // 2. Filter Witel (Gunakan TRIM untuk keamanan pencocokan)
            if (isset($validated['witels']) && !empty($validated['witels'])) {
                $query->whereIn(DB::raw('TRIM(witel_baru)'), $validated['witels']);
            } else {
                $query->whereIn(DB::raw('TRIM(witel_baru)'), $parentWitelList);
            }

            // 3. Filter Child Witel
            $query->whereIn(DB::raw('TRIM(witel_lama)'), $childWitelList);

            // 4. Filter PO Global (Fix Empty State)
            if (array_key_exists('pos', $validated)) {
                if (!empty($validated['pos'])) {
                    $poListString = implode("','", $validated['pos']);
                    // Filter berdasarkan nama PO yang sudah dikalkulasi
                    $query->whereRaw("COALESCE(NULLIF(po_name, ''), ({$poCaseString})) IN ('{$poListString}')");
                } else {
                    $query->whereRaw('1 = 0'); // Paksa kosong jika filter dipilih tapi kosong
                }
            }
        };

        $applyStrictReportFilters = function ($query) {
            $this->applyStrictFilters($query);
        };

        // --- E. BUILD QUERIES (Dengan Fix Strict Mode SQL) ---

        // 1. Chart Status
        $statusData = DB::table('spmk_mom')
            ->select(
                DB::raw('TRIM(witel_baru) as witel_induk'),
                DB::raw("SUM(CASE WHEN (UPPER(status_tomps_new) LIKE '%GO LIVE%' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) as golive"),
                DB::raw("SUM(CASE WHEN status_proyek NOT IN ('Selesai', 'Dibatalkan', 'GO LIVE') THEN 1 ELSE 0 END) as blm_golive"),
                DB::raw("SUM(CASE WHEN populasi_non_drop = 'N' THEN 1 ELSE 0 END) as `drop`")
            )
            ->whereIn(DB::raw('TRIM(witel_baru)'), $parentWitelList)
            ->tap($applyUserFilters)
            ->groupBy(DB::raw('TRIM(witel_baru)')) // [FIX] Strict SQL
            ->orderBy(DB::raw('TRIM(witel_baru)'))
            ->get();

        $pieChartData = ['doneGolive' => $statusData->sum('golive'), 'blmGolive' => $statusData->sum('blm_golive'), 'drop' => $statusData->sum('drop')];
        $stackedBarData = $statusData->map(fn ($item) => ['witel' => $item->witel_induk, 'golive' => $item->golive, 'blmGolive' => $item->blm_golive, 'drop' => $item->drop]);

        // ==========================================================================
        // 2 & 3. Chart Usia (Ranking Logic PHP-Side) - MENGHINDARI ROW_NUMBER()
        // ==========================================================================

        // Ambil semua data mentah yang diperlukan (Project name, usia, witel, po)
        // Ini aman di semua versi MySQL karena hanya SELECT biasa
        $rawRankingData = DB::table('spmk_mom')
            ->select(
                'uraian_kegiatan',
                DB::raw('TRIM(witel_baru) as witel_induk'),
                DB::raw("COALESCE(NULLIF(po_name, ''), ({$poCaseString})) as fixed_po_name"),
                DB::raw('DATEDIFF(NOW(), tanggal_mom) as usia')
            )
            ->tap($applyUserFilters)
            ->tap($applyStrictReportFilters)
            ->get();

        // Proses Ranking WITEL di PHP
        $usiaWitelData = $rawRankingData
            ->groupBy('witel_induk')
            ->flatMap(function ($items, $witel) {
                // Sortir berdasarkan usia tertinggi, ambil 3 teratas
                return $items->sortByDesc('usia')->values()->take(3)->map(function ($item, $index) {
                    return [
                        'witel_induk' => $item->witel_induk,
                        'po_name' => $item->fixed_po_name,
                        'uraian_kegiatan' => $item->uraian_kegiatan,
                        'usia' => $item->usia,
                        'rank' => $index + 1, // Ranking manual di PHP
                    ];
                });
            })->values()->all();

        // Proses Ranking PO di PHP
        $usiaPoData = $rawRankingData
            ->where('fixed_po_name', '!=', '')
            ->where('fixed_po_name', '!=', 'Belum Terdefinisi')
            ->groupBy('fixed_po_name')
            ->flatMap(function ($items, $poName) {
                // Sortir berdasarkan usia tertinggi, ambil 3 teratas
                return $items->sortByDesc('usia')->values()->take(3)->map(function ($item, $index) {
                    return [
                        'fixed_po_name' => $item->fixed_po_name, // Pastikan key ini ada untuk frontend
                        'po_name' => $item->fixed_po_name,
                        'uraian_kegiatan' => $item->uraian_kegiatan,
                        'usia' => $item->usia,
                        'rank' => $index + 1,
                    ];
                });
            })->values()->all();

        // ==========================================================================

        // 4. Radar Chart
        $radarData = DB::table('spmk_mom')
            ->select(
                DB::raw('TRIM(witel_baru) as witel_induk'),
                DB::raw("SUM(CASE WHEN (UPPER(status_tomps_new) LIKE '%INITIAL%' AND go_live = 'N' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) AS initial"),
                DB::raw("SUM(CASE WHEN (UPPER(status_tomps_new) LIKE '%SURVEY%' AND go_live = 'N' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) AS survey_drm"),
                DB::raw("SUM(CASE WHEN ((UPPER(status_tomps_new) LIKE '%PERIZINAN%' OR UPPER(status_tomps_new) LIKE '%MOS%') AND go_live = 'N' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) AS perizinan_mos"),
                DB::raw("SUM(CASE WHEN (UPPER(status_tomps_new) LIKE '%INSTALASI%' AND go_live = 'N' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) AS instalasi"),
                DB::raw("SUM(CASE WHEN ((UPPER(status_tomps_new) LIKE '%FI%' OR UPPER(status_tomps_new) LIKE '%OGP%') AND go_live = 'N' AND populasi_non_drop = 'Y') THEN 1 ELSE 0 END) AS fi_ogp_live")
            )
            ->tap($applyUserFilters)
            ->groupBy(DB::raw('TRIM(witel_baru)')) // [FIX] Strict SQL
            ->orderBy(DB::raw('TRIM(witel_baru)'))
            ->get()
            ->map(fn ($item) => [
                'witel' => $item->witel_induk, 'initial' => $item->initial, 'survey_drm' => $item->survey_drm,
                'perizinan_mos' => $item->perizinan_mos, 'instalasi' => $item->instalasi, 'fi_ogp_live' => $item->fi_ogp_live,
            ]);

        // 5. Data Preview
        $dataPreview = DB::table('spmk_mom')
            ->select(
                'uraian_kegiatan', 'status_proyek', 'tanggal_mom', 'witel_baru',
                DB::raw("COALESCE(NULLIF(po_name, ''), ({$poCaseString})) as po_name"),
                DB::raw('TRIM(witel_baru) as witel_induk'),
                DB::raw('DATEDIFF(NOW(), tanggal_mom) as usia')
            )
            ->tap($applyUserFilters)
            ->tap($applyStrictReportFilters)
            ->orderBy('usia', 'desc')
            ->paginate($limit)
            ->withQueryString();

        // F. Opsi Filter (Dropdown)
        // Ambil semua opsi PO (termasuk yang dihitung via CASE) untuk dropdown
        $allPoList = DB::table('spmk_mom')
            ->select(DB::raw("COALESCE(NULLIF(po_name, ''), ({$poCaseString})) as fixed_po_name"))
            ->whereIn(DB::raw('TRIM(witel_baru)'), $parentWitelList)
            ->distinct()
            ->orderBy('fixed_po_name')
            ->pluck('fixed_po_name')
            ->filter(fn ($val) => !empty($val) && $val !== 'Belum Terdefinisi')
            ->values();

        $response = Inertia::render('DashboardJT', [
            'pieChartData' => $pieChartData, 'stackedBarData' => $stackedBarData,
            'usiaWitelData' => $usiaWitelData, 'usiaPoData' => $usiaPoData, 'radarData' => $radarData,
            'dataPreview' => $dataPreview,
            'filters' => ['startDate' => $validated['startDate'] ?? null, 'endDate' => $validated['endDate'] ?? null, 'witels' => $validated['witels'] ?? null, 'pos' => $validated['pos'] ?? null, 'limit' => $limit],
            'filterOptions' => [
                'witelIndukList' => $parentWitelList,
                'poList' => $allPoList,
                'witelPoMap' => $witelPoMap,
                'initialStartDate' => $startDateForUI,
                'initialEndDate' => $endDateForUI,
            ],
            'isEmbed' => $isEmbed,
        ]);

        if ($isEmbed) {
            return $response->rootView('embed');
        }

        return $response;
    }
}
