<?php

use App\Http\Controllers\Admin\DataUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Models\AccountOfficer;
use App\Models\DocumentData;
use App\Models\SosData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/bot/digital-product/progress', function (Request $request) {
    // 1. Auth & Param
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $witelInput = strtoupper($request->query('witel'));
    $month = $request->query('month', date('m'));
    $year = $request->query('year', date('Y'));

    // Mapping Witel
    $witelMap = [
        'BALI' => 'BALI', 'DENPASAR' => 'BALI',
        'JATIM BARAT' => 'JATIM BARAT', 'JABAR' => 'JATIM BARAT',
        'JATIM TIMUR' => 'JATIM TIMUR', 'JATIM' => 'JATIM TIMUR',
        'NTT' => 'NUSA TENGGARA', 'NTB' => 'NUSA TENGGARA', 'NUSRA' => 'NUSA TENGGARA', 'NUSA TENGGARA' => 'NUSA TENGGARA',
        'SURAMADU' => 'SURAMADU', 'SURABAYA' => 'SURAMADU',
    ];
    $witel = $witelMap[$witelInput] ?? $witelInput;

    // 2. Siapkan Struktur Data Hasil
    $products = ['Netmonk', 'OCA', 'Antares', 'Pijar', 'Lainnya'];
    $stats = [];
    foreach ($products as $p) {
        $stats[$p] = [
            'ogp' => 0,          // In Progress
            'closed' => 0,       // Prov Comp
            'revenue' => 0,       // Revenue (dalam Juta)
        ];
    }

    // Helper untuk deteksi nama produk
    $detectProduct = function ($name) {
        $n = strtolower($name);
        if (str_contains($n, 'netmonk')) {
            return 'Netmonk';
        }
        if (str_contains($n, 'oca')) {
            return 'OCA';
        }
        if (str_contains($n, 'antares')) {
            return 'Antares';
        }
        if (str_contains($n, 'pijar')) {
            return 'Pijar';
        }

        return 'Lainnya';
    };

    // 3. AMBIL DATA IN PROGRESS (OGP)
    // Syarat: status 'in progress', filter by order_created_date
    $ogpDocs = DocumentData::where('nama_witel', $witel)
        ->where('status_wfm', 'in progress')
        ->whereMonth('order_created_date', $month)
        ->whereYear('order_created_date', $year)
        ->get();

    foreach ($ogpDocs as $doc) {
        if (str_contains($doc->product, '-')) {
            // Bundling
            $items = DB::table('order_products')->where('order_id', $doc->order_id)->where('status_wfm', 'in progress')->get();
            foreach ($items as $item) {
                $cat = $detectProduct($item->product_name);
                ++$stats[$cat]['ogp'];
            }
        } else {
            // Single
            $cat = $detectProduct($doc->product);
            ++$stats[$cat]['ogp'];
        }
    }

    // 4. AMBIL DATA PROV COMPLETE & REVENUE
    // Syarat: status 'done close bima', filter by order_date (sesuai controller dashboard)
    $closedDocs = DocumentData::where('nama_witel', $witel)
        ->where('status_wfm', 'done close bima')
        ->whereMonth('order_date', $month)
        ->whereYear('order_date', $year)
        ->get();

    foreach ($closedDocs as $doc) {
        if (str_contains($doc->product, '-')) {
            // Bundling
            $items = DB::table('order_products')->where('order_id', $doc->order_id)->where('status_wfm', 'done close bima')->get();
            foreach ($items as $item) {
                $cat = $detectProduct($item->product_name);
                ++$stats[$cat]['closed'];
                $stats[$cat]['revenue'] += ($item->net_price ?? 0);
            }
        } else {
            // Single
            $cat = $detectProduct($doc->product);
            ++$stats[$cat]['closed'];
            $stats[$cat]['revenue'] += ($doc->net_price ?? 0);
        }
    }

    // Konversi Revenue ke Juta (supaya ringkas di chat)
    foreach ($stats as $key => $val) {
        $stats[$key]['revenue'] = round($val['revenue'] / 1000000, 2); // Bagi 1 Juta
    }

    return response()->json([
        'witel' => $witel,
        'period_text' => Carbon::createFromDate(null, $month)->locale('id')->isoFormat('MMMM Y'),
        'data' => $stats,
    ]);
});

