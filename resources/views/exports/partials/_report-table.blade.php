{{--
    File ini menerima variabel:
    $reportData, $tableConfig, $segment, $period, $details
--}}
@php
    // Pindahkan kalkulasi Grand Total ke dalam partial ini
    $grandTotals = [];
    foreach ($reportData as $item) {
        foreach ($item as $key => $value) {
            if (is_numeric($value)) {
                $grandTotals[$key] = ($grandTotals[$key] ?? 0) + $value;
            }
        }
    }
@endphp

<table style="border-collapse: collapse;">
    <tr>
        <td colspan="5" style="font-size: 14px; font-weight: bold;">Progress WFM Digital Product MTD
            {{ $period }} Segmen {{ $segment }}</td>
    </tr>
    <tr></tr>
    <tr>
        <td style="font-weight: bold;">Total</td>
        <td>{{ $details['total'] ?? 0 }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">OGP</td>
        <td>{{ $details['ogp'] ?? 0 }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Closed</td>
        <td>{{ $details['closed'] ?? 0 }}</td>
    </tr>
    <tr></tr>
</table>
<table style="border-collapse: collapse;">
    <thead>
        <tr>
            <th rowspan="3"
                style="vertical-align: middle; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #333; color: #FFFFFF;">
                WILAYAH TELKOM</th>
            @foreach ($tableConfig as $group)
                <th colspan="{{ getGroupColspan($group) }}"
                    style="text-align: center; font-weight: bold; border: 1px solid #000; background-color: {{ tailwindToHex($group['groupClass']) }}; color: #FFFFFF;">
                    {{ $group['groupTitle'] }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($tableConfig as $group)
                @foreach ($group['columns'] as $col)
                    <th colspan="{{ count($col['subColumns'] ?? [1]) }}"
                        rowspan="{{ isset($col['subColumns']) ? 1 : 2 }}"
                        style="vertical-align: middle; text-align: center; font-weight: bold; border: 1px solid #000; background-color: {{ tailwindToHex($group['columnClass'] ?? '') }}; color: #FFFFFF;">
                        {{ $col['title'] }}</th>
                @endforeach
            @endforeach
        </tr>
        <tr>
            @foreach ($tableConfig as $group)
                @foreach ($group['columns'] as $col)
                    @if (isset($col['subColumns']))
                        @foreach ($col['subColumns'] as $subCol)
                            <th
                                style="text-align: center; font-weight: bold; border: 1px solid #000; background-color: {{ tailwindToHex($group['subColumnClass'] ?? '') }}; color: #000000;">
                                {{ $subCol['title'] }}</th>
                        @endforeach
                    @endif
                @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($reportData as $item)
            <tr>
                <td style="border: 1px solid #000; font-weight: bold;">{{ $item['nama_witel'] }}</td>
                @foreach ($tableConfig as $group)
                    @foreach ($group['columns'] as $col)
                        @if (isset($col['subColumns']))
                            @foreach ($col['subColumns'] as $subCol)
                                <td style="border: 1px solid #000; text-align: right;">
                                    {{ getCellValue($item, $subCol, $col) }}</td>
                            @endforeach
                        @else
                            <td style="border: 1px solid #000; text-align: right;">{{ getCellValue($item, $col) }}</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight: bold;">
            <td style="border: 1px solid #000; background-color: #333; color: #FFFFFF;">GRAND TOTAL</td>
            @foreach ($tableConfig as $group)
                @foreach ($group['columns'] as $col)
                    @if (isset($col['subColumns']))
                        @foreach ($col['subColumns'] as $subCol)
                            <td
                                style="border: 1px solid #000; text-align: right; background-color: {{ tailwindToHex($group['groupClass']) }}; color: #FFFFFF;">
                                {{ getCellValue($grandTotals, $subCol, $col) }}</td>
                        @endforeach
                    @else
                        <td
                            style="border: 1px solid #000; text-align: right; background-color: {{ tailwindToHex($group['groupClass']) }}; color: #FFFFFF;">
                            {{ getCellValue($grandTotals, $col) }}</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
    </tfoot>
</table>
