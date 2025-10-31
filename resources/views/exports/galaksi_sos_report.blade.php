{{-- resources/views/exports/galaksi_sos_report.blade.php --}}
<table>
    <thead>
        <tr>
            <th colspan="14" style="font-weight: bold; font-size: 14px; text-align: center;">POSISI GALAKSI AOSODOMORO
                CONN</th>
        </tr>
        <tr>
            <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">PO</th>
            <th colspan="6" style="font-weight: bold; text-align: center; border: 1px solid #000;">&lt; 3 BLN</th>
            <th colspan="6" style="font-weight: bold; text-align: center; border: 1px solid #000;">&gt; 3 BLN</th>
            <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">Achievement
                &gt;3 bln</th>
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid #000;">AO</th>
            <th style="font-weight: bold; border: 1px solid #000;">SO</th>
            <th style="font-weight: bold; border: 1px solid #000;">DO</th>
            <th style="font-weight: bold; border: 1px solid #000;">MO</th>
            <th style="font-weight: bold; border: 1px solid #000;">RO</th>
            <th style="font-weight: bold; border: 1px solid #000;">&lt; 3 BLN Total</th>
            <th style="font-weight: bold; border: 1px solid #000;">AO</th>
            <th style="font-weight: bold; border: 1px solid #000;">SO</th>
            <th style="font-weight: bold; border: 1px solid #000;">DO</th>
            <th style="font-weight: bold; border: 1px solid #000;">MO</th>
            <th style="font-weight: bold; border: 1px solid #000;">RO</th>
            <th style="font-weight: bold; border: 1px solid #000;">&gt; 3 BLN Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Inisialisasi Grand Total
            $grandTotal = [
                'ao_lt_3bln' => 0,
                'so_lt_3bln' => 0,
                'do_lt_3bln' => 0,
                'mo_lt_3bln' => 0,
                'ro_lt_3bln' => 0,
                'ao_gt_3bln' => 0,
                'so_gt_3bln' => 0,
                'do_gt_3bln' => 0,
                'mo_gt_3bln' => 0,
                'ro_gt_3bln' => 0,
            ];
        @endphp
        @foreach ($galaksiData as $item)
            @php
                $total_lt_3bln =
                    ($item['ao_lt_3bln'] ?? 0) +
                    ($item['so_lt_3bln'] ?? 0) +
                    ($item['do_lt_3bln'] ?? 0) +
                    ($item['mo_lt_3bln'] ?? 0) +
                    ($item['ro_lt_3bln'] ?? 0);
                $total_gt_3bln =
                    ($item['ao_gt_3bln'] ?? 0) +
                    ($item['so_gt_3bln'] ?? 0) +
                    ($item['do_gt_3bln'] ?? 0) +
                    ($item['mo_gt_3bln'] ?? 0) +
                    ($item['ro_gt_3bln'] ?? 0);
                $achievement = $total_lt_3bln > 0 ? ($total_gt_3bln / $total_lt_3bln) * 100 : 100; // Jika <3BLN 0, anggap 100%

                // Akumulasi Grand Total
                foreach ($grandTotal as $key => &$value) {
                    $value += $item[$key] ?? 0;
                }
            @endphp
            <tr>
                <td style="border: 1px solid #000;">{{ $item['po'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['ao_lt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['so_lt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['do_lt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['mo_lt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['ro_lt_3bln'] }}</td>
                <td style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $total_lt_3bln }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['ao_gt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['so_gt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['do_gt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['mo_gt_3bln'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['ro_gt_3bln'] }}</td>
                <td style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $total_gt_3bln }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ number_format($achievement, 0) }}%</td>
            </tr>
        @endforeach
        {{-- Baris Grand Total --}}
        @php
            $grand_total_lt_3bln =
                $grandTotal['ao_lt_3bln'] +
                $grandTotal['so_lt_3bln'] +
                $grandTotal['do_lt_3bln'] +
                $grandTotal['mo_lt_3bln'] +
                $grandTotal['ro_lt_3bln'];
            $grand_total_gt_3bln =
                $grandTotal['ao_gt_3bln'] +
                $grandTotal['so_gt_3bln'] +
                $grandTotal['do_gt_3bln'] +
                $grandTotal['mo_gt_3bln'] +
                $grandTotal['ro_gt_3bln'];
            $grand_achievement = $grand_total_lt_3bln > 0 ? ($grand_total_gt_3bln / $grand_total_lt_3bln) * 100 : 100;
        @endphp
        <tr style="font-weight: bold;">
            <td style="border: 1px solid #000;">Grand Total</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['ao_lt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['so_lt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['do_lt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['mo_lt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['ro_lt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grand_total_lt_3bln }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['ao_gt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['so_gt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['do_gt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['mo_gt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grandTotal['ro_gt_3bln'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $grand_total_gt_3bln }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ number_format($grand_achievement, 0) }}%</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            {{-- [PERUBAHAN] Menggunakan variabel $cutoffDate --}}
            <td colspan="14">{{ $cutoffDate }}</td>
        </tr>
        <tr>
            <td colspan="14" style="color: red;">Data Inprogress</td>
        </tr>
    </tfoot>
</table>
