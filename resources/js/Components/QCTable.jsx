// resources/js/Components/QcTable.jsx

import React from 'react';
import { router } from '@inertiajs/react';
import toast from 'react-hot-toast';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import Pagination from '@/Components/Pagination'; // Pastikan path ini benar

// Inisialisasi MySwal
const MySwal = withReactContent(Swal);

// Asumsi Anda memiliki fungsi utilitas ini
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const QcTable = ({ dataPaginator = { data: [], links: [], from: 0 }, activeView }) => {
    const handleSetInProgress = async (orderId) => {
        const result = await MySwal.fire({
            title: 'Kembalikan ke In Progress?',
            text: `Anda yakin ingin mengembalikan Order ID ${orderId} ke status "In Progress"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kembalikan!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.put(
                route("admin.complete.update.progress", { documentData: orderId }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.info(`Order ${orderId} dikembalikan ke In Progress.`);
                        router.reload({ preserveState: false });
                    },
                    onError: () => toast.error('Gagal mengubah status.')
                },
            );
        }
    };

    const handleSetDone = async (orderId) => {
        const result = await MySwal.fire({
            title: 'Selesaikan Order?',
            text: `Anda yakin ingin mengubah status Order ID ${orderId} menjadi "Done Close Bima"?`,
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Warna hijau untuk aksi positif
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Selesaikan!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.put(
                route("admin.qc.update.done", { order_id: orderId }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success(`Order ${orderId} berhasil diselesaikan.`);
                        router.reload({ preserveState: false });
                    },
                    onError: () => toast.error('Gagal menyelesaikan order.')
                },
            );
        }
    };

    const handleSetCancel = async (orderId) => {
        const result = await MySwal.fire({
            title: 'Batalkan Order?',
            text: `Anda yakin ingin mengubah status Order ID ${orderId} menjadi "Done Close Cancel"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Warna merah untuk aksi negatif
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.put(
                route("admin.qc.update.cancel", { order_id: orderId }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success(`Order ${orderId} berhasil di-cancel.`);
                        router.reload({ preserveState: false });
                    },
                    onError: () => toast.error('Gagal membatalkan order.')
                },
            );
        }
    };

    return (
        <>
            <div className="overflow-x-auto text-sm">
                <p className="text-gray-500 mb-2">
                    Menampilkan data order yang sedang dalam proses Quality Control (QC).
                </p>
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr className="text-left font-semibold text-gray-600">
                            <th className="p-3">No.</th>
                            <th className="p-3">Milestone</th>
                            <th className="p-3">Order ID</th>
                            <th className="p-3">Product Name</th>
                            <th className="p-3">Witel</th>
                            <th className="p-3">Customer Name</th>
                            <th className="p-3">Update Time</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y bg-white">
                        {dataPaginator.data.length > 0 ? (
                            dataPaginator.data.map((item, index) => (
                                <tr
                                    key={item.id ?? item.order_id}
                                    className="text-gray-700 hover:bg-gray-50"
                                >
                                    <td className="p-3">{dataPaginator.from + index}</td>
                                    <td className="p-3">{item.milestone}</td>
                                    <td className="p-3 font-mono">{item.order_id}</td>
                                    <td className="p-3">{item.product_name ?? item.product}</td>
                                    <td className="p-3">{item.nama_witel}</td>
                                    <td className="p-3">{item.customer_name}</td>
                                    <td className="p-3">{formatDate(item.updated_at)}</td>
                                    <td className="p-3 text-center">
                                        <div className="flex justify-center items-center gap-2">
                                            <button
                                                onClick={() => handleSetInProgress(item.order_id)}
                                                className="px-3 py-1 text-xs font-bold text-white bg-blue-500 rounded-md hover:bg-blue-600"
                                            >
                                                In Progress
                                            </button>
                                            <button
                                                onClick={() => handleSetDone(item.order_id)}
                                                className="px-3 py-1 text-xs font-bold text-white bg-green-500 rounded-md hover:bg-green-600"
                                            >
                                                Done Bima
                                            </button>
                                            <button
                                                onClick={() => handleSetCancel(item.order_id)}
                                                className="px-3 py-1 text-xs font-bold text-white bg-red-500 rounded-md hover:bg-red-600"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr><td colSpan="8" className="p-4 text-center text-gray-500">Tidak ada data QC saat ini.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={dataPaginator.links} activeView={activeView}/>
        </>
    );
};

export default QcTable;
