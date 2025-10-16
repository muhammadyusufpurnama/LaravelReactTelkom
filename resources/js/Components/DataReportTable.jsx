import React, { useMemo } from 'react';

// Asumsi Anda punya file utils/index.js untuk formatting
const formatPercent = (value) => `${(Number(value) || 0).toFixed(1)}%`;
const formatRupiah = (value, decimals = 2) => (Number(value) || 0).toFixed(decimals);
const formatNumber = (value) => Number(value) || 0;

const DataReportTable = ({ data = [], decimalPlaces, tableConfig = [] }) => {
    // Fungsi-fungsi ini disalin dari AnalysisDigitalProduct.jsx
    const findColumnDefinition = (keyToFind) => {
        for (const group of tableConfig) {
            for (const col of group.columns) {
                if (col.key === keyToFind) return { colDef: col, parentColDef: null };
                if (col.subColumns) {
                    for (const subCol of col.subColumns) {
                        if (col.key + subCol.key === keyToFind) {
                            return { colDef: subCol, parentColDef: col };
                        }
                    }
                }
            }
        }
        return { colDef: null, parentColDef: null };
    };

    const getCellValue = (item, columnDef, parentColumnDef = null) => {
        const fullKey = parentColumnDef ? parentColumnDef.key + columnDef.key : columnDef.key;

        if (columnDef.type === "calculation") {
            const { operation, operands } = columnDef.calculation;
            const values = operands.map((opKey) => {
                const { colDef: opDef, parentColDef: opParentDef } = findColumnDefinition(opKey);
                if (!opDef) return 0;
                return opDef.type === "calculation"
                    ? getCellValue(item, opDef, opParentDef)
                    : formatNumber(item[opKey] || 0);
            });
            switch (operation) {
                case "percentage":
                    const [numerator, denominator] = values;
                    return denominator === 0 ? formatPercent(0) : formatPercent((numerator / denominator) * 100);
                case "sum":
                    return formatNumber(values.reduce((a, b) => a + b, 0));
                case "average":
                    if (values.length === 0) return 0;
                    return formatNumber(
                        values.reduce((a, b) => a + b, 0) / values.length,
                    );
                case "count":
                    return values.filter((v) => v !== 0).length;
                default: return "N/A";
            }
        }

        if (fullKey.startsWith("revenue_")) {
            return formatRupiah(item[fullKey], decimalPlaces);
        }
        return formatNumber(item[fullKey]);
    };

    const totals = useMemo(() => {
        const initialTotals = {};
        tableConfig.forEach(group => {
            group.columns.forEach(col => {
                if (col.subColumns) {
                    col.subColumns.forEach(sc => {
                        if (sc.type !== 'calculation') {
                            const key = col.key + sc.key;
                            initialTotals[key] = data.reduce((sum, item) => sum + formatNumber(item[key]), 0);
                        }
                    });
                } else if (col.type !== 'calculation') {
                    initialTotals[col.key] = data.reduce((sum, item) => sum + formatNumber(item[col.key]), 0);
                }
            });
        });
        return initialTotals;
    }, [data, tableConfig]);

    // Render tabel statis, tanpa DndContext atau komponen Draggable
    return (
        <div className="overflow-x-auto text-xs">
            <table className="w-full border-collapse text-center">
                <thead className="bg-gray-800 text-white">
                    <tr>
                        <th className="border p-2 align-middle" rowSpan={3}>WILAYAH TELKOM</th>
                        {tableConfig.map(group => (
                            <th key={group.groupTitle} className={`border p-2 ${group.groupClass}`} colSpan={group.columns.reduce((sum, col) => sum + (col.subColumns?.length || 1), 0)}>
                                {group.groupTitle}
                            </th>
                        ))}
                    </tr>
                    <tr className="font-semibold">
                        {tableConfig.map(group => group.columns.map(col => (
                            <th key={col.key} className={`border p-2 ${group.columnClass || "bg-gray-700"}`} colSpan={col.subColumns?.length || 1} rowSpan={col.subColumns ? 1 : 2}>
                                {col.title}
                            </th>
                        )))}
                    </tr>
                    <tr className="font-medium">
                        {tableConfig.map(group => group.columns.map(col =>
                            col.subColumns ? col.subColumns.map(subCol => (
                                <th key={subCol.key} className={`border p-1 ${group.subColumnClass || "bg-gray-600"}`}>
                                    {subCol.title}
                                </th>
                            )) : null
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data.length > 0 ? (
                        data.map(item => (
                            <tr key={item.nama_witel} className="bg-white hover:bg-gray-50 text-black">
                                <td className="border p-2 font-semibold text-left">{item.nama_witel}</td>
                                {tableConfig.map(group => group.columns.map(col =>
                                    col.subColumns ? (
                                        col.subColumns.map(subCol => (
                                            <td key={`${col.key}-${subCol.key}`} className="border p-2">
                                                {getCellValue(item, subCol, col)}
                                            </td>
                                        ))
                                    ) : (
                                        <td key={col.key} className="border p-2">
                                            {getCellValue(item, col)}
                                        </td>
                                    )
                                ))}
                            </tr>
                        ))
                    ) : (
                        <tr><td colSpan={100} className="text-center p-4 border text-gray-500">Tidak ada data.</td></tr>
                    )}
                    <tr className="font-bold text-white">
                        <td className="border p-2 text-left bg-gray-800">GRAND TOTAL</td>
                        {tableConfig.map(group => group.columns.map(col =>
                            col.subColumns ? (
                                col.subColumns.map(subCol => (
                                    <td key={`total-${col.key}-${subCol.key}`} className={`border p-2 ${group.groupClass}`}>
                                        {getCellValue(totals, subCol, col)}
                                    </td>
                                ))
                            ) : (
                                <td key={`total-${col.key}`} className={`border p-2 ${group.groupClass}`}>
                                    {getCellValue(totals, col)}
                                </td>
                            )
                        ))}
                    </tr>
                </tbody>
            </table>
        </div>
    );
};

export default DataReportTable;
