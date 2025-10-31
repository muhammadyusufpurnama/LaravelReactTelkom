import React, { useEffect, useMemo, useState } from "react";
import { useForm } from "@inertiajs/react";

const CustomTargetFormSOS = ({ tableConfig, witelList, initialData, period }) => {
    const [isExpanded, setIsExpanded] = useState(false);

    const customTargetColumns = useMemo(() => {
        const targets = [];
        tableConfig.forEach((item) => {
            if (item.columns) { // Proses grup
                item.columns.forEach((col) => {
                    if (col.type === "target") {
                        targets.push({ key: col.key, title: `${item.groupTitle} > ${col.title}` });
                    }
                });
            } else if (item.type === "target") { // Proses kolom tunggal
                targets.push({ key: item.key, title: item.title });
            }
        });
        return targets;
    }, [tableConfig]);

    const { data, setData, post, processing, errors } = useForm({
        targets: {},
        period: period,
    });

    useEffect(() => {
        setData("targets", initialData || {});
    }, [initialData]);

    const handleInputChange = (targetKey, witel, value) => {
        setData("targets", {
            ...data.targets,
            [targetKey]: {
                ...data.targets[targetKey],
                [witel]: value,
            },
        });
    };

    function submit(e) {
        e.preventDefault();
        post(route("admin.analysisSOS.saveCustomTargets"), {
            preserveScroll: true,
        });
    }

    if (customTargetColumns.length === 0) {
        return null;
    }

    return (
        <form onSubmit={submit} className="bg-white p-6 rounded-lg shadow-md text-sm">
            <div
                className="flex justify-between items-center cursor-pointer mb-4"
                onClick={() => setIsExpanded(!isExpanded)}
            >
                <h3 className="font-semibold text-lg text-gray-800">
                    Edit Target Kustom
                </h3>
                <button
                    type="button"
                    className="text-blue-600 text-sm font-bold hover:underline p-2"
                >
                    {isExpanded ? "Minimize" : "Expand"}
                </button>
            </div>

            {isExpanded && (
                <div className="mt-4 space-y-6">
                    {customTargetColumns.map((col) => (
                        <fieldset key={col.key} className="border rounded-md p-3">
                            <legend className="text-base font-semibold px-2">{col.title}</legend>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-2">
                                {witelList.map((witel) => (
                                    <div key={witel}>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">{witel}</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={data.targets[col.key]?.[witel] ?? ""}
                                            onChange={(e) => handleInputChange(col.key, witel, e.target.value)}
                                            className="p-1 border rounded w-full"
                                            placeholder="0"
                                        />
                                    </div>
                                ))}
                            </div>
                        </fieldset>
                    ))}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 disabled:bg-blue-400"
                    >
                        {processing ? "Menyimpan..." : "Simpan Target Kustom"}
                    </button>
                </div>
            )}
        </form>
    );
};

export default CustomTargetFormSOS;