Route::get('/bot/digital-product/kpi-po', function (Request $request) {
    // 1. Auth
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $searchName = $request->query('name');

    // 2. Cari Account Officer
    $query = AccountOfficer::query();
    if ($searchName) {
        $query->where('name', 'LIKE', "%{$searchName}%");
    }
    $officers = $query->get();

    if ($officers->isEmpty()) {
        return response()->json(['message' => 'PO tidak ditemukan', 'data' => []]);
    }

    // 3. Hitung Data (Logic Copy-Paste dari Controller agar Akurat)
    $results = $officers->map(function ($officer) {
        $witelFilter = $officer->filter_witel_lama;
        $specialFilter = $officer->special_filter_column && $officer->special_filter_value
            ? ['column' => $officer->special_filter_column, 'value' => $officer->special_filter_value]
            : null;

        // Query Builder Dasar
        $singleQuery = DocumentData::where('witel_lama', $witelFilter)
            ->whereNotNull('product')
            ->where('product', 'NOT LIKE', '%-%')
            ->where('product', 'NOT LIKE', "%\n%")
            ->when($specialFilter, fn ($q) => $q->where($specialFilter['column'], $specialFilter['value']));

        $bundleQuery = DB::table('order_products')
            ->join('document_data', 'order_products.order_id', '=', 'document_data.order_id')
            ->where('document_data.witel_lama', $witelFilter)
            ->when($specialFilter, fn ($q) => $q->where('document_data.'.$specialFilter['column'], $specialFilter['value']));

        // --- HITUNG METRIK ---

        // 1. DONE (NCX vs SCONE)
        $done_ncx = $singleQuery->clone()->where('status_wfm', 'done close bima')->where('channel', '!=', 'SC-One')->count()
                  + $bundleQuery->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', '!=', 'SC-One')->count();

        $done_scone = $singleQuery->clone()->where('status_wfm', 'done close bima')->where('channel', 'SC-One')->count()
                    + $bundleQuery->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', 'SC-One')->count();

        // 2. OGP (NCX vs SCONE)
        $ogp_ncx = $singleQuery->clone()->where('status_wfm', 'in progress')->where('channel', '!=', 'SC-One')->count()
                 + $bundleQuery->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', '!=', 'SC-One')->count();

        $ogp_scone = $singleQuery->clone()->where('status_wfm', 'in progress')->where('channel', 'SC-One')->count()
                   + $bundleQuery->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', 'SC-One')->count();

        // 3. TOTAL & ACH YTD
        $total_ytd = $done_ncx + $done_scone + $ogp_ncx + $ogp_scone;
        $ach_ytd = $total_ytd > 0 ? round((($done_ncx + $done_scone) / $total_ytd) * 100, 1) : 0;

        // 4. ACH Q3 (Hardcoded Year/Month sesuai Controller)
        $q3Months = [7, 8, 9];
        $q3Year = 2025;

        $singleQueryQ3 = $singleQuery->clone()->whereYear('order_created_date', $q3Year)->whereIn(DB::raw('MONTH(order_created_date)'), $q3Months);
        $bundleQueryQ3 = $bundleQuery->clone()->whereYear('document_data.order_created_date', $q3Year)->whereIn(DB::raw('MONTH(document_data.order_created_date)'), $q3Months);

        $done_scone_q3 = $singleQueryQ3->clone()->where('status_wfm', 'done close bima')->where('channel', 'SC-One')->count()
                       + $bundleQueryQ3->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', 'SC-One')->count();

        $done_ncx_q3 = $singleQueryQ3->clone()->where('status_wfm', 'done close bima')->where('channel', '!=', 'SC-One')->count()
                     + $bundleQueryQ3->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', '!=', 'SC-One')->count();

        $ogp_ncx_q3 = $singleQueryQ3->clone()->where('status_wfm', 'in progress')->where('channel', '!=', 'SC-One')->count()
                    + $bundleQueryQ3->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', '!=', 'SC-One')->count();

        $ogp_scone_q3 = $singleQueryQ3->clone()->where('status_wfm', 'in progress')->where('channel', 'SC-One')->count()
                      + $bundleQueryQ3->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', 'SC-One')->count();

        $total_q3 = $done_ncx_q3 + $done_scone_q3 + $ogp_ncx_q3 + $ogp_scone_q3;
        $ach_q3 = $total_q3 > 0 ? round((($done_ncx_q3 + $done_scone_q3) / $total_q3) * 100, 1) : 0;

        return [
            'name' => $officer->name,
            'witel' => $officer->display_witel ?? $officer->filter_witel_lama, // Tampilkan witel
            'done_ncx' => $done_ncx,
            'done_scone' => $done_scone,
            'ogp_ncx' => $ogp_ncx,
            'ogp_scone' => $ogp_scone,
            'total' => $total_ytd,
            'ach_ytd' => $ach_ytd,
            'ach_q3' => $ach_q3,
        ];
    });

    return response()->json(['data' => $results]);
});

