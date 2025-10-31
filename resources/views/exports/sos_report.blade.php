{{-- resources/views/exports/sos_report.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body>
    {{-- Tabel Pertama: AO MO CONN --}}
    <table>
        <thead>
            <tr>
                <th colspan="15" style="font-weight: bold; font-size: 14px;">REPORT AO MO CONN TR3 JATIM BALNUS
                    {{ $period }}</th>
            </tr>
            <tr>
                <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">WITEL</th>
                <th colspan="7" style="font-weight: bold; text-align: center; border: 1px solid #000;">&lt;3BLN</th>
                <th colspan="6" style="font-weight: bold; text-align: center; border: 1px solid #000;">&gt;3BLN</th>
                <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">Grand Total
                    Order</th>
            </tr>
            <tr>
                {{-- Kolom <3BLN untuk AOMO --}}
                <th style="font-weight: bold; border: 1px solid #000;">PROVIDE ORDER</th>
                <th style="font-weight: bold; border: 1px solid #000;">EST BC (JT)</th>
                <th style="font-weight: bold; border: 1px solid #000;">IN PROCESS</th>
                <th style="font-weight: bold; border: 1px solid #000;">EST BC (JT)</th>
                <th style="font-weight: bold; border: 1px solid #000;">READY TO BILL</th>
                <th style="font-weight: bold; border: 1px solid #000;">EST BC (JT)</th>
                <th style="font-weight: bold; border: 1px solid #000;">&lt;3BLN Total</th>
                {{-- Kolom >3BLN untuk AOMO --}}
                <th style="font-weight: bold; border: 1px solid #000;">PROVIDE ORDER</th>
                <th style="font-weight: bold; border: 1px solid #000;">EST BC (JT)</th>
                <th style="font-weight: bold; border: 1px solid #000;">IN PROCESS</th>
                <th style="font-weight: bold; border: 1px solid #000;">EST BC (JT)</th>
                <th style="font-weight: bold; border: 1px solid #000;">READY TO BILL</th>
                <th style="font-weight: bold; border: 1px solid #000;">&gt;3BLN Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $item)
                @php
                    $isTotalRow = $item['isTotal'] ?? false;
                    $style = $isTotalRow ? 'font-weight: bold;' : '';
                @endphp
                <tr>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['witel'] }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['provide_order_lt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">
                        {{ number_format($item['est_bc_provide_order_lt_3bln'] ?? 0, 2) }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['in_process_lt_3bln'] ?? 0 }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">
                        {{ number_format($item['est_bc_in_process_lt_3bln'] ?? 0, 2) }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['ready_to_bill_lt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">
                        {{ number_format($item['est_bc_ready_to_bill_lt_3bln'] ?? 0, 2) }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['total_lt_3bln'] ?? 0 }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['provide_order_gt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">
                        {{ number_format($item['est_bc_provide_order_gt_3bln'] ?? 0, 2) }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['in_process_gt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">
                        {{ number_format($item['est_bc_in_process_gt_3bln'] ?? 0, 2) }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['ready_to_bill_gt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['total_gt_3bln'] ?? 0 }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['grand_total_order'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="15">Cutoff {{ $cutoffDate }}</td>
            </tr>
            <tr>
                <td colspan="15">Source: Database NCX | Filter: All usia</td>
            </tr>
        </tfoot>
    </table>

    {{-- Spasi Antar Tabel --}}
    <table>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td></td>
        </tr>
    </table>

    {{-- Tabel Kedua: SO DO RO CONN --}}
    <table>
        <thead>
            <tr>
                <th colspan="9" style="font-weight: bold; font-size: 14px;">REPORT SO DO RO CONN TR3 JATIM BALNUS
                    {{ $period }}</th>
            </tr>
            <tr>
                <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">WITEL</th>
                <th colspan="4" style="font-weight: bold; text-align: center; border: 1px solid #000;">&lt;3BLN</th>
                <th colspan="3" style="font-weight: bold; text-align: center; border: 1px solid #000;">&gt;3BLN</th>
                <th rowspan="2" style="font-weight: bold; vertical-align: middle; border: 1px solid #000;">Grand
                    Total Order</th>
            </tr>
            <tr>
                {{-- Kolom <3BLN untuk SODORO --}}
                <th style="font-weight: bold; border: 1px solid #000;">PROVIDE ORDER</th>
                <th style="font-weight: bold; border: 1px solid #000;">IN PROCESS</th>
                <th style="font-weight: bold; border: 1px solid #000;">READY TO BILL</th>
                <th style="font-weight: bold; border: 1px solid #000;">&lt;3BLN Total</th>
                {{-- Kolom >3BLN untuk SODORO --}}
                <th style="font-weight: bold; border: 1px solid #000;">PROVIDE ORDER</th>
                <th style="font-weight: bold; border: 1px solid #000;">IN PROCESS</th>
                <th style="font-weight: bold; border: 1px solid #000;">&gt;3BLN Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $item)
                @php
                    $isTotalRow = $item['isTotal'] ?? false;
                    $style = $isTotalRow ? 'font-weight: bold;' : '';
                @endphp
                <tr>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['witel'] }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['provide_order_lt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['in_process_lt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['ready_to_bill_lt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['total_lt_3bln'] ?? 0 }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['provide_order_gt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['in_process_gt_3bln'] ?? 0 }}
                    </td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['total_gt_3bln'] ?? 0 }}</td>
                    <td style="{{ $style }} border: 1px solid #000;">{{ $item['grand_total_order'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9">Cutoff {{ $cutoffDate }}</td>
            </tr>
            <tr>
                <td colspan="9">Source: Database NCX | Filter: All usia</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
