<?php

namespace App\Http\Controllers;

use App\Models\DocumentData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardDigitalProductController extends Controller
{
    // Definisi Mapping
    private function getWitelBranchMap()
    {
        return [
            'SURAMADU' => ['BANGKALAN', 'GRESIK', 'KENJERAN', 'KETINTANG', 'LAMONGAN', 'MANYAR', 'PAMEKASAN', 'TANDES'],
            'JATIM BARAT' => ['BATU', 'BLITAR', 'BOJONEGORO', 'KEDIRI', 'KEPANJEN', 'MADIUN', 'NGANJUK', 'NGAWI', 'PONOROGO', 'TRENGGALEK', 'TUBAN', 'TULUNGAGUNG'],
            'JATIM TIMUR' => ['BANYUWANGI', 'BONDOWOSO', 'JEMBER', 'JOMBANG', 'LUMAJANG', 'MOJOKERTO', 'PASURUAN', 'PROBOLINGGO', 'SITUBONDO'],
            'BALI' => ['SINGARAJA', 'GIANYAR', 'KLUNGKUNG', 'TABANAN', 'BULELENG', 'JEMBRANA', 'SANUR', 'UBUNG', 'JIMBRAN'],
            'NUSA TENGGARA' => ['ATAMBUA', 'BIMA', 'ENDE', 'KUPANG', 'LABUAN BAJO', 'LOMBOK BARAT TENGAH', 'LOMBOK TIMUR UTARA', 'MAUMERE', 'SUMBAWA', 'WAKAIBUBAK', 'WAINGAPU'],
        ];
    }

    public function index(Request $request)
    {
        $settings = Cache::get('granular_embed_settings', []);

        if (isset($settings['digitalProduct']) && $settings['digitalProduct']['enabled'] && !empty($settings['digitalProduct']['url'])) {
            return Inertia::render('Dashboard/ExternalEmbed', [
                'embedUrl' => $settings['digitalProduct']['url'],
                'headerTitle' => 'Dashboard Digital Product',
            ]);
        }

        // 1. Validasi
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

        $firstOrderDate = DocumentData::min('order_date');
        $latestOrderDate = DocumentData::max('order_date');

        $initialStartDate = $firstOrderDate ? \Carbon\Carbon::parse($firstOrderDate)->format('Y-m-d') : now()->format('Y-m-d');
        $initialEndDate = $latestOrderDate ? \Carbon\Carbon::parse($latestOrderDate)->format('Y-m-d') : now()->format('Y-m-d');

        $startDateToUse = $request->input('startDate', $initialStartDate);
        $endDateToUse = $request->input('endDate', $initialEndDate);

        // (Baris duplikat $startDateToUse dihapus disini)

        // Daftar opsi filter
        $products = ['Netmonk', 'OCA', 'Antares Eazy', 'Pijar'];
        $subTypes = ['AO', 'SO', 'DO', 'MO', 'RO'];
        $witelList = DocumentData::query()->select('nama_witel')->whereNotNull('nama_witel')->distinct()->orderBy('nama_witel')->pluck('nama_witel');
        $branchList = DocumentData::query()->select('telda')->whereNotNull('telda')->distinct()->orderBy('telda')->pluck('telda');

        // [TAMBAHAN PENTING] Panggil fungsi mapping
        $witelBranchMap = $this->getWitelBranchMap();

        // CASE statements
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

        // Filter Logic Closure
        $applyFilters = function ($query) use ($startDateToUse, $endDateToUse, $validated, $subTypeCaseStatement, $productCaseStatement) {
            $query->whereBetween('order_date', [$startDateToUse.' 00:00:00', $endDateToUse.' 23:59:59']);

            if (isset($validated['products']) && is_array($validated['products'])) {
                if (empty($validated['products'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn(DB::raw($productCaseStatement), $validated['products']);
                }
            }

            if (isset($validated['witels']) && is_array($validated['witels'])) {
                if (empty($validated['witels'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('nama_witel', $validated['witels']);
                }
            }

            if (isset($validated['branches']) && is_array($validated['branches'])) {
                if (empty($validated['branches'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('telda', $validated['branches']);
                }
            }

            if (isset($validated['subTypes']) && is_array($validated['subTypes'])) {
                if (empty($validated['subTypes'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn(DB::raw($subTypeCaseStatement), $validated['subTypes']);
                }
            }
        };

        // Queries (Data Fetching)
        $revenueBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('SUM(net_price) as total_revenue'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))->where('net_price', '>', 0)
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

        $amountBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('COUNT(*) as total_amount'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

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
                'startDate' => $startDateToUse,
                'endDate' => $endDateToUse,
                'products' => $validated['products'] ?? null,
                'witels' => $validated['witels'] ?? null,
                'subTypes' => $validated['subTypes'] ?? null,
                'branches' => $validated['branches'] ?? null,
                'limit' => $limit,
            ],
            'filterOptions' => [
                'products' => $products,
                'witelList' => $witelList,
                'subTypes' => $subTypes,
                'branchList' => $branchList,
                // [TAMBAHAN PENTING] Kirim mapping ke frontend
                'witelBranchMap' => $witelBranchMap,
            ],
            'isEmbed' => false,
        ]);
    }

    public function embed(Request $request)
    {
        // 1. Validasi
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

        // Opsi filter
        $products = ['Netmonk', 'OCA', 'Antares Eazy', 'Pijar'];
        $subTypes = ['AO', 'SO', 'DO', 'MO', 'RO'];
        $witelList = DocumentData::query()->select('nama_witel')->whereNotNull('nama_witel')->distinct()->orderBy('nama_witel')->pluck('nama_witel');
        $branchList = DocumentData::query()->select('telda')->whereNotNull('telda')->distinct()->orderBy('telda')->pluck('telda');

        // [TAMBAHAN PENTING] Panggil fungsi mapping di method embed juga
        $witelBranchMap = $this->getWitelBranchMap();

        // CASE statements
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

        // Filter Logic Closure
        $applyFilters = function ($query) use ($startDateToUse, $endDateToUse, $validated, $subTypeCaseStatement, $productCaseStatement) {
            $query->whereBetween('order_date', [$startDateToUse.' 00:00:00', $endDateToUse.' 23:59:59']);

            if (isset($validated['products']) && is_array($validated['products'])) {
                if (empty($validated['products'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn(DB::raw($productCaseStatement), $validated['products']);
                }
            }

            if (isset($validated['witels']) && is_array($validated['witels'])) {
                if (empty($validated['witels'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('nama_witel', $validated['witels']);
                }
            }

            if (isset($validated['branches']) && is_array($validated['branches'])) {
                if (empty($validated['branches'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('telda', $validated['branches']);
                }
            }

            if (isset($validated['subTypes']) && is_array($validated['subTypes'])) {
                if (empty($validated['subTypes'])) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn(DB::raw($subTypeCaseStatement), $validated['subTypes']);
                }
            }
        };

        // Queries
        $revenueBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('SUM(net_price) as total_revenue'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))->where('net_price', '>', 0)
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

        $amountBySubTypeData = DocumentData::query()
            ->select(DB::raw($subTypeCaseStatement.' as sub_type'), DB::raw($productCaseStatement.' as product'), DB::raw('COUNT(*) as total_amount'))
            ->whereNotNull(DB::raw($productCaseStatement))->whereNotNull(DB::raw($subTypeCaseStatement))
            ->groupBy('sub_type', 'product')->tap($applyFilters)->get();

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
                'startDate' => $startDateToUse,
                'endDate' => $endDateToUse,
                'products' => $validated['products'] ?? null,
                'witels' => $validated['witels'] ?? null,
                'subTypes' => $validated['subTypes'] ?? null,
                'branches' => $validated['branches'] ?? null,
                'limit' => $limit,
            ],
            'filterOptions' => [
                'products' => $products,
                'witelList' => $witelList,
                'subTypes' => $subTypes,
                'branchList' => $branchList,
                // [TAMBAHAN PENTING] Kirim mapping ke frontend
                'witelBranchMap' => $witelBranchMap,
            ],
            'isEmbed' => true,
        ])->rootView('embed');
    }
}