Route::get('/bot/jt/progress', function (Request $request) {
    // 1. Auth Check
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $witelInput = strtoupper($request->query('witel'));

    // 2. Query Database (spmk_mom)
    // Kita mencari berdasarkan witel_baru (Induk) dan mengelompokkan per witel_lama (Anak)
    $data = DB::table('spmk_mom')
        ->select(
            'witel_lama as witel_anak',
            DB::raw("SUM(CASE WHEN UPPER(status_tomps_new) LIKE '%INITIAL%' AND go_live = 'N' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS initial"),
            DB::raw("SUM(CASE WHEN UPPER(status_tomps_new) LIKE '%SURVEY & DRM%' AND go_live = 'N' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS survey_drm"),
            DB::raw("SUM(CASE WHEN UPPER(status_tomps_new) LIKE '%PERIZINAN & MOS%' AND go_live = 'N' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS perizinan_mos"),
            DB::raw("SUM(CASE WHEN UPPER(status_tomps_new) LIKE '%INSTALASI%' AND go_live = 'N' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS instalasi"),
            DB::raw("SUM(CASE WHEN UPPER(status_tomps_new) LIKE '%FI - OGP GOLIVE%' AND go_live = 'N' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS fi_ogp_live"),
            DB::raw("SUM(CASE WHEN go_live = 'Y' AND populasi_non_drop = 'Y' THEN 1 ELSE 0 END) AS golive"),
            DB::raw("SUM(CASE WHEN populasi_non_drop = 'N' THEN 1 ELSE 0 END) AS `drop`")
        )
        // Gunakan LIKE agar user bisa mengetik "BALI" saja, dan sistem mencocokkan "WITEL BALI"
        ->where('witel_baru', 'LIKE', "%{$witelInput}%")
        ->groupBy('witel_lama')
        ->orderBy('witel_lama')
        ->get();

    if ($data->isEmpty()) {
        return response()->json(['found' => false]);
    }

    // 3. Format Data untuk Bot
    return response()->json([
        'found' => true,
        'witel_induk' => $witelInput, // Nama yang diketik user
        'data' => $data,
    ]);
});

Route::get('/bot/jt/non-golive', function (Request $request) {
    // 1. Auth Check
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $witelInput = strtoupper($request->query('witel'));

    // 2. Query Database (Sama seperti getTocReportData di Controller)
    $data = DB::table('spmk_mom')
        ->select(
            'witel_lama as witel_anak',
            // Hitung DALAM TOC
            DB::raw("SUM(CASE
                WHEN UPPER(keterangan_toc) = 'DALAM TOC'
                AND go_live = 'N'
                AND populasi_non_drop = 'Y'
                THEN 1 ELSE 0
            END) as dalam_toc"),
            // Hitung LEWAT TOC
            DB::raw("SUM(CASE
                WHEN UPPER(keterangan_toc) = 'LEWAT TOC'
                AND go_live = 'N'
                AND populasi_non_drop = 'Y'
                THEN 1 ELSE 0
            END) as lewat_toc")
        )
        ->where('witel_baru', 'LIKE', "%{$witelInput}%")
        ->where('go_live', 'N')             // Hanya yang belum Go Live
        ->where('populasi_non_drop', 'Y')   // Kecualikan Drop
        ->groupBy('witel_lama')
        ->orderBy('witel_lama')
        ->get();

    if ($data->isEmpty()) {
        return response()->json(['found' => false]);
    }

    return response()->json([
        'found' => true,
        'witel_induk' => $witelInput,
        'data' => $data,
    ]);
});

