import React, { useState, useMemo, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';

// ... (Semua impor lain tetap sama)
import RevenueBySubTypeChart from '@/Components/RevenueBySubTypeChart';
import AmountBySubTypeChart from '@/Components/AmountBySubTypeChart';
import SessionSubTypeChart from '@/Components/SessionSubTypeChart';
import ProductRadarChart from '@/Components/ProductRadarChart';
import WitelPieChart from '@/Components/WitelPieChart';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler, RadialLinearScale } from 'chart.js';
import DatePicker from 'react-datepicker';
import "react-datepicker/dist/react-datepicker.css";
import DropdownCheckbox from '@/Components/DropdownCheckbox';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler, RadialLinearScale);

const StatusBadge = ({ text, color }) => (
    <span className={`px-2 py-1 text-xs font-semibold leading-tight rounded-full ${color}`}>
        {text}
    </span>
);

export default function DashboardDigitalProduct({
    auth, revenueBySubTypeData, amountBySubTypeData, dataPreview, sessionBySubType, productRadarData, witelPieData, filters = {}, filterOptions = {},
    // 1. Terima prop isEmbed dari controller, dengan nilai default false
    isEmbed = false
}) {
    // --- STATE MANAGEMENT & HOOKS --- (Tidak ada perubahan di sini)
    const productOptions = useMemo(() => filterOptions.products || [], [filterOptions.products]);
    const witelOptions = useMemo(() => filterOptions.witelList || [], [filterOptions.witelList]);
    const subTypeOptions = useMemo(() => filterOptions.subTypes || [], [filterOptions.subTypes]);
    const branchOptions = useMemo(() => filterOptions.branchList || [], [filterOptions.branchList]);

    const [localFilters, setLocalFilters] = useState({
        products: [], witels: [], subTypes: [], branches: [],
        startDate: null, endDate: null,
    });

    const [revenueFilters, setRevenueFilters] = useState({ products: productOptions });
    const [amountFilters, setAmountFilters] = useState({ products: productOptions });
    const [radarFilters, setRadarFilters] = useState({ products: productOptions, witels: witelOptions });
    const [pieFilters, setPieFilters] = useState({ products: productOptions, witels: witelOptions });

    useEffect(() => {
        setLocalFilters({
            products: filters.products && Array.isArray(filters.products) ? filters.products : productOptions,
            witels: filters.witels && Array.isArray(filters.witels) ? filters.witels : witelOptions,
            subTypes: filters.subTypes && Array.isArray(filters.subTypes) ? filters.subTypes : subTypeOptions,
            branches: filters.branches && Array.isArray(filters.branches) ? filters.branches : branchOptions,
            startDate: filters.startDate ? new Date(`${filters.startDate}T00:00:00`) : null,
            endDate: filters.endDate ? new Date(`${filters.endDate}T00:00:00`) : null,
        });

        setRevenueFilters({ products: productOptions });
        setAmountFilters({ products: productOptions });
        setRadarFilters({ products: productOptions, witels: witelOptions });
        setPieFilters({ products: productOptions, witels: witelOptions });

    }, [filters, productOptions, witelOptions, subTypeOptions, branchOptions]);


    // --- FUNGSI HANDLER --- (Tidak ada perubahan di sini)
    const formatDateForQuery = (date) => {
        if (!date) return undefined;
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const applyFilters = () => {
        const queryParams = {
            products: localFilters.products.length > 0 && localFilters.products.length < productOptions.length ? localFilters.products : undefined,
            witels: localFilters.witels.length > 0 && localFilters.witels.length < witelOptions.length ? localFilters.witels : undefined,
            subTypes: localFilters.subTypes.length > 0 && localFilters.subTypes.length < subTypeOptions.length ? localFilters.subTypes : undefined,
            branches: localFilters.branches.length > 0 && localFilters.branches.length < branchOptions.length ? localFilters.branches : undefined,
            startDate: formatDateForQuery(localFilters.startDate),
            endDate: formatDateForQuery(localFilters.endDate),
        };
        const targetRoute = isEmbed ? route('dashboardDigitalProduct.embed') : route('dashboardDigitalProduct');
        router.get(targetRoute, queryParams, { replace: true, preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        const targetRoute = isEmbed ? route('dashboardDigitalProduct.embed') : route('dashboardDigitalProduct');
        router.get(targetRoute, {}, { preserveScroll: true });
    }
    const handleLimitChange = (value) => {
        const targetRoute = isEmbed ? route('dashboardDigitalProduct.embed') : route('dashboardDigitalProduct');
        router.get(targetRoute, { ...filters, limit: value }, { preserveScroll: true, replace: true });
    }


    // --- LOGIKA FILTERING DATA --- (Tidak ada perubahan di sini)
    const filteredRevenueData = useMemo(() => revenueBySubTypeData?.filter(item => revenueFilters.products.includes(item.product)) || [], [revenueBySubTypeData, revenueFilters]);
    const filteredAmountData = useMemo(() => amountBySubTypeData?.filter(item => amountFilters.products.includes(item.product)) || [], [amountBySubTypeData, amountFilters]);
    const transformedRadarData = useMemo(() => {
        if (!productRadarData || productRadarData.length === 0) return [];
        const filteredByWitel = productRadarData.filter(item => radarFilters.witels.includes(item.nama_witel));
        return radarFilters.products.map(product => ({
            product_name: product,
            ...Object.fromEntries(filteredByWitel.map(witelData => [witelData.nama_witel, witelData[product] || 0]))
        }));
    }, [productRadarData, radarFilters]);
    const filteredWitelPieData = useMemo(() => witelPieData?.filter(item => pieFilters.witels.includes(item.nama_witel)) || [], [witelPieData, pieFilters]);


    // 2. Ekstrak konten utama dashboard ke dalam sebuah variabel/konstanta.
    // Ini membuat kode lebih bersih (DRY - Don't Repeat Yourself).
    const DashboardContent = (
        <>
            {/* Panel Filter Global */}
            <div className="bg-white p-4 rounded-lg shadow-md mb-6">
                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Rentang Tanggal</label>
                        <DatePicker selectsRange startDate={localFilters.startDate} endDate={localFilters.endDate} onChange={(update) => setLocalFilters(prev => ({ ...prev, startDate: update[0], endDate: update[1] }))} isClearable={true} dateFormat="dd/MM/yyyy" className="w-full border-gray-300 rounded-md shadow-sm" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Produk</label>
                        <DropdownCheckbox title="Pilih Produk" options={productOptions} selectedOptions={localFilters.products} onSelectionChange={s => setLocalFilters(p => ({ ...p, products: s }))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Witel</label>
                        <DropdownCheckbox title="Pilih Witel" options={witelOptions} selectedOptions={localFilters.witels} onSelectionChange={s => setLocalFilters(p => ({ ...p, witels: s }))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Sub Type</label>
                        <DropdownCheckbox title="Pilih Sub Type" options={subTypeOptions} selectedOptions={localFilters.subTypes} onSelectionChange={s => setLocalFilters(p => ({ ...p, subTypes: s }))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <DropdownCheckbox title="Pilih Branch" options={branchOptions} selectedOptions={localFilters.branches} onSelectionChange={s => setLocalFilters(p => ({ ...p, branches: s }))} />
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-4">
                    <button onClick={resetFilters} className="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">Reset Filter</button>
                    <button onClick={applyFilters} className="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Terapkan Filter</button>
                </div>
            </div>

            {/* Grid Chart */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-lg shadow-md flex flex-col">
                    <h3 className="font-semibold text-lg text-gray-800">Revenue by Sub-type</h3>
                    <div className="flex-grow min-h-[300px]"><RevenueBySubTypeChart data={filteredRevenueData} /></div>
                    <DropdownCheckbox title="Filter Produk" options={productOptions} selectedOptions={revenueFilters.products} onSelectionChange={(s) => setRevenueFilters(p => ({ ...p, products: s }))} />
                </div>
                <div className="bg-white p-6 rounded-lg shadow-md flex flex-col">
                    <h3 className="font-semibold text-lg text-gray-800">Amount by Sub-type</h3>
                    <div className="flex-grow min-h-[300px]"><AmountBySubTypeChart data={filteredAmountData} /></div>
                    <DropdownCheckbox title="Filter Produk" options={productOptions} selectedOptions={amountFilters.products} onSelectionChange={(s) => setAmountFilters(p => ({ ...p, products: s }))} />
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h3 className="font-semibold text-lg text-gray-800 mb-4">Session by Sub-type</h3>
                    <div className="min-h-[350px]"><SessionSubTypeChart data={sessionBySubType} /></div>
                </div>
                <div className="bg-white p-6 rounded-lg shadow-md flex flex-col">
                    <h3 className="font-semibold text-lg text-gray-800">Product Radar Chart per Witel</h3>
                    <div className="flex-grow min-h-[300px]"><ProductRadarChart data={transformedRadarData} /></div>
                    <DropdownCheckbox title="Filter Produk" options={productOptions} selectedOptions={radarFilters.products} onSelectionChange={(s) => setRadarFilters(p => ({ ...p, products: s }))} />
                    <DropdownCheckbox title="Filter Witel" options={witelOptions} selectedOptions={radarFilters.witels} onSelectionChange={(s) => setRadarFilters(p => ({ ...p, witels: s }))} />
                </div>
                <div className="bg-white p-6 rounded-lg shadow-md flex flex-col">
                    <h3 className="font-semibold text-lg text-gray-800">Witel Pie Chart</h3>
                    <div className="flex-grow min-h-[300px]"><WitelPieChart data={filteredWitelPieData} /></div>
                    <DropdownCheckbox title="Filter Witel" options={witelOptions} selectedOptions={pieFilters.witels} onSelectionChange={(s) => setPieFilters(p => ({ ...p, witels: s }))} />
                </div>
            </div>

            {/* Tabel Data Preview */}
            <div className="bg-white p-6 rounded-lg shadow-md mt-6">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="font-semibold text-lg text-gray-800">Data Preview</h3>
                    <div>
                        <label htmlFor="limit-filter" className="text-sm font-semibold text-gray-600 mr-2">Tampilkan:</label>
                        <select id="limit-filter" value={filters.limit || '10'} onChange={e => handleLimitChange(e.target.value)} className="border border-gray-300 rounded-md text-sm p-2">
                            <option value="10">10 Baris</option><option value="50">50 Baris</option><option value="100">100 Baris</option><option value="500">500 Baris</option>
                        </select>
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left text-gray-500">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3">Order ID</th><th scope="col" className="px-6 py-3">Product</th><th scope="col" className="px-6 py-3">Milestone</th>
                                <th scope="col" className="px-6 py-3">Witel</th><th scope="col" className="px-6 py-3">Status</th><th scope="col" className="px-6 py-3">Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {dataPreview?.data?.length > 0 ? (
                                dataPreview.data.map((item) => (
                                    <tr key={item.order_id} className="bg-white border-b hover:bg-gray-50">
                                        <td className="px-6 py-4 font-mono">{item.order_id}</td><td className="px-6 py-4 font-medium text-gray-900">{item.product}</td>
                                        <td className="px-6 py-4 max-w-xs truncate">{item.milestone}</td><td className="px-6 py-4">{item.nama_witel}</td>
                                        <td className="px-6 py-4"><StatusBadge text={item.status_wfm?.toUpperCase()} color={item.status_wfm === 'in progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'} /></td>
                                        <td className="px-6 py-4">{new Date(item.order_created_date).toLocaleString('id-ID')}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="6" className="text-center py-4 text-gray-500">Tidak ada data yang cocok dengan filter yang dipilih.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {dataPreview?.links?.length > 0 && dataPreview.total > 0 && (
                    <div className="mt-4 flex flex-col sm:flex-row justify-between items-center text-sm text-gray-600 gap-4">
                        <span>Menampilkan {dataPreview.from} sampai {dataPreview.to} dari {dataPreview.total} hasil</span>
                        <div className="flex items-center flex-wrap justify-center sm:justify-end">
                            {dataPreview.links.map((link, index) => (
                                <Link key={index} href={link.url || '#'} className={`px-3 py-1 border rounded-md mx-1 transition ${link.active ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100'} ${!link.url ? 'text-gray-400 cursor-not-allowed' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} as="button" disabled={!link.url} preserveScroll />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    );

    // 3. Render secara kondisional.
    // Jika isEmbed true, hanya render kontennya di dalam div sederhana.
    // Jika false, bungkus konten dengan AuthenticatedLayout dan Head.
    if (isEmbed) {
        return (
            <div className="p-4 sm:p-6 bg-gray-100 font-sans">
                {DashboardContent}
            </div>
        );
    }

    return (
        <AuthenticatedLayout
            user={auth.user} // 'auth' sudah di-pass ke komponen, bisa di-pass ke layout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard Digital Product</h2>}
        >
            <Head title="Dashboard Digital Product" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {DashboardContent}
                </div>
            </div>

        </AuthenticatedLayout>
    );
}
