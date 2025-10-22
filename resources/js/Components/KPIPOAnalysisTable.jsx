// resources/js/Components/KPIPOAnalysisTable.jsx

import React from 'react';

const KPIPOAnalysisTable = ({ data = [], accountOfficers = [], openModal, activeView }) => {
    return (
        <div className="overflow-x-auto text-sm">
            <table className="min-w-full divide-y divide-gray-200 border">
                <thead className="bg-gray-50">
                    <tr>
                        <th
                            rowSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-green-600"
                        >
                            NAMA PO
                        </th>
                        <th
                            rowSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-green-600"
                        >
                            WITEL
                        </th>
                        <th
                            colSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-orange-500"
                        >
                            PRODIGI DONE
                        </th>
                        <th
                            colSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-blue-500"
                        >
                            PRODIGI OGP
                        </th>
                        <th
                            rowSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-green-600"
                        >
                            TOTAL
                        </th>
                        <th
                            colSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-yellow-400"
                        >
                            ACH
                        </th>
                        <th
                            rowSpan="2"
                            className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-gray-600"
                        >
                            AKSI
                        </th>
                    </tr>
                    <tr>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-orange-400">
                            NCX
                        </th>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-orange-400">
                            SCONE
                        </th>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-blue-400">
                            NCX
                        </th>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-blue-400">
                            SCONE
                        </th>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-yellow-300">
                            YTD
                        </th>
                        <th className="px-4 py-2 text-center text-xs font-medium text-white uppercase tracking-wider border bg-yellow-300">
                            Q3
                        </th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {/* Filter data null/undefined sebelum mapping untuk mencegah error */}
                    {data.filter(Boolean).map((po) => (
                        <tr key={po.nama_po} className="hover:bg-gray-50">
                            <td className="px-4 py-2 whitespace-nowrap border font-medium">
                                {po.nama_po}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border">
                                {po.witel}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center">
                                {po.done_ncx}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center">
                                {po.done_scone}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center">
                                {po.ogp_ncx}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center">
                                {po.ogp_scone}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center font-bold">
                                {po.total}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center font-bold bg-yellow-200">
                                {po.ach_ytd}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border text-center font-bold bg-yellow-200">
                                {po.ach_q3}
                            </td>
                            <td className="px-4 py-2 whitespace-nowrap border">
                                <button
                                    onClick={() =>
                                        openModal(
                                            accountOfficers.find(
                                                (a) => a.id === po.id,
                                            ),
                                        )
                                    }
                                    className="text-indigo-600 hover:text-indigo-900 text-xs font-semibold"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default KPIPOAnalysisTable;