Route::get('/bot/jt/top3-progress', function (Request $request) {
    // 1. Auth Check
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $witelInput = strtoupper($request->query('witel'));

    // 2. Query Database
    // Logic: Ambil data spmk_mom, filter witel, filter belum go live, urutkan berdasarkan umur (desc), ambil 3.
    $data = DB::table('spmk_mom')
        ->select(
            'uraian_kegiatan as nama_project',
            'id_i_hld as ihld',
            'tanggal_mom',
            'revenue_plan as revenue',
            'status_tomps_new as status_tomps',
            // Hitung Usia (Hari) = Selisih Hari Ini - Tanggal MOM
            DB::raw('DATEDIFF(NOW(), tanggal_mom) as usia_hari')
        )
        ->where('witel_baru', 'LIKE', "%{$witelInput}%")
        ->where('go_live', 'N')             // Masih In Progress
        ->where('populasi_non_drop', 'Y')   // Bukan Drop
        ->whereNotNull('tanggal_mom')       // Tanggal MoM harus ada
        ->orderBy('usia_hari', 'desc')      // Urutkan dari yang paling tua
        ->limit(3)                          // Ambil 3 saja
        ->get();

    // Format Tanggal dan Revenue di sisi Server biar bot tinggal tampilkan
    $formattedData = $data->map(function ($item) {
        return [
            'nama_project' => $item->nama_project,
            'ihld' => $item->ihld,
            // Format Tanggal: 29-Apr-25
            'tgl_mom' => $item->tanggal_mom ? Carbon::parse($item->tanggal_mom)->translatedFormat('d-M-y') : '-',
            // Format Revenue: Rp 894.040.000
            'revenue' => 'Rp '.number_format($item->revenue, 0, ',', '.'),
            'status_tomps' => $item->status_tomps,
            'usia_hari' => $item->usia_hari,
        ];
    });

    return response()->json([
        'found' => $formattedData->isNotEmpty(),
        'witel' => $witelInput,
        'data' => $formattedData,
    ]);
});

Route::get('/bot/jt/top3-po', function (Request $request) {
    // 1. Auth Check
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $poName = $request->query('name');

    // 2. Query Database
    // Filter berdasarkan kolom 'po_name' di tabel spmk_mom
    $data = DB::table('spmk_mom')
        ->select(
            'po_name',
            'uraian_kegiatan as nama_project',
            'id_i_hld as ihld',
            'tanggal_mom',
            'revenue_plan as revenue',
            'status_tomps_new as status_tomps',
            DB::raw('DATEDIFF(NOW(), tanggal_mom) as usia_hari')
        )
        // Gunakan LIKE agar pencarian fleksibel (misal ketik "Andre" ketemu "Andre Yana")
        ->where('po_name', 'LIKE', "%{$poName}%")
        ->where('go_live', 'N')             // Belum Go Live
        ->where('populasi_non_drop', 'Y')   // Bukan Drop
        ->whereNotNull('tanggal_mom')
        ->orderBy('usia_hari', 'desc')      // Urutkan dari yang paling tua
        ->limit(3)
        ->get();

    if ($data->isEmpty()) {
        return response()->json(['found' => false]);
    }

    // Format Data
    $formattedData = $data->map(function ($item) {
        return [
            'po_name' => $item->po_name, // Kembalikan nama lengkap dari DB
            'nama_project' => $item->nama_project,
            'ihld' => $item->ihld,
            'tgl_mom' => $item->tanggal_mom ? Carbon::parse($item->tanggal_mom)->translatedFormat('d-M-y') : '-',
            'revenue' => 'Rp '.number_format($item->revenue, 0, ',', '.'),
            'status_tomps' => $item->status_tomps,
            'usia_hari' => $item->usia_hari,
        ];
    });

    return response()->json([
        'found' => true,
        'data' => $formattedData,
    ]);
});

