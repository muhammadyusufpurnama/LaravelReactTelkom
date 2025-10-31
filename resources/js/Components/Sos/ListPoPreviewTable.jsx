import React from 'react';
import { Link } from '@inertiajs/react';

// Pagination component (mirip dengan yang ada di halaman utama)
const Pagination = ({ links = [] }) => {
    if (links.length <= 3) return null;

    return (
        <div className="flex flex-wrap justify-center items-center mt-4 space-x-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? "#"}
                    className={`px-3 py-1 text-xs border rounded hover:bg-gray-600 hover:text-white transition-colors ${link.active ? "bg-gray-600 text-white" : "bg-white text-gray-700"} ${!link.url ? "text-gray-400 cursor-not-allowed" : ""}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    preserveScroll
                    preserveState
                />
            ))}
        </div>
    );
};

export default function ListPoPreviewTable({ dataPaginator }) {
    // Ekstrak data dan links dari paginator
    const { data = [], links = [] } = dataPaginator || {};

    return (
        <div>
            <h4 className="font-semibold text-md text-gray-700 mb-2">Daftar PO Saat Ini</h4>
            <div className="overflow-x-auto">
                <table className="min-w-full bg-white text-sm">
                    <thead className="bg-gray-100">
                        <tr>
                            {/* Header Kolom */}
                            <th className="py-2 px-4 border text-left font-semibold text-gray-600">PO</th>
                            <th className="py-2 px-4 border text-left font-semibold text-gray-600">NIPNAS</th>
                            <th className="py-2 px-4 border text-left font-semibold text-gray-600">Segment</th>
                            {/* [PERUBAHAN] Menambahkan header Bill City */}
                            <th className="py-2 px-4 border text-left font-semibold text-gray-600">Bill City</th>
                            <th className="py-2 px-4 border text-left font-semibold text-gray-600">Witel</th>
                        </tr>
                    </thead>
                    <tbody className="text-gray-700">
                        {data.length > 0 ? (
                            data.map((item) => (
                                <tr key={item.id} className="border-b hover:bg-gray-50">
                                    {/* Data Kolom */}
                                    <td className="py-2 px-4 border">{item.po}</td>
                                    <td className="py-2 px-4 border">{item.nipnas}</td>
                                    <td className="py-2 px-4 border">{item.segment}</td>
                                    {/* [PERUBAHAN] Menampilkan data bill_city */}
                                    <td className="py-2 px-4 border">{item.bill_city}</td>
                                    <td className="py-2 px-4 border">{item.witel}</td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                {/* [PERUBAHAN] Menyesuaikan colSpan menjadi 5 */}
                                <td colSpan="5" className="py-4 px-4 border text-center text-gray-500">
                                    Tidak ada data PO.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            {/* Tampilkan pagination jika ada data */}
            {data.length > 0 && <Pagination links={links} />}
        </div>
    );
}
