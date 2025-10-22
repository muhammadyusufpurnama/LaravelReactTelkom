import React from 'react';
import { useForm, Link, router } from '@inertiajs/react';

// ===================================================================
// KOMPONEN INLINE UNTUK MENGATASI ERROR KOMPILASI
// ===================================================================

const PrimaryButton = ({ className = '', disabled, children, ...props }) => {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 ${disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
};

const InputError = ({ message, className = '', ...props }) => {
    return message ? (
        <p {...props} className={'text-sm text-red-600 ' + className}>
            {message}
        </p>
    ) : null;
};

const Pagination = ({ links = [], activeView }) => {
    if (!links || links.length <= 3) return null;

    const appendTabViewToUrl = (url) => {
        if (!url || !activeView) return url;
        try {
            const urlObject = new URL(url, window.location.origin);
            urlObject.searchParams.set('tab', activeView);
            return `${urlObject.pathname}${urlObject.search}`;
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

// ===================================================================
// Komponen Utama
// ===================================================================

const TableRow = ({ item }) => {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        net_price: item.net_price || '',
        product_name: item.product_name, // [BARU] Sertakan product_name
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.analysisDigitalProduct.updateNetPrice', { order_id: item.order_id }), {
            preserveScroll: true,
            onSuccess: () => {
                // Setelah berhasil, muat ulang halaman dengan filter yang benar
                router.get(route('admin.analysisDigitalProduct.index'), {
                    // Pertahankan filter yang ada jika ada (misal pencarian)
                    ...route().params,
                    // Arahkan ke tab yang benar
                    tab: 'netprice',
                    // Set filter status harga menjadi 'pasti'
                    net_price_status: 'pasti'
                }, {
                    preserveState: true,
                    replace: true,
                });
            }
        });
    };



    return (
        <tr className="bg-white border-b hover:bg-gray-50">
            <td className="px-4 py-2">{item.product_name || item.product}</td>
            <td className="px-4 py-2 font-mono">{item.order_id}</td>
            <td className="px-4 py-2">{item.nama_witel}</td>
            <td className="px-4 py-2">{item.customer_name}</td>
            <td className="px-4 py-2 whitespace-nowrap">{new Date(item.order_created_date).toLocaleString('id-ID')}</td>
            <td className="px-4 py-2">
                <form onSubmit={submit} className="flex items-center gap-2">
                    <div className="flex-grow">
                        <input
                            type="number"
                            value={data.net_price}
                            onChange={(e) => setData('net_price', e.target.value)}
                            className="p-1 border rounded w-full text-sm"
                            placeholder="0"
                            step="0.01"
                            disabled={processing}
                        />
                        <InputError message={errors.net_price} className="mt-1" />
                    </div>
                    <PrimaryButton disabled={processing} className={recentlySuccessful ? 'bg-green-500 hover:bg-green-600' : ''}>
                        {processing ? '...' : (recentlySuccessful ? 'Disimpan!' : 'Simpan')}
                    </PrimaryButton>
                </form>
            </td>
        </tr>
    );
};

export default function NetPriceTable({ dataPaginator, activeView }) {
    return (
        <>
            <div className="overflow-x-auto text-sm">
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr className="text-left font-semibold text-gray-600">
                            <th className="p-3">Product Name</th>
                            <th className="p-3">Order ID</th>
                            <th className="p-3">Witel</th>
                            <th className="p-3">Customer Name</th>
                            <th className="p-3">Order Created Date</th>
                            <th className="p-3 w-1/4">Edit Net Price</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y bg-white">
                        {dataPaginator && dataPaginator.data.length > 0 ? (
                            dataPaginator.data.map((item) => <TableRow key={item.uid} item={item} />)
                        ) : (
                            <tr>
                                <td colSpan="6" className="p-4 text-center text-gray-500">
                                    Tidak ada data yang cocok.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={dataPaginator?.links} activeView={activeView} />
        </>
    );
}

// ===================================================================