Route::get('/bot/datin/report', function (Request $request) {
    // 1. Auth & Mapping Logic
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $segmentInput = strtoupper($request->query('segment'));
    $witelInput = strtoupper($request->query('witel') ?? 'ALL');

    $segmentMap = ['SME' => '1. SME', 'GOV' => '2. GOV', 'PRIVATE' => '3. PRIVATE', 'SOE' => '4. SOE'];
    $targetSegment = $segmentMap[$segmentInput] ?? null;

    if (!$targetSegment) {
        return response()->json(['found' => false, 'message' => 'Segmen tidak valid']);
    }

    // --- Definisi Status Kategori yang AKURAT (Sesuai Trait) ---
    $provideStatus = "('PROVIDE ORDER', '1. PROVIDE ORDER')";
    $inProcessStatus = "('IN PROCESS', 'PROV. COMPLETE', '2. IN PROCESS')"; // Status yang diperbaiki
    $readyToBillStatus = "('READY TO BILL', '3. READY TO BILL')";

    // --- 2. FUNGSI HELPER UNTUK MENGAMBIL DATA UTAMA (BASED ON TIPE GRUP) ---
    $getRawData = function ($tipeGrup) use ($targetSegment, $witelInput, $provideStatus, $inProcessStatus, $readyToBillStatus) {
        $selects = [
            // COUNT < 3 BLN
            DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$provideStatus} THEN 1 ELSE 0 END) as cnt_prov_less"),
            DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$inProcessStatus} THEN 1 ELSE 0 END) as cnt_proc_less"),
            DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$readyToBillStatus} THEN 1 ELSE 0 END) as cnt_bill_less"),

            // COUNT > 3 BLN
            DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$provideStatus} THEN 1 ELSE 0 END) as cnt_prov_more"),
            DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$inProcessStatus} THEN 1 ELSE 0 END) as cnt_proc_more"),
            DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$readyToBillStatus} THEN 1 ELSE 0 END) as cnt_bill_more"),
        ];

        // Tambahkan kolom Revenue HANYA jika tipe grup adalah AOMO (menggunakan SCALLING1)
        if ($tipeGrup === 'AOMO') {
            $selects = array_merge($selects, [
                // REVENUE < 3 BLN
                DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$provideStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_prov_less"),
                DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$inProcessStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_proc_less"),
                DB::raw("SUM(CASE WHEN `kategori_umur` = '< 3 BLN' AND UPPER(`kategori`) IN {$readyToBillStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_bill_less"),

                // REVENUE > 3 BLN
                DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$provideStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_prov_more"),
                DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$inProcessStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_proc_more"),
                DB::raw("SUM(CASE WHEN `kategori_umur` = '> 3 BLN' AND UPPER(`kategori`) IN {$readyToBillStatus} THEN COALESCE(`scalling1`, 0) ELSE 0 END) as rev_bill_more"),
            ]);
        }

        return SosData::select('bill_witel')
            ->where('segmen_baru', $targetSegment)
            ->where('tipe_grup', $tipeGrup)
            ->whereIn('bill_witel', ['BALI', 'JATIM BARAT', 'JATIM TIMUR', 'NUSA TENGGARA', 'SURAMADU'])
            ->when($witelInput !== 'ALL', function ($q) use ($witelInput) {
                return $q->where('bill_witel', 'LIKE', "%{$witelInput}%");
            })
            ->groupBy('bill_witel')
            ->addSelect($selects)
            ->get();
    }; // Akhir $getRawData

    // --- 3. EKSEKUSI DUAL QUERY ---
    $rawDataSodoro = $getRawData('SODORO')->keyBy('bill_witel');
    $rawDataAomo = $getRawData('AOMO')->keyBy('bill_witel');

    // --- 4. KOMBINASI DAN FORMATTING ---

    // Fungsi konversi ke juta (menggunakan closure untuk kompatibilitas PHP < 7.4)
    $toJuta = function ($val) {
        return round((float) $val / 1000000, 2);
    };

    $witelKeys = $rawDataSodoro->keys()->merge($rawDataAomo->keys())->unique()->sort()->values();

    $formatted = $witelKeys->map(function ($witel) use ($rawDataSodoro, $rawDataAomo, $toJuta) {
        $sodoro = $rawDataSodoro->get($witel);
        $aomo = $rawDataAomo->get($witel);

        // Nilai Default
        $emptySodoro = ['prov' => 0, 'proc' => 0, 'bill' => 0, 'total' => 0];
        $emptyAomo = ['prov' => 0.00, 'proc' => 0.00, 'bill' => 0.00, 'total' => 0.00];

        // A. SODORO (Count)
        $sodoro_less_total = $sodoro ? $sodoro->cnt_prov_less + $sodoro->cnt_proc_less + $sodoro->cnt_bill_less : 0;
        $sodoro_more_total = $sodoro ? $sodoro->cnt_prov_more + $sodoro->cnt_proc_more + $sodoro->cnt_bill_more : 0;

        // B. AOMO (Revenue)
        $aomo_less_total = $aomo ? $toJuta($aomo->rev_prov_less + $aomo->rev_proc_less + $aomo->rev_bill_less) : 0.00;
        $aomo_more_total = $aomo ? $toJuta($aomo->rev_prov_more + $aomo->rev_proc_more + $aomo->rev_bill_more) : 0.00;

        return [
            'witel' => $witel,
            'sodoro' => [
                'less' => $sodoro ? ['prov' => (int) $sodoro->cnt_prov_less, 'proc' => (int) $sodoro->cnt_proc_less, 'bill' => (int) $sodoro->cnt_bill_less, 'total' => (int) $sodoro_less_total] : $emptySodoro,
                'more' => $sodoro ? ['prov' => (int) $sodoro->cnt_prov_more, 'proc' => (int) $sodoro->cnt_proc_more, 'bill' => (int) $sodoro->cnt_bill_more, 'total' => (int) $sodoro_more_total] : $emptySodoro,
            ],
            'aomo' => [
                'less' => $aomo ? ['prov' => $toJuta($aomo->rev_prov_less), 'proc' => $toJuta($aomo->rev_proc_less), 'bill' => $toJuta($aomo->rev_bill_less), 'total' => number_format($aomo_less_total, 2, '.', '')] : $emptyAomo,
                'more' => $aomo ? ['prov' => $toJuta($aomo->rev_prov_more), 'proc' => $toJuta($aomo->rev_proc_more), 'bill' => $toJuta($aomo->rev_bill_more), 'total' => number_format($aomo_more_total, 2, '.', '')] : $emptyAomo,
            ],
        ];
    });

    return response()->json([
        'found' => $formatted->isNotEmpty(),
        'segment' => $targetSegment,
        'data' => $formatted,
    ]);
});

