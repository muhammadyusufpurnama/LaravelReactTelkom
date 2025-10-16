// resources/js/Components/InProgressTable.jsx

import React from 'react';
import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { toast } from 'react-toastify';
import Pagination from '@/Components/Pagination';

const MySwal = withReactContent(Swal);

const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleString("id-ID", {
        year: "numeric", month: "2-digit", day: "2-digit",
        hour: "2-digit", minute: "2-digit",
    });
};

// [PERUBAHAN 1] Terima prop `showActions` dengan nilai default true
const InProgressTable = ({ dataPaginator = { data: [], links: [], from: 0 }, showActions = true }) => {

    // ... (Fungsi handleCompleteClick dan handleCancelClick tidak perlu diubah) ...
    const handleCompleteClick = async (orderId) => { /* ... */ };
    const handleCancelClick = async (orderId) => { /* ... */ };

    return (
        <>
            <div className="overflow-x-auto text-sm">
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr className="text-left font-semibold text-gray-600">
                            <th className="p-3">No.</th>
                            <th className="p-3">Milestone</th>
                            <th className="p-3">Status Order</th>
                            <th className="p-3">Product Name</th>
                            <th className="p-3">Order ID</th>
                            <th className="p-3">Witel</th>
                            <th className="p-3">Customer Name</th>
                            <th className="p-3">Order Created Date</th>
                            {/* [PERUBAHAN 2] Tampilkan header 'Action' hanya jika showActions true */}
                            {showActions && <th className="p-3 text-center">Action</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y bg-white">
                        {dataPaginator.data.length > 0 ? (
                            dataPaginator.data.map((item, index) => (
                                <tr key={item.order_id} className="text-gray-700 hover:bg-gray-50">
                                    <td className="p-3">{dataPaginator.from + index}</td>
                                    <td className="p-3">{item.milestone}</td>
                                    <td className="p-3 whitespace-nowrap">
                                        <span className="px-2 py-1 font-semibold leading-tight text-blue-700 bg-blue-100 rounded-full">
                                            {item.order_status_n}
                                        </span>
                                    </td>
                                    <td className="p-3">{item.product_name ?? item.product}</td>
                                    <td className="p-3 font-mono">{item.order_id}</td>
                                    <td className="p-3">{item.nama_witel}</td>
                                    <td className="p-3">{item.customer_name}</td>
                                    <td className="p-3">{formatDate(item.order_created_date)}</td>
                                    {/* [PERUBAHAN 3] Tampilkan kolom 'Action' hanya jika showActions true */}
                                    {showActions && (
                                        <td className="p-3 text-center">
                                            <div className="flex justify-center items-center gap-2">
                                                <button onClick={() => handleCompleteClick(item.order_id)} className="px-3 py-1 text-xs font-bold text-white bg-green-500 rounded-md hover:bg-green-600">
                                                    COMPLETE
                                                </button>
                                                <button onClick={() => handleCancelClick(item.order_id)} className="px-3 py-1 text-xs font-bold text-white bg-red-500 rounded-md hover:bg-red-600">
                                                    CANCEL
                                                </button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                {/* Sesuaikan colSpan jika kolom aksi disembunyikan */}
                                <td colSpan={showActions ? 9 : 8} className="p-4 text-center text-gray-500">
                                    Tidak ada data yang sesuai dengan filter.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={dataPaginator.links} />
        </>
    );
};

export default InProgressTable;
