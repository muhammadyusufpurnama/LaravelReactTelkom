// resources/js/Components/EditReportForm.jsx

import React, { useState, useMemo, useEffect } from 'react';
import { useForm } from '@inertiajs/react';

const EditReportForm = ({ currentSegment, reportData, period }) => {
    const [isExpanded, setIsExpanded] = useState(false);

    const witelList = useMemo(() => {
        if (!Array.isArray(reportData)) return [];
        return Array.from(new Set(reportData.map((item) => item.nama_witel)));
    }, [reportData]);

    const products = [
        { key: "n", label: "Netmonk" }, { key: "o", label: "OCA" },
        { key: "ae", label: "Antares" }, { key: "ps", label: "Pijar Sekolah" },
    ];

    const { data, setData, post, processing } = useForm({
        targets: {},
        segment: currentSegment,
        period: period + "-01",
    });

    useEffect(() => {
        const initialTargets = {};
        reportData.forEach((item) => {
            initialTargets[item.nama_witel] = {
                prov_comp: {
                    n: item.prov_comp_n_target || 0, o: item.prov_comp_o_target || 0,
                    ae: item.prov_comp_ae_target || 0, ps: item.prov_comp_ps_target || 0,
                },
                revenue: {
                    n: item.revenue_n_target || 0, o: item.revenue_o_target || 0,
                    ae: item.revenue_ae_target || 0, ps: item.revenue_ps_target || 0,
                },
            };
        });
        setData((currentData) => ({ ...currentData, targets: initialTargets }));
    }, [reportData]);

    useEffect(() => {
        setData((currentData) => ({
            ...currentData,
            segment: currentSegment,
            period: period + "-01",
        }));
    }, [currentSegment, period]);

    function submit(e) {
        e.preventDefault();
        post(route("admin.analysisDigitalProduct.targets"), { preserveScroll: true });
    }

    const handleInputChange = (witel, metric, product, value) => {
        setData("targets", {
            ...data.targets,
            [witel]: {
                ...data.targets[witel],
                [metric]: {
                    ...data.targets[witel]?.[metric],
                    [product]: value,
                },
            },
        });
    };

    return (
        <form onSubmit={submit} className="bg-white p-6 rounded-lg shadow-md text-sm">
            <div className="flex justify-between items-center cursor-pointer mb-4" onClick={() => setIsExpanded(!isExpanded)}>
                <h3 className="font-semibold text-lg text-gray-800">Edit Target</h3>
                <button type="button" className="text-blue-600 text-sm font-bold hover:underline p-2">
                    {isExpanded ? "Minimize" : "Expand"}
                </button>
            </div>

            {isExpanded && (
                <div className="mt-4">
                    {currentSegment === "SME" && (
                        <fieldset className="mb-4 border rounded-md p-3">
                            <legend className="text-base font-semibold px-2">Prov Comp Targets</legend>
                            {witelList.map((witel) => (
                                <div key={`${witel}-prov`} className="mb-3">
                                    <h4 className="font-bold text-gray-600">{witel}</h4>
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 mb-1 px-1">
                                        {products.map((p) => <label key={p.key} className="text-xs font-semibold text-gray-500">{p.label}</label>)}
                                    </div>
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        {products.map((p) => (
                                            <input
                                                key={p.key} type="number"
                                                value={data.targets[witel]?.prov_comp?.[p.key] ?? ""}
                                                onChange={(e) => handleInputChange(witel, "prov_comp", p.key, e.target.value)}
                                                placeholder={p.label} className="p-1 border rounded w-full"
                                            />
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </fieldset>
                    )}

                    <fieldset className="border rounded-md p-3">
                        <legend className="text-base font-semibold px-2">Revenue Targets (Rp Juta)</legend>
                        {witelList.map((witel) => (
                            <div key={`${witel}-rev`} className="mb-3">
                                <h4 className="font-bold text-gray-600">{witel}</h4>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 mb-1 px-1">
                                    {products.map((p) => <label key={p.key} className="text-xs font-semibold text-gray-500">{p.label}</label>)}
                                </div>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    {products.map((p) => (
                                        <input
                                            key={p.key} type="number" step="0.01"
                                            value={data.targets[witel]?.revenue?.[p.key] ?? ""}
                                            onChange={(e) => handleInputChange(witel, "revenue", p.key, e.target.value)}
                                            placeholder={p.label} className="p-1 border rounded w-full"
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </fieldset>

                    <button type="submit" disabled={processing} className="w-full mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 disabled:bg-blue-400">
                        {processing ? "Menyimpan..." : "Simpan Target"}
                    </button>
                </div>
            )}
        </form>
    );
};

export default EditReportForm;