Route::get('/bot/datin/galaksi-po', function (Request $request) {
    // 1. Auth Check
    if ($request->header('Authorization') !== env('BOT_API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $poName = $request->query('name');

    if (empty($poName)) {
        return response()->json(['found' => false, 'message' => 'Nama PO wajib diisi.'], 400);
    }

    // 2. Ambil Data Galaksi dari Controller/Trait (Kita tiru Controller yang memanggilnya)
    // Karena kita tidak bisa memanggil trait langsung di Route Closure, kita harus membuat Controller sementara
    // atau menduplikasi logika getGalaksiReportData di sini.
    // Pilihan terbaik adalah memindahkan logika dari trait ke fungsi helper sementara di sini
    // atau, lebih bersih, membuat Controller baru untuk API ini jika memungkinkan.

    // ASUMSI: Karena Anda menyediakan file controller, kita asumsikan SosReportable trait
    // TIDAK DAPAT dipanggil langsung di Route Closure.
    // Kami akan menggunakan logika yang DIBUTUHKAN DARI TRAIT (yaitu, memanggil DB).

    // --- FUNGSI HELPER SEMENTARA UNTUK MENGAMBIL DATA GALAKSI ---
    // Logika ini diduplikasi dari trait SosReportable::getGalaksiReportData()
    $getGalaksiData = function () {
        // Logika PO Mapping HARUS DISERTAKAN (seperti di trait Anda)
        $nipnasToPoMap = App\Models\ListPo::query()
            ->whereNotNull('po')
            ->where('po', '!=', '')
            ->whereNotIn('po', ['HOLD', 'LANDING'])
            ->get(['nipnas', 'po'])
            ->pluck('po', 'nipnas')
            ->map(fn ($poName) => trim($poName)); // Gunakan Arrow Function karena PHP Anda 8.2

        // Lakukan agregasi data dari SEMUA sos_data.
        $aggregatedData = SosData::query()
            ->select(
                'nipnas',
                DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' AND UPPER(order_subtype) = 'NEW INSTALL' THEN 1 ELSE 0 END) as ao_lt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' AND UPPER(order_subtype) = 'SUSPEND' THEN 1 ELSE 0 END) as so_lt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' AND UPPER(order_subtype) = 'DISCONNECT' THEN 1 ELSE 0 END) as do_lt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' AND UPPER(order_subtype) IN ('MODIFY PRICE', 'MODIFY', 'MODIFY BA', 'RENEWAL AGREEMENT', 'MODIFY TERMIN') THEN 1 ELSE 0 END) as mo_lt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' AND UPPER(order_subtype) = 'RESUME' THEN 1 ELSE 0 END) as ro_lt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' AND UPPER(order_subtype) = 'NEW INSTALL' THEN 1 ELSE 0 END) as ao_gt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' AND UPPER(order_subtype) = 'SUSPEND' THEN 1 ELSE 0 END) as so_gt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' AND UPPER(order_subtype) = 'DISCONNECT' THEN 1 ELSE 0 END) as do_gt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' AND UPPER(order_subtype) IN ('MODIFY PRICE', 'MODIFY', 'MODIFY BA', 'RENEWAL AGREEMENT', 'MODIFY TERMIN') THEN 1 ELSE 0 END) as mo_gt_3bln"),
                DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' AND UPPER(order_subtype) = 'RESUME' THEN 1 ELSE 0 END) as ro_gt_3bln")
            )
            ->where(DB::raw('UPPER(li_status)'), 'IN PROGRESS')
            ->groupBy('nipnas')
            ->get();

        $resultsByPoName = [];
        $blankRow = [
            'ao_lt_3bln' => 0, 'so_lt_3bln' => 0, 'do_lt_3bln' => 0, 'mo_lt_3bln' => 0, 'ro_lt_3bln' => 0,
            'ao_gt_3bln' => 0, 'so_gt_3bln' => 0, 'do_gt_3bln' => 0, 'mo_gt_3bln' => 0, 'ro_gt_3bln' => 0,
        ];

        foreach ($aggregatedData as $item) {
            if (isset($nipnasToPoMap[$item->nipnas])) {
                $poName = $nipnasToPoMap[$item->nipnas];
                if (!isset($resultsByPoName[$poName])) {
                    $resultsByPoName[$poName] = array_merge(['po' => $poName], $blankRow);
                }
                foreach ($blankRow as $key => $value) {
                    $resultsByPoName[$poName][$key] += $item->$key;
                }
            }
        }

        return $resultsByPoName;
    };
    // --- AKHIR FUNGSI HELPER SEMENTARA ---

    $galaksiData = $getGalaksiData();

    // 3. Filter Data
    $filteredData = collect($galaksiData)
        ->filter(fn ($item) => str_contains(strtoupper($item['po']), strtoupper($poName)))
        ->sortByDesc(fn ($item) => ($item['ao_lt_3bln'] + $item['so_lt_3bln'] + $item['do_lt_3bln'] + $item['mo_lt_3bln'] + $item['ro_lt_3bln']))
        ->values()
        ->all();

    if (empty($filteredData)) {
        return response()->json(['found' => false, 'message' => "Data Galaksi untuk PO '{$poName}' tidak ditemukan."]);
    }

    // 4. Hitung Achievement (sesuai tabel)
    // Ach = DONE / TOTAL * 100
    // Total = (<3 BLN Total + >3 BLN Total)
    // Done = TOTAL - TOTAL OGP (>3 BLN)
    $results = array_map(function ($item) {
        $lt3_total = $item['ao_lt_3bln'] + $item['so_lt_3bln'] + $item['do_lt_3bln'] + $item['mo_lt_3bln'] + $item['ro_lt_3bln'];
        $gt3_total = $item['ao_gt_3bln'] + $item['so_gt_3bln'] + $item['do_gt_3bln'] + $item['mo_gt_3bln'] + $item['ro_gt_3bln'];

        $grand_total = $lt3_total + $gt3_total;

        // Ach yang ditampilkan di tabel adalah % OGP > 3 bln
        // Ach = 100% jika GT3 Total = 0
        $achievement = ($grand_total > 0) ? round(($gt3_total / $grand_total) * 100, 0) : 100;

        // Ach yang sebenarnya di tabel: 100% jika OGP > 3 BLN = 0
        $achievement_tabel = ($gt3_total == 0) ? 100 : (100 - ($gt3_total / $grand_total) * 100);

        // Kita gunakan logika yang paling mirip tabel (yaitu, % OGP > 3 BLN yang rendah itu bagus)
        // Di tabel, Achievement 100% saat OGP > 3 BLN kecil/nol. Kita gunakan logik Achievement tabel.

        return array_merge($item, [
            'lt3_total' => $lt3_total,
            'gt3_total' => $gt3_total,
            'achievement' => number_format($achievement_tabel, 0).'%',
        ]);
    }, $filteredData);

    return response()->json([
        'found' => true,
        'data' => $results,
    ]);
});

