// resources/js/Components/HistoryTable.jsx

import React from 'react';
import Pagination from '@/Components/Pagination'; // Pastikan path ini benar

const HistoryTable = ({ historyData = { data: [], links: [] }, activeView }) => {
    const formatDateFull = (dateString) => {
        if (!dateString) return "-";
        return new Date(dateString).toLocaleString("id-ID", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    };

    const StatusChip = ({ text }) => {
        const lowerText = text.toLowerCase();
        let colorClasses = "bg-gray-100 text-gray-800";
        if (lowerText.includes("progress")) {
            colorClasses = "bg-blue-100 text-blue-800";
        } else if (lowerText.includes("bima")) {
            colorClasses = "bg-green-100 text-green-800";
        } else if (lowerText.includes("cancel")) {
            colorClasses = "bg-red-100 text-red-800";
        }
        return (
            <span
                className={`px-2 py-1 text-xs font-semibold leading-tight rounded-full ${colorClasses}`}
            >
                {text}
            </span>
        );
    };

    return (
        <div className="overflow-x-auto text-sm">
            {historyData.data.length > 0 && (
                <p className="text-gray-500 mb-2">
                    Menampilkan data histori update.
                </p>
            )}
            <table className="w-full whitespace-nowrap">
                <thead className="bg-gray-50">
                    <tr className="text-left font-semibold text-gray-600">
                        <th className="p-3">Waktu Update</th>
                        <th className="p-3">Order ID</th>
                        <th className="p-3">Customer</th>
                        <th className="p-3">Witel</th>
                        <th className="p-3">Status Lama</th>
                        <th className="p-3">Status Baru</th>
                        <th className="p-3">Sumber</th>
                    </tr>
                </thead>
                <tbody className="divide-y bg-white">
                    {historyData.data.length > 0 ? (
                        historyData.data.map((item) => (
                            <tr
                                key={item.id}
                                className="text-gray-700 hover:bg-gray-50"
                            >
                                <td className="p-3 font-semibold">
                                    {formatDateFull(item.created_at)}
                                </td>
                                <td className="p-3 font-mono">
                                    {item.order_id}
                                </td>
                                <td className="p-3">{item.customer_name}</td>
                                <td className="p-3">{item.nama_witel}</td>
                                <td className="p-3">
                                    <StatusChip text={item.status_lama} />
                                </td>
                                <td className="p-3">
                                    <StatusChip text={item.status_baru} />
                                </td>
                                <td className="p-3 font-medium text-gray-600">
                                    {item.sumber_update}
                                </td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td
                                colSpan="7"
                                className="p-4 text-center text-gray-500"
                            >
                                Belum ada histori update yang tercatat.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
            <Pagination links={historyData.links} activeView={activeView} />
        </div>
    );
};

export default HistoryTable;
