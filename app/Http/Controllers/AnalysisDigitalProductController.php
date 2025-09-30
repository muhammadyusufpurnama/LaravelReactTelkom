<?php

namespace App\Http\Controllers;

use App\Exports\InProgressExport;
use App\Jobs\ImportAndProcessDocument;
use App\Jobs\ProcessCanceledOrders;
use App\Jobs\ProcessCompletedOrders;
use App\Models\AccountOfficer;
use App\Models\CanceledOrder;
use App\Models\CompletedOrder;
use App\Models\DocumentData;
use App\Models\Target;
use App\Models\UpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AnalysisDigitalProductController extends Controller
{
    // Method untuk uploadComplete, syncCompletedOrders, dll. tetap sama...
    // ... (Tidak perlu mengubah method-method ini)
    public function uploadComplete(Request $request)
    {
        $request->validate(['complete_document' => 'required|file|mimes:xlsx,xls,csv']);
        $path = $request->file('complete_document')->store('excel-imports-complete', 'local');

        $batch = Bus::batch([
            new ProcessCompletedOrders($path),
        ])->name('Import Order Complete')->dispatch();

        return Redirect::back()->with([
            'success' => 'File Order Complete diterima.',
            'batchId' => $batch->id,
            'jobType' => 'complete'
        ]);
    }

    public function syncCompletedOrders()
    {
        // 1. Ambil semua order_id dari tabel sementara, bersihkan, dan pastikan unik.
        $orderIdsToUpdate = CompletedOrder::pluck('order_id')
            ->map('trim')       // Hapus spasi di awal/akhir
            ->filter()         // Hapus entri yang kosong
            ->map(fn($id) => (string) $id) // Konversi semua ID ke string untuk konsistensi
            ->unique();       // Ambil ID yang unik saja

        if ($orderIdsToUpdate->isEmpty()) {
            return Redirect::back()->with('error', 'Tidak ada data order complete yang perlu disinkronkan.');
        }

        // 2. Cari order di tabel utama (DocumentData) yang statusnya 'in progress'
        //    DAN order_id-nya ada di dalam daftar yang kita dapat dari file Excel.
        $ordersToUpdateAndLog = DocumentData::where('status_wfm', 'in progress')
            ->whereIn('order_id', $orderIdsToUpdate)
            ->get(); // Ambil data lengkapnya untuk proses logging

        if ($ordersToUpdateAndLog->isEmpty()) {
            CompletedOrder::truncate(); // Tetap bersihkan tabel sementara
            return Redirect::back()->with('info', 'Tidak ada order "In Progress" yang cocok untuk di-complete dari daftar yang diunggah.');
        }

        // 3. Lakukan update HANYA pada order_id yang sudah kita filter di langkah #2.
        $orderIdsToActuallyUpdate = $ordersToUpdateAndLog->pluck('order_id');

        $updatedCount = DocumentData::whereIn('order_id', $orderIdsToActuallyUpdate)
            ->update([
                'status_wfm' => 'done close bima',
                'milestone' => 'Completed via Sync Process',
                'order_status_n' => 'COMPLETE'
            ]);

        // 4. Buat log HANYA untuk data yang berhasil diupdate.
        $logs = $ordersToUpdateAndLog->map(function ($order) {
            return [
                'order_id' => $order->order_id,
                'product_name' => $order->product_name ?? $order->product,
                'customer_name' => $order->customer_name,
                'nama_witel' => $order->nama_witel,
                'status_lama' => $order->status_wfm,
                'status_baru' => 'done close bima',
                'sumber_update' => 'Upload Complete',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        if (!empty($logs)) {
            UpdateLog::insert($logs);
        }

        // 5. Kosongkan tabel sementara setelah proses selesai.
        CompletedOrder::truncate();

        return Redirect::back()->with('success', "Sinkronisasi selesai. Berhasil mengupdate {$updatedCount} order.");
    }

    public function clearHistory()
    {
        // Perintah ini akan menghapus semua record dari tabel update_logs
        UpdateLog::truncate();

        // Redirect kembali ke halaman sebelumnya dengan pesan sukses
        return Redirect::back()->with('success', 'Seluruh data histori berhasil dihapus.');
    }

    // ===================================================================
    //  METHOD INDEX UTAMA (YANG DIPERBAIKI)
    // ===================================================================
    public function index(Request $request)
    {
        // LANGKAH 1: PERSIAPAN FILTER
        $periodInput = $request->input('period', now()->format('Y-m'));
        $selectedSegment = $request->input('segment', 'SME');
        $reportPeriod = \Carbon\Carbon::parse($periodInput)->startOfMonth();
        $inProgressYear = $request->input('in_progress_year', now()->year);
        $searchQuery = $request->input('search', '');
        $paginationCount = 15;

        // ===================================================================
        // LANGKAH 2: DATA REPORT UTAMA (TIDAK BERUBAH)
        // ===================================================================
        $masterWitelList = ['BALI', 'JATIM BARAT', 'JATIM TIMUR', 'NUSA TENGGARA', 'SURAMADU'];
        $productMap = [
            'netmonk' => 'n', 'oca' => 'o',
            'antares' => 'ae', 'antares eazy' => 'ae',
            'pijar' => 'ps'
        ];
        // ... (Logika untuk mengisi $reportData tetap sama)
        $reportDataMap = collect($masterWitelList)->mapWithKeys(function ($witel) use ($productMap) {
             $data = ['nama_witel' => $witel];
             $initials = array_unique(array_values($productMap));
             foreach ($initials as $initial) {
                 $data["in_progress_{$initial}"] = 0;
                 $data["prov_comp_{$initial}_realisasi"] = 0;
                 $data["prov_comp_{$initial}_target"] = 0;
                 $data["revenue_{$initial}_ach"] = 0;
                 $data["revenue_{$initial}_target"] = 0;
             }
             return [$witel => $data];
         });

         $realizationDocuments = DocumentData::whereIn('nama_witel', $masterWitelList)
             ->where('segment', $selectedSegment)->where('status_wfm', 'done close bima')
             ->whereYear('order_created_date', $reportPeriod->year)->whereMonth('order_date', $reportPeriod->month)
             ->get();

         $inProgressDocuments = DocumentData::whereIn('nama_witel', $masterWitelList)
             ->where('segment', $selectedSegment)->where('status_wfm', 'in progress')
             ->whereYear('order_created_date', $reportPeriod->year)->whereMonth('order_created_date', $reportPeriod->month)
             ->get();

         foreach ($inProgressDocuments as $doc) {
             $witel = $doc->nama_witel;
             $pName = strtolower(trim($doc->product));
             if (isset($productMap[$pName]) && $reportDataMap->has($witel)) {
                 $currentData = $reportDataMap->get($witel);
                 $initial = $productMap[$pName];
                 $currentData["in_progress_{$initial}"]++;
                 $reportDataMap->put($witel, $currentData);
             }
         }

         foreach ($realizationDocuments as $doc) {
             $witel = $doc->nama_witel;
             $pName = strtolower(trim($doc->product));
             if (isset($productMap[$pName]) && $reportDataMap->has($witel)) {
                 $currentData = $reportDataMap->get($witel);
                 $initial = $productMap[$pName];
                 $currentData["prov_comp_{$initial}_realisasi"]++;
                 $currentData["revenue_{$initial}_ach"] += $doc->net_price;
                 $reportDataMap->put($witel, $currentData);
             }
         }

         $targets = Target::where('segment', $selectedSegment)->where('period', $reportPeriod->format('Y-m-d'))->get();
         foreach ($targets as $target) {
             $witel = $target->nama_witel;
             $pName = strtolower(trim($target->product_name));
             if (isset($reportDataMap[$witel]) && isset($productMap[$pName])) {
                 $currentData = $reportDataMap->get($witel);
                 $initial = $productMap[$pName];
                 $metricKey = $target->metric_type;
                 $currentData["{$metricKey}_{$initial}_target"] = $target->target_value;
                 $reportDataMap->put($witel, $currentData);
             }
         }

         foreach ($reportDataMap as $witel => $data) {
             $currentData = $reportDataMap->get($witel);
             foreach (array_unique(array_values($productMap)) as $initial) {
                 $currentData["revenue_{$initial}_ach"] /= 1000000;
             }
             $reportDataMap->put($witel, $currentData);
         }

         $reportData = $reportDataMap->values()->map(fn($item) => (object) $item);

        // ===================================================================
        // LANGKAH 3: Kueri untuk TABEL-TABEL DI BAWAH (PAGINASI DIPERBAIKI)
        // ===================================================================
        $inProgressQuery = DocumentData::query()
            ->where('status_wfm', 'in progress')
            ->where('segment', $selectedSegment)
            ->whereYear('order_created_date', $inProgressYear);

        $completeQuery = DocumentData::query()
            ->where('status_wfm', 'done close bima')
            ->where('segment', $selectedSegment);

        $qcQuery = DocumentData::query()
            ->where('status_wfm', '');

        // Terapkan filter pencarian HANYA ke kueri yang relevan
        if ($searchQuery) {
            $inProgressQuery->where('order_id', 'like', '%' . $searchQuery . '%');
            $completeQuery->where('order_id', 'like', '%' . $searchQuery . '%');
            $qcQuery->where('order_id', 'like', '%' . $searchQuery . '%');
        }

        // EKSEKUSI KUERI DENGAN NAMA PAGINASI UNIK UNTUK MENGHINDARI KONFLIK
        $inProgressData = $inProgressQuery->orderBy('order_created_date', 'desc')
            ->paginate($paginationCount, ['*'], 'in_progress_page') // <-- NAMA UNIK
            ->withQueryString();

        $completeData = $completeQuery->orderBy('updated_at', 'desc')
            ->paginate($paginationCount, ['*'], 'complete_page') // <-- NAMA UNIK
            ->withQueryString();

        $qcData = $qcQuery->orderBy('updated_at', 'desc')
            ->paginate($paginationCount, ['*'], 'qc_page') // <-- NAMA UNIK
            ->withQueryString();

        // Kueri untuk History tidak terpengaruh filter search, dan punya nama paginasi sendiri
        $historyData = UpdateLog::latest()
            ->paginate(10, ['*'], 'history_page') // <-- NAMA UNIK
            ->withQueryString();

        // ===================================================================
        // LANGKAH 4: KALKULASI KPI (TIDAK BERUBAH)
        // ===================================================================
        $officers = AccountOfficer::orderBy('name')->get();
        $kpiData = $officers->map(function ($officer) {
            // ... (Logika KPI Anda tetap sama, tidak perlu diubah) ...
            $witelFilter = $officer->filter_witel_lama;
            $specialFilter = null;
            if ($officer->special_filter_column && $officer->special_filter_value) {
                $specialFilter = ['column' => $officer->special_filter_column, 'value' => $officer->special_filter_value];
            }

            $singleQuery = DocumentData::where('witel_lama', $witelFilter)
                ->whereNotNull('product')->where('product', 'NOT LIKE', '%-%')->where('product', 'NOT LIKE', "%\n%");
            if ($specialFilter) {
                $singleQuery->where($specialFilter['column'], $specialFilter['value']);
            }

            $bundleQuery = DB::table('order_products')
                ->join('document_data', 'order_products.order_id', '=', 'document_data.order_id')
                ->where('document_data.witel_lama', $witelFilter);
            if ($specialFilter) {
                $bundleQuery->where('document_data.' . $specialFilter['column'], $specialFilter['value']);
            }

            $done_scone_single = $singleQuery->clone()->where('status_wfm', 'done close bima')->where('channel', 'SC-One')->count();
            $done_ncx_single   = $singleQuery->clone()->where('status_wfm', 'done close bima')->where('channel', '!=', 'SC-One')->count();
            $ogp_scone_single  = $singleQuery->clone()->where('status_wfm', 'in progress')->where('channel', 'SC-One')->count();
            $ogp_ncx_single    = $singleQuery->clone()->where('status_wfm', 'in progress')->where('channel', '!=', 'SC-One')->count();

            $done_scone_bundle = $bundleQuery->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', 'SC-One')->count();
            $done_ncx_bundle   = $bundleQuery->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', '!=', 'SC-One')->count();
            $ogp_scone_bundle  = $bundleQuery->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', 'SC-One')->count();
            $ogp_ncx_bundle    = $bundleQuery->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', '!=', 'SC-One')->count();

            $done_ncx   = $done_ncx_single + $done_ncx_bundle;
            $done_scone = $done_scone_single + $done_scone_bundle;
            $ogp_ncx    = $ogp_ncx_single + $ogp_ncx_bundle;
            $ogp_scone  = $ogp_scone_single + $ogp_scone_bundle;
            $total_ytd  = $done_ncx + $done_scone + $ogp_ncx + $ogp_scone;

            $q3Months = [7, 8, 9];
            $q3Year = 2025;

            $singleQueryQ3 = $singleQuery->clone()->whereYear('order_created_date', $q3Year)->whereIn(DB::raw('MONTH(order_created_date)'), $q3Months);
            $bundleQueryQ3 = $bundleQuery->clone()->whereYear('document_data.order_created_date', $q3Year)->whereIn(DB::raw('MONTH(document_data.order_created_date)'), $q3Months);

            $done_scone_single_q3 = $singleQueryQ3->clone()->where('status_wfm', 'done close bima')->where('channel', 'SC-One')->count();
            $done_ncx_single_q3   = $singleQueryQ3->clone()->where('status_wfm', 'done close bima')->where('channel', '!=', 'SC-One')->count();
            $ogp_scone_single_q3  = $singleQueryQ3->clone()->where('status_wfm', 'in progress')->where('channel', 'SC-One')->count();
            $ogp_ncx_single_q3    = $singleQueryQ3->clone()->where('status_wfm', 'in progress')->where('channel', '!=', 'SC-One')->count();

            $done_scone_bundle_q3 = $bundleQueryQ3->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', 'SC-One')->count();
            $done_ncx_bundle_q3   = $bundleQueryQ3->clone()->where('order_products.status_wfm', 'done close bima')->where('order_products.channel', '!=', 'SC-One')->count();
            $ogp_scone_bundle_q3  = $bundleQueryQ3->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', 'SC-One')->count();
            $ogp_ncx_bundle_q3    = $bundleQueryQ3->clone()->where('order_products.status_wfm', 'in progress')->where('order_products.channel', '!=', 'SC-One')->count();

            $done_ncx_q3   = $done_ncx_single_q3 + $done_ncx_bundle_q3;
            $done_scone_q3 = $done_scone_single_q3 + $done_scone_bundle_q3;
            $ogp_ncx_q3    = $ogp_ncx_single_q3 + $ogp_ncx_bundle_q3;
            $ogp_scone_q3  = $ogp_scone_single_q3 + $ogp_scone_bundle_q3;
            $total_q3      = $done_ncx_q3 + $done_scone_q3 + $ogp_ncx_q3 + $ogp_scone_q3;

            $ach_ytd = ($total_ytd > 0)
                ? number_format((($done_ncx + $done_scone) / $total_ytd) * 100, 1) . '%'
                : '0.0%';

            $ach_q3 = ($total_q3 > 0)
                ? number_format((($done_ncx_q3 + $done_scone_q3) / $total_q3) * 100, 1) . '%'
                : '0.0%';

            return [
                'id'         => $officer->id,
                'nama_po'    => $officer->name,
                'witel'      => $officer->display_witel,
                'done_ncx'   => $done_ncx,
                'done_scone' => $done_scone,
                'ogp_ncx'    => $ogp_ncx,
                'ogp_scone'  => $ogp_scone,
                'total'      => $total_ytd,
                'ach_ytd'    => $ach_ytd,
                'ach_q3'     => $ach_q3,
            ];
        });

        // ===================================================================
        // LANGKAH 5: RENDER DENGAN SEMUA DATA
        // ===================================================================
        return Inertia::render('AnalysisDigitalProduct', [
            'reportData' => $reportData,
            'currentSegment' => $selectedSegment,
            'period' => $periodInput,
            'inProgressData' => $inProgressData,
            'completeData' => $completeData, // Tambahkan completeData
            'historyData' => $historyData,
            'qcData' => $qcData,
            'accountOfficers' => $officers,
            'kpiData' => $kpiData,
            'currentInProgressYear' => $inProgressYear,
            'filters' => $request->only(['search', 'period', 'segment', 'in_progress_year']),
        ]);
    }

    // ... (method-method lain seperti updateTargets, getImportProgress, upload, dll. tetap sama)
    // ... (Tidak perlu mengubah method-method ini)
     public function saveTableConfig(Request $request)
    {
        $validated = $request->validate([
            'configuration' => 'required|array',
            'segment' => 'required|string|in:SME,LEGS'
        ]);

        $pageName = 'analysis_digital_' . strtolower($validated['segment']);

        TableConfiguration::updateOrCreate(
            ['user_id' => Auth::id(), 'page_name' => $pageName],
            ['configuration' => $validated['configuration']]
        );

        return Redirect::back()->with('success', 'Tampilan tabel berhasil disimpan!');
    }

    public function updateTargets(Request $request)
    {
        $validated = $request->validate([
            'targets' => 'required|array', 'segment' => 'required|string|in:SME,LEGS',
            'period' => 'required|date_format:Y-m-d', 'targets.*.prov_comp.*' => 'nullable|numeric',
            'targets.*.revenue.*' => 'nullable|numeric',
        ]);
        foreach ($validated['targets'] as $witelName => $metrics) {
            foreach ($metrics as $metricType => $products) {
                foreach ($products as $productInitial => $targetValue) {
                    $productName = $this->mapProductInitialToName($productInitial);
                    if (!$productName) continue;
                    Target::updateOrCreate(
                        ['segment' => $validated['segment'], 'period' => $validated['period'], 'nama_witel' => $witelName, 'metric_type' => $metricType, 'product_name' => $productName],
                        ['target_value' => $targetValue ?? 0]
                    );
                }
            }
        }
        return Redirect::back()->with('success', 'Target berhasil diperbarui!');
    }

    private function mapProductInitialToName(string $initial): ?string
    {
        $map = ['n' => 'Netmonk', 'o' => 'OCA', 'ae' => 'Antares Eazy', 'ps' => 'Pijar Sekolah'];
        return $map[strtolower($initial)] ?? null;
    }

    public function getImportProgress(string $batchId)
    {
        $batch = Bus::findBatch($batchId);
        if (!$batch || $batch->finished()) {
            return response()->json(['progress' => 100]);
        }
        $progress = Cache::get('import_progress_' . $batchId, 0);
        return response()->json(['progress' => $progress]);
    }

    public function upload(Request $request)
    {
        $request->validate(['document' => 'required|file|mimes:xlsx,xls,csv']);
        $path = $request->file('document')->store('excel-imports', 'local');

        $batch = Bus::batch([
            // Pastikan namespace ke Job Anda sudah benar
            new \App\Jobs\ImportAndProcessDocument($path),
        ])->name('Import Data Mentah')->dispatch();

        // Baris ini adalah KUNCI-nya. Ia harus mengarah ke 'index', BUKAN ke URL lain.
        return Inertia::location(route('analysisDigitalProduct.index', [
            'batch_id' => $batch->id,
            'job_type' => 'mentah',
            // Kita juga tambahkan filter yang sedang aktif agar tidak hilang saat redirect
            'segment' => $request->input('segment', 'SME'),
            'period' => $request->input('period', now()->format('Y-m')),
            'in_progress_year' => $request->input('in_progress_year', now()->year),
        ]));
    }

    private function updateOrderStatus($orderId, $newStatusWfm, $newOrderStatusN, $milestone, $logSource)
    {
        $order = DocumentData::where('order_id', $orderId)->first();

        if (!$order) {
            return Redirect::back()->with('error', "Order ID: {$orderId} tidak ditemukan.");
        }

        $oldStatus = $order->status_wfm;

        if ($oldStatus === $newStatusWfm) {
             return Redirect::back()->with('info', "Order ID: {$orderId} sudah dalam status yang dituju.");
        }

        UpdateLog::create([
            'order_id' => $order->order_id,
            'product_name' => $order->product_name ?? $order->product,
            'customer_name' => $order->customer_name,
            'nama_witel' => $order->nama_witel,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatusWfm,
            'sumber_update' => $logSource,
        ]);

        $order->status_wfm = $newStatusWfm;
        $order->order_status_n = $newOrderStatusN;
        $order->milestone = $milestone;
        $order->save();

        return Redirect::back()->with('success', "Order ID: {$orderId} berhasil diupdate ke status '{$newStatusWfm}'.");
    }

    public function updateManualComplete(Request $request, $order_id)
    {
        return $this->updateOrderStatus(
            $order_id,
            'done close bima',
            'COMPLETE',
            'Completed Manually',
            'Manual Action'
        );
    }

    public function updateManualCancel(Request $request, $order_id)
    {
        return $this->updateOrderStatus(
            $order_id,
            'done close cancel',
            'CANCEL',
            'Canceled Manually',
            'Manual Action'
        );
    }

    public function updateCompleteToProgress(Request $request, $order_id)
    {
        return $this->updateOrderStatus(
            $order_id,
            'in progress',
            'IN PROGRESS',
            'Reverted to In Progress from Complete',
            'Manual Action from Complete Tab'
        );
    }

    public function updateCompleteToCancel(Request $request, $order_id)
    {
        return $this->updateOrderStatus(
            $order_id,
            'done close cancel',
            'CANCEL',
            'Changed to Cancel from Complete',
            'Manual Action from Complete Tab'
        );
    }

    public function updateQcStatusToProgress(Request $request, $order_id)
    {
        $order = DocumentData::find($order_id);
        if ($order) {
            return $this->updateOrderStatus(
                $order->order_id,
                'in progress',
                'IN PROGRESS',
                'QC Processed - Return to In Progress',
                'Manual Action from QC Tab'
            );
        }
        return Redirect::back()->with('error', "Order dengan ID: {$order_id} tidak ditemukan.");
    }

    public function updateQcStatusToDone(Request $request, $order_id)
    {
        $order = DocumentData::find($order_id);
        if ($order) {
            return $this->updateOrderStatus(
                $order->order_id,
                'done close bima',
                'COMPLETE',
                'QC Processed - Marked as Complete',
                'Manual Action from QC Tab'
            );
        }
        return Redirect::back()->with('error', "Order dengan ID: {$order_id} tidak ditemukan.");
    }

    public function updateQcStatusToCancel(Request $request, $order_id)
    {
        $order = DocumentData::find($order_id);
        if ($order) {
            return $this->updateOrderStatus(
                $order->order_id,
                'done close cancel',
                'CANCEL',
                'QC Processed - Marked as Cancel',
                'Manual Action from QC Tab'
            );
        }
        return Redirect::back()->with('error', "Order dengan ID: {$order_id} tidak ditemukan.");
    }

    public function exportInProgress(Request $request)
    {
        $segment = $request->input('segment', 'SME');
        $year = $request->input('in_progress_year', now()->year);
        $fileName = 'in_progress_data_' . $segment . '_' . $year . '.xlsx';
        return Excel::download(new InProgressExport($segment, $year), $fileName);
    }

    public function uploadCancel(Request $request)
    {
        $request->validate(['cancel_document' => 'required|file|mimes:xlsx,xls,csv']);
        $path = $request->file('cancel_document')->store('excel-imports-cancel', 'local');

        $batch = Bus::batch([
            new ProcessCanceledOrders($path),
        ])->name('Import Order Cancel')->dispatch();

        return Redirect::back()->with([
            'success' => 'File Order Cancel diterima.',
            'batchId' => $batch->id,
            'jobType' => 'cancel'
        ]);
    }

    public function syncCanceledOrders()
    {
        // Ambil semua order_id, trim spasi, filter nilai kosong, LALU UBAH SEMUA JADI STRING
        $orderIdsToUpdate = CanceledOrder::pluck('order_id')
            ->map('trim')
            ->filter()
            ->map(fn($id) => (string) $id) // <-- TAMBAHKAN BARIS INI
            ->unique(); // Tambahkan unique untuk mencegah duplikasi

        if ($orderIdsToUpdate->isEmpty()) {
            return Redirect::back()->with('error', 'Tidak ada data order cancel yang perlu disinkronkan.');
        }

        // Sekarang $orderIdsToUpdate berisi array string yang siap dibandingkan
        $ordersToLog = DocumentData::whereIn('order_id', $orderIdsToUpdate)
            ->where('status_wfm', 'in progress')
            ->get();

        if ($ordersToLog->isEmpty()) {
            CanceledOrder::truncate(); // Tetap bersihkan tabel sementara
            return Redirect::back()->with('info', 'Tidak ada order "In Progress" yang cocok untuk di-cancel dari daftar yang diberikan.');
        }

        // Lakukan update HANYA pada order yang sudah kita ambil datanya
        $updatedCount = DocumentData::whereIn('order_id', $ordersToLog->pluck('order_id'))
            ->update([
                'status_wfm' => 'done close cancel',
                'milestone' => 'Canceled via Sync Process',
                'order_status_n' => 'CANCEL'
            ]);

        // Buat log dari variabel $ordersToLog yang sudah kita siapkan
        $logs = $ordersToLog->map(function ($order) {
            return [
                'order_id' => $order->order_id,
                // Pastikan kolom product_name ada di model atau gunakan kolom 'product'
                'product_name' => $order->product_name ?? $order->product,
                'customer_name' => $order->customer_name,
                'nama_witel' => $order->nama_witel,
                'status_lama' => $order->status_wfm,
                'status_baru' => 'done close cancel',
                'sumber_update' => 'Upload Cancel',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        if (!empty($logs)) {
            UpdateLog::insert($logs);
        }

        // Kosongkan tabel sementara
        CanceledOrder::truncate();

        return Redirect::back()->with('success', "Sinkronisasi selesai. Berhasil meng-cancel {$updatedCount} order.");
    }
}