// =================== [BARU] ROUTE UNTUK BOT TELEGRAM ===================
Route::get('/progress-witel', function (Request $request) {
    // 1. Cek Keamanan (Optional tapi Disarankan)
    // Mencocokkan dengan secret key yang ada di .env (BOT_API_SECRET)
    // Jika header tidak sesuai, tolak akses.
    $secret = $request->header('Authorization');
    $envSecret = env('BOT_API_SECRET');

    // Jika di .env ada secret, kita cek. Jika kosong, kita loloskan saja (mode dev).
    if ($envSecret && $secret !== $envSecret) {
        return response()->json(['error' => 'Unauthorized Access'], 401);
    }

    // 2. Data Dummy (Pura-pura)
    // Nanti bagian ini diganti dengan: DataWitel::all();
    $data = [
        [
            'witel' => 'BALI',
            'initial' => 10,
            'survey_drm' => 5,
            'perizinan_mos' => 2,
            'instalasi' => 1,
            'fi_ogp_live' => 25, // Data yang diambil bot
        ],
        [
            'witel' => 'JATIM BARAT',
            'initial' => 15,
            'survey_drm' => 8,
            'perizinan_mos' => 5,
            'instalasi' => 3,
            'fi_ogp_live' => 12,
        ],
        [
            'witel' => 'JATIM TIMUR',
            'initial' => 8,
            'survey_drm' => 4,
            'perizinan_mos' => 2,
            'instalasi' => 1,
            'fi_ogp_live' => 50,
        ],
    ];

    return response()->json($data);
});
// =======================================================================

