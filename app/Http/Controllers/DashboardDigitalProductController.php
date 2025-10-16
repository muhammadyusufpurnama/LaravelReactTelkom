<?php

namespace App\Http\Controllers;

use App\Models\DocumentData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardDigitalProductController extends Controller
{
    public function index(Request $request)
    {
        // 1. [PERBAIKAN] Validasi filter untuk menerima array
        $validated = $request->validate([
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
            'products' => 'nullable|array', 'products.*' => 'string|max:255',
            'witels' => 'nullable|array', 'witels.*' => 'string|max:255',
            'subTypes' => 'nullable|array', 'subTypes.*' => 'string|in:AO,SO,DO,MO,RO',
            'branches' => 'nullable|array', 'branches.*' => 'string|max:255',
            'limit' => 'nullable|in:10,50,100,500',
        ]);
        $limit = $validated['limit'] ?? '10';

        $initialStartDate = now()->startOfYear()->format('Y-m-d');
        $latestOrderDate = DocumentData::max('order_date');
        $initialEndDate = $latestOrderDate ? \Carbon\Carbon::parse($latestOrderDate)->format('Y-m-d') : now()->format('Y-m-d');

        $startDateToUse = $request->input('startDate', $initialStartDate);
        $endDateToUse = $request->input('endDate', $initialEndDate);

        // Daftar opsi filter (tidak berubah)
        $products = ['Netmonk', 'OCA', 'Antares Eazy', 'Pijar'];
        $subTypes = ['AO', 'SO', 'DO', 'MO', 'RO'];
        $witelList = DocumentData::query()->select('nama_witel')->whereNotNull('nama_witel')->distinct()->orderBy('nama_witel')->pluck('nama_witel');
        $branchList = DocumentData::query()->select('telda')->whereNotNull('telda')->distinct()->orderBy('telda')->pluck('telda');

        // CASE statements untuk produk dan sub-type (tidak berubah)
        $productCaseStatement = 'CASE '.
            "WHEN UPPER(TRIM(product)) LIKE 'NETMONK%' THEN 'Netmonk' ".
            "WHEN UPPER(TRIM(product)) LIKE 'OCA%' THEN 'OCA' ".
            "WHEN UPPER(TRIM(product)) LIKE 'ANTARES EAZY%' THEN 'Antares Eazy' ".
            "WHEN UPPER(TRIM(product)) LIKE 'PIJAR%' THEN 'Pijar' ".
            'ELSE NULL END';

        $subTypeMapping = [
            'AO' => ['New Install', 'ADD SERVICE', 'NEW SALES'], 'MO' => ['MODIFICATION', 'Modify'],
            'SO' => ['Suspend'], 'DO' => ['Disconnect'], 'RO' => ['Resume'],
        ];
        $subTypeCaseStatement = 'CASE ';
        foreach ($subTypeMapping as $group => $types) {
            $inClause = implode("', '", array_map(fn ($v) => strtoupper(trim($v)), $types));
            $subTypeCaseStatement .= "WHEN UPPER(TRIM(order_sub_type)) IN ('".$inClause."') THEN '".$group."' ";
        }
        $subTypeCaseStatement .= 'ELSE NULL END';

        // [PERBAIKAN] Fungsi closure untuk filter global yang menangani array
        $applyFilters = function ($query) use ($startDateToUse, $endDateToUse, $validated, $subTypeCaseStatement, $productCaseStatement) {
            $query
                // Selalu terapkan filter tanggal
                ->whereBetween('order_date', [$startDateToUse.' 00:00:00', $endDateToUse.' 23:59:59'])

                // Terapkan filter lain jika ada
                ->when($validated['products'] ?? null, fn ($q, $p) => $q->whereIn(DB::raw($productCaseStatement), $p))
                ->when($validated['witels'] ?? null, fn ($q, $w) => $q->whereIn('nama_witel', $w))
                ->when($validated['branches'] ?? null, fn ($q, $b) => $q->whereIn('telda', $b))
                ->when($validated['subTypes'] ?? null, fn ($q, $st) => $q->whereIn(DB::raw($subTypeCaseStatement), $st));
        };

        // Semua query di bawah ini akan menggunakan closure $applyFilters yang baru
        // dan tidak perlu diubah sama sekali.

        // --- Query untuk Revenue by Sub-type ---
        $revenueBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('SUM(net_price) as total_revenue'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))->where('net_price', '>', 0)
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

        // --- Query untuk Amount by Sub-type ---
        $amountBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('COUNT(*) as total_amount'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

        // --- Query lainnya (Session, Radar, Pie, Preview) ---
        // (Tidak perlu ada perubahan di sini karena mereka sudah menggunakan ->tap($applyFilters))
        $sessionBySubTypeQuery = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw('COUNT(*) as total'))
            ->whereNotNull(DB::raw($subTypeCaseStatement))->groupBy('sub_type')->tap($applyFilters);
        $existingSubTypeCounts = $sessionBySubTypeQuery->get()->keyBy('sub_type');
        $allSubTypes = collect($subTypes)->map(fn ($st) => ['sub_type' => $st, 'total' => 0]);
        $sessionBySubType = $allSubTypes->map(function ($item) use ($existingSubTypeCounts) {
            if ($existingSubTypeCounts->has($item['sub_type'])) {
                $item['total'] = $existingSubTypeCounts->get($item['sub_type'])['total'];
            }

            return $item;
        });

        $productRadarData = DocumentData::query()
            ->select('nama_witel', ...collect($products)->map(fn ($p) => DB::raw('SUM(CASE WHEN '.$productCaseStatement." = '{$p}' THEN 1 ELSE 0 END) as `{$p}`")))
            ->whereNotNull('nama_witel')->whereNotNull(DB::raw($productCaseStatement))
            ->groupBy('nama_witel')->tap($applyFilters)->get();

        $witelPieData = DocumentData::query()->select('nama_witel', DB::raw('COUNT(*) as value'))
            ->groupBy('nama_witel')->tap($applyFilters)->get();

        $dataPreview = DocumentData::query()
            ->select('order_id', 'product', 'milestone', 'nama_witel', 'status_wfm', 'order_created_date', 'order_date')
            ->orderBy('order_date', 'desc')->tap($applyFilters)->paginate($limit)->withQueryString();

        return Inertia::render('DashboardDigitalProduct', [
            'revenueBySubTypeData' => $revenueBySubTypeData,
            'amountBySubTypeData' => $amountBySubTypeData,
            'sessionBySubType' => $sessionBySubType,
            'productRadarData' => $productRadarData,
            'witelPieData' => $witelPieData,
            'dataPreview' => $dataPreview,
            'filters' => [
                'startDate' => $startDateToUse, // Kirim tanggal yang benar-benar digunakan
                'endDate' => $endDateToUse,     // Kirim tanggal yang benar-benar digunakan
                'products' => $request->input('products'),
                'witels' => $request->input('witels'),
                'subTypes' => $request->input('subTypes'),
                'branches' => $request->input('branches'),
                'limit' => $request->input('limit', '10'),
            ],
            'filterOptions' => [
                'products' => $products, 'witelList' => $witelList, 'subTypes' => $subTypes, 'branchList' => $branchList,
            ],
        ]);
    }
}
