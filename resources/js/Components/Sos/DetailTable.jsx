// file: resources/js/Components/Sos/DetailTable.jsx

import React from 'react';
import { Link, usePage } from '@inertiajs/react';

// Komponen Pagination (bisa dipindah ke file sendiri jika digunakan di banyak tempat)
const Pagination = ({ links = [], activeView }) => {
    if (links.length <= 3) return null;

    const appendTabViewToUrl = (url) => {
        if (!url || !activeView) return url;
        try {
            const urlObject = new URL(url);
            urlObject.searchParams.set('tab', activeView);
            return urlObject.toString();
        } catch (error) {
            console.error("URL Pagination tidak valid:", url);
            return url;
        }
    };

    return (
        <div className="flex flex-wrap justify-center items-center mt-4 space-x-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={appendTabViewToUrl(link.url) ?? "#"}
                    className={`px-3 py-2 text-sm border rounded hover:bg-blue-600 hover:text-white transition-colors ${link.active ? "bg-blue-600 text-white" : "bg-white text-gray-700"} ${!link.url ? "text-gray-400 cursor-not-allowed" : ""}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    preserveScroll
                    preserveState
                />
            ))}
        </div>
    );
};


// Komponen Utama DetailTable
const DetailTable = ({ dataPaginator, columns }) => {
    const { data = [], links = [] } = dataPaginator || { data: [], links: [] };

    const formatCell = (item, column) => {
        const value = item[column.accessor];
        if (value === null || value === undefined) return '-';

        switch (column.type) {
            case 'date':
                return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
            case 'currency':
                return parseFloat(value).toLocaleString('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 });
            default:
                return value;
        }
    };

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full bg-white text-sm table-auto">
                {/* Header Tabel - Selalu Ditampilkan */}
                <thead className="bg-gray-50 border-b">
                    <tr>
                        {columns.map((col) => (
                            <th
                                key={col.Header}
                                className="py-3 px-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                {col.Header}
                            </th>
                        ))}
                    </tr>
                </thead>

                {/* Body Tabel - Menampilkan data atau pesan kosong */}
                <tbody>
                    {data.length > 0 ? (
                        data.map((item, index) => (
                            <tr key={item.id || item.order_id || index} className="border-b hover:bg-gray-50 transition-colors">
                                {columns.map((col) => (
                                    <td key={col.accessor} className="py-3 px-4 whitespace-nowrap">
                                        {formatCell(item, col)}
                                    </td>
                                ))}
                            </tr>
                        ))
                    ) : (
                        // Jika tidak ada data, tampilkan pesan di tengah
                        <tr>
                            <td
                                colSpan={columns.length}
                                className="text-center py-10 text-gray-500"
                            >
                                Tidak ada data untuk ditampilkan dalam kategori ini.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>

            {/* Tampilkan pagination jika ada data */}
            {data.length > 0 && (
                <div className="mt-4">
                    <Pagination links={links} activeView={usePage().props.filters.tab} />
                </div>
            )}
        </div>
    );
};

export default DetailTable;