// =================== ROUTE LAMA (STATISTIK JARINGAN) ===================
Route::get('/network-stats', function (Request $request) {
    $target_host = 'google.com';
    $command = '';

    // Mendeteksi Sistem Operasi (OS)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = 'ping -n 5 '.escapeshellarg($target_host);
    } else {
        $command = 'ping -c 5 '.escapeshellarg($target_host);
    }

    exec($command, $output, $status);

    if ($status === 0) {
        $result_string = implode("\n", $output);
        $packet_matches = [];
        $time_matches = [];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            preg_match('/Sent = (\d+), Received = (\d+), Lost = \d+ \((\d+)% loss\)/', $result_string, $packet_matches_win);
            preg_match('/Average = (\d+)ms/', $result_string, $time_matches_win);
            $packet_matches = [null, $packet_matches_win[1] ?? 'N/A', $packet_matches_win[2] ?? 'N/A', $packet_matches_win[3] ?? 'N/A'];
            $time_matches = [null, $time_matches_win[1] ?? 'N/A'];
        } else {
            preg_match('/(\d+)\s+packets transmitted,\s+(\d+)\s+received,\s+([\d.]+)%\s+packet loss/', $result_string, $packet_matches);
            preg_match('/rtt min\/avg\/max\/mdev = [\d.]+\/([\d.]+)\//', $result_string, $time_matches);
        }

        return response()->json([
            'ip' => $request->ip(),
            'alive' => true,
            'transmitted' => $packet_matches[1] ?? 'N/A',
            'received' => $packet_matches[2] ?? 'N/A',
            'loss' => isset($packet_matches[3]) ? $packet_matches[3].'%' : 'N/A',
            'time' => isset($time_matches[1]) ? round($time_matches[1]).' ms' : 'N/A',
            'traceroute' => 'N/A',
        ]);
    } else {
        return response()->json([
            'ip' => $request->ip(),
            'alive' => false,
            'transmitted' => 0,
            'received' => 0,
            'loss' => '100%',
            'time' => 'N/A',
            'traceroute' => 'N/A',
        ], 500);
    }
});

// =================== ROUTE OTENTIKASI ===================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Grup route KHUSUS ADMIN
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [DataUserController::class, 'getAllUsers']);
    Route::post('/products', function () {
        // Logika menambah produk
    });
});
