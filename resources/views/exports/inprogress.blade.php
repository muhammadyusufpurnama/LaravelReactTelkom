{{-- resources/views/exports/inprogress.blade.php --}}

<table>
    <thead>
        {{-- Baris Judul Dinamis --}}
        <tr>
            <th colspan="8" style="font-size: 16px; font-weight: bold; text-align: center;">
                Report Digital In Progress Witel {{ $witel ?? 'Semua Witel' }}
            </th>
        </tr>
        <tr>
            {{-- Baris kosong untuk spasi --}}
        </tr>
        {{-- Header Tabel --}}
        <tr style="font-weight: bold; background-color: #366092; color: #FFFFFF;">
            <th style="border: 1px solid #000;">Order Id</th>
            <th style="border: 1px solid #000;">PRODUCT NAME</th>
            <th style="border: 1px solid #000;">WITEL</th>
            <th style="border: 1px solid #000;">Customer Name</th>
            <th style="border: 1px solid #000;">Milestone</th>
            <th style="border: 1px solid #000;">Order created date</th>
            <th style="border: 1px solid #000;">Segment</th>
            <th style="border: 1px solid #000;">Branch</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <td style="border: 1px solid #000;">{{ $item->order_id }}</td>
                <td style="border: 1px solid #000;">{{ $item->product_name ?? $item->product }}</td>
                <td style="border: 1px solid #000;">{{ $item->nama_witel }}</td>
                <td style="border: 1px solid #000;">{{ $item->customer_name }}</td>
                <td style="border: 1px solid #000;">{{ $item->milestone }}</td>
                <td style="border: 1px solid #000;">
                    {{ \Carbon\Carbon::parse($item->order_created_date)->format('d/m/Y H:i A') }}</td>
                <td style="border: 1px solid #000;">{{ $item->segment }}</td>
                <td style="border: 1px solid #000;">{{ $item->witel_lama ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
