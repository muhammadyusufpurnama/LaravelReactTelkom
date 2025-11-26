<?php

namespace App\Http\Controllers;

use App\Models\SosData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardSOSController extends Controller
{
    // [BARU] Buat fungsi private untuk kueri agar tidak duplikat kode
    private function getDashboardData(Request $request)
    {
        // 1. Validasi filter
        $validated = $request->validate([
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
            'witels' => 'nullable|array', 'witels.*' => 'string',
            'segmens' => 'nullable|array', 'segmens.*' => 'string',
            'kategoris' => 'nullable|array', 'kategoris.*' => 'string',
            'limit' => 'nullable|in:10,50,100',
        ]);

        $limit = $validated['limit'] ?? '10';

        // 2. Base Query Awal (Untuk Filter Dropdown)
        // Kita exclude RSO1 dari awal agar tidak muncul di opsi filter juga
        $rootQuery = SosData::query()
            ->where('witel_baru', '!=', 'RSO1'); // [BARU] Filter Global Exclude RSO1

        // 3. Siapkan opsi untuk dropdown filter (Menggunakan $rootQuery agar RSO1 tidak muncul di list)
        $filterOptions = [
            'witelList' => (clone $rootQuery)->select(DB::raw('TRIM(UPPER(bill_witel)) as witel'))->whereNotNull('bill_witel')->distinct()->orderBy('witel')->pluck('witel'),
            'segmenList' => (clone $rootQuery)->select(DB::raw('TRIM(UPPER(segmen)) as segmen'))->whereNotNull('segmen')->distinct()->orderBy('segmen')->pluck('segmen'),
            'kategoriList' => (clone $rootQuery)->select('kategori')->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori'),
            'umurList' => (clone $rootQuery)->select('kategori_umur')->whereNotNull('kategori_umur')->distinct()->orderBy('kategori_umur')->pluck('kategori_umur'),
        ];

        // 4. Buat closure untuk menerapkan filter User
        $applyFilters = function ($query) use ($validated) {
            if (!empty($validated['startDate'])) {
                $query->where('order_created_date', '>=', $validated['startDate'].' 00:00:00');
            }
            if (!empty($validated['endDate'])) {
                $query->where('order_created_date', '<=', $validated['endDate'].' 23:59:59');
            }
            if (!empty($validated['witels'])) {
                $query->whereIn(DB::raw('TRIM(UPPER(bill_witel))'), $validated['witels']);
            }
            if (!empty($validated['segmens'])) {
                $query->whereIn(DB::raw('TRIM(UPPER(segmen))'), $validated['segmens']);
            }
            if (!empty($validated['kategoris'])) {
                $query->whereIn('kategori', $validated['kategoris']);
            }
        };

        // 5. Query Utama untuk Data Chart
        // Gabungkan Filter Global (Exclude RSO1) + Filter User
        $baseQuery = SosData::query()
            ->where('witel_baru', '!=', 'RSO1') // [PENTING] Pastikan data grafik juga kena filter ini
            ->tap($applyFilters);

        $ordersByCategory = (clone $baseQuery)->select(
            'kategori',
            DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' THEN 1 ELSE 0 END) as lt_3bln_total"),
            DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' THEN 1 ELSE 0 END) as gt_3bln_total")
        )->whereNotNull('kategori')->groupBy('kategori')->get();

        $revenueByCategory = (clone $baseQuery)->select(
            'kategori',
            DB::raw("SUM(CASE WHEN kategori_umur = '< 3 BLN' THEN revenue ELSE 0 END) / 1000000 as lt_3bln_revenue"),
            DB::raw("SUM(CASE WHEN kategori_umur = '> 3 BLN' THEN revenue ELSE 0 END) / 1000000 as gt_3bln_revenue")
        )->whereNotNull('kategori')->groupBy('kategori')->get();

        $witelDistribution = (clone $baseQuery)->select(
            DB::raw('TRIM(UPPER(bill_witel)) as witel'),
            DB::raw('COUNT(*) as value')
        )->whereNotNull('bill_witel')->groupBy('witel')->orderBy('value', 'desc')->get();

        $segmenDistribution = (clone $baseQuery)->select(
            DB::raw('TRIM(UPPER(segmen)) as witel'),
            DB::raw('COUNT(*) as value')
        )->whereNotNull('segmen')->groupBy('witel')->orderBy('value', 'desc')->get();

        // 6. Query untuk Data Preview
        $dataPreview = SosData::query()
            ->select(
                'id', 'order_id', 'nipnas', 'standard_name', 'li_product_name',
                'segmen', 'bill_witel',
                'kategori', 'li_status', 'kategori_umur', 'order_created_date'
            )
            ->where('witel_baru', '!=', 'RSO1') // [PENTING] Tabel preview juga tidak boleh menampilkan RSO1
            ->tap($applyFilters)
            ->orderBy('order_created_date', 'desc')
            ->paginate($limit)
            ->withQueryString();

        // 7. Kembalikan semua data
        return [
            'ordersByCategory' => $ordersByCategory,
            'revenueByCategory' => $revenueByCategory,
            'witelDistribution' => $witelDistribution,
            'segmenDistribution' => $segmenDistribution,
            'dataPreview' => $dataPreview,
            'filters' => $validated + ['limit' => $limit],
            'filterOptions' => $filterOptions,
        ];
    }

    public function index(Request $request)
    {
        $settings = Cache::get('granular_embed_settings', []);

        if (isset($settings['datin']) && $settings['datin']['enabled'] && !empty($settings['datin']['url'])) {
            return Inertia::render('Dashboard/ExternalEmbed', [
                'embedUrl' => $settings['datin']['url'],
                'headerTitle' => 'Dashboard SOS Datin'
            ]);
        }

        $data = $this->getDashboardData($request);
        return Inertia::render('DashboardSOS', array_merge($data, [
            'isEmbed' => false,
        ]));
    }

    public function embed(Request $request)
    {
        $data = $this->getDashboardData($request);

        return Inertia::render('DashboardSOS', array_merge($data, [
            'isEmbed' => true,
        ]))->rootView('embed');
    }
}
